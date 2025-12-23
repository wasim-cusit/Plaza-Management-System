<?php
/**
 * Quick Fix for Agreements Foreign Key
 * This will immediately fix the agreements table foreign key issue
 */

require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_agreements'])) {
    try {
        // Step 1: Find and drop the old foreign key
        $fk_query = "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'agreements' 
                     AND COLUMN_NAME = 'tenant_id' 
                     AND REFERENCED_TABLE_NAME = 'users'";
        
        $fk_result = $conn->query($fk_query);
        if ($fk_result->num_rows > 0) {
            $fk = $fk_result->fetch_assoc();
            $fk_name = $fk['CONSTRAINT_NAME'];
            $conn->query("ALTER TABLE agreements DROP FOREIGN KEY `$fk_name`");
            $message .= "Dropped old foreign key: $fk_name<br>";
        }
        
        // Step 2: Add customer_id column if it doesn't exist
        $check_col = $conn->query("SHOW COLUMNS FROM agreements LIKE 'customer_id'");
        if ($check_col->num_rows == 0) {
            $conn->query("ALTER TABLE agreements ADD COLUMN customer_id INT AFTER tenant_id");
            $message .= "Added customer_id column<br>";
        }
        
        // Step 3: Add new foreign key constraint
        $check_fk = $conn->query("SELECT CONSTRAINT_NAME 
                                  FROM information_schema.KEY_COLUMN_USAGE 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = 'agreements' 
                                  AND COLUMN_NAME = 'customer_id' 
                                  AND REFERENCED_TABLE_NAME = 'customers'");
        
        if ($check_fk->num_rows == 0) {
            $conn->query("ALTER TABLE agreements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE");
            $message .= "Added new foreign key constraint for customer_id<br>";
        }
        
        $message .= "<strong>Agreements table fixed successfully!</strong>";
        $message_type = 'success';
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

$page_title = 'Quick Fix - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-bolt"></i> Quick Fix - Agreements Foreign Key</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="padding: 1.5rem;">
        <p><strong>This will fix the immediate foreign key constraint error on the agreements table.</strong></p>
        <p>It will:</p>
        <ul>
            <li>Drop the old foreign key constraint (tenant_id → users)</li>
            <li>Add customer_id column if missing</li>
            <li>Add new foreign key constraint (customer_id → customers)</li>
        </ul>
        
        <form method="POST" onsubmit="return confirm('Fix the agreements table foreign key constraint?');" style="margin-top: 1.5rem;">
            <button type="submit" name="fix_agreements" class="btn btn-primary btn-lg">
                <i class="fas fa-bolt"></i> Fix Agreements Table Now
            </button>
        </form>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--light-color); border-radius: 0.375rem;">
            <p><strong>After fixing:</strong> Try assigning a space again. The error should be resolved.</p>
            <p><strong>Note:</strong> You may also want to run the full database fix at <a href="fix-database.php">fix-database.php</a> to fix all tables.</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

