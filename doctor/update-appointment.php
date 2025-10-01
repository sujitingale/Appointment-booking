<?php
require_once '../config/database.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection failed. Please check your configuration.");
}

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointments.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? 0;
$action = $_POST['action'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// Validate appointment belongs to this doctor
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found or you do not have permission to modify it.';
    header('Location: appointments.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'approved', notes = ? WHERE id = ?");
            $stmt->execute([$notes, $appointment_id]);
            
            // Create notification for patient
            $message = "Your appointment on " . date('F j, Y', strtotime($appointment['appointment_date'])) . 
                      " at " . date('g:i A', strtotime($appointment['start_time'])) . " has been approved.";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, related_type, related_id) VALUES (?, 'Appointment Approved', ?, 'appointment', ?)");
            $stmt->execute([$appointment['patient_id'], $message, $appointment_id]);
            
            $_SESSION['success'] = 'Appointment approved successfully!';
            break;
            
        case 'reject':
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'rejected', notes = ? WHERE id = ?");
            $stmt->execute([$notes, $appointment_id]);
            
            // Create notification for patient
            $message = "Your appointment on " . date('F j, Y', strtotime($appointment['appointment_date'])) . 
                      " at " . date('g:i A', strtotime($appointment['start_time'])) . " has been rejected.";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, related_type, related_id) VALUES (?, 'Appointment Rejected', ?, 'appointment', ?)");
            $stmt->execute([$appointment['patient_id'], $message, $appointment_id]);
            
            $_SESSION['success'] = 'Appointment rejected.';
            break;
            
        case 'complete':
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed', notes = ? WHERE id = ?");
            $stmt->execute([$notes, $appointment_id]);
            
            // Create notification for patient
            $message = "Your appointment on " . date('F j, Y', strtotime($appointment['appointment_date'])) . " has been marked as completed.";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, related_type, related_id) VALUES (?, 'Appointment Completed', ?, 'appointment', ?)");
            $stmt->execute([$appointment['patient_id'], $message, $appointment_id]);
            
            $_SESSION['success'] = 'Appointment marked as completed!';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
}

header('Location: appointments.php');
exit();
?>
