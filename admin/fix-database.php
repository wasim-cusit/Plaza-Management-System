<?php
/**
 * Database Fix Script
 * Run this once to fix foreign key constraints from tenant_id to customer_id
 */

require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$errors = [];
$success = [];

// Check if customers table exists
$check = $conn->query("SHOW TABLES LIKE 'customers'");
if ($check->num_rows == 0) {
    die("Customers table does not exist. Please run the database.sql file first.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
    // Get all foreign keys and drop them
    $tables = ['agreements', 'shops', 'rooms', 'basements', 'ledger', 'payments', 'maintenance_requests'];
    
    foreach ($tables as $table) {
        // Get foreign key names that reference users table
        $fk_query = "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = '$table' 
                     AND REFERENCED_TABLE_NAME = 'users'";
        
        $fk_result = $conn->query($fk_query);
        while ($fk = $fk_result->fetch_assoc()) {
            $fk_name = $fk['CONSTRAINT_NAME'];
            try {
                $conn->query("ALTER TABLE $table DROP FOREIGN KEY `$fk_name`");
                $success[] = "Dropped foreign key $fk_name from $table";
            } catch (Exception $e) {
                $errors[] = "Error dropping FK $fk_name from $table: " . $e->getMessage();
            }
        }
    }
    
    // Ensure customer_id columns exist
    foreach ($tables as $table) {
        $check_col = $conn->query("SHOW COLUMNS FROM $table LIKE 'customer_id'");
        if ($check_col->num_rows == 0) {
            try {
                $conn->query("ALTER TABLE $table ADD COLUMN customer_id INT");
                $success[] = "Added customer_id column to $table";
            } catch (Exception $e) {
                $errors[] = "$table: " . $e->getMessage();
            }
        }
    }
    
    // Add new foreign key constraints (only if they don't exist)
    $fk_definitions = [
        'shops' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL",
        'rooms' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL",
        'basements' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL",
        'agreements' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE",
        'ledger' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE",
        'payments' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE",
        'maintenance_requests' => "FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE"
    ];
    
    foreach ($fk_definitions as $table => $fk_def) {
        // Check if foreign key already exists
        $check_fk = $conn->query("SELECT CONSTRAINT_NAME 
                                  FROM information_schema.KEY_COLUMN_USAGE 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = '$table' 
                                  AND COLUMN_NAME = 'customer_id' 
                                  AND REFERENCED_TABLE_NAME = 'customers'");
        
        if ($check_fk->num_rows == 0) {
            try {
                $conn->query("ALTER TABLE $table ADD $fk_def");
                $success[] = "Added foreign key for $table.customer_id";
            } catch (Exception $e) {
                $errors[] = "$table FK: " . $e->getMessage();
            }
        } else {
            $success[] = "Foreign key for $table.customer_id already exists";
        }
    }
}

$page_title = 'Fix Database - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-tools"></i> Fix Database Foreign Keys</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <strong>Success:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <?php foreach ($success as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Errors:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="padding: 1.5rem;">
        <p>This script will fix the foreign key constraints to use <code>customer_id</code> instead of <code>tenant_id</code>.</p>
        <p><strong>Warning:</strong> Make sure you have backed up your database before running this.</p>
        
        <form method="POST" onsubmit="return confirm('Are you sure you want to fix the database constraints? Make sure you have a backup!');">
            <button type="submit" name="fix" class="btn btn-primary">
                <i class="fas fa-wrench"></i> Fix Foreign Key Constraints
            </button>
        </form>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--light-color); border-radius: 0.375rem;">
            <h3>What this script does:</h3>
            <ul>
                <li>Drops old foreign key constraints that reference <code>users</code> table</li>
                <li>Adds <code>customer_id</code> columns if they don't exist</li>
                <li>Creates new foreign key constraints referencing <code>customers</code> table</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
