<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDbConnection();
$settingsResult = $conn->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
$settings = $settingsResult && $settingsResult->num_rows > 0 ? $settingsResult->fetch_assoc() : [];

$invoiceStmt = $conn->prepare('SELECT * FROM invoices WHERE id = ?');
$invoiceStmt->bind_param('i', $id);
$invoiceStmt->execute();
$invoice = $invoiceStmt->get_result()->fetch_assoc();
$invoiceStmt->close();
$template = in_array(isset($invoice['template']) ? $invoice['template'] : 'invoice', ['invoice', 'quote', 'receipt', 'fuel_order', 'estimate', 'proforma', 'delivery_note', 'purchase_order'], true)
    ? $invoice['template']
    : 'invoice';
$layout = in_array(isset($invoice['layout']) ? $invoice['layout'] : 'clean', ['clean', 'modern', 'minimal', 'letter', 'executive', 'boxed', 'compact', 'bold', 'monochrome', 'elegant', 'air', 'color', 'fuel_quote'], true)
    ? $invoice['layout']
    : 'clean';

if (!$invoice) {
    $conn->close();
    header('Location: index.php');
    exit;
}

$itemsStmt = $conn->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC');
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemsStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">N</div>
                <h1>Invoice Preview</h1>
            </div>
            <div class="top-actions">
                <a href="index.php" class="button secondary">Back to Dashboard</a>
                <button class="button" type="button" onclick="window.print()">Print</button>
            </div>
        </header>

        <?php if ($template === 'quote'): ?>
            <div class="invoice-details print-area template-quote layout-<?= htmlspecialchars($layout) ?>">
                <div class="quotation-sheet">
                    <div class="quotation-header">
                        <div class="quote-heading">
                            <div class="quote-title"><?= $layout === 'fuel_quote' ? 'QUOTATION FOR DIESEL FUEL' : 'QUOTATION' ?></div>
                            <h2><?= htmlspecialchars($settings['company_name'] ?? 'Your Company') ?></h2>
                        </div>
                        <div class="quote-company-meta">
                            <p><?= htmlspecialchars($settings['company_email'] ?? '') ?></p>
                            <p><?= htmlspecialchars($settings['company_phone'] ?? '') ?></p>
                            <p><?= htmlspecialchars($settings['company_address'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="quotation-body">
                        <div class="customer-block quote-party">
                            <span>FROM</span>
                            <strong><?= htmlspecialchars($settings['company_name'] ?? 'Your Company') ?></strong>
                            <p><?= htmlspecialchars($settings['company_email'] ?? '') ?></p>
                            <p><?= htmlspecialchars($settings['company_phone'] ?? '') ?></p>
                        </div>
                        <div class="customer-block quote-party">
                            <span>BILL TO</span>
                            <strong><?= htmlspecialchars($invoice['customer_name'] ?: 'Customer Name') ?></strong>
                            <p><?= htmlspecialchars($invoice['customer_email'] ?: '') ?></p>
                            <p><?= htmlspecialchars($invoice['customer_phone'] ?: '') ?></p>
                        </div>
                        <div class="quote-number">
                            <p><strong>Quote #</strong> <?= htmlspecialchars($invoice['invoice_number'] ?: 'QTN-0001') ?></p>
                            <p><strong>Date</strong> <?= htmlspecialchars($invoice['invoice_date'] ?: date('Y-m-d')) ?></p>
                        </div>
                    </div>

                    <div class="reference-block">
                        <p>Thank you for your business. Please find our quotation below.</p>
                    </div>

                    <?php if ($layout === 'fuel_quote'): ?>
                        <div class="reference-block fuel-reference"><p>Dear Sir,</p><p><strong>RE: QUOTATION FOR DIESEL FUEL</strong></p><p>Kindly find our quotation below.</p></div>
                    <?php endif; ?>
                    <table class="quote-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['item_name'] ?: 'Service item') ?></strong><br><small><?= htmlspecialchars($item['description'] ?: '') ?></small></td>
                                    <td><?= htmlspecialchars(number_format((float)($item['unit_price'] ?: 0), 2)) ?></td>
                                    <td><?= htmlspecialchars($item['quantity'] ?: '1') ?></td>
                                    <td><?= htmlspecialchars(number_format((float)($item['total'] ?: $item['unit_price'] * $item['quantity']), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3" class="tfoot-label">Subtotal</td><td><?= htmlspecialchars(number_format((float)($invoice['subtotal'] ?: 0), 2)) ?></td></tr>
                            <tr><td colspan="3" class="tfoot-label">Tax (<?= htmlspecialchars(number_format((float)($invoice['tax_rate'] ?: 0), 2)) ?>%)</td><td><?= htmlspecialchars(number_format((float)($invoice['tax_amount'] ?: 0), 2)) ?></td></tr>
                            <tr><td colspan="3" class="tfoot-label">Total</td><td><?= htmlspecialchars(number_format((float)($invoice['total'] ?: 0), 2)) ?></td></tr>
                            <tr><td colspan="3" class="tfoot-label balance-label">Balance Due</td><td class="balance-value"><?= htmlspecialchars(number_format((float)($invoice['total'] ?: 0), 2)) ?></td></tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        <?php elseif ($template === 'receipt'): ?>
            <div class="invoice-details print-area template-receipt layout-<?= htmlspecialchars($layout) ?>">
                <div class="receipt-paper">
                    <div class="receipt-header"><?= htmlspecialchars($settings['company_name'] ?? 'Your Company') ?></div>
                    <div class="receipt-subtitle">Official Receipt</div>
                    <div class="receipt-meta-row">
                        <span>Receipt # <?= htmlspecialchars($invoice['invoice_number'] ?: 'RCPT-0001') ?></span>
                        <span><?= htmlspecialchars($invoice['invoice_date'] ?: date('d/m/Y')) ?></span>
                    </div>
                    <div class="receipt-customer-box">
                        <strong><?= htmlspecialchars($invoice['customer_name'] ?: 'Customer') ?></strong>
                        <span><?= htmlspecialchars($invoice['customer_email'] ?: '') ?></span>
                    </div>
                    <div class="receipt-items-box">
                        <?php foreach ($items as $item): ?>
                            <div class="receipt-row">
                                <span><?= htmlspecialchars($item['item_name'] ?: 'Item') ?></span>
                                <strong><?= htmlspecialchars(number_format((float)($item['total'] ?: 0), 2)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="receipt-total-box">
                        <div><span>Subtotal</span><strong><?= htmlspecialchars(number_format((float)($invoice['subtotal'] ?: 0), 2)) ?></strong></div>
                        <div><span>Tax (<?= htmlspecialchars(number_format((float)($invoice['tax_rate'] ?: 0), 2)) ?>%)</span><strong><?= htmlspecialchars(number_format((float)($invoice['tax_amount'] ?: 0), 2)) ?></strong></div>
                        <div class="grand"><span>Total Paid</span><strong><?= htmlspecialchars(number_format((float)($invoice['total'] ?: 0), 2)) ?></strong></div>
                    </div>
                </div>
            </div>
        <?php elseif ($template === 'fuel_order'): ?>
            <div class="invoice-details print-area template-fuel-order layout-<?= htmlspecialchars($layout) ?>">
                <div class="fuel-sheet">
                    <div class="fuel-top">
                        <div class="fuel-brand">CB ENERGY</div>
                        <div class="fuel-company"><?= htmlspecialchars($settings['company_name'] ?? 'CLB ENERGY LIMITED') ?><br><small><?= htmlspecialchars($settings['company_email'] ?? 'clbenergy@gmail.com') ?></small></div>
                    </div>
                    <div class="fuel-header">FUEL ORDER</div>
                    <div class="fuel-info-grid">
                        <div><span>Customer</span><strong><?= htmlspecialchars($invoice['customer_name'] ?: 'Customer Name') ?></strong></div>
                        <div><span>Order #</span><strong><?= htmlspecialchars($invoice['invoice_number'] ?: 'FO-0001') ?></strong></div>
                        <div><span>Due</span><strong><?= htmlspecialchars($invoice['due_date'] ?: date('d/m/Y')) ?></strong></div>
                        <div><span>Issue Date</span><strong><?= htmlspecialchars($invoice['invoice_date'] ?: date('d/m/Y')) ?></strong></div>
                    </div>
                    <table class="fuel-order-table">
                        <thead><tr><th>Fuel</th><th>Qty</th><th>Rate</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name'] ?: 'Fuel') ?></td>
                                    <td><?= htmlspecialchars($item['quantity'] ?: '1') ?></td>
                                    <td><?= htmlspecialchars(number_format((float)($item['unit_price'] ?: 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float)($item['total'] ?: 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="fuel-total-box">
                        <div><span>Sub-total</span><strong><?= htmlspecialchars(number_format((float)($invoice['subtotal'] ?: 0), 2)) ?></strong></div>
                        <div><span>Tax (<?= htmlspecialchars(number_format((float)($invoice['tax_rate'] ?: 0), 2)) ?>%)</span><strong><?= htmlspecialchars(number_format((float)($invoice['tax_amount'] ?: 0), 2)) ?></strong></div>
                        <div class="grand"><span>Total</span><strong><?= htmlspecialchars(number_format((float)($invoice['total'] ?: 0), 2)) ?></strong></div>
                    </div>
                    <div class="fuel-note"><?= nl2br(htmlspecialchars($invoice['notes'] ?: 'We shall deliver fuel to the site.')) ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="invoice-details print-area template-standard layout-<?= htmlspecialchars($layout) ?>">
                <div class="invoice-header">
                    <div class="company-block">
                        <div class="company-logo">N</div>
                        <div>
                            <h2><?= htmlspecialchars($settings['company_name'] ?? 'Your Company') ?></h2>
                            <p><?= htmlspecialchars($settings['company_email'] ?: '') ?></p>
                            <p><?= htmlspecialchars($settings['company_phone'] ?: '') ?></p>
                            <p><?= htmlspecialchars($settings['company_address'] ?: '') ?></p>
                        </div>
                    </div>

                    <div class="invoice-meta">
                        <p><strong>Invoice #</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></p>
                        <p><strong>Invoice Date:</strong> <?= htmlspecialchars($invoice['invoice_date']) ?></p>
                        <p><strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date'] ?: 'Not set') ?></p>
                        <span class="badge"><?= htmlspecialchars($invoice['status']) ?></span>
                    </div>
                </div>

                <div class="bill-to">
                    <h3>Bill To</h3>
                    <p><strong><?= htmlspecialchars($invoice['customer_name']) ?></strong></p>
                    <p><?= htmlspecialchars($invoice['customer_email']) ?></p>
                    <p><?= htmlspecialchars($invoice['customer_phone']) ?></p>
                </div>

                <table class="invoice-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars($item['description']) ?></td>
                                <td><?= htmlspecialchars($item['quantity']) ?></td>
                                <td><?= htmlspecialchars(number_format((float)($item['unit_price'] ?: 0), 2)) ?></td>
                                <td><?= htmlspecialchars(number_format((float)($item['total'] ?: 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="summary-box">
                    <div class="summary-row"><span>Subtotal</span><span><?= htmlspecialchars(number_format((float)($invoice['subtotal'] ?: 0), 2)) ?></span></div>
                    <div class="summary-row"><span>Tax (<?= htmlspecialchars(number_format((float)($invoice['tax_rate'] ?: 0), 2)) ?>%)</span><span><?= htmlspecialchars(number_format((float)($invoice['tax_amount'] ?: 0), 2)) ?></span></div>
                    <div class="summary-row"><span>Discount</span><span><?= htmlspecialchars(number_format((float)($invoice['discount'] ?: 0), 2)) ?></span></div>
                    <div class="summary-row total"><span>Total</span><span><?= htmlspecialchars(number_format((float)($invoice['total'] ?: 0), 2)) ?></span></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="invoice-actions">
            <div></div>
            <a href="index.php" class="button secondary">Done</a>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; }
            .topbar, .button, .invoice-actions { display: none !important; }
            .container { max-width: 100%; margin: 0; padding: 0; }
            .invoice-details { box-shadow: none; border: none; }
        }
    </style>
</body>
</html>
