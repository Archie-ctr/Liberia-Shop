<?php
// pending_orders.php
session_start();
include("../../db.php");
include_once __DIR__ . '/../../currency.php';
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
    $order_id = $_GET['id'] ?? 0;
    
    if ($action === 'process_order' || $action === 'confirm_order') {
        $confirmation_note = "\nConfirmed by staff: " . ($_SESSION['admin_name'] ?? 'Staff') . " at " . date('Y-m-d H:i:s');
        $stmt = mysqli_prepare($con, "UPDATE orders_info SET order_status='processing', admin_notes=CONCAT(COALESCE(admin_notes,''), ?), updated_at=NOW() WHERE order_id=? AND order_status='pending'");
        mysqli_stmt_bind_param($stmt, 'si', $confirmation_note, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = mysqli_stmt_affected_rows($stmt) > 0 ? "Order confirmed and moved to processing." : "The order has already been confirmed or is no longer pending.";
        } else {
            $_SESSION['error'] = "Error confirming order: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
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
                        <div class="header-icon-container bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">schedule</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Pending Orders</h1>
                            <p class="page-subtitle text-muted mb-0">Review and process new customer orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-warning badge-pill p-2">
                        <i class="material-icons mr-1">shopping_cart</i>
                        <?php
                        $pending_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM orders_info WHERE order_status = 'pending'"))['total'];
                        echo $pending_count . ' Pending';
                        ?>
                    </span>
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

        <!-- Pending Orders List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2">warning</i>
                    Orders Requiring Attention
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
                                <th>Payment</th>
                                <th>Items</th>
                                <th>Time Pending</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT o.*, u.first_name, u.last_name, u.email,
                                     (SELECT COUNT(*) FROM order_products WHERE order_id = o.order_id) as item_count,
                                     o.total_amt AS total_amount,
                                     TIMESTAMPDIFF(HOUR, o.created_at, NOW()) as hours_pending
                                     FROM orders_info o 
                                     JOIN user_info u ON o.user_id = u.user_id 
                                     WHERE o.order_status = 'pending'
                                     ORDER BY o.created_at ASC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($order = mysqli_fetch_assoc($result)):
                                    $urgency_class = $order['hours_pending'] > 24 ? 'danger' : ($order['hours_pending'] > 12 ? 'warning' : 'info');
                                    $is_local_test_order = strpos((string)($order['admin_notes'] ?? ''), 'LOCAL TEST ORDER') === 0;
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $order['order_id']; ?></strong>
                                    <?php if ($is_local_test_order): ?>
                                        <br><span class="badge badge-danger">TEST ONLY - DO NOT FULFILL</span>
                                    <?php endif; ?>
                                    <?php if ($order['hours_pending'] > 24): ?>
                                        <br><small class="text-danger">Overdue!</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(format_store_price($order['total_amount'], $con)); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-light"><?php echo $order['item_count']; ?> items</span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $urgency_class; ?>">
                                        <?php echo $order['hours_pending']; ?> hours
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="orders.php?action=view&id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="material-icons mr-1" style="font-size:16px;">visibility</i> View
                                        </a>
                                        <?php if (!$is_local_test_order): ?>
                                        <a href="pending_orders.php?action=confirm_order&id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-success">
                                            <i class="material-icons mr-1" style="font-size:16px;">check_circle</i> Confirm Order
                                        </a>
                                        <?php endif; ?>
                                        <a href="orders.php?action=update_status&id=<?php echo $order['order_id']; ?>&status=cancelled" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to cancel this order?')">
                                            <i class="material-icons" style="font-size:16px;">cancel</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4">No pending orders found. Great job!</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="material-icons" style="font-size: 48px;">auto_awesome</i>
                        <h4>Process All</h4>
                        <p>Move all pending orders to processing</p>
                        <button class="btn btn-light" onclick="processAllOrders()">
                            <i class="material-icons mr-1">play_arrow</i> Process All
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="material-icons" style="font-size: 48px;">email</i>
                        <h4>Send Notifications</h4>
                        <p>Notify customers about order status</p>
                        <button class="btn btn-light" onclick="sendNotifications()">
                            <i class="material-icons mr-1">send</i> Send Updates
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="material-icons" style="font-size: 48px;">assessment</i>
                        <h4>Generate Report</h4>
                        <p>Create pending orders report</p>
                        <button class="btn btn-light" onclick="generateReport()">
                            <i class="material-icons mr-1">description</i> Create Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function processAllOrders() {
    if (confirm('Are you sure you want to process all pending orders?')) {
        // This would typically be an AJAX call to process all orders
        alert('All orders would be processed in a real implementation');
        window.location.reload();
    }
}

function sendNotifications() {
    alert('Customer notifications would be sent in a real implementation');
}

function generateReport() {
    alert('Pending orders report would be generated in a real implementation');
}
</script>

<?php
include "footer.php";
?>