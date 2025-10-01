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

// Pagination settings
$appointments_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $appointments_per_page;

// Filter parameters
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build the base query
$query = "
    SELECT a.*, 
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           dp.specialization,
           CONCAT(p.first_name, ' ', p.last_name) as patient_name
    FROM appointments a
    JOIN users d ON a.doctor_id = d.id
    LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
    JOIN users p ON a.patient_id = p.id
    WHERE ";

if ($user_type === 'patient') {
    $query .= "a.patient_id = :user_id ";
} else {
    $query .= "a.doctor_id = :user_id ";
}

$params = [':user_id' => $user_id];

// Apply status filter
if ($status !== 'all') {
    $query .= " AND a.status = :status";
    $params[':status'] = $status;
}

// Apply date range filter
if (!empty($date_from)) {
    $query .= " AND a.appointment_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND a.appointment_date <= :date_to";
    $params[':date_to'] = $date_to;
}

// Apply search
if (!empty($search)) {
    if ($user_type === 'patient') {
        $query .= " AND (d.first_name LIKE :search OR d.last_name LIKE :search OR dp.specialization LIKE :search)";
    } else {
        $query .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search)";
    }
    $params[':search'] = "%$search%";
}

// Build count query separately to avoid subquery issues
$count_query = "
    SELECT COUNT(*) as total
    FROM appointments a
    JOIN users d ON a.doctor_id = d.id
    LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
    JOIN users p ON a.patient_id = p.id
    WHERE ";

if ($user_type === 'patient') {
    $count_query .= "a.patient_id = :user_id ";
} else {
    $count_query .= "a.doctor_id = :user_id ";
}

// Apply same filters to count query
if ($status !== 'all') {
    $count_query .= " AND a.status = :status";
}

if (!empty($date_from)) {
    $count_query .= " AND a.appointment_date >= :date_from";
}

if (!empty($date_to)) {
    $count_query .= " AND a.appointment_date <= :date_to";
}

if (!empty($search)) {
    if ($user_type === 'patient') {
        $count_query .= " AND (d.first_name LIKE :search OR d.last_name LIKE :search OR dp.specialization LIKE :search)";
    } else {
        $count_query .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search)";
    }
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_appointments = $stmt->fetch()['total'];
$total_pages = ceil($total_appointments / $appointments_per_page);

// Add sorting and pagination to the query
$query .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$query .= " LIMIT :offset, :limit";

// Prepare and execute the main query
$stmt = $pdo->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind pagination parameters
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $appointments_per_page, PDO::PARAM_INT);

$stmt->execute();
$appointments = $stmt->fetchAll();

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Appointments</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="appointments.php" class="active">My Appointments</a>
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
            <div class="appointments-container">
                <!-- Filters -->
                <div class="card mb-4">
                    <h2>Filter Appointments</h2>
                    <form method="get" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Search</label>
                            <div class="search-box">
                                <input type="text" id="search" name="search" class="form-control" 
                                       placeholder="Search by <?php echo $user_type === 'patient' ? 'doctor name or specialization' : 'patient name'; ?>"
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <a href="appointments.php" class="btn btn-outline mr-2">Reset Filters</a>
                        </div>
                    </form>
                </div>

                <!-- Appointments List -->
                <div class="card">
                    <div class="appointments-header">
                        <h2>Appointments</h2>
                        <?php if ($user_type === 'patient'): ?>
                            <a href="book-appointment.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Appointment
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($appointments)): ?>
                        <div class="no-appointments">
                            <i class="far fa-calendar-alt"></i>
                            <p>No appointments found</p>
                            <?php if ($user_type === 'patient'): ?>
                                <a href="book-appointment.php" class="btn btn-primary mt-2">Book Your First Appointment</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="appointments-list">
                            <?php foreach ($appointments as $appt): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', strtotime($appt['appointment_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($appt['appointment_date'])); ?></span>
                                        <span class="year"><?php echo date('Y', strtotime($appt['appointment_date'])); ?></span>
                                    </div>
                                    
                                    <div class="appointment-details">
                                        <div class="appointment-header">
                                            <h3>
                                                <?php if ($user_type === 'patient'): ?>
                                                    Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?>
                                                    <span class="specialization"><?php echo htmlspecialchars($appt['specialization']); ?></span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($appt['patient_name']); ?>
                                                <?php endif; ?>
                                            </h3>
                                            <span class="status <?php echo getStatusBadgeClass($appt['status']); ?>">
                                                <?php echo ucfirst($appt['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="appointment-info">
                                            <div class="info-item">
                                                <i class="far fa-clock"></i>
                                                <span><?php echo date('h:i A', strtotime($appt['start_time'])); ?> - <?php echo date('h:i A', strtotime($appt['end_time'])); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="far fa-calendar"></i>
                                                <span><?php echo date('l, F j, Y', strtotime($appt['appointment_date'])); ?></span>
                                            </div>
                                            <?php if (!empty($appt['purpose'])): ?>
                                                <div class="info-item">
                                                    <i class="far fa-comment"></i>
                                                    <span><?php echo htmlspecialchars($appt['purpose']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="appointment-actions">
                                            <a href="appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-outline btn-sm">
                                                <i class="far fa-eye"></i> View Details
                                            </a>
                                            
                                            <?php if ($appt['status'] === 'pending' && $user_type === 'patient'): ?>
                                                <button type="button" class="btn btn-outline btn-sm btn-cancel" 
                                                        data-appointment-id="<?php echo $appt['id']; ?>">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($appt['status'] === 'approved' && strtotime($appt['appointment_date'] . ' ' . $appt['start_time']) > time()): ?>
                                                <a href="#" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-video"></i> Join Call
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i === $current_page): ?>
                                            <span class="page-link active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                               class="page-link">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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
