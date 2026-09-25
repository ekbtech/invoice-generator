<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$conn = getDbConnection();

$sql = "SELECT * FROM invoices ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);
$invoices = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EKBTECH Billing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">N</div>
                <h1>EKBTECH Billing</h1>
            </div>
            <div class="top-actions">
                <a href="clients.php" class="button secondary">Clients</a>
                <a href="settings.php" class="button secondary">Settings</a>
                <div class="export-menu">
                    <button class="button ghost" type="button" id="export-btn" aria-expanded="false">Export</button>
                    <div class="export-options" id="export-options" hidden>
                        <button type="button" id="export-word-btn">Word (.doc)</button>
                        <button type="button" id="export-excel-btn">Excel (.xls)</button>
                        <button type="button" id="export-pdf-btn">PDF</button>
                        <button type="button" id="export-print-btn">Print</button>
                    </div>
                </div>
                <a href="logout.php" class="button ghost">Logout</a>
                <a href="#invoice-form" class="button">Create Invoice</a>
            </div>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="notice success">
                Invoice saved successfully.
                <?php if (!empty($_GET['receipt_id'])): ?>
                    <a href="view_invoice.php?id=<?= (int)$_GET['receipt_id'] ?>">Open automatically created receipt</a>
                <?php endif; ?>
                <?php if (!empty($_GET['delivery_note_id'])): ?>
                    <a href="view_invoice.php?id=<?= (int)$_GET['delivery_note_id'] ?>">Open automatically created delivery note</a>
                <?php endif; ?>
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
            <div class="notice error">There was an issue saving the invoice.</div>
        <?php endif; ?>

        <div class="grid">
            <section class="card" id="invoice-form">
                <form action="save_invoice.php" method="POST">
                    <div class="form-grid">
                        <div class="full template-editor-heading">
                            <label for="template">Editable Template</label>
                            <select id="template" name="template" class="template-dropdown">
                                <option value="invoice" selected>Standard Invoice</option>
                                <option value="quote">Quotation</option>
                                <option value="receipt">Receipt</option>
                                <option value="fuel_order">Fuel Order</option>
                                <option value="estimate">Estimate</option>
                                <option value="proforma">Pro Forma Invoice</option>
                                <option value="delivery_note">Delivery Note</option>
                                <option value="purchase_order">Purchase Order</option>
                            </select>
                            <label for="layout">Template Layout</label>
                            <select id="layout" name="layout" class="template-dropdown">
                                <option value="clean">Clean Professional</option>
                                <option value="modern">Modern Accent</option>
                                <option value="minimal">Minimal Lines</option>
                                <option value="letter">Formal Letterhead</option>
                                <option value="executive">Executive</option>
                                <option value="boxed">Boxed Detail</option>
                                <option value="compact">Compact</option>
                                <option value="bold">Bold Header</option>
                                <option value="monochrome">Monochrome</option>
                                <option value="elegant">Elegant Serif</option>
                                <option value="air">Airy Spacing</option>
                                <option value="color">Color Block</option>
                                <option value="fuel_quote">Fuel Quotation</option>
                            </select>
                            <label class="logo-upload" for="company_logo">Company Logo
                                <input type="file" id="company_logo" accept="image/png,image/jpeg,image/webp">
                            </label>

                            <div class="template-preview-shell">
                                <div id="template-preview" class="template-preview preview-invoice"></div>
                                <div class="template-actions">
                                    <button type="button" class="button secondary" id="print-template-btn">Print</button>
                                    <button type="button" class="button secondary" id="pdf-template-btn">Download PDF</button>
                                </div>
                            </div>
                        </div>

                        <div class="full template-source-fields">
                            <label for="company_name">Your Company</label>
                            <input type="text" id="company_name" name="company_name" value="CLB ENERGY LIMITED" required>
                        </div>

                        <div class="template-source-fields">
                            <label for="invoice_number">Invoice #</label>
                            <input type="text" id="invoice_number" name="invoice_number" value="QTN-<?= date('YmdHis') ?>" required>
                        </div>

                        <div class="template-source-fields">
                            <label for="invoice_date">Invoice Date</label>
                            <input type="date" id="invoice_date" name="invoice_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="template-source-fields">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" id="customer_name" name="customer_name" placeholder="Customer full name" required>
                        </div>

                        <div class="template-source-fields">
                            <label for="customer_email">Customer Email</label>
                            <input type="email" id="customer_email" name="customer_email" placeholder="customer@example.com">
                        </div>

                        <div class="template-source-fields">
                            <label for="customer_phone">Customer Phone</label>
                            <input type="text" id="customer_phone" name="customer_phone" placeholder="+123456789">
                        </div>

                        <div class="template-source-fields">
                            <label for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="template-source-fields">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="Draft">Draft</option>
                                <option value="Sent">Sent</option>
                                <option value="Paid">Paid</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                        </div>

                        <div class="full template-source-fields">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" placeholder="Optional notes for the customer..."></textarea>
                        </div>
                    </div>

                    <div class="template-source-fields">
                    <h3 class="section-title">Line Items</h3>
                    <table class="items-table" id="items-table">
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
                            <tr>
                                <td><input type="text" name="item_name[]" placeholder="Service or item" required></td>
                                <td><input type="text" name="item_description[]" placeholder="Description"></td>
                                <td><input type="number" name="qty[]" value="1" min="1" step="0.01" class="qty" required></td>
                                <td><input type="number" name="unit_price[]" value="0" min="0" step="0.01" class="unit-price" required></td>
                                <td><input type="number" name="line_total[]" value="0" min="0" step="0.01" class="line-total" readonly></td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="margin-top: 18px;">
                        <button type="button" class="button secondary" id="add-item-btn">+ Add Item</button>
                    </div>
                    </div>

                    <div class="summary-box template-source-fields">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal-display">$0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>Tax Rate</span>
                            <span>
                                <input type="number" id="tax_rate" name="tax_rate" value="10" min="0" step="0.01">
                                %
                            </span>
                        </div>

                        <div class="summary-row">
                            <span>Discount</span>
                            <span>
                                <input type="number" id="discount" name="discount" value="0" min="0" step="0.01">
                            </span>
                        </div>

                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="grand-total">$0.00</span>
                        </div>

                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                        <input type="hidden" name="tax_amount" id="tax-amount-input" value="0">
                        <input type="hidden" name="total" id="total-input" value="0">
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="submit" class="button">Save Invoice</button>
                    </div>
                </form>
            </section>

        </div>
    </div>

    <div id="export-print-area" hidden></div>

    <script>
        const pdfLibrary = document.createElement('script');
        pdfLibrary.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        document.head.appendChild(pdfLibrary);
        function loadPdfScript(source) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = source;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        const pdfEngineReady = Promise.all([
            loadPdfScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'),
            loadPdfScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
        ]);

        const tableBody = document.querySelector('#items-table tbody');
        const addItemBtn = document.getElementById('add-item-btn');
        const taxRateInput = document.getElementById('tax_rate');
        const discountInput = document.getElementById('discount');
        const customColumns = [];
        const customColumnValues = {};
        let logoDataUrl = '';

        function calculateRow(row) {
            const qty = parseFloat(row.querySelector('.qty').value || 0);
            const price = parseFloat(row.querySelector('.unit-price').value || 0);
            const total = qty * price;
            row.querySelector('.line-total').value = total.toFixed(2);
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('#items-table tbody tr').forEach((row) => {
                calculateRow(row);
                subtotal += parseFloat(row.querySelector('.line-total').value || 0);
            });

            const taxRate = parseFloat(taxRateInput.value || 0);
            const discount = parseFloat(discountInput.value || 0);
            const taxAmount = subtotal * (taxRate / 100);
            const total = subtotal + taxAmount - discount;

            document.getElementById('subtotal-display').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('grand-total').textContent = '$' + total.toFixed(2);
            document.getElementById('subtotal-input').value = subtotal.toFixed(2);
            document.getElementById('tax-amount-input').value = taxAmount.toFixed(2);
            document.getElementById('total-input').value = total.toFixed(2);
        }

        tableBody.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', calculateTotals);
        });

        taxRateInput.addEventListener('input', calculateTotals);
        discountInput.addEventListener('input', calculateTotals);

        const templateSelect = document.getElementById('template');
        const layoutSelect = document.getElementById('layout');
        const templatePreview = document.getElementById('template-preview');

        function getInvoiceRows() {
            const rows = [];
            document.querySelectorAll('#items-table tbody tr').forEach((row) => {
                const name = row.querySelector('input[name="item_name[]"]')?.value || 'Service item';
                const desc = row.querySelector('input[name="item_description[]"]')?.value || '';
                const qty = row.querySelector('.qty')?.value || '1';
                const price = row.querySelector('.unit-price')?.value || '0';
                const total = row.querySelector('.line-total')?.value || '0';

                rows.push({ name, desc, qty, price, total });
            });
            return rows;
        }

        function formatMoney(value) {
            const num = Number(value || 0);
            return '$' + num.toFixed(2);
        }

        function customHeaders() {
            return customColumns.map((column, index) => `<th><span class="editable-value custom-header" contenteditable="true" data-custom-header-index="${index}">${column}</span><button type="button" class="cell-delete delete-preview-column" data-column-index="${index}" title="Delete column">×</button></th>`).join('');
        }

        function rowDelete(index) {
            return `<button type="button" class="cell-delete delete-preview-row" data-row-index="${index}" title="Delete row">×</button>`;
        }

        function logoMarkup() {
            return logoDataUrl ? `<img class="template-logo" src="${logoDataUrl}" alt="Company logo">` : '';
        }

        function customCells(rowIndex) {
            return customColumns.map((column, columnIndex) => {
                const key = `${columnIndex}-${rowIndex}`;
                const value = customColumnValues[key] || '';
                return `<td><span class="editable-value custom-cell" contenteditable="true" data-custom-key="${key}">${value}</span></td>`;
            }).join('');
        }

        function itemEditorControls() {
            return `<div class="template-item-controls"><button type="button" class="button secondary add-preview-row">+ Add Row</button><button type="button" class="button secondary add-preview-column">+ Add Column</button></div>`;
        }

        function bindEditablePreview() {
            templatePreview.querySelectorAll('.editable-value').forEach((element) => {
                element.contentEditable = 'true';
                element.title = 'Click to edit';
                element.addEventListener('focus', () => {
                    const sampleValues = [
                        'Your Company',
                        'CLB ENERGY LIMITED',
                        'Client Name',
                        'client@example.com',
                        '+000000000',
                        'Service item',
                        'Description',
                        'Customer'
                    ];
                    if (sampleValues.includes(element.innerText.trim()) || /^QTN-\d+$/.test(element.innerText.trim())) {
                        element.innerText = '';
                    }
                }, { once: true });
                element.addEventListener('input', () => {
                    const field = element.dataset.editField;
                    if (field) {
                        const input = document.getElementById(field);
                        if (input) input.value = element.innerText.trim();
                    }

                    const itemField = element.dataset.itemField;
                    const itemIndex = Number(element.dataset.itemIndex);
                    if (itemField && Number.isInteger(itemIndex)) {
                        const rows = document.querySelectorAll('#items-table tbody tr');
                        const row = rows[itemIndex];
                        if (row) {
                            const inputMap = {
                                name: 'input[name="item_name[]"]',
                                desc: 'input[name="item_description[]"]',
                                qty: '.qty',
                                price: '.unit-price'
                            };
                            const input = row.querySelector(inputMap[itemField]);
                            if (input) input.value = element.innerText.replace('$', '').trim();
                        }
                        calculateTotals();
                    }
                });
                element.addEventListener('blur', () => {
                    if (element.dataset.itemField) {
                        renderTemplatePreview();
                    }
                });
                element.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        element.blur();
                    }
                });
            });

            templatePreview.querySelectorAll('.custom-cell').forEach((element) => {
                element.addEventListener('input', () => {
                    customColumnValues[element.dataset.customKey] = element.innerText.trim();
                });
            });

            templatePreview.querySelectorAll('.custom-header').forEach((element) => {
                element.addEventListener('input', () => {
                    customColumns[Number(element.dataset.customHeaderIndex)] = element.innerText.trim() || `Column ${Number(element.dataset.customHeaderIndex) + 1}`;
                });
            });

            templatePreview.querySelectorAll('.delete-preview-row').forEach((button) => {
                button.addEventListener('click', () => {
                    const rows = document.querySelectorAll('#items-table tbody tr');
                    const row = rows[Number(button.dataset.rowIndex)];
                    if (row && rows.length > 1) {
                        row.remove();
                        calculateTotals();
                        renderTemplatePreview();
                    }
                });
            });

            templatePreview.querySelectorAll('.delete-preview-column').forEach((button) => {
                button.addEventListener('click', () => {
                    const columnIndex = Number(button.dataset.columnIndex);
                    customColumns.splice(columnIndex, 1);
                    Object.keys(customColumnValues).forEach((key) => {
                        const [storedColumn, rowIndex] = key.split('-').map(Number);
                        if (storedColumn === columnIndex) delete customColumnValues[key];
                        if (storedColumn > columnIndex) {
                            customColumnValues[`${storedColumn - 1}-${rowIndex}`] = customColumnValues[key];
                            delete customColumnValues[key];
                        }
                    });
                    renderTemplatePreview();
                });
            });

            templatePreview.querySelectorAll('.add-preview-row').forEach((button) => {
                button.addEventListener('click', () => addItemBtn.click());
            });

            templatePreview.querySelectorAll('.add-preview-column').forEach((button) => {
                button.addEventListener('click', () => {
                    customColumns.push(`Column ${customColumns.length + 1}`);
                    renderTemplatePreview();
                });
            });
        }

        function renderTemplatePreview() {
            const selected = templateSelect.value;
            const company = document.getElementById('company_name').value || 'Your Company';
            const number = document.getElementById('invoice_number').value || 'INV-0001';
            const customer = document.getElementById('customer_name').value || 'Client Name';
            const email = document.getElementById('customer_email').value || 'client@example.com';
            const phone = document.getElementById('customer_phone').value || '+000000000';
            const date = document.getElementById('invoice_date').value || '2026-09-25';
            const due = document.getElementById('due_date').value || '2026-10-02';
            const subtotal = document.getElementById('subtotal-input').value || '0';
            const tax = document.getElementById('tax-amount-input').value || '0';
            const taxRate = document.getElementById('tax_rate').value || '0';
            const total = document.getElementById('total-input').value || '0';
            const rows = getInvoiceRows();
            const documentLabels = {
                invoice: 'INVOICE',
                estimate: 'ESTIMATE',
                proforma: 'PRO FORMA INVOICE',
                delivery_note: 'DELIVERY NOTE',
                purchase_order: 'PURCHASE ORDER'
            };
            const documentLabel = documentLabels[selected] || 'INVOICE';
            const isFuelQuotation = selected === 'quote' && layoutSelect.value === 'fuel_quote';

            const invoiceMarkup = `
                <div class="preview-paper preview-invoice-layout">
                    <div class="preview-header-row">
                        <div>
                            ${logoMarkup()}<div class="document-type-label">${documentLabel}</div><div class="preview-company editable-value" data-edit-field="company_name">${company}</div>
                            <small class="editable-value" data-edit-field="customer_email">${email}</small>
                        </div>
                        <div class="preview-right">
                            <strong class="editable-value" data-edit-field="invoice_number">${number}</strong>
                            <small class="editable-value" data-edit-field="invoice_date">${date}</small>
                        </div>
                    </div>
                    <div class="preview-customer-box">
                        <span>Bill to</span>
                        <strong class="editable-value" data-edit-field="customer_name">${customer}</strong>
                        <small class="editable-value" data-edit-field="customer_phone">${phone}</small>
                    </div>
                    <table class="preview-table">
                        <thead><tr><th>Item</th><th>Qty</th><th>Price</th>${customHeaders()}<th>Total</th></tr></thead>
                        <tbody>
                            ${rows.map((row, index) => `<tr><td><span class="editable-value" data-item-field="name" data-item-index="${index}">${row.name}</span><br><small class="editable-value" data-item-field="desc" data-item-index="${index}">${row.desc}</small></td><td class="editable-value" data-item-field="qty" data-item-index="${index}">${row.qty}</td><td class="editable-value" data-item-field="price" data-item-index="${index}">${formatMoney(row.price)}</td>${customCells(index)}<td>${formatMoney(row.total)} ${rowDelete(index)}</td></tr>`).join('')}
                        </tbody>
                    </table>
                    ${itemEditorControls()}
                    <div class="preview-total-box">
                        <div><span>Subtotal</span><strong>${formatMoney(subtotal)}</strong></div>
                        <div><span>Tax (${taxRate}%)</span><strong>${formatMoney(tax)}</strong></div>
                        <div class="grand"><span>Total</span><strong>${formatMoney(total)}</strong></div>
                    </div>
                </div>
            `;

            const quoteMarkup = `
                <div class="preview-paper preview-quote-layout">
                    <div class="quote-topbar">
                        <div>
                            ${logoMarkup()}<div class="quote-label">${isFuelQuotation ? 'QUOTATION FOR DIESEL FUEL' : 'QUOTATION'}</div>
                            <strong class="editable-value" data-edit-field="company_name">${company}</strong>
                            <small class="editable-value" data-edit-field="customer_email">${email}</small>
                            <small class="editable-value" data-edit-field="customer_phone">${phone}</small>
                        </div>
                        <div class="quote-document-meta">
                            <strong class="editable-value" data-edit-field="invoice_number">${number}</strong>
                            <span class="editable-value" data-edit-field="invoice_date">${date}</span>
                        </div>
                    </div>
                    <div class="quote-parties">
                        <div>
                            <span>FROM</span>
                            <strong class="editable-value" data-edit-field="company_name">${company}</strong>
                            <small class="editable-value" data-edit-field="customer_email">${email}</small>
                            <small class="editable-value" data-edit-field="customer_phone">${phone}</small>
                        </div>
                        <div>
                            <span>BILL TO</span>
                            <strong class="editable-value" data-edit-field="customer_name">${customer}</strong>
                            <small class="editable-value" data-edit-field="customer_email">${email}</small>
                            <small class="editable-value" data-edit-field="customer_phone">${phone}</small>
                        </div>
                    </div>
                    ${isFuelQuotation ? '<div class="fuel-reference"><p>Dear Sir,</p><p><strong>RE: QUOTATION FOR DIESEL FUEL</strong></p><p>Kindly find our quotation below.</p></div>' : ''}
                    <table class="preview-table quote-table">
                        <thead><tr><th>Description</th><th>${isFuelQuotation ? 'Quantity/unit' : 'Rate'}</th><th>${isFuelQuotation ? 'Unit Price' : 'Qty'}</th>${customHeaders()}${isFuelQuotation ? `<th>Tax</th>` : ''}<th>Amount</th></tr></thead>
                        <tbody>
                            ${rows.map((row, index) => `<tr><td><strong class="editable-value" data-item-field="name" data-item-index="${index}">${row.name}</strong><br><small class="editable-value" data-item-field="desc" data-item-index="${index}">${row.desc}</small></td><td class="editable-value" data-item-field="${isFuelQuotation ? 'qty' : 'price'}" data-item-index="${index}">${isFuelQuotation ? row.qty : formatMoney(row.price)}</td><td class="editable-value" data-item-field="${isFuelQuotation ? 'price' : 'qty'}" data-item-index="${index}">${isFuelQuotation ? formatMoney(row.price) : row.qty}</td>${customCells(index)}${isFuelQuotation ? `<td>${taxRate}%</td>` : ''}<td>${formatMoney(row.total)} ${rowDelete(index)}</td></tr>`).join('')}
                        </tbody>
                    </table>
                    ${itemEditorControls()}
                    <div class="preview-total-box quote-total">
                        <div><span>Subtotal</span><strong>${formatMoney(subtotal)}</strong></div>
                        <div><span>Tax (${taxRate}%)</span><strong>${formatMoney(tax)}</strong></div>
                        <div class="grand"><span>Total</span><strong>${formatMoney(total)}</strong></div>
                        <div class="balance"><span>Balance Due</span><strong>${formatMoney(total)}</strong></div>
                    </div>
                </div>
            `;

            const receiptMarkup = `
                <div class="preview-paper preview-receipt-layout">
                    ${logoMarkup()}<div class="receipt-header editable-value" data-edit-field="company_name">${company}</div>
                    <div class="receipt-meta">
                        <span>Receipt # <span class="editable-value" data-edit-field="invoice_number">${number}</span></span>
                        <span class="editable-value" data-edit-field="invoice_date">${date}</span>
                    </div>
                    <div class="receipt-customer">
                        <strong class="editable-value" data-edit-field="customer_name">${customer}</strong><br>
                        <small class="editable-value" data-edit-field="customer_email">${email}</small>
                    </div>
                    <table class="preview-table receipt-items">
                        <thead><tr><th>Item</th><th>Qty</th><th>Amount</th>${customHeaders()}</tr></thead>
                        <tbody>${rows.map((row, index) => `<tr><td class="editable-value" data-item-field="name" data-item-index="${index}">${row.name}</td><td class="editable-value" data-item-field="qty" data-item-index="${index}">${row.qty}</td><td>${formatMoney(row.total)} ${rowDelete(index)}</td>${customCells(index)}</tr>`).join('')}</tbody>
                    </table>
                    ${itemEditorControls()}
                    <div class="preview-total-box receipt-total">
                        <div><span>Subtotal</span><strong>${formatMoney(subtotal)}</strong></div>
                        <div><span>Tax (${taxRate}%)</span><strong>${formatMoney(tax)}</strong></div>
                        <div class="grand"><span>Total Paid</span><strong>${formatMoney(total)}</strong></div>
                    </div>
                </div>
            `;

            const fuelMarkup = `
                <div class="preview-paper preview-fuel-layout">
                    <div class="fuel-top">
                        ${logoMarkup()}<div class="fuel-brand">CB ENERGY</div>
                        <div class="fuel-meta"><strong>${company}</strong><small>${date}</small></div>
                    </div>
                    <div class="fuel-header">FUEL ORDER</div>
                    <div class="fuel-info">
                        <div><span>Customer</span><strong class="editable-value" data-edit-field="customer_name">${customer}</strong></div>
                        <div><span>Order #</span><strong class="editable-value" data-edit-field="invoice_number">${number}</strong></div>
                        <div><span>Due</span><strong class="editable-value" data-edit-field="due_date">${due}</strong></div>
                    </div>
                    <table class="preview-table fuel-table">
                        <thead><tr><th>Fuel</th><th>Qty</th><th>Rate</th>${customHeaders()}<th>Total</th></tr></thead>
                        <tbody>
                            ${rows.map((row, index) => `<tr><td class="editable-value" data-item-field="name" data-item-index="${index}">${row.name}</td><td class="editable-value" data-item-field="qty" data-item-index="${index}">${row.qty}</td><td class="editable-value" data-item-field="price" data-item-index="${index}">${formatMoney(row.price)}</td>${customCells(index)}<td>${formatMoney(row.total)} ${rowDelete(index)}</td></tr>`).join('')}
                        </tbody>
                    </table>
                    ${itemEditorControls()}
                    <div class="preview-total-box fuel-total">
                        <div><span>Sub-total</span><strong>${formatMoney(subtotal)}</strong></div>
                        <div><span>Tax (${taxRate}%)</span><strong>${formatMoney(tax)}</strong></div>
                        <div class="grand"><span>Total</span><strong>${formatMoney(total)}</strong></div>
                    </div>
                </div>
            `;

            const layoutMap = {
                invoice: invoiceMarkup,
                quote: quoteMarkup,
                receipt: receiptMarkup,
                fuel_order: fuelMarkup,
                estimate: invoiceMarkup,
                proforma: invoiceMarkup,
                delivery_note: invoiceMarkup,
                purchase_order: invoiceMarkup
            };

            templatePreview.innerHTML = layoutMap[selected] || invoiceMarkup;
            templatePreview.className = `template-preview preview-${selected} layout-${layoutSelect.value}`;
            bindEditablePreview();
        }

        templateSelect.addEventListener('change', renderTemplatePreview);
        layoutSelect.addEventListener('change', renderTemplatePreview);
        document.getElementById('company_logo').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                logoDataUrl = reader.result;
                renderTemplatePreview();
            });
            reader.readAsDataURL(file);
        });
        function deliveryNoteMarkup() {
            if (templateSelect.value !== 'invoice') return '';
            const rows = getInvoiceRows();
            const company = document.getElementById('company_name').value || 'Your Company';
            const customer = document.getElementById('customer_name').value || 'Customer';
            const number = document.getElementById('invoice_number').value || 'INV-0001';
            const date = document.getElementById('invoice_date').value || '';
            return `<section class="export-delivery-note"><h1>DELIVERY NOTE</h1><p><strong>${company}</strong><br>Customer: ${customer}<br>Reference: ${number}<br>Date: ${date}</p><table><thead><tr><th>Item</th><th>Description</th><th>Quantity</th></tr></thead><tbody>${rows.map(row => `<tr><td>${row.name}</td><td>${row.desc}</td><td>${row.qty}</td></tr>`).join('')}</tbody></table></section>`;
        }

        function exportablePreviewHtml() {
            const preview = document.querySelector('#template-preview .preview-paper');
            if (!preview) return '';
            const clone = preview.cloneNode(true);
            clone.querySelectorAll('.template-item-controls, .cell-delete').forEach((element) => element.remove());
            return clone.outerHTML;
        }

        function printCurrentDocuments() {
            const preview = document.querySelector('#template-preview .preview-paper');
            const printArea = document.getElementById('export-print-area');
            if (!preview) return;
            printArea.innerHTML = `${exportablePreviewHtml()}${deliveryNoteMarkup()}`;
            printArea.hidden = false;
            document.body.classList.add('exporting-documents');
            window.addEventListener('afterprint', () => {
                document.body.classList.remove('exporting-documents');
                printArea.hidden = true;
                printArea.innerHTML = '';
            }, { once: true });
            window.print();
        }

        document.getElementById('print-template-btn').addEventListener('click', printCurrentDocuments);
        document.getElementById('pdf-template-btn').addEventListener('click', downloadPdf);
        const exportButton = document.getElementById('export-btn');
        const exportOptions = document.getElementById('export-options');

        function downloadFile(filename, content, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        }

        function getLoadedStyles() {
            return Array.from(document.styleSheets).map((sheet) => {
                try {
                    return Array.from(sheet.cssRules).map((rule) => rule.cssText).join('\n');
                } catch (error) {
                    return '';
                }
            }).join('\n');
        }

        function exportDocumentHtml(content, title) {
            return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>${getLoadedStyles()} body{background:#fff!important}.template-source-fields,.template-actions,.template-item-controls,.cell-delete,.export-menu,.topbar{display:none!important}.preview-paper{box-shadow:none!important;margin:0 auto!important}</style></head><body><main class="export-document">${content}</main></body></html>`;
        }

        function exportStyledHtml(includeDeliveryNote = false) {
            const company = document.getElementById('company_name').value || 'Your Company';
            const number = document.getElementById('invoice_number').value || 'INV-0001';
            const styledInvoice = exportablePreviewHtml();
            const deliveryNote = includeDeliveryNote && templateSelect.value === 'invoice' ? deliveryNoteMarkup() : '';
            return exportDocumentHtml(`${styledInvoice}${deliveryNote}`, `${company} ${number}`);
        }

        exportButton.addEventListener('click', () => {
            exportOptions.hidden = !exportOptions.hidden;
            exportButton.setAttribute('aria-expanded', String(!exportOptions.hidden));
        });
        document.getElementById('export-word-btn').addEventListener('click', () => {
            const number = document.getElementById('invoice_number').value || 'invoice';
            downloadFile(`${number}-invoice.doc`, exportStyledHtml(), 'application/msword');
            if (templateSelect.value === 'invoice') {
                setTimeout(() => downloadFile(`${number}-delivery-note.doc`, exportStyledHtml(false).replace('</main>', `${deliveryNoteMarkup()}</main>`), 'application/msword'), 300);
            }
            exportOptions.hidden = true;
        });
        document.getElementById('export-excel-btn').addEventListener('click', () => {
            const number = document.getElementById('invoice_number').value || 'invoice';
            downloadFile(`${number}-invoice.xls`, exportStyledHtml(), 'application/vnd.ms-excel');
            if (templateSelect.value === 'invoice') {
                setTimeout(() => downloadFile(`${number}-delivery-note.xls`, exportStyledHtml(false).replace('</main>', `${deliveryNoteMarkup()}</main>`), 'application/vnd.ms-excel'), 300);
            }
            exportOptions.hidden = true;
        });
        async function downloadPdfFile(content, filename) {
            const pdfArea = document.createElement('div');
            pdfArea.className = 'pdf-export-area';
            pdfArea.innerHTML = content;
            pdfArea.style.width = '794px';
            pdfArea.style.height = '1123px';
            pdfArea.style.display = 'block';
            pdfArea.style.top = `${window.scrollY}px`;
            document.body.appendChild(pdfArea);
            try {
                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                await pdfEngineReady;
                const canvas = await window.html2canvas(pdfArea, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    scrollX: 0,
                    scrollY: -window.scrollY,
                    width: 794,
                    height: 1123,
                    windowWidth: 794,
                    windowHeight: 1123
                });
                if (!canvas || canvas.width === 0 || canvas.height === 0) throw new Error('The template canvas is empty.');
                const PdfDocument = window.jspdf.jsPDF;
                const pdf = new PdfDocument({ unit: 'mm', format: 'a4', orientation: 'portrait' });
                const image = canvas.toDataURL('image/jpeg', 0.98);
                const pageWidth = 210;
                const pageHeight = 297;
                const imageHeight = canvas.height * pageWidth / canvas.width;
                let offset = 0;
                while (offset < imageHeight) {
                    if (offset > 0) pdf.addPage();
                    pdf.addImage(image, 'JPEG', 0, -offset, pageWidth, imageHeight);
                    offset += pageHeight;
                }
                pdf.save(filename);
            } finally {
                pdfArea.remove();
            }
        }

        async function downloadPdf() {
            const preview = document.querySelector('#template-preview .preview-paper');
            const number = document.getElementById('invoice_number').value || 'invoice';
            if (!preview) return;
            if (!window.html2pdf) {
                window.alert('PDF converter is still loading. Please try again.');
                return;
            }
            await downloadPdfFile(exportablePreviewHtml(), `${number}-invoice.pdf`);
            if (templateSelect.value === 'invoice') {
                await downloadPdfFile(deliveryNoteMarkup(), `${number}-delivery-note.pdf`);
            }
        }
        document.getElementById('export-pdf-btn').addEventListener('click', downloadPdf);
        document.getElementById('export-print-btn').addEventListener('click', printCurrentDocuments);
        document.getElementById('company_name').addEventListener('input', renderTemplatePreview);
        document.getElementById('invoice_number').addEventListener('input', renderTemplatePreview);
        document.getElementById('customer_name').addEventListener('input', renderTemplatePreview);
        document.getElementById('customer_email').addEventListener('input', renderTemplatePreview);
        document.getElementById('customer_phone').addEventListener('input', renderTemplatePreview);
        document.getElementById('invoice_date').addEventListener('input', renderTemplatePreview);
        document.getElementById('due_date').addEventListener('input', renderTemplatePreview);

        tableBody.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', () => {
                calculateTotals();
                renderTemplatePreview();
            });
        });

        taxRateInput.addEventListener('input', () => {
            calculateTotals();
            renderTemplatePreview();
        });
        discountInput.addEventListener('input', () => {
            calculateTotals();
            renderTemplatePreview();
        });

        addItemBtn.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="item_name[]" placeholder="New item" required></td>
                <td><input type="text" name="item_description[]" placeholder="Description"></td>
                <td><input type="number" name="qty[]" value="1" min="1" step="0.01" class="qty" required></td>
                <td><input type="number" name="unit_price[]" value="0" min="0" step="0.01" class="unit-price" required></td>
                <td><input type="number" name="line_total[]" value="0" min="0" step="0.01" class="line-total" readonly></td>
            `;
            tableBody.appendChild(row);
            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', () => {
                    calculateTotals();
                    renderTemplatePreview();
                });
            });
            calculateTotals();
            renderTemplatePreview();
        });

        calculateTotals();
        renderTemplatePreview();
    </script>
</body>
</html>
