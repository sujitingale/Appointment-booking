<?php
session_start();

// Redirect logged-in users directly to their dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor/Teacher Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome to Our Appointment System</h1>
            <nav>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="appointments.php">My Appointments</a>
                    <?php if ($_SESSION['user_type'] === 'doctor'): ?>
                        <a href="doctor/appointments.php">Manage Appointments</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </header>
        
        <main>
            <section class="hero">
                <h2>Book an Appointment with Ease</h2>
                <p>Connect with professional doctors and teachers for your needs.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn">Get Started</a>
                <?php else: ?>
                    <a href="appointments.php" class="btn">Book Appointment</a>
                <?php endif; ?>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
