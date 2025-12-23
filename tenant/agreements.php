<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$tenant_id = $_SESSION['user_id'];

$agreements = $conn->query("SELECT * FROM agreements WHERE tenant_id = $tenant_id ORDER BY created_at DESC");

$page_title = 'My Agreements - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-file-contract"></i> My Agreements</h1>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Agreement Number</th>
                    <th>Space Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Monthly Rent</th>
                    <th>Security Deposit</th>
                    <th>Status</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agreements->num_rows > 0): ?>
                    <?php while ($agreement = $agreements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($agreement['agreement_number']); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($agreement['space_type']); ?></span></td>
                            <td><?php echo formatDate($agreement['start_date']); ?></td>
                            <td><?php echo formatDate($agreement['end_date']); ?></td>
                            <td><?php echo formatCurrency($agreement['monthly_rent']); ?></td>
                            <td><?php echo formatCurrency($agreement['security_deposit']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $agreement['status'] === 'active' ? 'success' : 
                                        ($agreement['status'] === 'expired' ? 'danger' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($agreement['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($agreement['document_file']): ?>
                                    <a href="<?php echo BASE_URL; ?>uploads/agreements/<?php echo htmlspecialchars($agreement['document_file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">No document</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-light);">No agreements found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

