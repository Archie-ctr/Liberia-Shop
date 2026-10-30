<?php
session_start();
include("./includes/db.php");

error_reporting(0);

// Delete functionality
if(isset($_GET['action']) && $_GET['action']!="" && $_GET['action']=='delete') {
    $order_id = mysqli_real_escape_string($con, $_GET['order_id']);
    mysqli_query($con, "DELETE FROM orders WHERE order_id='$order_id'") or die("Delete query is incorrect...");
}

// Search and Filter functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? mysqli_real_escape_string($con, $_GET['date_filter']) : '';

// Build query with filters
$query_where = "WHERE 1=1";
if(!empty($search)) {
    $query_where .= " AND (oi.email LIKE '%$search%' OR oi.address LIKE '%$search%' OR p.product_title LIKE '%$search%')";
}
if(!empty($date_filter)) {
    $query_where .= " AND DATE(oi.order_date) = '$date_filter'";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$count_query = "SELECT COUNT(DISTINCT oi.order_id) as total FROM orders_info oi 
                LEFT JOIN order_products op ON oi.order_id = op.order_id
                LEFT JOIN products p ON op.product_id = p.product_id 
                $query_where";
$count_result = mysqli_query($con, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

include "sidenav.php";
include "topheader.php";
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Sales Overview Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">shopping_cart</i>
                        </div>
                        <p class="card-category">Total Orders</p>
                        <?php
                        $total_orders = mysqli_query($con, "SELECT COUNT(*) as total FROM orders_info");
                        $orders_data = mysqli_fetch_assoc($total_orders);
                        ?>
                        <h3 class="card-title"><?php echo number_format($orders_data['total']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">attach_money</i>
                        </div>
                        <p class="card-category">Total Revenue</p>
                        <?php
                        $total_revenue = mysqli_query($con, "SELECT SUM(total_amt) as total FROM orders_info");
                        $revenue_data = mysqli_fetch_assoc($total_revenue);
                        ?>
                        <h3 class="card-title">$<?php echo number_format($revenue_data['total'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">inventory</i>
                        </div>
                        <p class="card-category">Avg Order Value</p>
                        <?php
                        $avg_order = mysqli_query($con, "SELECT AVG(total_amt) as avg_value FROM orders_info");
                        $avg_data = mysqli_fetch_assoc($avg_order);
                        ?>
                        <h3 class="card-title">$<?php echo number_format($avg_data['avg_value'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">people</i>
                        </div>
                        <p class="card-category">Unique Customers</p>
                        <?php
                        $unique_customers = mysqli_query($con, "SELECT COUNT(DISTINCT user_id) as total FROM orders_info");
                        $customers_data = mysqli_fetch_assoc($unique_customers);
                        ?>
                        <h3 class="card-title"><?php echo number_format($customers_data['total']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Filters & Search</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="bmd-label-floating">Search Orders</label>
                            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by email, address, or product...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="bmd-label-floating">Filter by Date</label>
                            <input type="date" name="date_filter" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="bmd-label-floating">Items Per Page</label>
                            <select name="limit" class="form-control" onchange="this.form.submit()">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px;">
                            <i class="material-icons">search</i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="col-md-14">
            <div class="card">
                <div class="card-header card-header-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Sales Management</h4>
                        <div class="card-actions">
                            <button class="btn btn-sm btn-light" onclick="exportToExcel()">
                                <i class="material-icons">file_download</i> Export
                            </button>
                            <button class="btn btn-sm btn-light" data-toggle="collapse" data-target="#salesTable">
                                <i class="material-icons">unfold_less</i>
                            </button>
                        </div>
                    </div>
                    <p class="card-category">Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                    (Total: <?php echo number_format($total_rows); ?> orders)</p>
                </div>
                <div class="card-body collapse show" id="salesTable">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesTableData">
                            <thead class="text-primary">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Amount</th>
                                    <th>Quantity</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT DISTINCT oi.*, u.first_name, u.last_name 
                                          FROM orders_info oi 
                                          LEFT JOIN user_info u ON oi.user_id = u.user_id
                                          LEFT JOIN order_products op ON oi.order_id = op.order_id
                                          LEFT JOIN products p ON op.product_id = p.product_id 
                                          $query_where 
                                          ORDER BY oi.order_id DESC 
                                          LIMIT $offset, $limit";
                                
                                $run = mysqli_query($con, $query);
                                
                                if(mysqli_num_rows($run) > 0) {
                                    while($row = mysqli_fetch_array($run)) {
                                        $order_id = $row['order_id'];
                                        $email = $row['email'];
                                        $address = $row['address'];
                                        $total_amount = $row['total_amt'];
                                        $user_id = $row['user_id'];
                                        $qty = $row['prod_count'];
                                        $order_date = $row['order_date'];
                                        $customer_name = $row['first_name'] . ' ' . $row['last_name'];
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $order_id; ?></strong></td>
                                    <td>
                                        <?php if(!empty($customer_name) && $customer_name != ' '): ?>
                                            <?php echo htmlspecialchars($customer_name); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Guest</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $product_query = "SELECT p.product_title, op.product_quantity 
                                                         FROM order_products op 
                                                         JOIN products p ON op.product_id = p.product_id 
                                                         WHERE op.order_id = $order_id";
                                        $product_run = mysqli_query($con, $product_query);
                                        $products = [];
                                        while($product_row = mysqli_fetch_array($product_run)) {
                                            $products[] = $product_row['product_title'] . ' (x' . $product_row['product_quantity'] . ')';
                                        }
                                        echo implode('<br>', $products);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td><?php echo htmlspecialchars(substr($address, 0, 30)) . (strlen($address) > 30 ? '...' : ''); ?></td>
                                    <td><strong>$<?php echo number_format($total_amount, 2); ?></strong></td>
                                    <td><span class="badge badge-pill badge-info"><?php echo $qty; ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($order_date)); ?></td>
                                    <td>
                                        <span class="badge badge-success">Completed</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order_id; ?>)" 
                                                    title="View Details">
                                                <i class="material-icons">visibility</i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editOrder(<?php echo $order_id; ?>)" 
                                                    title="Edit Order">
                                                <i class="material-icons">edit</i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $order_id; ?>)" 
                                                    title="Delete Order">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="10" class="text-center py-4"><h4>No orders found</h4><p>Try adjusting your search filters</p></td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles -->
<style>
.card-stats .card-header .card-icon {
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    padding: 15px;
    margin-top: -20px;
    margin-right: 15px;
    float: left;
}

.card-stats .card-header .card-category {
    color: rgba(255,255,255,0.8);
    font-size: 14px;
}

.card-stats .card-header .card-title {
    color: white;
    font-size: 1.8rem;
    font-weight: 300;
}

.card-actions {
    display: flex;
    gap: 5px;
}

.badge-pill {
    font-size: 0.8rem;
    padding: 4px 8px;
}

.table-responsive {
    border-radius: 4px;
}

.btn-group .btn {
    margin: 0 2px;
    padding: 4px 8px;
}

.pagination .page-item.active .page-link {
    background-color: #9c27b0;
    border-color: #9c27b0;
}

.pagination .page-link {
    color: #9c27b0;
}

.pagination .page-link:hover {
    color: #7b1fa2;
}
</style>

<!-- JavaScript Functions -->
<script>
function confirmDelete(orderId) {
    if(confirm('Are you sure you want to delete order #' + orderId + '? This action cannot be undone.')) {
        window.location.href = '?action=delete&order_id=' + orderId;
    }
}

function viewOrder(orderId) {
    // Implement view order details functionality
    alert('View order details for #' + orderId);
    // window.location.href = 'order_details.php?id=' + orderId;
}

function editOrder(orderId) {
    // Implement edit order functionality
    alert('Edit order #' + orderId);
    // window.location.href = 'edit_order.php?id=' + orderId;
}

function exportToExcel() {
    let table = document.getElementById('salesTableData');
    let html = table.outerHTML;
    let url = 'data:application/vnd.ms-excel,' + escape(html);
    let downloadLink = document.createElement("a");
    downloadLink.href = url;
    downloadLink.download = "sales-report-<?php echo date('Y-m-d'); ?>.xls";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Auto-refresh page every 30 seconds for real-time updates
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>

<?php
include "footer.php";
?>