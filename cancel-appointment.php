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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointments.php');
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? 0;
$cancellation_reason = trim($_POST['cancellation_reason'] ?? '');

// Validate appointment ID
if (empty($appointment_id) || !is_numeric($appointment_id)) {
    $_SESSION['error'] = 'Invalid appointment ID';
    header('Location: appointments.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get appointment details
$stmt = $pdo->prepare("
    SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    WHERE a.id = ? AND (a.patient_id = ? OR a.doctor_id = ?)
    LIMIT 1
");

$stmt->execute([$appointment_id, $user_id, $user_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found or you do not have permission to cancel it.';
    header('Location: appointments.php');
    exit();
}

// Check if appointment can be cancelled (not completed or already cancelled)
if ($appointment['status'] === 'completed' || $appointment['status'] === 'cancelled') {
    $_SESSION['error'] = 'This appointment cannot be cancelled.';
    header('Location: appointments.php');
    exit();
}

// Update appointment status
try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'cancelled', notes = ? 
        WHERE id = ?
    ");
    
    $cancellation_note = "Cancelled by user";
    if (!empty($cancellation_reason)) {
        $cancellation_note .= ". Reason: " . $cancellation_reason;
    }
    
    $stmt->execute([$cancellation_note, $appointment_id]);
    
    // Create notification for the other party
    $other_user_id = ($user_type === 'patient') ? $appointment['doctor_id'] : $appointment['patient_id'];
    $other_user_name = ($user_type === 'patient') ? 'patient' : 'doctor';
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, related_type, related_id)
        VALUES (?, 'Appointment Cancelled', ?, 'appointment', ?)
    ");
    
    $message = "An appointment scheduled for " . date('F j, Y', strtotime($appointment['appointment_date'])) . 
               " at " . date('g:i A', strtotime($appointment['start_time'])) . " has been cancelled.";
    
    $stmt->execute([$other_user_id, $message, $appointment_id]);
    
    $pdo->commit();
    
    $_SESSION['success'] = 'Appointment has been cancelled successfully.';
    header('Location: appointments.php');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'An error occurred while cancelling the appointment. Please try again.';
    header('Location: appointments.php');
    exit();
}
?>
