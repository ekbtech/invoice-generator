<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?status=error');
    exit;
}

$conn = getDbConnection();

$invoiceNumber = trim($_POST['invoice_number'] ?? '');
$customerName = trim($_POST['customer_name'] ?? '');
$customerEmail = trim($_POST['customer_email'] ?? '');
$customerPhone = trim($_POST['customer_phone'] ?? '');
$companyName = trim($_POST['company_name'] ?? '');
$invoiceDate = trim($_POST['invoice_date'] ?? '');
$dueDate = trim($_POST['due_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$status = trim($_POST['status'] ?? 'Draft');
$template = in_array(trim($_POST['template'] ?? 'invoice'), ['invoice', 'quote', 'receipt', 'fuel_order', 'estimate', 'proforma', 'delivery_note', 'purchase_order'], true)
    ? trim($_POST['template'])
    : 'invoice';
$layout = in_array(trim($_POST['layout'] ?? 'clean'), ['clean', 'modern', 'minimal', 'letter', 'executive', 'boxed', 'compact', 'bold', 'monochrome', 'elegant', 'air', 'color', 'fuel_quote'], true)
    ? trim($_POST['layout'])
    : 'clean';
$subtotal = (float)($_POST['subtotal'] ?? 0);
$taxAmount = (float)($_POST['tax_amount'] ?? 0);
$total = (float)($_POST['total'] ?? 0);
$discount = (float)($_POST['discount'] ?? 0);
$taxRate = (float)($_POST['tax_rate'] ?? 0);
$receiptId = 0;
$deliveryNoteId = 0;

if (empty($invoiceNumber) || empty($customerName) || empty($invoiceDate)) {
    header('Location: index.php?status=error');
    exit;
}

$stmt = $conn->prepare(
    'INSERT INTO invoices (invoice_number, customer_name, customer_email, customer_phone, company_name, invoice_date, due_date, notes, subtotal, tax_rate, tax_amount, discount, total, status, template, layout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->bind_param(
    'ssssssssdddddsss',
    $invoiceNumber,
    $customerName,
    $customerEmail,
    $customerPhone,
    $companyName,
    $invoiceDate,
    $dueDate,
    $notes,
    $subtotal,
    $taxRate,
    $taxAmount,
    $discount,
    $total,
    $status,
    $template,
    $layout
);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: index.php?status=error');
    exit;
}

$invoiceId = $stmt->insert_id;
$stmt->close();

$itemNames = $_POST['item_name'] ?? [];
$itemDescriptions = $_POST['item_description'] ?? [];
$itemQuantities = $_POST['qty'] ?? [];
$itemPrices = $_POST['unit_price'] ?? [];

$itemInsert = $conn->prepare(
    'INSERT INTO invoice_items (invoice_id, item_name, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)'
);

for ($i = 0; $i < count($itemNames); $i++) {
    $name = trim($itemNames[$i] ?? '');
    if ($name === '') {
        continue;
    }

    $description = trim($itemDescriptions[$i] ?? '');
    $qty = (float)($itemQuantities[$i] ?? 0);
    $price = (float)($itemPrices[$i] ?? 0);
    $lineTotal = $qty * $price;

    $itemInsert->bind_param('issddd', $invoiceId, $name, $description, $qty, $price, $lineTotal);
    $itemInsert->execute();
}

$itemInsert->close();

if ($template === 'invoice') {
    $deliveryNoteNumber = 'DN-' . date('YmdHis') . '-' . random_int(100, 999);
    $deliveryNoteNotes = trim($notes . ($notes !== '' ? "\n" : '') . 'Automatically generated from invoice ' . $invoiceNumber . '.');
    $deliveryNoteStatus = 'Draft';
    $deliveryNoteTemplate = 'delivery_note';
    $zero = 0.0;

    $deliveryNoteStmt = $conn->prepare(
        'INSERT INTO invoices (invoice_number, customer_name, customer_email, customer_phone, company_name, invoice_date, due_date, notes, subtotal, tax_rate, tax_amount, discount, total, status, template, layout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $deliveryNoteStmt->bind_param(
        'ssssssssdddddsss',
        $deliveryNoteNumber,
        $customerName,
        $customerEmail,
        $customerPhone,
        $companyName,
        $invoiceDate,
        $dueDate,
        $deliveryNoteNotes,
        $zero,
        $zero,
        $zero,
        $zero,
        $zero,
        $deliveryNoteStatus,
        $deliveryNoteTemplate,
        $layout
    );
    $deliveryNoteStmt->execute();
    $deliveryNoteId = $deliveryNoteStmt->insert_id;
    $deliveryNoteStmt->close();

    $copyDeliveryItems = $conn->prepare(
        'INSERT INTO invoice_items (invoice_id, item_name, description, quantity, unit_price, total) SELECT ?, item_name, description, quantity, 0, 0 FROM invoice_items WHERE invoice_id = ?'
    );
    $copyDeliveryItems->bind_param('ii', $deliveryNoteId, $invoiceId);
    $copyDeliveryItems->execute();
    $copyDeliveryItems->close();
}

if ($template === 'delivery_note') {
    $receiptNumber = 'RCT-' . date('YmdHis') . '-' . random_int(100, 999);
    $receiptNotes = trim($notes . ($notes !== '' ? "\n" : '') . 'Automatically generated from delivery note ' . $invoiceNumber . '.');
    $receiptTemplate = 'receipt';
    $receiptStatus = 'Draft';

    $receiptStmt = $conn->prepare(
        'INSERT INTO invoices (invoice_number, customer_name, customer_email, customer_phone, company_name, invoice_date, due_date, notes, subtotal, tax_rate, tax_amount, discount, total, status, template, layout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $receiptStmt->bind_param(
        'ssssssssdddddsss',
        $receiptNumber,
        $customerName,
        $customerEmail,
        $customerPhone,
        $companyName,
        $invoiceDate,
        $dueDate,
        $receiptNotes,
        $subtotal,
        $taxRate,
        $taxAmount,
        $discount,
        $total,
        $receiptStatus,
        $receiptTemplate,
        $layout
    );
    $receiptStmt->execute();
    $receiptId = $receiptStmt->insert_id;
    $receiptStmt->close();

    $copyItems = $conn->prepare(
        'INSERT INTO invoice_items (invoice_id, item_name, description, quantity, unit_price, total) SELECT ?, item_name, description, quantity, unit_price, total FROM invoice_items WHERE invoice_id = ?'
    );
    $copyItems->bind_param('ii', $receiptId, $invoiceId);
    $copyItems->execute();
    $copyItems->close();
}

$conn->close();

$redirect = 'index.php?status=success';
if ($receiptId > 0) {
    $redirect .= '&receipt_id=' . $receiptId;
}
if ($deliveryNoteId > 0) {
    $redirect .= '&delivery_note_id=' . $deliveryNoteId;
}
header('Location: ' . $redirect);
exit;
?>
