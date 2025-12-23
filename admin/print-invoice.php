<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$ledger_id = intval($_GET['ledger_id'] ?? 0);

if (!$ledger_id) {
    die('Invoice not found.');
}

// Get invoice/ledger entry with related data
$invoice = $conn->query("SELECT l.*, a.agreement_number, a.space_type, a.space_id, a.start_date, a.end_date,
                         c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email, 
                         c.address as customer_address, c.cnic as customer_cnic
                         FROM ledger l 
                         LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                         LEFT JOIN customers c ON l.customer_id = c.customer_id 
                         WHERE l.ledger_id = $ledger_id")->fetch_assoc();

if (!$invoice) {
    die('Invoice not found.');
}

// Get space details
$space_info = null;
if ($invoice['space_type'] && $invoice['space_id']) {
    if ($invoice['space_type'] === 'shop') {
        $space_info = $conn->query("SELECT shop_number, shop_name, floor_number, area_sqft FROM shops WHERE shop_id = " . $invoice['space_id'])->fetch_assoc();
    } elseif ($invoice['space_type'] === 'room') {
        $space_info = $conn->query("SELECT room_number as shop_number, room_name as shop_name, floor_number, area_sqft FROM rooms WHERE room_id = " . $invoice['space_id'])->fetch_assoc();
    } elseif ($invoice['space_type'] === 'basement') {
        $space_info = $conn->query("SELECT basement_number as shop_number, basement_name as shop_name, NULL as floor_number, area_sqft FROM basements WHERE basement_id = " . $invoice['space_id'])->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            padding: 2rem;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 1rem;
        }
        .invoice-header h1 {
            font-size: 2rem;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-section h3 {
            color: #2563eb;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .info-row {
            margin-bottom: 0.5rem;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .invoice-items {
            margin: 2rem 0;
        }
        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-items th,
        .invoice-items td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .invoice-items th {
            background: #f3f4f6;
            font-weight: bold;
        }
        .invoice-total {
            margin-top: 1.5rem;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 0.5rem;
        }
        .total-label {
            font-weight: bold;
            width: 150px;
            text-align: right;
            padding-right: 1rem;
        }
        .total-amount {
            width: 150px;
            text-align: right;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .grand-total {
            border-top: 2px solid #2563eb;
            padding-top: 0.5rem;
            font-size: 1.25rem;
            color: #2563eb;
        }
        .invoice-footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="no-print" style="text-align: center; margin-bottom: 1rem;">
            <button onclick="window.print()" style="padding: 0.75rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 1rem;">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="assigned-spaces.php" style="display: inline-block; margin-left: 1rem; padding: 0.75rem 1.5rem; background: #6b7280; color: white; text-decoration: none; border-radius: 0.375rem;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="invoice-header">
            <h1>INVOICE</h1>
            <p>Plaza Management System</p>
        </div>

        <div class="invoice-info">
            <div class="info-section">
                <h3>Invoice Details</h3>
                <div class="info-row">
                    <span class="info-label">Invoice #:</span>
                    <?php echo htmlspecialchars($invoice['invoice_number'] ?: 'N/A'); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <?php echo formatDate($invoice['payment_date']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge status-<?php echo $invoice['status']; ?>">
                        <?php echo ucfirst($invoice['status']); ?>
                    </span>
                </div>
                <?php if ($invoice['agreement_number']): ?>
                    <div class="info-row">
                        <span class="info-label">Agreement #:</span>
                        <?php echo htmlspecialchars($invoice['agreement_number']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h3>Customer Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <?php echo htmlspecialchars($invoice['customer_name']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <?php echo htmlspecialchars($invoice['customer_phone']); ?>
                </div>
                <?php if ($invoice['customer_email']): ?>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <?php echo htmlspecialchars($invoice['customer_email']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($invoice['customer_address']): ?>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <?php echo htmlspecialchars($invoice['customer_address']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($space_info): ?>
            <div class="info-section" style="margin-bottom: 2rem;">
                <h3>Space Information</h3>
                <div class="info-row">
                    <span class="info-label">Space Type:</span>
                    <?php echo ucfirst($invoice['space_type']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Space Number:</span>
                    <?php echo htmlspecialchars($space_info['shop_number']); ?>
                </div>
                <?php if ($space_info['shop_name']): ?>
                    <div class="info-row">
                        <span class="info-label">Space Name:</span>
                        <?php echo htmlspecialchars($space_info['shop_name']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($space_info['floor_number']): ?>
                    <div class="info-row">
                        <span class="info-label">Floor:</span>
                        <?php echo $space_info['floor_number']; ?>
                    </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Area:</span>
                    <?php echo number_format($space_info['area_sqft'], 2); ?> sqft
                </div>
            </div>
        <?php endif; ?>

        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Payment Method</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['description'] ?: 'Invoice Payment'); ?></td>
                        <td>
                            <span class="status-badge status-paid">
                                <?php echo ucfirst(str_replace('_', ' ', $invoice['transaction_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $invoice['payment_method'])); ?></td>
                        <td style="text-align: right;"><?php echo formatCurrency($invoice['amount']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="invoice-total">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-amount"><?php echo formatCurrency($invoice['amount']); ?></span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total Amount:</span>
                <span class="total-amount"><?php echo formatCurrency($invoice['amount']); ?></span>
            </div>
        </div>

        <div class="invoice-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p style="margin-top: 0.5rem; font-size: 0.875rem;">Plaza Management System - Internal Use Only</p>
        </div>
    </div>
</body>
</html>

