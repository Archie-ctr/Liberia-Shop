<?php
// Invoice.php (Completely Safe Version)
session_start();
include("../../db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Function to check if column exists
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '$column'");
    return ($result && mysqli_num_rows($result) > 0);
}

// Function to check if table exists
function tableExists($con, $table) {
    $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return ($result && mysqli_num_rows($result) > 0);
}

// Get safe date column for ordering
function getSafeDateColumn($con) {
    if (tableExists($con, 'orders_info')) {
        if (columnExists($con, 'orders_info', 'order_date')) {
            return 'o.order_date';
        } elseif (columnExists($con, 'orders_info', 'created_at')) {
            return 'o.created_at';
        }
    }
    // Fallback - use current timestamp
    return 'CURRENT_TIMESTAMP';
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $invoice_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'generate':
            generateInvoice($con);
            break;
        case 'view':
            viewInvoice($con, $invoice_id);
            exit;
        case 'download':
            downloadInvoice($con, $invoice_id);
            exit;
        case 'send':
            sendInvoice($con, $invoice_id);
            break;
        case 'delete':
            deleteInvoice($con, $invoice_id);
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_invoice'])) {
        createInvoice($con);
    } elseif (isset($_POST['update_invoice'])) {
        updateInvoice($con);
    }
}

include "sidenav.php";
include "topheader.php";

function generateInvoice($con) {
    $order_id = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$order_id || $order_id < 1) {
        $_SESSION['error'] = "A valid order ID is required.";
        return;
    }
    
    // Check if invoices table exists
    if (!tableExists($con, 'invoices')) {
        $_SESSION['error'] = "Invoices table not found. Please run the database fix script first.";
        return;
    }
    
    // Check if invoice already exists
    $check_statement = mysqli_prepare($con, "SELECT invoice_id FROM invoices WHERE order_id = ? LIMIT 1");
    if (!$check_statement) {
        $_SESSION['error'] = "Unable to check for an existing invoice: " . mysqli_error($con);
        return;
    }
    mysqli_stmt_bind_param($check_statement, 'i', $order_id);
    if (!mysqli_stmt_execute($check_statement)) {
        $_SESSION['error'] = "Unable to check for an existing invoice: " . mysqli_stmt_error($check_statement);
        return;
    }
    $result = mysqli_stmt_get_result($check_statement);
    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Invoice already exists for this order.";
        return;
    }
    
    // Get order details
    $order_statement = mysqli_prepare(
        $con,
        "SELECT o.total_amt AS total_amount
         FROM orders_info o
         WHERE o.order_id = ?
         LIMIT 1"
    );
    if (!$order_statement) {
        $_SESSION['error'] = "Unable to load order details: " . mysqli_error($con);
        return;
    }
    mysqli_stmt_bind_param($order_statement, 'i', $order_id);
    if (!mysqli_stmt_execute($order_statement)) {
        $_SESSION['error'] = "Unable to load order details: " . mysqli_stmt_error($order_statement);
        return;
    }
    $order_result = mysqli_stmt_get_result($order_statement);
    if (!$order_result || mysqli_num_rows($order_result) === 0) {
        $_SESSION['error'] = "Order not found.";
        return;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    $order_total = (float)$order['total_amount'];
    
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
    
    // Insert invoice
    $insert_statement = mysqli_prepare(
        $con,
        "INSERT INTO invoices (order_id, invoice_number, invoice_date, due_date,
         subtotal, tax_amount, shipping_cost, total_amount, invoice_status)
         VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, 0.00, 0.00, ?, 'draft')"
    );
    if (!$insert_statement) {
        $_SESSION['error'] = "Unable to prepare invoice: " . mysqli_error($con);
        return;
    }
    mysqli_stmt_bind_param($insert_statement, 'isdd', $order_id, $invoice_number, $order_total, $order_total);
    if (mysqli_stmt_execute($insert_statement)) {
        $_SESSION['success'] = "Invoice generated successfully!";
    } else {
        $_SESSION['error'] = "Error generating invoice: " . mysqli_stmt_error($insert_statement);
    }
}

function viewInvoice($con, $invoice_id) {
    if (!tableExists($con, 'invoices')) {
        echo "Invoices table not found. Please run the database fix script first.";
        return;
    }
    
    $query = "SELECT i.*, o.*, u.first_name, u.last_name, u.email, u.phone, u.address1, u.address2
             FROM invoices i
             JOIN orders_info o ON i.order_id = o.order_id
             JOIN user_info u ON o.user_id = u.user_id
             WHERE i.invoice_id = '$invoice_id'";
    $result = mysqli_query($con, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "Invoice not found.";
        return;
    }
    
    $invoice = mysqli_fetch_assoc($result);
    
    // Get order items if table exists
    $items_result = null;
    if (tableExists($con, 'order_items')) {
        $items_query = "SELECT * FROM order_items WHERE order_id = '{$invoice['order_id']}'";
        $items_result = mysqli_query($con, $items_query);
    }
    
    // HTML for invoice view
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invoice <?php echo $invoice['invoice_number']; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .invoice-container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
            .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .company-info { flex: 1; }
            .invoice-info { text-align: right; }
            .customer-info { margin-bottom: 30px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .table th { background-color: #f8f9fa; }
            .totals { text-align: right; margin-bottom: 30px; }
            .footer { text-align: center; margin-top: 50px; color: #666; }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <div class="company-info">
                    <h2>TechShop Liberia</h2>
                    <p>Monrovia, Liberia</p>
                    <p>Email: info@techshopliberia.com</p>
                    <p>Phone: +231 123 456 789</p>
                </div>
                <div class="invoice-info">
                    <h1>INVOICE</h1>
                    <p><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></p>
                    <p><strong>Date:</strong> <?php echo $invoice['invoice_date']; ?></p>
                    <p><strong>Due Date:</strong> <?php echo $invoice['due_date']; ?></p>
                </div>
            </div>
            
            <div class="customer-info">
                <h3>Bill To:</h3>
                <p><?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?></p>
                <p><?php echo $invoice['email']; ?></p>
                <p><?php echo $invoice['phone']; ?></p>
                <p><?php echo $invoice['address1']; ?></p>
                <p><?php echo $invoice['address2']; ?></p>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($items_result && mysqli_num_rows($items_result) > 0) {
                        while($item = mysqli_fetch_assoc($items_result)): 
                    ?>
                    <tr>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="4" style="text-align: center;">Order items not available</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="totals">
                <p><strong>Subtotal: $<?php echo number_format($invoice['subtotal'], 2); ?></strong></p>
                <p><strong>Tax: $<?php echo number_format($invoice['tax_amount'], 2); ?></strong></p>
                <p><strong>Shipping: $<?php echo number_format($invoice['shipping_cost'], 2); ?></strong></p>
                <p><strong>Total: $<?php echo number_format($invoice['total_amount'], 2); ?></strong></p>
            </div>
            
            <?php if (!empty($invoice['notes'])): ?>
            <div class="notes">
                <h4>Notes:</h4>
                <p><?php echo $invoice['notes']; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>TechShop Liberia - Monrovia, Liberia</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function downloadInvoice($con, $invoice_id) {
    $invoice_id = filter_var($invoice_id, FILTER_VALIDATE_INT);
    if (!$invoice_id || $invoice_id < 1) {
        http_response_code(400);
        exit('A valid invoice ID is required.');
    }

    $invoice_statement = mysqli_prepare(
        $con,
        "SELECT i.invoice_number, i.invoice_date, i.due_date, i.subtotal,
                i.tax_amount, i.shipping_cost, i.total_amount, i.invoice_status, i.notes,
                o.order_id, o.payment_method, o.f_name, o.email AS order_email,
                o.address, o.city, o.state, o.zip, o.admin_notes AS order_notes,
                COALESCE(NULLIF(CONCAT_WS(' ', u.first_name, u.last_name), ''), o.f_name) AS customer_name,
                COALESCE(NULLIF(u.email, ''), o.email) AS customer_email,
                u.phone, u.mobile, u.address1, u.address2
         FROM invoices i
         JOIN orders_info o ON o.order_id = i.order_id
         LEFT JOIN user_info u ON u.user_id = o.user_id
         WHERE i.invoice_id = ?
         LIMIT 1"
    );
    if (!$invoice_statement) {
        error_log('Unable to prepare invoice PDF lookup: ' . mysqli_error($con));
        http_response_code(500);
        exit('Unable to generate this invoice PDF.');
    }
    mysqli_stmt_bind_param($invoice_statement, 'i', $invoice_id);
    if (!mysqli_stmt_execute($invoice_statement)) {
        error_log('Unable to load invoice for PDF: ' . mysqli_stmt_error($invoice_statement));
        http_response_code(500);
        exit('Unable to generate this invoice PDF.');
    }
    $invoice_result = mysqli_stmt_get_result($invoice_statement);
    $invoice = $invoice_result ? mysqli_fetch_assoc($invoice_result) : null;
    if (!$invoice) {
        http_response_code(404);
        exit('Invoice not found.');
    }

    $items_statement = mysqli_prepare(
        $con,
        'SELECT p.product_title, op.qty, op.amt
         FROM order_products op
         JOIN products p ON p.product_id = op.product_id
         WHERE op.order_id = ?
         ORDER BY op.order_pro_id'
    );
    if (!$items_statement) {
        error_log('Unable to prepare invoice PDF item lookup: ' . mysqli_error($con));
        http_response_code(500);
        exit('Unable to generate this invoice PDF.');
    }
    $order_id = (int)$invoice['order_id'];
    mysqli_stmt_bind_param($items_statement, 'i', $order_id);
    if (!mysqli_stmt_execute($items_statement)) {
        error_log('Unable to load invoice PDF items: ' . mysqli_stmt_error($items_statement));
        http_response_code(500);
        exit('Unable to generate this invoice PDF.');
    }
    $items_result = mysqli_stmt_get_result($items_statement);
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $quantity = max(0, (int)$item['qty']);
        $line_total = (float)$item['amt'];
        $items[] = [
            'description' => (string)$item['product_title'],
            'quantity' => $quantity,
            'unit_price' => $quantity > 0 ? $line_total / $quantity : 0,
            'total' => $line_total
        ];
    }

    $pdf = buildInvoicePdf($invoice, $items);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string)$invoice['invoice_number']) . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('X-Content-Type-Options: nosniff');
    echo $pdf;
}

function invoicePdfEscape($value) {
    $text = (string)$value;
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    $text = preg_replace('/[^\x20-\xFF]/', '', $text);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function invoicePdfWrap($text, $max_length = 48) {
    $words = preg_split('/\s+/', trim((string)$text), -1, PREG_SPLIT_NO_EMPTY);
    if (!$words) {
        return [''];
    }
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        if ($line !== '' && strlen($line . ' ' . $word) > $max_length) {
            $lines[] = $line;
            $line = '';
        }
        while (strlen($word) > $max_length) {
            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }
            $lines[] = substr($word, 0, $max_length);
            $word = substr($word, $max_length);
        }
        $line = $line === '' ? $word : $line . ' ' . $word;
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    return $lines;
}

function buildInvoicePdf(array $invoice, array $items) {
    $pages = [];
    $page_content = '';
    $page_number = 0;
    $y = 0;

    $add_text = static function ($x, $baseline, $text, $size = 10, $bold = false) use (&$page_content) {
        $font = $bold ? 'F2' : 'F1';
        $page_content .= "BT /$font $size Tf 1 0 0 1 $x $baseline Tm (" . invoicePdfEscape($text) . ") Tj ET\n";
    };
    $add_line = static function ($x1, $y1, $x2, $y2) use (&$page_content) {
        $page_content .= "$x1 $y1 m $x2 $y2 l S\n";
    };
    $start_page = static function () use (&$pages, &$page_content, &$page_number, &$y, $add_text, $add_line, $invoice) {
        if ($page_number > 0) {
            $pages[] = $page_content . "BT /F1 8 Tf 1 0 0 1 270 28 Tm (Page " . $page_number . ") Tj ET\n";
        }
        $page_number++;
        $page_content = '';
        $page_content .= "0.12 0.20 0.33 rg 40 746 515 56 re f\n";
        $page_content .= "0 0 0 rg\n";
        $add_text(56, 776, 'TECHSHOP LIBERIA', 16, true);
        $add_text(56, 758, 'Monrovia, Liberia | +231 123 456 789', 9);
        $add_text(406, 779, 'INVOICE', 17, true);
        $add_text(406, 760, '#' . (string)$invoice['invoice_number'], 9, true);
        $add_text(40, 720, 'Bill To', 11, true);
        $add_text(40, 702, (string)($invoice['customer_name'] ?: $invoice['f_name']), 10, true);
        $add_text(40, 686, (string)($invoice['customer_email'] ?: $invoice['order_email']), 9);
        $address = trim(implode(', ', array_filter([
            (string)($invoice['address1'] ?: $invoice['address']),
            (string)($invoice['address2'] ?: $invoice['city']),
            (string)$invoice['state'],
            (string)$invoice['zip']
        ], static function ($part) { return $part !== ''; })));
        foreach (invoicePdfWrap($address, 65) as $line_index => $address_line) {
            $add_text(40, 670 - ($line_index * 13), $address_line, 9);
        }
        $add_text(385, 720, 'Invoice date: ' . (string)$invoice['invoice_date'], 9);
        $add_text(385, 702, 'Due date: ' . (string)$invoice['due_date'], 9);
        $add_text(385, 684, 'Status: ' . ucfirst((string)$invoice['invoice_status']), 9);
        $payment_method = trim((string)($invoice['payment_method'] ?? ''));
        if ($payment_method !== '') {
            $add_text(385, 666, 'Payment: ' . ucwords(str_replace('_', ' ', $payment_method)), 9);
        }
        if (strpos((string)($invoice['order_notes'] ?? ''), 'LOCAL TEST ORDER') === 0) {
            $page_content .= "0.75 0.08 0.08 rg\n";
            $add_text(385, 648, 'TEST ORDER - DO NOT FULFILL OR SHIP', 8, true);
            $page_content .= "0 0 0 rg\n";
        }
        $page_content .= "0.91 0.93 0.96 rg 40 620 515 22 re f\n0 0 0 rg\n";
        $add_text(48, 628, 'Description', 9, true);
        $add_text(355, 628, 'Qty', 9, true);
        $add_text(400, 628, 'Unit Price', 9, true);
        $add_text(490, 628, 'Total', 9, true);
        $add_line(40, 615, 555, 615);
        $y = 597;
    };

    $start_page();
    foreach ($items as $item) {
        $description_lines = invoicePdfWrap($item['description'], 48);
        if ($y - (count($description_lines) * 13) < 105) {
            $start_page();
        }
        foreach ($description_lines as $line_index => $description_line) {
            $add_text(48, $y - ($line_index * 13), $description_line, 9);
        }
        $add_text(359, $y, (string)$item['quantity'], 9);
        $add_text(400, $y, 'L$' . number_format($item['unit_price'], 2), 9);
        $add_text(480, $y, 'L$' . number_format($item['total'], 2), 9);
        $y -= max(20, count($description_lines) * 13 + 7);
        $add_line(40, $y + 3, 555, $y + 3);
    }

    if ($y < 135) {
        $start_page();
        $y = 595;
    }
    $y -= 12;
    foreach ([
        ['Subtotal', (float)$invoice['subtotal']],
        ['Tax', (float)$invoice['tax_amount']],
        ['Shipping', (float)$invoice['shipping_cost']],
        ['Total', (float)$invoice['total_amount']]
    ] as $total_row) {
        $add_text(390, $y, $total_row[0], 10, $total_row[0] === 'Total');
        $add_text(480, $y, 'L$' . number_format($total_row[1], 2), 10, $total_row[0] === 'Total');
        $y -= 18;
    }
    $notes = trim((string)($invoice['notes'] ?? ''));
    if ($notes !== '') {
        $y -= 10;
        foreach (invoicePdfWrap('Notes: ' . $notes, 90) as $notes_line) {
            if ($y < 55) {
                $start_page();
                $y = 595;
            }
            $add_text(40, $y, $notes_line, 9);
            $y -= 13;
        }
    }
    $add_text(40, 42, 'Thank you for your business. | TechShop Liberia', 8);
    $pages[] = $page_content . "BT /F1 8 Tf 1 0 0 1 270 28 Tm (Page " . $page_number . ") Tj ET\n";

    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    $page_ids = [];
    foreach ($pages as $index => $content) {
        $page_id = 5 + ($index * 2);
        $content_id = $page_id + 1;
        $page_ids[] = $page_id . ' 0 R';
        $objects[$page_id] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $content_id . ' 0 R >>';
        $objects[$content_id] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $page_ids) . '] /Count ' . count($pages) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $object_id => $object_body) {
        $offsets[$object_id] = strlen($pdf);
        $pdf .= $object_id . " 0 obj\n" . $object_body . "\nendobj\n";
    }
    $xref_offset = strlen($pdf);
    $object_count = max(array_keys($objects)) + 1;
    $pdf .= "xref\n0 " . $object_count . "\n0000000000 65535 f \n";
    for ($object_id = 1; $object_id < $object_count; $object_id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$object_id] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . $object_count . " /Root 1 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";
    return $pdf;
}

function sendInvoice($con, $invoice_id) {
    if (!tableExists($con, 'invoices')) {
        $_SESSION['error'] = "Invoices table not found.";
        return;
    }
    
    $query = "SELECT i.*, u.email, u.first_name, u.last_name 
             FROM invoices i 
             JOIN orders_info o ON i.order_id = o.order_id 
             JOIN user_info u ON o.user_id = u.user_id 
             WHERE i.invoice_id = '$invoice_id'";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $invoice = mysqli_fetch_assoc($result);
        
        $update_query = "UPDATE invoices SET invoice_status = 'sent' WHERE invoice_id = '$invoice_id'";
        mysqli_query($con, $update_query);
        
        $_SESSION['success'] = "Invoice sent to " . $invoice['email'];
    } else {
        $_SESSION['error'] = "Invoice not found.";
    }
}

function deleteInvoice($con, $invoice_id) {
    if (!tableExists($con, 'invoices')) {
        $_SESSION['error'] = "Invoices table not found.";
        return;
    }
    
    $delete_query = "DELETE FROM invoices WHERE invoice_id = '$invoice_id'";
    if (mysqli_query($con, $delete_query)) {
        $_SESSION['success'] = "Invoice deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting invoice: " . mysqli_error($con);
    }
}

function createInvoice($con) {
    if (!tableExists($con, 'invoices')) {
        $_SESSION['error'] = "Invoices table not found. Please run the database fix script first.";
        return;
    }
    
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $invoice_number = mysqli_real_escape_string($con, $_POST['invoice_number']);
    $invoice_date = mysqli_real_escape_string($con, $_POST['invoice_date']);
    $due_date = mysqli_real_escape_string($con, $_POST['due_date']);
    $subtotal = mysqli_real_escape_string($con, $_POST['subtotal']);
    $tax_amount = mysqli_real_escape_string($con, $_POST['tax_amount']);
    $shipping_cost = mysqli_real_escape_string($con, $_POST['shipping_cost']);
    $total_amount = mysqli_real_escape_string($con, $_POST['total_amount']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    $query = "INSERT INTO invoices (order_id, invoice_number, invoice_date, due_date, 
              subtotal, tax_amount, shipping_cost, total_amount, notes) 
              VALUES ('$order_id', '$invoice_number', '$invoice_date', '$due_date',
              '$subtotal', '$tax_amount', '$shipping_cost', '$total_amount', '$notes')";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Invoice created successfully!";
    } else {
        $_SESSION['error'] = "Error creating invoice: " . mysqli_error($con);
    }
}

function updateInvoice($con) {
    if (!tableExists($con, 'invoices')) {
        $_SESSION['error'] = "Invoices table not found.";
        return;
    }
    
    $invoice_id = mysqli_real_escape_string($con, $_POST['invoice_id']);
    $invoice_status = mysqli_real_escape_string($con, $_POST['invoice_status']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    $query = "UPDATE invoices SET invoice_status = '$invoice_status', notes = '$notes' 
              WHERE invoice_id = '$invoice_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Invoice updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating invoice: " . mysqli_error($con);
    }
}
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-container bg-primary rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">receipt</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Invoice Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage and generate customer invoices</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <?php if (tableExists($con, 'invoices')): ?>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createInvoiceModal">
                        <i class="material-icons mr-2">add</i>Create Invoice
                    </button>
                    <?php else: ?>
                    <a href="complete_database_fix.php" class="btn btn-warning">
                        <i class="material-icons mr-2">build</i>Fix Database First
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Database Warning -->
        <?php if (!tableExists($con, 'invoices')): ?>
        <div class="alert alert-warning">
            <i class="material-icons mr-2">warning</i>
            <strong>Database not set up:</strong> The invoice system requires database tables to be created. 
            <a href="complete_database_fix.php" class="alert-link">Click here to fix the database</a>.
        </div>
        <?php endif; ?>

        <!-- Notifications -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">error</i>
            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Invoices List -->
        <?php if (tableExists($con, 'invoices')): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-primary">list</i>
                    All Invoices
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT i.*, o.order_id, u.first_name, u.last_name 
                                     FROM invoices i 
                                     JOIN orders_info o ON i.order_id = o.order_id 
                                     JOIN user_info u ON o.user_id = u.user_id 
                                     ORDER BY i.invoice_date DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($invoice = mysqli_fetch_assoc($result)):
                                    $status_class = [
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'cancelled' => 'warning'
                                    ][$invoice['invoice_status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td><strong><?php echo $invoice['invoice_number']; ?></strong></td>
                                <td>#<?php echo $invoice['order_id']; ?></td>
                                <td><?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?></td>
                                <td><?php echo $invoice['invoice_date']; ?></td>
                                <td><?php echo $invoice['due_date']; ?></td>
                                <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($invoice['invoice_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="invoice.php?action=view&id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-outline-primary" target="_blank">
                                            <i class="material-icons" style="font-size:16px;">visibility</i>
                                        </a>
                                        <a href="invoice.php?action=download&id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-outline-success" title="Download invoice as PDF">
                                            <i class="material-icons" style="font-size:16px;">download</i>
                                        </a>
                                        <a href="invoice.php?action=send&id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-outline-info">
                                            <i class="material-icons" style="font-size:16px;">email</i>
                                        </a>
                                        <button class="btn btn-outline-warning" 
                                                data-toggle="modal" 
                                                data-target="#editInvoiceModal" 
                                                data-invoice-id="<?php echo $invoice['invoice_id']; ?>"
                                                data-invoice-status="<?php echo $invoice['invoice_status']; ?>"
                                                data-notes="<?php echo htmlspecialchars($invoice['notes'] ?? ''); ?>">
                                            <i class="material-icons" style="font-size:16px;">edit</i>
                                        </button>
                                        <a href="invoice.php?action=delete&id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this invoice?')">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4">No invoices found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orders without invoices -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-warning">shopping_cart</i>
                    Orders Without Invoices
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $date_column = getSafeDateColumn($con);
                            
                            $query = "SELECT o.*, o.total_amt AS total_amount,
                                             COALESCE(NULLIF(CONCAT_WS(' ', u.first_name, u.last_name), ''), o.f_name) AS customer_name
                                     FROM orders_info o 
                                     LEFT JOIN user_info u ON o.user_id = u.user_id 
                                     WHERE o.order_id NOT IN (SELECT order_id FROM invoices)
                                     ORDER BY $date_column DESC";
                            $result = mysqli_query($con, $query);
                            if (!$result) {
                                throw new RuntimeException('Unable to load orders without invoices: ' . mysqli_error($con));
                            }
                            
                            if (mysqli_num_rows($result) > 0) {
                                while($order = mysqli_fetch_assoc($result)):
                            ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Customer unavailable', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php 
                                    // Safe date display
                                    if (isset($order['order_date'])) {
                                        echo $order['order_date'];
                                    } elseif (isset($order['created_at'])) {
                                        echo $order['created_at'];
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['order_status'] === 'delivered' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoice.php?action=generate&order_id=<?php echo $order['order_id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="material-icons mr-1" style="font-size:16px;">receipt</i>
                                        Generate Invoice
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-4">No orders without invoices found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Invoice Modal -->
<?php if (tableExists($con, 'invoices')): ?>
<div class="modal fade" id="createInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- ... modal content same as before ... -->
        </div>
    </div>
</div>

<!-- Edit Invoice Modal -->
<div class="modal fade" id="editInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- ... modal content same as before ... -->
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Edit invoice modal handler
$('#editInvoiceModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var invoiceId = button.data('invoice-id');
    var invoiceStatus = button.data('invoice-status');
    var notes = button.data('notes');
    
    var modal = $(this);
    modal.find('#edit_invoice_id').val(invoiceId);
    modal.find('#edit_invoice_status').val(invoiceStatus);
    modal.find('#edit_notes').val(notes);
});

// Auto-calculate total amount
document.addEventListener('DOMContentLoaded', function() {
    const subtotal = document.querySelector('input[name="subtotal"]');
    const tax = document.querySelector('input[name="tax_amount"]');
    const shipping = document.querySelector('input[name="shipping_cost"]');
    const total = document.querySelector('input[name="total_amount"]');
    
    function calculateTotal() {
        if (subtotal && tax && shipping && total) {
            const subtotalVal = parseFloat(subtotal.value) || 0;
            const taxVal = parseFloat(tax.value) || 0;
            const shippingVal = parseFloat(shipping.value) || 0;
            total.value = (subtotalVal + taxVal + shippingVal).toFixed(2);
        }
    }
    
    if (subtotal) subtotal.addEventListener('input', calculateTotal);
    if (tax) tax.addEventListener('input', calculateTotal);
    if (shipping) shipping.addEventListener('input', calculateTotal);
});
</script>

<?php
include "footer.php";
?>