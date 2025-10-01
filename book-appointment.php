<?php
require_once 'config/database.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection failed. Please check your configuration.");
}

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get list of doctors
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, dp.specialization, dp.consultation_fee
    FROM users u
    JOIN doctor_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'doctor'
    ORDER BY u.first_name, u.last_name
");
$doctors = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate inputs
    if (empty($doctor_id)) {
        $errors[] = 'Please select a doctor';
    }
    
    if (empty($appointment_date)) {
        $errors[] = 'Please select a date';
    } elseif (strtotime($appointment_date) < strtotime('today')) {
        $errors[] = 'Appointment date cannot be in the past';
    }
    
    if (empty($start_time)) {
        $errors[] = 'Please select a time slot';
    }
    
    if (empty($purpose)) {
        $errors[] = 'Please provide a purpose for the appointment';
    }
    
    // Check if the selected time slot is available
    if (empty($errors)) {
        $start_datetime = $appointment_date . ' ' . $start_time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . ' +30 minutes'));
        
        $stmt = $pdo->prepare("
            SELECT id 
            FROM appointments 
            WHERE doctor_id = ? 
            AND (
                (appointment_date = ? AND start_time = ?) OR
                (appointment_date = ? AND status IN ('pending', 'approved'))
            )
            LIMIT 1
        ");
        
        $stmt->execute([$doctor_id, $appointment_date, $start_time, $appointment_date]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'The selected time slot is no longer available. Please choose another time.';
        }
    }
    
    // If no errors, create the appointment
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create the appointment
            $stmt = $pdo->prepare("
                INSERT INTO appointments 
                (patient_id, doctor_id, appointment_date, start_time, end_time, purpose, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $patient_id,
                $doctor_id,
                $appointment_date,
                $start_time,
                date('H:i:s', strtotime($start_time . ' +30 minutes')),
                $purpose,
                $notes
            ]);
            
            // Create notification for the doctor
            $appointment_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, title, message, related_type, related_id)
                VALUES (?, 'New Appointment Request', ?, 'appointment', ?)
            ");
            
            $patient_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $message = "You have a new appointment request from $patient_name for " . date('F j, Y', strtotime($appointment_date)) . " at " . date('h:i A', strtotime($start_time));
            
            $stmt->execute([$doctor_id, $message, $appointment_id]);
            
            $pdo->commit();
            
            // Redirect to success page
            $_SESSION['success'] = 'Your appointment has been requested successfully! The doctor will review your request and confirm the appointment.';
            header('Location: appointment-success.php?id=' . $appointment_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while booking your appointment. Please try again.';
            error_log('Appointment booking error: ' . $e->getMessage());
        }
    }
}

// Get available time slots for a doctor on a specific date
function getAvailableTimeSlots($pdo, $doctor_id, $date) {
    if (empty($doctor_id) || empty($date)) {
        return [];
    }
    
    // Get doctor's working hours (default 9 AM to 5 PM if not set)
    $working_hours = [
        'start' => '09:00:00',
        'end' => '17:00:00'
    ];
    
    // Get doctor's availability from database if exists
    $stmt = $pdo->prepare("
        SELECT start_time, end_time 
        FROM doctor_availability 
        WHERE doctor_id = ? AND day_of_week = ?
        LIMIT 1
    ");
    
    $day_of_week = date('w', strtotime($date)); // 0 (Sunday) to 6 (Saturday)
    $stmt->execute([$doctor_id, $day_of_week]);
    
    if ($row = $stmt->fetch()) {
        $working_hours = [
            'start' => $row['start_time'],
            'end' => $row['end_time']
        ];
    }
    
    // Get booked time slots
    $stmt = $pdo->prepare("
        SELECT start_time, end_time 
        FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND status IN ('pending', 'approved')
        ORDER BY start_time
    ");
    
    $stmt->execute([$doctor_id, $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate time slots (30-minute intervals)
    $start = strtotime($working_hours['start']);
    $end = strtotime($working_hours['end']);
    $interval = 30 * 60; // 30 minutes in seconds
    
    $slots = [];
    
    for ($time = $start; $time < $end; $time += $interval) {
        $slot_start = date('H:i:s', $time);
        $slot_end = date('H:i:s', $time + $interval);
        $is_available = true;
        
        // Check if slot is booked
        foreach ($booked_slots as $booked) {
            $booked_start = strtotime($booked['start_time']);
            $booked_end = strtotime($booked['end_time']);
            
            if (($time >= $booked_start && $time < $booked_end) || 
                ($time + $interval > $booked_start && $time + $interval <= $booked_end) ||
                ($time <= $booked_start && $time + $interval >= $booked_end)) {
                $is_available = false;
                break;
            }
        }
        
        if ($is_available) {
            $slots[] = [
                'start' => $slot_start,
                'end' => $slot_end,
                'display' => date('g:i A', $time) . ' - ' . date('g:i A', $time + $interval)
            ];
        }
    }
    
    return $slots;
}

// Get available time slots via AJAX
if (isset($_GET['get_time_slots']) && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = (int)$_GET['doctor_id'];
    $date = $_GET['date'];
    
    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format']);
        exit();
    }
    
    // Get available time slots
    $time_slots = getAvailableTimeSlots($pdo, $doctor_id, $date);
    
    header('Content-Type: application/json');
    echo json_encode(['time_slots' => $time_slots]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Book an Appointment</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="appointments.php">My Appointments</a>
                <a href="book-appointment.php" class="active">Book Appointment</a>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="booking-container">
                <div class="card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form id="appointmentForm" method="post" class="booking-form">
                        <div class="form-group">
                            <label for="doctor_id">Select Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-control" required>
                                <option value="">-- Select a Doctor --</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>" data-fee="<?php echo $doctor['consultation_fee']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> 
                                        (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                        - $<?php echo number_format($doctor['consultation_fee'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appointment_date">Select Date</label>
                                <input type="date" id="appointment_date" name="appointment_date" 
                                       class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                <small class="text-muted">Select a date to see available time slots</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_time">Select Time</label>
                                <select name="start_time" id="start_time" class="form-control" disabled required>
                                    <option value="">-- Select a date first --</option>
                                </select>
                                <div id="loading" class="loading-spinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading time slots...
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Purpose of Visit</label>
                            <input type="text" id="purpose" name="purpose" class="form-control" 
                                   placeholder="Briefly describe the reason for your visit" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Additional Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" 
                                      placeholder="Any additional information you'd like to share with the doctor"></textarea>
                        </div>
                        
                        <div class="booking-summary">
                            <h3>Appointment Summary</h3>
                            <div class="summary-item">
                                <span class="label">Doctor:</span>
                                <span id="summary-doctor">--</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Date:</span>
                                <span id="summary-date">--</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Time:</span>
                                <span id="summary-time">--</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Consultation Fee:</span>
                                <span id="summary-fee">$0.00</span>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="far fa-calendar-check"></i> Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Booking Instructions</h2>
                    <ol class="instructions">
                        <li>Select a doctor from the dropdown list.</li>
                        <li>Choose an available date for your appointment.</li>
                        <li>Select an available time slot.</li>
                        <li>Provide the purpose of your visit and any additional notes.</li>
                        <li>Review your appointment details and click "Book Appointment".</li>
                    </ol>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> After booking, the doctor will review your request and confirm the appointment. 
                        You'll receive a notification once your appointment is confirmed.
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorSelect = document.getElementById('doctor_id');
            const dateInput = document.getElementById('appointment_date');
            const timeSelect = document.getElementById('start_time');
            const loadingDiv = document.getElementById('loading');
            
            // Summary elements
            const summaryDoctor = document.getElementById('summary-doctor');
            const summaryDate = document.getElementById('summary-date');
            const summaryTime = document.getElementById('summary-time');
            const summaryFee = document.getElementById('summary-fee');
            
            // Update time slots when doctor or date changes
            function updateTimeSlots() {
                const doctorId = doctorSelect.value;
                const date = dateInput.value;
                
                if (!doctorId || !date) {
                    timeSelect.innerHTML = '<option value="">-- Select a date first --</option>';
                    timeSelect.disabled = true;
                    updateSummary();
                    return;
                }
                
                // Show loading spinner
                loadingDiv.style.display = 'block';
                timeSelect.disabled = true;
                timeSelect.innerHTML = '<option value="">Loading time slots...</option>';
                
                // Fetch available time slots via AJAX
                fetch(`book-appointment.php?get_time_slots=1&doctor_id=${doctorId}&date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            timeSelect.innerHTML = `<option value="">${data.error}</option>`;
                            return;
                        }
                        
                        const slots = data.time_slots || [];
                        
                        if (slots.length === 0) {
                            timeSelect.innerHTML = '<option value="">No available time slots</option>';
                        } else {
                            timeSelect.innerHTML = '<option value="">-- Select a time --</option>';
                            slots.forEach(slot => {
                                const option = document.createElement('option');
                                option.value = slot.start;
                                option.textContent = slot.display;
                                timeSelect.appendChild(option);
                            });
                            timeSelect.disabled = false;
                        }
                        
                        // Update summary
                        updateSummary();
                    })
                    .catch(error => {
                        console.error('Error fetching time slots:', error);
                        timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                    })
                    .finally(() => {
                        loadingDiv.style.display = 'none';
                    });
            }
            
            // Update summary when form fields change
            function updateSummary() {
                // Update doctor
                const selectedDoctor = doctorSelect.options[doctorSelect.selectedIndex];
                if (selectedDoctor.value) {
                    summaryDoctor.textContent = selectedDoctor.text.split('(')[0].trim();
                    
                    // Update fee
                    const fee = selectedDoctor.getAttribute('data-fee') || '0';
                    summaryFee.textContent = `$${parseFloat(fee).toFixed(2)}`;
                } else {
                    summaryDoctor.textContent = '--';
                    summaryFee.textContent = '$0.00';
                }
                
                // Update date
                if (dateInput.value) {
                    const date = new Date(dateInput.value);
                    summaryDate.textContent = date.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                } else {
                    summaryDate.textContent = '--';
                }
                
                // Update time
                const selectedTime = timeSelect.options[timeSelect.selectedIndex];
                if (selectedTime.value) {
                    const time = new Date(`2000-01-01T${selectedTime.value}`);
                    summaryTime.textContent = time.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                } else {
                    summaryTime.textContent = '--';
                }
            }
            
            // Event listeners
            doctorSelect.addEventListener('change', updateTimeSlots);
            dateInput.addEventListener('change', updateTimeSlots);
            timeSelect.addEventListener('change', updateSummary);
            
            // Initialize summary
            updateSummary();
            
            // Form validation
            document.getElementById('appointmentForm').addEventListener('submit', function(e) {
                if (!doctorSelect.value || !dateInput.value || !timeSelect.value) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>
