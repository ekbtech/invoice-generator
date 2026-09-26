<?php
function getDbConnection() {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $dbName = 'invoice_generator';
    $port = 3307;

    $conn = new mysqli($host, $user, $pass, '', $port);

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    $conn->query("CREATE DATABASE IF NOT EXISTS `invoice_generator` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($dbName);

    $conn->query("
        CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) NOT NULL UNIQUE,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            company_name VARCHAR(255),
            invoice_date DATE NOT NULL,
            due_date DATE,
            notes TEXT,
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            tax_rate DECIMAL(5,2) DEFAULT 0.00,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            discount DECIMAL(10,2) DEFAULT 0.00,
            total DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(30) DEFAULT 'Draft',
            template VARCHAR(30) NOT NULL DEFAULT 'classic'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            description TEXT,
            quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $invoiceColumns = $conn->query("SHOW COLUMNS FROM invoices");
    $hasLayout = false;
    if ($invoiceColumns) {
        while ($column = $invoiceColumns->fetch_assoc()) {
            if ($column['Field'] === 'layout') {
                $hasLayout = true;
                break;
            }
        }
    }
    if (!$hasLayout) {
        $conn->query("ALTER TABLE invoices ADD COLUMN layout VARCHAR(30) NOT NULL DEFAULT 'clean'");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            company VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            address TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) DEFAULT 'EKBTECH',
            company_email VARCHAR(255) DEFAULT 'billing@northstar.com',
            company_phone VARCHAR(255) DEFAULT '+1 (415) 555-0148',
            company_address TEXT,
            currency_symbol VARCHAR(10) DEFAULT '$',
            tax_rate DECIMAL(10,2) DEFAULT 10.00,
            footer_note TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $settingCheck = $conn->query("SELECT id FROM settings WHERE id = 1 LIMIT 1");
    if ($settingCheck && $settingCheck->num_rows === 0) {
        $conn->query("INSERT INTO settings (id, company_name, company_email, company_phone, company_address, currency_symbol, tax_rate, footer_note) VALUES (1, 'EKBTECH', 'billing@northstar.com', '+1 (415) 555-0148', '245 Market Street, Suite 300, San Francisco, CA', '$', 10.00, 'Thank you for your business.')");
    }

    $checkUser = $conn->query("SELECT id FROM users WHERE email = 'admin@northstar.com' LIMIT 1");
    if ($checkUser && $checkUser->num_rows === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (full_name, email, password_hash) VALUES ('EKBTECH Admin', 'admin@northstar.com', '$hash')");
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2);
}
?>
