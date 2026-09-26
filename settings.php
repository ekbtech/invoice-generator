<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
$conn = getDbConnection();

$settings = [
    'company_name' => 'EKBTECH',
    'company_email' => 'billing@northstar.com',
    'company_phone' => '+1 (415) 555-0148',
    'company_address' => '245 Market Street, Suite 300, San Francisco, CA',
    'currency_symbol' => '$',
    'tax_rate' => '10.00',
    'footer_note' => 'Thank you for your business.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_email = trim($_POST['company_email'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $currency_symbol = trim($_POST['currency_symbol'] ?? '$');
    $tax_rate = trim($_POST['tax_rate'] ?? '10');
    $footer_note = trim($_POST['footer_note'] ?? '');

    $stmt = $conn->prepare('UPDATE settings SET company_name=?, company_email=?, company_phone=?, company_address=?, currency_symbol=?, tax_rate=?, footer_note=? WHERE id=1');
    $stmt->bind_param('sssssss', $company_name, $company_email, $company_phone, $company_address, $currency_symbol, $tax_rate, $footer_note);
    $stmt->execute();
    $stmt->close();

    $settings = [
        'company_name' => $company_name,
        'company_email' => $company_email,
        'company_phone' => $company_phone,
        'company_address' => $company_address,
        'currency_symbol' => $currency_symbol,
        'tax_rate' => $tax_rate,
        'footer_note' => $footer_note
    ];
}

$result = $conn->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $settings = [
        'company_name' => $row['company_name'],
        'company_email' => $row['company_email'],
        'company_phone' => $row['company_phone'],
        'company_address' => $row['company_address'],
        'currency_symbol' => $row['currency_symbol'],
        'tax_rate' => $row['tax_rate'],
        'footer_note' => $row['footer_note']
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings | EKBTECH Billing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">N</div>
                <h1>Company Settings</h1>
            </div>
            <div class="top-actions">
                <a href="index.php" class="button secondary">Dashboard</a>
                <a href="clients.php" class="button secondary">Clients</a>
                <a href="logout.php" class="button ghost">Logout</a>
            </div>
        </header>

        <section class="card" style="max-width:920px; margin:0 auto;">
            <div class="card-header">
                <h2>Brand & Billing Details</h2>
                <span class="card-subtitle">Configure your invoice identity</span>
            </div>

            <form method="POST" action="settings.php">
                <div class="form-grid">
                    <div class="full">
                        <label for="company_name">Company Name</label>
                        <input id="company_name" name="company_name" type="text" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
                    </div>

                    <div>
                        <label for="company_email">Company Email</label>
                        <input id="company_email" name="company_email" type="email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="company_phone">Company Phone</label>
                        <input id="company_phone" name="company_phone" type="text" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                    </div>

                    <div class="full">
                        <label for="company_address">Company Address</label>
                        <textarea id="company_address" name="company_address"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label for="currency_symbol">Currency Symbol</label>
                        <input id="currency_symbol" name="currency_symbol" type="text" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '$') ?>">
                    </div>

                    <div>
                        <label for="tax_rate">Default Tax Rate (%)</label>
                        <input id="tax_rate" name="tax_rate" type="number" step="0.01" value="<?= htmlspecialchars($settings['tax_rate'] ?? '10') ?>">
                    </div>

                    <div class="full">
                        <label for="footer_note">Footer Note</label>
                        <textarea id="footer_note" name="footer_note"><?= htmlspecialchars($settings['footer_note'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button class="button" type="submit">Save Settings</button>
                </div>
            </form>
        </section>
    </div>
</body>
</html>
