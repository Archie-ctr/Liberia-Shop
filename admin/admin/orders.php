<?php
session_start();
include("./includes/db.php");
include_once __DIR__ . '/../../currency.php';
include "sidenav.php";
include "topheader.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

error_reporting(0);

// Handle actions
if(isset($_GET['action']) && $_GET['action']!="" && $_GET['action']=='delete') {
    $order_id=$_GET['order_id'];
    mysqli_query($con,"delete from orders where order_id='$order_id'")or die("delete query is incorrect...");
    $_SESSION['success'] = "Order deleted successfully!";
} 

// Update order status
if(isset($_POST['update_status'])) {
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $order_status = mysqli_real_escape_string($con, $_POST['order_status']);
    $admin_notes = mysqli_real_escape_string($con, $_POST['admin_notes']);
    
    $update_query = "UPDATE orders SET order_status='$order_status', admin_notes='$admin_notes' WHERE order_id='$order_id'";
    if(mysqli_query($con, $update_query)) {
        $_SESSION['success'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating order status: " . mysqli_error($con);
    }
}

// Pagination
$page = $_GET['page'] ?? 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders";
$count_result = mysqli_query($con, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Check if we need to create the enhanced orders table structure
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM orders LIKE 'order_status'");
if(mysqli_num_rows($check_columns) == 0) {
    // Add missing columns to orders table
    $alter_orders = "ALTER TABLE orders 
                    ADD COLUMN order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                    ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                    ADD COLUMN admin_notes TEXT,
                    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    mysqli_query($con, $alter_orders);
}
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
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

        <!-- Orders Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Total Orders</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">shopping_cart</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">schedule</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'processing'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Processing</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">autorenew</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'delivered'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Delivered</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">local_shipping</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'cancelled'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Cancelled</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">cancel</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM orders WHERE payment_status = 'paid'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Paid</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">payments</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="col-md-14">
            <div class="card ">
                <div class="card-header card-header-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Orders Management</h4>
                        <div>
                            <span class="badge badge-light">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive ps">
                        <table class="table table-hover">
                            <thead class="text-primary">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Contact</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $result = mysqli_query($con, "
                                    SELECT o.order_id, o.product_id, o.qty, p.product_price, o.order_date, 
                                           o.order_status, o.payment_status, o.admin_notes,
                                           p.product_title, 
                                           u.first_name, u.last_name, u.mobile, u.email, u.address1, u.address2
                                    FROM orders o
                                    LEFT JOIN products p ON o.product_id = p.product_id
                                    LEFT JOIN user_info u ON o.user_id = u.user_id
                                    ORDER BY o.order_date DESC 
                                    LIMIT $offset, $records_per_page
                                ") or die("query incorrect...");

                                if(mysqli_num_rows($result) > 0) {
                                    while($order = mysqli_fetch_assoc($result)) {	
                                        $total_amount = $order['product_price'] * $order['qty'];
                                        $display_price = format_store_price($order['product_price'], $con);
                                        $display_total = format_store_price($total_amount, $con);
                                        $status_class = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger'
                                        ][$order['order_status']] ?? 'secondary';
                                        
                                        $payment_class = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                            'refunded' => 'info'
                                        ][$order['payment_status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <div class="customer-info">
                                            <strong><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></strong>
                                            <br><small class="text-muted"><?php echo $order['email']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <strong><?php echo $order['product_title']; ?></strong>
                                            <br><small class="text-muted">Qty: <?php echo $order['qty']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?php echo $order['mobile']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($display_total); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $payment_class; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($order['order_date'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#orderDetailsModal"
                                                    data-order-id="<?php echo $order['order_id']; ?>"
                                                    data-customer-name="<?php echo $order['first_name'] . ' ' . $order['last_name']; ?>"
                                                    data-product="<?php echo $order['product_title']; ?>"
                                                    data-quantity="<?php echo $order['qty']; ?>"
                                                    data-price="<?php echo $order['product_price']; ?>"
                                                    data-total="<?php echo $total_amount; ?>"
                                                    data-display-price="<?php echo htmlspecialchars($display_price, ENT_QUOTES); ?>"
                                                    data-display-total="<?php echo htmlspecialchars($display_total, ENT_QUOTES); ?>"
                                                    data-email="<?php echo $order['email']; ?>"
                                                    data-phone="<?php echo $order['mobile']; ?>"
                                                    data-address="<?php echo $order['address1'] . ' ' . $order['address2']; ?>"
                                                    data-order-status="<?php echo $order['order_status']; ?>"
                                                    data-payment-status="<?php echo $order['payment_status']; ?>"
                                                    data-admin-notes="<?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?>"
                                                    data-order-date="<?php echo $order['order_date']; ?>">
                                                <i class="material-icons" style="font-size:16px;">visibility</i>
                                            </button>
                                            <button class="btn btn-outline-warning" 
                                                    data-toggle="modal" 
                                                    data-target="#updateStatusModal"
                                                    data-order-id="<?php echo $order['order_id']; ?>"
                                                    data-order-status="<?php echo $order['order_status']; ?>"
                                                    data-admin-notes="<?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?>">
                                                <i class="material-icons" style="font-size:16px;">edit</i>
                                            </button>
                                            <?php if(!orderHasInvoice($con, $order['order_id'])): ?>
                                            <a href="invoice.php?action=generate&order_id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-outline-info" title="Generate Invoice">
                                                <i class="material-icons" style="font-size:16px;">receipt</i>
                                            </a>
                                            <?php else: ?>
                                            <a href="invoice.php?action=view&id=<?php echo getInvoiceId($con, $order['order_id']); ?>" 
                                               class="btn btn-outline-success" target="_blank" title="View Invoice">
                                                <i class="material-icons" style="font-size:16px;">receipt</i>
                                            </a>
                                            <?php endif; ?>
                                            <a class="btn btn-outline-danger" 
                                               href="orders.php?order_id=<?php echo $order['order_id']; ?>&action=delete" 
                                               onclick="return confirm('Are you sure you want to delete this order?')">
                                                <i class="material-icons" style="font-size:16px;">delete</i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="text-center py-4">No orders found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="orders.php?page=<?php echo $page-1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="orders.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="orders.php?page=<?php echo $page+1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details - #<span id="modalOrderId"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                        <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
                        <p><strong>Address:</strong> <span id="modalAddress"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p><strong>Order Date:</strong> <span id="modalOrderDate"></span></p>
                        <p><strong>Status:</strong> <span id="modalOrderStatus"></span></p>
                        <p><strong>Payment Status:</strong> <span id="modalPaymentStatus"></span></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Product Details</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="modalProduct"></td>
                                    <td id="modalQuantity"></td>
                                    <td id="modalPrice"></td>
                                    <td id="modalTotal"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Admin Notes</h6>
                        <p id="modalAdminNotes" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="modalInvoiceLink">
                    <i class="material-icons mr-1">receipt</i> View Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="updateOrderId">
                    <div class="form-group">
                        <label>Order Status</label>
                        <select name="order_status" class="form-control" id="updateOrderStatus" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="3" id="updateAdminNotes" placeholder="Add any notes or updates about this order..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper functions
function orderHasInvoice($con, $order_id) {
    $query = "SELECT COUNT(*) as count FROM invoices WHERE order_id = '$order_id'";
    $result = mysqli_query($con, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['count'] > 0;
}

function getInvoiceId($con, $order_id) {
    $query = "SELECT invoice_id FROM invoices WHERE order_id = '$order_id' LIMIT 1";
    $result = mysqli_query($con, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['invoice_id'];
    }
    return 0;
}
?>

<style>
.customer-info, .product-info {
    max-width: 200px;
}
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.table th {
    border-top: none;
    font-weight: 600;
}
</style>

<script>
// Order Details Modal Handler
$('#orderDetailsModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var orderId = button.data('order-id');
    var customerName = button.data('customer-name');
    var product = button.data('product');
    var quantity = button.data('quantity');
    var price = button.data('price');
    var total = button.data('total');
    var email = button.data('email');
    var phone = button.data('phone');
    var address = button.data('address');
    var orderStatus = button.data('order-status');
    var paymentStatus = button.data('payment-status');
    var adminNotes = button.data('admin-notes');
    var orderDate = button.data('order-date');
    
    var modal = $(this);
    modal.find('#modalOrderId').text(orderId);
    modal.find('#modalCustomerName').text(customerName);
    modal.find('#modalProduct').text(product);
    modal.find('#modalQuantity').text(quantity);
    modal.find('#modalPrice').text(button.data('display-price'));
    modal.find('#modalTotal').text(button.data('display-total'));
    modal.find('#modalEmail').text(email);
    modal.find('#modalPhone').text(phone);
    modal.find('#modalAddress').text(address);
    modal.find('#modalOrderStatus').text(orderStatus.charAt(0).toUpperCase() + orderStatus.slice(1));
    modal.find('#modalPaymentStatus').text(paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1));
    modal.find('#modalAdminNotes').text(adminNotes || 'No notes available.');
    modal.find('#modalOrderDate').text(new Date(orderDate).toLocaleDateString());
    
    // Update invoice link
    modal.find('#modalInvoiceLink').attr('href', 'invoice.php?action=view&id=' + orderId);
});

// Update Status Modal Handler
$('#updateStatusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var orderId = button.data('order-id');
    var orderStatus = button.data('order-status');
    var adminNotes = button.data('admin-notes');
    
    var modal = $(this);
    modal.find('#updateOrderId').val(orderId);
    modal.find('#updateOrderStatus').val(orderStatus);
    modal.find('#updateAdminNotes').val(adminNotes || '');
});
</script>

<?php
include "footer.php";
?>