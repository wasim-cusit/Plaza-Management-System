<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $username, $email, $password, $full_name, $phone, $address, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $username, $email, $full_name, $phone, $address, $user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $message = 'Profile updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error updating profile: ' . $conn->error;
        $message_type = 'danger';
    }
    $stmt->close();
}

$user = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();

$page_title = 'My Profile - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-user"></i> My Profile</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="max-width: 600px;">
        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
            <small style="color: var(--text-light);">Leave blank to keep current password</small>
        </div>

        <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Profile
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

