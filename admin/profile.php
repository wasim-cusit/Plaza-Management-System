<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$success = '';
$error = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        // Update full name
        $update_name = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $update_name->bind_param("si", $full_name, $_SESSION['user_id']);
        $update_name->execute();
        $update_name->close();
        
        // Update session
        $_SESSION['full_name'] = $full_name;
        
        // Update password if provided
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $error = 'Current password is required to change password.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (empty($new_password)) {
                $error = 'New password is required.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirm password do not match.';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_password->bind_param("si", $hashed_password, $_SESSION['user_id']);
                $update_password->execute();
                $update_password->close();
                $success = 'Profile and password updated successfully!';
            }
        } else {
            $success = 'Profile updated successfully!';
        }
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
}

$page_title = 'Profile';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-user"></i>
            My Profile
        </h2>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="form-control" style="background-color: #f3f4f6; cursor: not-allowed;">
                <small class="form-text">Username cannot be changed.</small>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled class="form-control" style="background-color: #f3f4f6; cursor: not-allowed;">
                <small class="form-text">Email cannot be changed.</small>
            </div>
            
            <div class="form-group">
                <label for="user_type">User Type</label>
                <input type="text" id="user_type" name="user_type" value="<?php echo ucfirst($user['user_type']); ?>" disabled class="form-control" style="background-color: #f3f4f6; cursor: not-allowed;">
            </div>
            
            <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border-color);">
            
            <h3 style="margin-bottom: 1.5rem; color: var(--dark-color); font-size: 1.1rem;">
                <i class="fas fa-lock"></i> Change Password
            </h3>
            <p style="margin-bottom: 1.5rem; color: var(--text-light); font-size: 0.9rem;">
                Leave password fields blank if you don't want to change your password.
            </p>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" autocomplete="current-password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" autocomplete="new-password" minlength="6">
                <small class="form-text">Password must be at least 6 characters long.</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password" minlength="6">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark-color);
}

.form-group .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-light);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

hr {
    margin: 2rem 0;
    border: none;
    border-top: 1px solid var(--border-color);
}
</style>

<?php include '../includes/footer.php'; ?>

