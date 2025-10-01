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

$doctor_id = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$date = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT a.*, 
           CONCAT(p.first_name, ' ', p.last_name) as patient_name,
           p.email as patient_email,
           p.phone as patient_phone
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    WHERE a.doctor_id = ?";

$params = [$doctor_id];

if ($status !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

if (!empty($date)) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date;
}

$query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'pending'");
$stmt->execute([$doctor_id]);
$pending_count = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$stmt->execute([$doctor_id]);
$today_count = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
            margin: 0;
        }
        
        .header-actions a {
            margin-left: 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .header-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 36px;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-box p {
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .appointments-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-stethoscope"></i> Manage Appointments</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Review and manage your patient appointments</p>
            </div>
            <div class="header-actions">
                <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="../appointments.php"><i class="fas fa-calendar"></i> My Schedule</a>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stat-box">
                <i class="fas fa-clock" style="font-size: 24px; color: #ffc107;"></i>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Requests</p>
            </div>
            <div class="stat-box">
                <i class="fas fa-calendar-day" style="font-size: 24px; color: #28a745;"></i>
                <h3><?php echo $today_count; ?></h3>
                <p>Today's Appointments</p>
            </div>
            <div class="stat-box">
                <i class="fas fa-users" style="font-size: 24px; color: #667eea;"></i>
                <h3><?php echo count($appointments); ?></h3>
                <p>Total Listed</p>
            </div>
        </div>
        
        <div class="filters">
            <form method="get">
                <div class="filter-group">
                    <label for="status">Filter by Status</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Filter by Date</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="appointments.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>
        
        <div class="appointments-table">
            <?php if (empty($appointments)): ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments found</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></strong><br>
                                    <small style="color: #999;"><?php echo date('l', strtotime($appt['appointment_date'])); ?></small>
                                </td>
                                <td>
                                    <?php echo date('h:i A', strtotime($appt['start_time'])); ?><br>
                                    <small style="color: #999;">to <?php echo date('h:i A', strtotime($appt['end_time'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($appt['patient_email']); ?><br>
                                        <?php if (!empty($appt['patient_phone'])): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($appt['patient_phone']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($appt['purpose'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($appt['status']); ?>">
                                        <?php echo ucfirst($appt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($appt['status'] === 'pending'): ?>
                                            <form method="post" action="update-appointment.php" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" action="update-appointment.php" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php elseif ($appt['status'] === 'approved'): ?>
                                            <form method="post" action="update-appointment.php" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="../appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm" style="background: #6c757d; color: white;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
