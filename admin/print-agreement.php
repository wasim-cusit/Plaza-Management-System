<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$agreement_id = intval($_GET['id'] ?? 0);

if (!$agreement_id) {
    die('Agreement not found.');
}

$agreement = $conn->query("SELECT a.*, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address, c.gender as customer_gender, c.cnic as customer_cnic
                          FROM agreements a 
                          JOIN customers c ON a.customer_id = c.customer_id 
                          WHERE a.agreement_id = $agreement_id")->fetch_assoc();

if (!$agreement) {
    die('Agreement not found.');
}

// Get space details
$space_info = null;
if ($agreement['space_type'] === 'shop') {
    $space_info = $conn->query("SELECT shop_number, shop_name, floor_number, area_sqft FROM shops WHERE shop_id = " . $agreement['space_id'])->fetch_assoc();
} elseif ($agreement['space_type'] === 'room') {
    $space_info = $conn->query("SELECT room_number as shop_number, room_name as shop_name, floor_number, area_sqft FROM rooms WHERE room_id = " . $agreement['space_id'])->fetch_assoc();
} elseif ($agreement['space_type'] === 'basement') {
    $space_info = $conn->query("SELECT basement_number as shop_number, basement_name as shop_name, NULL as floor_number, area_sqft FROM basements WHERE basement_id = " . $agreement['space_id'])->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement - <?php echo htmlspecialchars($agreement['agreement_number']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            padding: 2rem;
            line-height: 1.6;
        }
        .agreement-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 3px solid #000;
            padding-bottom: 1rem;
        }
        .agreement-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .agreement-body {
            margin: 2rem 0;
        }
        .section {
            margin-bottom: 1.5rem;
        }
        .section h2 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #ccc;
            padding-bottom: 0.25rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
        }
        .info-label {
            font-weight: bold;
            width: 40%;
        }
        .info-value {
            width: 60%;
        }
        .terms {
            background: #f5f5f5;
            padding: 1rem;
            border-left: 4px solid #2563eb;
            margin: 1rem 0;
        }
        .signature-section {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 3rem;
            padding-top: 0.5rem;
        }
        @media print {
            body {
                padding: 1rem;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 1rem;">
        <button onclick="window.print()" style="padding: 0.75rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 1rem;">
            <i class="fas fa-print"></i> Print Agreement
        </button>
        <a href="customer-details.php?customer_id=<?php echo $agreement['tenant_id']; ?>" style="display: inline-block; margin-left: 1rem; padding: 0.75rem 1.5rem; background: #6b7280; color: white; text-decoration: none; border-radius: 0.375rem;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="agreement-header">
        <h1>LEASE AGREEMENT</h1>
        <p>Plaza Management System</p>
    </div>

    <div class="agreement-body">
        <div class="section">
            <h2>Agreement Information</h2>
            <div class="info-row">
                <span class="info-label">Agreement Number:</span>
                <span class="info-value"><?php echo htmlspecialchars($agreement['agreement_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value"><?php echo formatDate($agreement['created_at']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?php echo ucfirst($agreement['status']); ?></span>
            </div>
        </div>

        <div class="section">
            <h2>Customer Information</h2>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($agreement['customer_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($agreement['customer_email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo htmlspecialchars($agreement['customer_phone'] ?? '-'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value"><?php echo htmlspecialchars($agreement['customer_address'] ?? '-'); ?></span>
            </div>
        </div>

        <div class="section">
            <h2>Space Information</h2>
            <div class="info-row">
                <span class="info-label">Space Type:</span>
                <span class="info-value"><?php echo ucfirst($agreement['space_type']); ?></span>
            </div>
            <?php if ($space_info): ?>
                <div class="info-row">
                    <span class="info-label">Space Number:</span>
                    <span class="info-value"><?php echo htmlspecialchars($space_info['shop_number']); ?></span>
                </div>
                <?php if ($space_info['shop_name']): ?>
                    <div class="info-row">
                        <span class="info-label">Space Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($space_info['shop_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($space_info['floor_number']): ?>
                    <div class="info-row">
                        <span class="info-label">Floor:</span>
                        <span class="info-value"><?php echo $space_info['floor_number']; ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Area:</span>
                    <span class="info-value"><?php echo number_format($space_info['area_sqft'], 2); ?> sqft</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Lease Terms</h2>
            <div class="info-row">
                <span class="info-label">Start Date:</span>
                <span class="info-value"><?php echo formatDate($agreement['start_date']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">End Date:</span>
                <span class="info-value"><?php echo formatDate($agreement['end_date']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Monthly Rent:</span>
                <span class="info-value"><?php echo formatCurrency($agreement['monthly_rent']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Security Deposit:</span>
                <span class="info-value"><?php echo formatCurrency($agreement['security_deposit']); ?></span>
            </div>
        </div>

        <?php if ($agreement['terms']): ?>
            <div class="section">
                <h2>Terms & Conditions</h2>
                <div class="terms">
                    <?php echo nl2br(htmlspecialchars($agreement['terms'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Customer Signature</strong>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Administrator Signature</strong>
            </div>
        </div>
    </div>
</body>
</html>

