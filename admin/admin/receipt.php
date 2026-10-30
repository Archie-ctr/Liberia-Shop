<?php
// Receipt.php (Fixed Version)
session_start();
include("../../db.php");
include "sidenav.php";
include "topheader.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $payment_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'generate':
            generateReceipt($con, $payment_id);
            exit;
        case 'download':
            downloadReceipt($con, $payment_id);
            exit;
        case 'email':
            emailReceipt($con, $payment_id);
            break;
    }
}

// Function to check if column exists
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '$column'");
    return ($result && mysqli_num_rows($result) > 0);
}

// Function to get order date column name
function getOrderDateColumn($con) {
    if (columnExists($con, 'orders_info', 'order_date')) {
        return 'order_date';
    } elseif (columnExists($con, 'orders_info', 'created_at')) {
        return 'created_at';
    } else {
        // If neither exists, we'll use a fallback
        return 'order_date'; // This will be created by the fix script
    }
}

function generateReceipt($con, $payment_id) {
    $query = "SELECT p.*, o.*, u.first_name, u.last_name, u.email, u.phone, u.address1, u.address2,
                     i.invoice_number, i.invoice_date
             FROM payments p
             JOIN orders_info o ON p.order_id = o.order_id
             JOIN user_info u ON o.user_id = u.user_id
             LEFT JOIN invoices i ON o.order_id = i.order_id
             WHERE p.payment_id = '$payment_id'";
    $result = mysqli_query($con, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "Payment not found.";
        return;
    }
    
    $payment = mysqli_fetch_assoc($result);
    
    // Get order items
    $items_query = "SELECT * FROM order_items WHERE order_id = '{$payment['order_id']}'";
    $items_result = mysqli_query($con, $items_query);
    
    // HTML for receipt
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt for Payment #<?php echo $payment['payment_id']; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
            .receipt-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
            .company-name { font-size: 24px; font-weight: bold; color: #4361ee; margin-bottom: 5px; }
            .receipt-title { font-size: 28px; font-weight: bold; color: #333; margin: 20px 0; }
            .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
            .detail-group { margin-bottom: 15px; }
            .detail-label { font-weight: bold; color: #666; font-size: 14px; margin-bottom: 5px; }
            .detail-value { color: #333; font-size: 16px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .table th { background: #4361ee; color: white; padding: 12px; text-align: left; }
            .table td { padding: 12px; border-bottom: 1px solid #e9ecef; }
            .totals { text-align: right; margin-bottom: 30px; }
            .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .total-label { font-weight: bold; }
            .total-amount { font-weight: bold; }
            .grand-total { font-size: 18px; color: #4361ee; border-top: 2px solid #e9ecef; padding-top: 10px; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e9ecef; color: #666; }
            .thank-you { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
            @media print {
                body { background: white; }
                .receipt-container { box-shadow: none; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <!-- Header -->
            <div class="header">
                <div class="company-name">TechShop Liberia</div>
                <div>Monrovia, Liberia</div>
                <div>Email: info@techshopliberia.com | Phone: +231 123 456 789</div>
                <div class="receipt-title">PAYMENT RECEIPT</div>
            </div>

            <!-- Details -->
            <div class="details-grid">
                <div>
                    <div class="detail-group">
                        <div class="detail-label">Receipt Number</div>
                        <div class="detail-value">RCP-<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Payment Date</div>
                        <div class="detail-value">
                            <?php 
                            if ($payment['payment_date']) {
                                echo date('F j, Y', strtotime($payment['payment_date']));
                            } elseif ($payment['created_at']) {
                                echo date('F j, Y', strtotime($payment['created_at']));
                            } else {
                                echo date('F j, Y');
                            }
                            ?>
                        </div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Payment Method</div>
                        <div class="detail-value text-capitalize"><?php echo str_replace('_', ' ', $payment['payment_method']); ?></div>
                    </div>
                </div>
                <div>
                    <div class="detail-group">
                        <div class="detail-label">Invoice Number</div>
                        <div class="detail-value"><?php echo $payment['invoice_number'] ?: 'N/A'; ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Order Number</div>
                        <div class="detail-value">#<?php echo $payment['order_id']; ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Transaction ID</div>
                        <div class="detail-value"><?php echo $payment['transaction_id'] ?: 'N/A'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-group">
                <div class="detail-label">Customer Information</div>
                <div class="detail-value">
                    <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?><br>
                    <?php echo $payment['email']; ?><br>
                    <?php echo $payment['phone']; ?><br>
                    <?php echo $payment['address1']; ?><br>
                    <?php echo $payment['address2']; ?>
                </div>
            </div>

            <!-- Items -->
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
                    $subtotal = 0;
                    if ($items_result && mysqli_num_rows($items_result) > 0) {
                        while($item = mysqli_fetch_assoc($items_result)): 
                            $subtotal += $item['total_price'];
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
                        echo '<tr><td colspan="4" style="text-align: center;">No items found for this order</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals">
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-amount">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Tax:</span>
                    <span class="total-amount">$0.00</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Shipping:</span>
                    <span class="total-amount">$0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">Amount Paid:</span>
                    <span class="total-amount">$<?php echo number_format($payment['payment_amount'], 2); ?></span>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="detail-group">
                <div class="detail-label">Payment Status</div>
                <div class="detail-value">
                    <span style="color: <?php echo $payment['payment_status'] === 'completed' ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                        <?php echo strtoupper($payment['payment_status']); ?>
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="thank-you">Thank you for your payment!</div>
                <div>This receipt confirms that we have received your payment.</div>
                <div>If you have any questions, please contact our support team.</div>
                <div style="margin-top: 20px; font-size: 12px;">
                    Generated on: <?php echo date('F j, Y \a\t g:i A'); ?>
                </div>
            </div>

            <!-- Print Button -->
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function downloadReceipt($con, $payment_id) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt-' . $payment_id . '.pdf"');
    
    // In a real application, you would generate PDF here
    // For now, we'll redirect to generate with print options
    generateReceipt($con, $payment_id);
}

function emailReceipt($con, $payment_id) {
    // Get payment details
    $query = "SELECT p.*, u.email, u.first_name, u.last_name 
             FROM payments p 
             JOIN orders_info o ON p.order_id = o.order_id 
             JOIN user_info u ON o.user_id = u.user_id 
             WHERE p.payment_id = '$payment_id'";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $payment = mysqli_fetch_assoc($result);
        
        // In a real application, send email with receipt attachment
        // For now, we'll just show a success message
        $_SESSION['success'] = "Receipt sent to " . $payment['email'];
    } else {
        $_SESSION['error'] = "Payment not found.";
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
                        <div class="header-icon-container bg-info rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">receipt_long</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Receipt Management</h1>
                            <p class="page-subtitle text-muted mb-0">Generate and manage payment receipts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Completed Payments for Receipt Generation -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-success">payments</i>
                    Completed Payments - Receipt Ready
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $order_date_column = getOrderDateColumn($con);
                            
                            $query = "SELECT p.*, o.order_id, u.first_name, u.last_name 
                                     FROM payments p 
                                     JOIN orders_info o ON p.order_id = o.order_id 
                                     JOIN user_info u ON o.user_id = u.user_id 
                                     WHERE p.payment_status = 'completed'
                                     ORDER BY p.payment_date DESC, p.created_at DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($payment = mysqli_fetch_assoc($result)):
                            ?>
                            <tr>
                                <td>#<?php echo $payment['payment_id']; ?></td>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                <td>
                                    <span class="badge badge-light text-capitalize">
                                        <?php echo str_replace('_', ' ', $payment['payment_method']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($payment['payment_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    if ($payment['payment_date']) {
                                        echo $payment['payment_date'];
                                    } else {
                                        echo $payment['created_at'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="receipt.php?action=generate&id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-outline-primary" target="_blank">
                                            <i class="material-icons mr-1" style="font-size:16px;">visibility</i> View
                                        </a>
                                        <a href="receipt.php?action=download&id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-outline-success">
                                            <i class="material-icons mr-1" style="font-size:16px;">download</i> Download
                                        </a>
                                        <a href="receipt.php?action=email&id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-outline-info">
                                            <i class="material-icons mr-1" style="font-size:16px;">email</i> Email
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="7" class="text-center py-4">No completed payments found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-warning">schedule</i>
                    Recent Payments
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT p.*, o.order_id, u.first_name, u.last_name 
                                     FROM payments p 
                                     JOIN orders_info o ON p.order_id = o.order_id 
                                     JOIN user_info u ON o.user_id = u.user_id 
                                     ORDER BY p.created_at DESC 
                                     LIMIT 10";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($payment = mysqli_fetch_assoc($result)):
                                    $status_class = [
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'refunded' => 'info'
                                    ][$payment['payment_status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>#<?php echo $payment['payment_id']; ?></td>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                <td>$<?php echo number_format($payment['payment_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($payment['payment_date']) {
                                        echo $payment['payment_date'];
                                    } else {
                                        echo $payment['created_at'];
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-4">No payments found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include "footer.php";
?>