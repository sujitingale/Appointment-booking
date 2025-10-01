<?php
require_once 'config/database.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection failed. Please check your configuration.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user's upcoming appointments
if ($user_type === 'patient') {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               dp.specialization
        FROM appointments a
        JOIN users d ON a.doctor_id = d.id
        LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
        WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date, a.start_time
        LIMIT 5
    ");
} else {
    // For doctors, show their upcoming appointments
    $stmt = $pdo->prepare("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date, a.start_time
        LIMIT 5
    ");
}

$stmt->execute([$user_id]);
$upcoming_appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="appointments.php">My Appointments</a>
                <?php if ($user_type === 'doctor'): ?>
                    <a href="doctor/appointments.php">Manage Appointments</a>
                    <a href="doctor/availability.php">Set Availability</a>
                <?php else: ?>
                    <a href="book-appointment.php">Book Appointment</a>
                <?php endif; ?>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="dashboard-grid">
                <section class="card">
                    <h2>Upcoming Appointments</h2>
                    <?php if (empty($upcoming_appointments)): ?>
                        <p>No upcoming appointments found.</p>
                    <?php else: ?>
                        <div class="appointment-list">
                            <?php foreach ($upcoming_appointments as $appt): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', strtotime($appt['appointment_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($appt['appointment_date'])); ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <h3>
                                            <?php if ($user_type === 'patient'): ?>
                                                Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?>
                                                <span class="specialization"><?php echo htmlspecialchars($appt['specialization']); ?></span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($appt['patient_name']); ?>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="time">
                                            <?php echo date('h:i A', strtotime($appt['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($appt['end_time'])); ?>
                                        </p>
                                        <p class="status status-<?php echo strtolower($appt['status']); ?>">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="appointments.php" class="btn btn-outline">View All Appointments</a>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="card">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <?php if ($user_type === 'patient'): ?>
                            <a href="book-appointment.php" class="btn btn-primary">
                                <i class="icon-calendar"></i> Book New Appointment
                            </a>
                            <a href="doctors.php" class="btn btn-outline">
                                <i class="icon-search"></i> Find a Doctor
                            </a>
                        <?php else: ?>
                            <a href="doctor/availability.php" class="btn btn-primary">
                                <i class="icon-calendar"></i> Update Availability
                            </a>
                            <a href="doctor/appointments.php" class="btn btn-outline">
                                <i class="icon-list"></i> View All Appointments
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="btn btn-outline">
                            <i class="icon-user"></i> Edit Profile
                        </a>
                    </div>
                </section>

                <?php if ($user_type === 'doctor'): ?>
                    <section class="card">
                        <h2>Today's Schedule</h2>
                        <?php
                        $today = date('Y-m-d');
                        $stmt = $pdo->prepare("
                            SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name
                            FROM appointments a
                            JOIN users p ON a.patient_id = p.id
                            WHERE a.doctor_id = ? AND a.appointment_date = ?
                            ORDER BY a.start_time
                        ");
                        $stmt->execute([$user_id, $today]);
                        $todays_appointments = $stmt->fetchAll();
                        ?>
                        
                        <?php if (empty($todays_appointments)): ?>
                            <p>No appointments scheduled for today.</p>
                        <?php else: ?>
                            <div class="schedule-list">
                                <?php foreach ($todays_appointments as $appt): ?>
                                    <div class="schedule-item">
                                        <div class="time">
                                            <?php echo date('h:i A', strtotime($appt['start_time'])); ?>
                                        </div>
                                        <div class="details">
                                            <h4><?php echo htmlspecialchars($appt['patient_name']); ?></h4>
                                            <p class="purpose"><?php echo htmlspecialchars($appt['purpose']); ?></p>
                                            <span class="status status-<?php echo strtolower($appt['status']); ?>">
                                                <?php echo ucfirst($appt['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
