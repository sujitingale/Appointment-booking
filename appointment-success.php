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
$appointment_id = $_GET['id'] ?? 0;

// Get appointment details
$stmt = $pdo->prepare("
    SELECT a.*, 
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           dp.specialization,
           dp.consultation_fee,
           CONCAT(p.first_name, ' ', p.last_name) as patient_name,
           p.email as patient_email,
           p.phone as patient_phone
    FROM appointments a
    JOIN users d ON a.doctor_id = d.id
    LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
    JOIN users p ON a.patient_id = p.id
    WHERE a.id = ? AND (a.patient_id = ? OR a.doctor_id = ?)
    LIMIT 1
");

$stmt->execute([$appointment_id, $user_id, $user_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found or you do not have permission to view it.';
    header('Location: appointments.php');
    exit();
}

// Format date and time
$appointment_date = date('l, F j, Y', strtotime($appointment['appointment_date']));
$start_time = date('g:i A', strtotime($appointment['start_time']));
$end_time = date('g:i A', strtotime($appointment['end_time']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Requested - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Appointment Requested</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="appointments.php">My Appointments</a>
                <?php if ($_SESSION['user_type'] === 'doctor'): ?>
                    <a href="doctor/appointments.php">Manage Appointments</a>
                <?php else: ?>
                    <a href="book-appointment.php">Book Appointment</a>
                <?php endif; ?>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="success-container">
                <div class="success-card">
                    <div class="success-icon">
                        <i class="far fa-check-circle"></i>
                    </div>
                    
                    <h2>Appointment Request Submitted Successfully!</h2>
                    
                    <div class="appointment-details">
                        <div class="detail-item">
                            <span class="label">Appointment ID:</span>
                            <span class="value">#<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="label">Doctor:</span>
                            <span class="value">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="label">Specialization:</span>
                            <span class="value"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="label">Date:</span>
                            <span class="value"><?php echo $appointment_date; ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="label">Time:</span>
                            <span class="value"><?php echo $start_time; ?> - <?php echo $end_time; ?></span>
                        </div>
                        
                        <?php if (!empty($appointment['purpose'])): ?>
                            <div class="detail-item">
                                <span class="label">Purpose:</span>
                                <span class="value"><?php echo htmlspecialchars($appointment['purpose']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-item status">
                            <span class="label">Status:</span>
                            <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="next-steps">
                        <h3>What Happens Next?</h3>
                        <ol>
                            <li>Your appointment request has been sent to the doctor for review.</li>
                            <li>The doctor will review your request and either confirm or suggest an alternative time.</li>
                            <li>You'll receive an email notification once the appointment is confirmed or if any changes are needed.</li>
                            <li>You can check the status of your appointment in the <a href="appointments.php">My Appointments</a> section.</li>
                        </ol>
                    </div>
                    
                    <div class="actions">
                        <a href="appointments.php" class="btn btn-primary">
                            <i class="far fa-calendar-alt"></i> View All Appointments
                        </a>
                        <a href="dashboard.php" class="btn btn-outline">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    </div>
                    
                    <div class="print-section">
                        <button onclick="window.print()" class="btn btn-outline">
                            <i class="fas fa-print"></i> Print Confirmation
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Need Help?</h3>
                    <p>If you have any questions or need to make changes to your appointment, please contact our support team.</p>
                    
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@appointmentsystem.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>Monday - Friday, 9:00 AM - 6:00 PM</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>
    
    <style>
        .success-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        .success-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        
        .success-card h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
        }
        
        .appointment-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-item {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-item .label {
            font-weight: 600;
            width: 150px;
            color: #6c757d;
        }
        
        .detail-item .value {
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .next-steps {
            text-align: left;
            margin: 2.5rem 0;
        }
        
        .next-steps h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .next-steps ol {
            padding-left: 1.5rem;
        }
        
        .next-steps li {
            margin-bottom: 0.75rem;
            color: #6c757d;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .print-section {
            margin-top: 2rem;
        }
        
        .contact-info {
            margin-top: 1.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #6c757d;
        }
        
        .contact-item i {
            width: 30px;
            color: #4a6cf7;
        }
        
        @media print {
            header, footer, .card:last-child, .print-section {
                display: none;
            }
            
            .success-card {
                box-shadow: none;
                padding: 0;
            }
            
            .actions {
                display: none;
            }
        }
    </style>
</body>
</html>
