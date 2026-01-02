<?php
require_once __DIR__ . '/../config/config.php';
$current_user = null;
if (isLoggedIn()) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Plaza Management System'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <div class="app-container">
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Left Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-building"></i>
                    <span>Plaza MS</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <?php if (isAdmin()): ?>
                        <?php 
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $current_path = $_SERVER['PHP_SELF'];
                        ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php' && strpos($current_path, 'admin') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/spaces.php" class="<?php echo (in_array($current_page, ['spaces.php', 'assign-space.php', 'unassign-space.php'])) ? 'active' : ''; ?>">
                                <i class="fas fa-building"></i>
                                <span>Spaces</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/assigned-spaces.php" class="<?php echo ($current_page == 'assigned-spaces.php' || $current_page == 'update-assignment.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Assigned Spaces</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="<?php echo (in_array($current_page, ['customers.php', 'customer-details.php', 'tenants.php'])) ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Customers</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/ledger.php" class="<?php echo ($current_page == 'ledger.php' || $current_page == 'payments-ledger.php') ? 'active' : ''; ?>">
                                <i class="fas fa-book"></i>
                                <span>Ledger</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/payments.php" class="<?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Payments</span>
                            </a>
                        </li>
                        <?php /*
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/maintenance.php" class="<?php echo ($current_page == 'maintenance.php') ? 'active' : ''; ?>">
                                <i class="fas fa-tools"></i>
                                <span>Maintenance</span>
                            </a>
                        </li>
                        */ ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/agreements.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'agreements.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-file-contract"></i>
                                <span>My Agreements</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/ledger.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ledger.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-book"></i>
                                <span>My Ledger</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/payments.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Payments</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/maintenance.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'maintenance.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-tools"></i>
                                <span>Maintenance</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>tenant/profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i>
                                <span>Profile</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-wrapper">
            <header class="top-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-title">
                    <h1><?php echo isset($page_title) ? $page_title : 'Plaza Management System'; ?></h1>
                </div>
                <div class="header-user">
                    <div class="user-info-header" id="userInfoHeader">
                        <div class="user-info-text">
                            <div class="user-name-header"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                            <div class="user-role-header"><?php echo $current_user['user_type'] === 'tenant' ? 'Customer' : ucfirst($current_user['user_type']); ?></div>
                        </div>
                        <i class="fas fa-user-circle"></i>
                        <i class="fas fa-chevron-down user-dropdown-icon"></i>
                    </div>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="<?php echo BASE_URL; ?>admin/profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </header>
            <main class="main-content">
    <?php else: ?>
        <!-- Public pages without sidebar -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <i class="fas fa-building"></i>
                    <!-- <span>Plaza MS - Internal Use Only</span> -->
                </div>
            </div>
        </nav>
        <main class="main-content">
    <?php endif; ?>

