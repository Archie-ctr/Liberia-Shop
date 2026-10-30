<?php
// completed_orders.php
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
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-container bg-success rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">check_circle</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Completed Orders</h1>
                            <p class="page-subtitle text-muted mb-0">View successfully delivered orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-success badge-pill p-2">
                        <i class="material-icons mr-1">local_shipping</i>
                        <?php
                        $completed_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM orders_info WHERE order_status = 'delivered'"))['total'];
                        echo $completed_count . ' Delivered';
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Completed Orders Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <p class="card-category">This Month</p>
                        <h3 class="card-title">
                            <?php
                            $month_query = "SELECT COUNT(*) as total FROM orders_info 
                                          WHERE order_status = 'delivered' 
                                          AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                                          AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                            $month_result = mysqli_query($con, $month_query);
                            $month_data = mysqli_fetch_assoc($month_result);
                            echo $month_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">attach_money</i>
                        </div>
                        <p class="card-category">Revenue</p>
                        <h3 class="card-title">
                            <?php
                            $revenue_query = "SELECT SUM(total_amt) as total FROM orders_info 
                                            WHERE order_status = 'delivered' 
                                            AND MONTH(created_at) = MONTH(CURRENT_DATE())";
                            $revenue_result = mysqli_query($con, $revenue_query);
                            $revenue_data = mysqli_fetch_assoc($revenue_result);
                            echo '$' . number_format($revenue_data['total'] ?? 0, 0);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">star</i>
                        </div>
                        <p class="card-category">Avg. Rating</p>
                        <h3 class="card-title">4.7</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-primary card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">repeat</i>
                        </div>
                        <p class="card-category">Repeat Customers</p>
                        <h3 class="card-title">68%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Orders List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-success">list</i>
                    Recently Completed Orders
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Delivery Date</th>
                                <th>Amount</th>
                                <th>Items</th>
                                <th>Delivery Time</th>
                                <th>Customer Feedback</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT o.*, u.first_name, u.last_name, u.email,
                                     (SELECT COUNT(*) FROM order_products WHERE order_id = o.order_id) as item_count,
                                     o.total_amt AS total_amount,
                                     DATEDIFF(o.updated_at, o.created_at) as delivery_days
                                     FROM orders_info o 
                                     JOIN user_info u ON o.user_id = u.user_id 
                                     WHERE o.order_status = 'delivered'
                                     ORDER BY o.updated_at DESC 
                                     LIMIT 20";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($order = mysqli_fetch_assoc($result)):
                                    $delivery_class = $order['delivery_days'] <= 3 ? 'success' : ($order['delivery_days'] <= 7 ? 'warning' : 'danger');
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $order['order_id']; ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['updated_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(format_store_price($order['total_amount'], $con)); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-light"><?php echo $order['item_count']; ?> items</span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $delivery_class; ?>">
                                        <?php echo $order['delivery_days']; ?> days
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="text-warning">
                                            <i class="material-icons" style="font-size:16px;">star</i>
                                            <i class="material-icons" style="font-size:16px;">star</i>
                                            <i class="material-icons" style="font-size:16px;">star</i>
                                            <i class="material-icons" style="font-size:16px;">star</i>
                                            <i class="material-icons" style="font-size:16px;">star_half</i>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="orders.php?action=view&id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="material-icons" style="font-size:16px;">visibility</i>
                                        </a>
                                        <a href="receipt.php?action=generate&id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-outline-success" target="_blank">
                                            <i class="material-icons" style="font-size:16px;">receipt</i>
                                        </a>
                                        <button class="btn btn-outline-info" onclick="requestFeedback(<?php echo $order['order_id']; ?>)">
                                            <i class="material-icons" style="font-size:16px;">feedback</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4">No completed orders found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons mr-2">trending_up</i>
                            Delivery Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h3 class="text-success">85%</h3>
                                <small>On Time</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-warning">12%</h3>
                                <small>Delayed</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-danger">3%</h3>
                                <small>Issues</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons mr-2">people</i>
                            Customer Satisfaction
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h3 class="text-warning">4.7</h3>
                                <small>Avg Rating</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-info">92%</h3>
                                <small>Happy Customers</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-primary">45%</h3>
                                <small>Repeat Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function requestFeedback(orderId) {
    if (confirm('Send feedback request to customer?')) {
        alert('Feedback request sent for order #' + orderId);
        // In real implementation, this would be an AJAX call
    }
}

// Export completed orders
function exportCompletedOrders() {
    alert('Export feature for completed orders would be implemented here');
}
</script>

<?php
include "footer.php";
?>