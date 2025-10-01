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

$appointment_id = $_GET['id'] ?? 0;

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
    SELECT a.*,
           CONCAT(p.first_name, ' ', p.last_name) as patient_name,
           p.email as patient_email,
           p.phone as patient_phone,
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           dp.specialization,
           dp.consultation_fee,
           dp.qualification,
           dp.experience_years,
           dp.bio
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    JOIN users d ON a.doctor_id = d.id
    LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
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
$duration = date('i', strtotime($appointment['end_time']) - strtotime($appointment['start_time'])) . ' minutes';

// Get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Appointment Details</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="appointments.php">My Appointments</a>
                <?php if ($user_type === 'doctor'): ?>
                    <a href="doctor/appointments.php">Manage Appointments</a>
                <?php else: ?>
                    <a href="book-appointment.php">Book Appointment</a>
                <?php endif; ?>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="appointment-details-container">
                <div class="card">
                    <div class="appointment-header">
                        <h2>Appointment #<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></h2>
                        <span class="status <?php echo getStatusBadgeClass($appointment['status']); ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>

                    <div class="details-grid">
                        <div class="detail-section">
                            <h3>Appointment Information</h3>
                            <div class="detail-row">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo $appointment_date; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Time:</span>
                                <span class="value"><?php echo $start_time; ?> - <?php echo $end_time; ?> (<?php echo $duration; ?>)</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Purpose:</span>
                                <span class="value"><?php echo htmlspecialchars($appointment['purpose'] ?? 'Not specified'); ?></span>
                            </div>
                            <?php if (!empty($appointment['notes'])): ?>
                                <div class="detail-row">
                                    <span class="label">Notes:</span>
                                    <span class="value"><?php echo htmlspecialchars($appointment['notes']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-section">
                            <h3><?php echo ($user_type === 'patient') ? 'Doctor' : 'Patient'; ?> Information</h3>
                            <?php if ($user_type === 'patient'): ?>
                                <div class="detail-row">
                                    <span class="label">Doctor:</span>
                                    <span class="value">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Specialization:</span>
                                    <span class="value"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                                </div>
                                <?php if (!empty($appointment['qualification'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Qualification:</span>
                                        <span class="value"><?php echo htmlspecialchars($appointment['qualification']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($appointment['experience_years'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Experience:</span>
                                        <span class="value"><?php echo $appointment['experience_years']; ?> years</span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="detail-row">
                                    <span class="label">Patient:</span>
                                    <span class="value"><?php echo htmlspecialchars($appointment['patient_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Email:</span>
                                    <span class="value"><?php echo htmlspecialchars($appointment['patient_email']); ?></span>
                                </div>
                                <?php if (!empty($appointment['patient_phone'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Phone:</span>
                                        <span class="value"><?php echo htmlspecialchars($appointment['patient_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($user_type === 'doctor' && !empty($appointment['bio'])): ?>
                            <div class="detail-section">
                                <h3>Doctor Biography</h3>
                                <p><?php echo htmlspecialchars($appointment['bio']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="detail-section">
                            <h3>Actions</h3>
                            <div class="action-buttons">
                                <?php if ($appointment['status'] === 'pending' && $user_type === 'patient'): ?>
                                    <button type="button" class="btn btn-outline btn-cancel" data-appointment-id="<?php echo $appointment['id']; ?>">
                                        <i class="fas fa-times"></i> Cancel Appointment
                                    </button>
                                <?php endif; ?>

                                <?php if ($appointment['status'] === 'approved' && strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']) > time()): ?>
                                    <a href="#" class="btn btn-primary">
                                        <i class="fas fa-video"></i> Join Video Call
                                    </a>
                                <?php endif; ?>

                                <?php if ($appointment['status'] === 'completed'): ?>
                                    <a href="#" class="btn btn-outline">
                                        <i class="fas fa-star"></i> Rate & Review
                                    </a>
                                <?php endif; ?>

                                <a href="appointments.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i> Back to Appointments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($user_type === 'doctor' && in_array($appointment['status'], ['pending', 'approved'])): ?>
                    <div class="card">
                        <h3>Quick Actions</h3>
                        <div class="doctor-actions">
                            <?php if ($appointment['status'] === 'pending'): ?>
                                <form method="post" action="doctor/update-appointment.php" class="inline-form">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve Appointment
                                    </button>
                                </form>

                                <form method="post" action="doctor/update-appointment.php" class="inline-form">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject Appointment
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($appointment['status'] === 'approved'): ?>
                                <form method="post" action="doctor/update-appointment.php" class="inline-form">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check-circle"></i> Mark as Completed
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>

    <!-- Cancel Appointment Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Cancel Appointment</h3>
            <p>Are you sure you want to cancel this appointment? This action cannot be undone.</p>
            <form id="cancelForm" method="post" action="cancel-appointment.php">
                <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                <div class="form-group">
                    <label for="cancellation_reason">Reason for cancellation (optional)</label>
                    <textarea id="cancellation_reason" name="cancellation_reason" class="form-control" rows="3"></textarea>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-outline" id="cancelBtn">No, Keep It</button>
                    <button type="submit" class="btn btn-danger">Yes, Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .appointment-details-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .detail-section h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }

        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-row .label {
            font-weight: 600;
            width: 120px;
            color: #6c757d;
        }

        .detail-row .value {
            flex: 1;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .doctor-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .inline-form {
            display: inline;
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .doctor-actions {
                flex-direction: column;
            }
        }
    </style>

    <script>
        // Cancel Appointment Modal
        const modal = document.getElementById('cancelModal');
        const cancelButtons = document.querySelectorAll('.btn-cancel');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');

        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-appointment-id');
                document.getElementById('cancelAppointmentId').value = appointmentId;
                modal.style.display = 'block';
            });
        });

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
