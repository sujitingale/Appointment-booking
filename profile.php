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
$errors = [];
$success = '';

// Get user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get doctor profile if user is a doctor
$doctor_profile = null;
if ($user_type === 'doctor') {
    $stmt = $pdo->prepare('SELECT * FROM doctor_profiles WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $doctor_profile = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic user info
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Doctor-specific fields
    $specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : '';
    $qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : '';
    $experience = isset($_POST['experience']) ? (int)$_POST['experience'] : 0;
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Password change fields
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate basic info
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email is already registered';
        }
    }
    
    // Validate doctor-specific fields
    if ($user_type === 'doctor') {
        if (empty($specialization)) $errors[] = 'Specialization is required';
        if (empty($qualification)) $errors[] = 'Qualification is required';
    }
    
    // Validate password change if any password field is filled
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update users table
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE id = ?');
                $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);
            }
            
            // Update doctor profile if user is a doctor
            if ($user_type === 'doctor') {
                if ($doctor_profile) {
                    $stmt = $pdo->prepare('UPDATE doctor_profiles SET specialization = ?, qualification = ?, experience_years = ?, bio = ? WHERE user_id = ?');
                    $stmt->execute([$specialization, $qualification, $experience, $bio, $user_id]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO doctor_profiles (user_id, specialization, qualification, experience_years, bio) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$user_id, $specialization, $qualification, $experience, $bio]);
                }
            }
            
            $pdo->commit();
            
            // Update session data
            $_SESSION['first_name'] = $first_name;
            
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user_type === 'doctor') {
                $stmt = $pdo->prepare('SELECT * FROM doctor_profiles WHERE user_id = ?');
                $stmt->execute([$user_id]);
                $doctor_profile = $stmt->fetch();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while updating your profile: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Profile</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="appointments.php">My Appointments</a>
                <?php if ($user_type === 'doctor'): ?>
                    <a href="doctor/appointments.php">Manage Appointments</a>
                <?php else: ?>
                    <a href="book-appointment.php">Book Appointment</a>
                <?php endif; ?>
                <a href="profile.php" class="active">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="profile-container">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="profile.php" method="post" class="profile-form">
                    <div class="card">
                        <h2>Personal Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <?php if ($user_type === 'doctor'): ?>
                            <h3 class="mt-4">Professional Information</h3>
                            <div class="form-group">
                                <label for="specialization">Specialization</label>
                                <input type="text" id="specialization" name="specialization" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctor_profile['specialization'] ?? ''); ?>" 
                                       <?php echo $user_type === 'doctor' ? 'required' : ''; ?>>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="qualification">Qualification</label>
                                    <input type="text" id="qualification" name="qualification" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor_profile['qualification'] ?? ''); ?>"
                                           <?php echo $user_type === 'doctor' ? 'required' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label for="experience">Years of Experience</label>
                                    <input type="number" id="experience" name="experience" class="form-control" min="0"
                                           value="<?php echo htmlspecialchars($doctor_profile['experience_years'] ?? '0'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($doctor_profile['bio'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card mt-4">
                        <h2>Change Password</h2>
                        <p class="text-muted">Leave these fields blank to keep your current password.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-right mt-4">
                        <a href="dashboard.php" class="btn btn-outline mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Appointment Booking System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
