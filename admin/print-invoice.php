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

// Check if this ledger entry is part of a combined payment
$all_invoice_items = [$invoice]; // Start with the main invoice item
$payment_info = null;

// Find payment that includes this ledger_id
$payment_query = "SELECT * FROM payments WHERE ledger_id = $ledger_id OR notes LIKE '%Ledger IDs: %' ORDER BY created_at DESC LIMIT 1";
$payment_result = $conn->query($payment_query);

if ($payment_result && $payment_result->num_rows > 0) {
    $payment_info = $payment_result->fetch_assoc();
    
    // Check if payment notes contain multiple ledger IDs
    if (preg_match('/Ledger IDs:\s*([0-9,]+)/', $payment_info['notes'], $matches)) {
        $ledger_ids_str = $matches[1];
        $ledger_ids = array_map('intval', explode(',', $ledger_ids_str));
        
        if (count($ledger_ids) > 1) {
            // Fetch all ledger entries for this combined payment
            $ledger_ids_safe = implode(',', $ledger_ids);
            $all_items_query = "SELECT l.*, a.agreement_number, a.space_type, a.space_id, a.start_date, a.end_date,
                               c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email, 
                               c.address as customer_address, c.cnic as customer_cnic
                               FROM ledger l 
                               LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                               LEFT JOIN customers c ON l.customer_id = c.customer_id 
                               WHERE l.ledger_id IN ($ledger_ids_safe)
                               ORDER BY FIELD(l.transaction_type, 'deposit', 'rent', 'other'), l.ledger_id";
            $all_items_result = $conn->query($all_items_query);
            
            if ($all_items_result && $all_items_result->num_rows > 0) {
                $all_invoice_items = [];
                while ($item = $all_items_result->fetch_assoc()) {
                    $all_invoice_items[] = $item;
                }
                // Use the first item for customer/space info, but show all items in the table
                $invoice = $all_invoice_items[0];
            }
        }
    }
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
            padding: 1rem;
            line-height: 1.4;
            background: #f5f5f5;
            font-size: 12px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 1rem;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 0.5rem;
        }
        .invoice-header h1 {
            font-size: 1.5rem;
            color: #2563eb;
            margin-bottom: 0.25rem;
        }
        .invoice-header p {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .info-section {
            font-size: 0.875rem;
        }
        .info-section h3 {
            color: #2563eb;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        .info-row {
            margin-bottom: 0.25rem;
            display: flex;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
        }
        .invoice-items {
            margin: 1rem 0;
        }
        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .invoice-items th,
        .invoice-items td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .invoice-items th {
            background: #f3f4f6;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .invoice-total {
            margin-top: 1rem;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }
        .total-label {
            font-weight: bold;
            width: 120px;
            text-align: right;
            padding-right: 0.5rem;
        }
        .total-amount {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            border-top: 2px solid #2563eb;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-size: 1rem;
            color: #2563eb;
        }
        .grand-total .total-amount {
            font-size: 1.1rem;
        }
        .invoice-footer {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: bold;
            font-size: 0.75rem;
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
        .space-info-compact {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }
            body {
                background: white;
                padding: 0;
                font-size: 11px;
            }
            .invoice-container {
                box-shadow: none;
                padding: 0.75rem;
                max-width: 100%;
            }
            .no-print {
                display: none;
            }
            .invoice-header {
                margin-bottom: 0.75rem;
            }
            .invoice-header h1 {
                font-size: 1.25rem;
            }
            .invoice-info {
                margin-bottom: 0.75rem;
            }
            .invoice-items {
                margin: 0.75rem 0;
            }
            .invoice-total {
                margin-top: 0.75rem;
            }
            .invoice-footer {
                margin-top: 0.75rem;
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
                    <span class="info-value"><?php echo htmlspecialchars($invoice['invoice_number'] ?: 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value"><?php echo formatDate($invoice['payment_date']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo $invoice['status']; ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </span>
                </div>
                <?php if ($invoice['agreement_number']): ?>
                    <div class="info-row">
                        <span class="info-label">Agreement #:</span>
                        <span class="info-value"><?php echo htmlspecialchars($invoice['agreement_number']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h3>Customer Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($invoice['customer_phone']); ?></span>
                </div>
                <?php if ($invoice['customer_email']): ?>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($invoice['customer_email']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($invoice['customer_address']): ?>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($invoice['customer_address']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($space_info): ?>
            <div class="space-info-compact">
                <div><strong>Space Type:</strong> <?php echo ucfirst($invoice['space_type']); ?></div>
                <div><strong>Space Number:</strong> <?php echo htmlspecialchars($space_info['shop_number']); ?></div>
                <?php if ($space_info['shop_name']): ?>
                    <div><strong>Space Name:</strong> <?php echo htmlspecialchars($space_info['shop_name']); ?></div>
                <?php endif; ?>
                <?php if ($space_info['floor_number']): ?>
                    <div><strong>Floor:</strong> <?php echo $space_info['floor_number']; ?></div>
                <?php endif; ?>
                <div><strong>Area:</strong> <?php echo number_format($space_info['area_sqft'], 2); ?> sqft</div>
            </div>
        <?php endif; ?>

        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Description</th>
                        <th style="width: 15%;">Type</th>
                        <th style="width: 20%;">Invoice #</th>
                        <th style="width: 25%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_amount = 0;
                    foreach ($all_invoice_items as $item): 
                        $total_amount += floatval($item['amount']);
                    ?>
                        <tr>
                            <td style="font-size: 0.8rem;"><?php echo htmlspecialchars($item['description'] ?: ucfirst(str_replace('_', ' ', $item['transaction_type'])) . ' Payment'); ?></td>
                            <td>
                                <span class="status-badge status-paid">
                                    <?php echo ucfirst(str_replace('_', ' ', $item['transaction_type'])); ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem;"><?php echo htmlspecialchars($item['invoice_number'] ?? '-'); ?></td>
                            <td style="text-align: right; font-weight: 600;"><?php echo formatCurrency($item['amount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="invoice-total">
            <?php if (count($all_invoice_items) > 1): ?>
                <div class="total-row">
                    <span class="total-label">Items (<?php echo count($all_invoice_items); ?>):</span>
                    <span class="total-amount"><?php echo formatCurrency($total_amount); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($payment_info): ?>
                <div class="total-row">
                    <span class="total-label">Payment Amount:</span>
                    <span class="total-amount"><?php echo formatCurrency($payment_info['amount']); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Payment Method:</span>
                    <span class="total-amount"><?php echo ucfirst(str_replace('_', ' ', $payment_info['payment_method'])); ?></span>
                </div>
                <?php if ($payment_info['transaction_id']): ?>
                    <div class="total-row">
                        <span class="total-label">Transaction ID:</span>
                        <span class="total-amount"><?php echo htmlspecialchars($payment_info['transaction_id']); ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span class="total-label">Total Amount:</span>
                <span class="total-amount"><?php echo formatCurrency($payment_info ? $payment_info['amount'] : $total_amount); ?></span>
            </div>
        </div>

        <div class="invoice-footer">
            <p style="margin-bottom: 0.25rem;"><strong>Thank you for your business!</strong></p>
            <p style="font-size: 0.75rem; color: #9ca3af;">Plaza Management System</p>
        </div>
    </div>
</body>
</html>

