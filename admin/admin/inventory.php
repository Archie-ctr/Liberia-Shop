<?php
// inventory.php
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
    $product_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'update_stock':
            updateStock($con, $product_id);
            break;
        case 'low_stock_alert':
            toggleLowStockAlert($con, $product_id);
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_update'])) {
        bulkUpdateStock($con);
    } elseif (isset($_POST['update_inventory'])) {
        updateInventoryItem($con);
    }
}

function updateStock($con, $product_id) {
    $new_stock = $_GET['stock'] ?? 0;
    
    $query = "UPDATE products SET stock_quantity = '$new_stock' WHERE product_id = '$product_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Stock updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating stock: " . mysqli_error($con);
    }
}

function toggleLowStockAlert($con, $product_id) {
    $query = "UPDATE products SET low_stock_alert = NOT low_stock_alert WHERE product_id = '$product_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Low stock alert updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating low stock alert: " . mysqli_error($con);
    }
}

function bulkUpdateStock($con) {
    if (isset($_POST['product_ids']) && isset($_POST['stock_values'])) {
        $product_ids = $_POST['product_ids'];
        $stock_values = $_POST['stock_values'];
        
        for ($i = 0; $i < count($product_ids); $i++) {
            $product_id = mysqli_real_escape_string($con, $product_ids[$i]);
            $stock_value = mysqli_real_escape_string($con, $stock_values[$i]);
            
            $query = "UPDATE products SET stock_quantity = '$stock_value' WHERE product_id = '$product_id'";
            mysqli_query($con, $query);
        }
        
        $_SESSION['success'] = "Bulk stock update completed successfully!";
    }
}

function updateInventoryItem($con) {
    $product_id = mysqli_real_escape_string($con, $_POST['product_id']);
    $stock_quantity = mysqli_real_escape_string($con, $_POST['stock_quantity']);
    $low_stock_threshold = mysqli_real_escape_string($con, $_POST['low_stock_threshold']);
    $restock_quantity = mysqli_real_escape_string($con, $_POST['restock_quantity']);
    
    $query = "UPDATE products SET 
              stock_quantity = '$stock_quantity',
              low_stock_threshold = '$low_stock_threshold',
              restock_quantity = '$restock_quantity'
              WHERE product_id = '$product_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Inventory updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating inventory: " . mysqli_error($con);
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
                        <div class="header-icon-container bg-danger rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">warehouse</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Inventory Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage product stock levels and inventory</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-danger" data-toggle="modal" data-target="#bulkUpdateModal">
                        <i class="material-icons mr-2">update</i>Bulk Update
                    </button>
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

        <!-- Inventory Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">inventory</i>
                        </div>
                        <p class="card-category">Total Products</p>
                        <h3 class="card-title">
                            <?php
                            $total_query = "SELECT COUNT(*) as total FROM products";
                            $total_result = mysqli_query($con, $total_query);
                            $total_data = mysqli_fetch_assoc($total_result);
                            echo $total_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <p class="card-category">In Stock</p>
                        <h3 class="card-title">
                            <?php
                            $in_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0";
                            $in_stock_result = mysqli_query($con, $in_stock_query);
                            $in_stock_data = mysqli_fetch_assoc($in_stock_result);
                            echo $in_stock_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">warning</i>
                        </div>
                        <p class="card-category">Low Stock</p>
                        <h3 class="card-title">
                            <?php
                            $low_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity <= 5 AND stock_quantity > 0";
                            $low_stock_result = mysqli_query($con, $low_stock_query);
                            $low_stock_data = mysqli_fetch_assoc($low_stock_result);
                            echo $low_stock_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">cancel</i>
                        </div>
                        <p class="card-category">Out of Stock</p>
                        <h3 class="card-title">
                            <?php
                            $out_of_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0";
                            $out_of_stock_result = mysqli_query($con, $out_of_stock_query);
                            $out_of_stock_data = mysqli_fetch_assoc($out_of_stock_result);
                            echo $out_of_stock_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Tabs -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-tabs card-header-tabs" id="inventoryTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">
                            All Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="low-stock-tab" data-toggle="tab" href="#low-stock" role="tab">
                            Low Stock <span class="badge badge-warning" id="lowStockCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="out-of-stock-tab" data-toggle="tab" href="#out-of-stock" role="tab">
                            Out of Stock <span class="badge badge-danger" id="outOfStockCount">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="inventoryTabsContent">
                    <!-- All Products Tab -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="inventoryTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT p.*, c.cat_title, b.brand_title 
                                             FROM products p 
                                             LEFT JOIN categories c ON p.product_cat = c.cat_id 
                                             LEFT JOIN brands b ON p.product_brand = b.brand_id 
                                             ORDER BY p.stock_quantity ASC";
                                    $result = mysqli_query($con, $query);
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while($product = mysqli_fetch_assoc($result)):
                                            $stock_status = getStockStatus($product['stock_quantity']);
                                            $stock_value = format_store_price($product['stock_quantity'] * $product['product_price'], $con);
                                    ?>
                                    <tr>
                                        <td>#<?php echo $product['product_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_title']); ?></strong>
                                            <?php if ($product['brand_title']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($product['brand_title']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['cat_title'] ?? 'Uncategorized'); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="mr-2"><?php echo $product['stock_quantity']; ?></span>
                                                <div class="stock-bar" style="width: 100px; height: 6px; background: #e9ecef; border-radius: 3px;">
                                                    <div class="stock-level" style="width: <?php echo min(100, ($product['stock_quantity'] / 50) * 100); ?>%; height: 100%; background: <?php echo $stock_status['color']; ?>; border-radius: 3px;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $stock_status['badge']; ?>">
                                                <?php echo $stock_status['text']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($stock_value); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#updateInventoryModal"
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['product_title']); ?>"
                                                        data-product-stock="<?php echo $product['stock_quantity']; ?>"
                                                        data-product-threshold="<?php echo $product['low_stock_threshold'] ?? 5; ?>"
                                                        data-product-restock="<?php echo $product['restock_quantity'] ?? 25; ?>">
                                                    <i class="material-icons" style="font-size:16px;">edit</i>
                                                </button>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown">
                                                        <i class="material-icons" style="font-size:16px;">add</i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo $product['stock_quantity'] + 1; ?>">+1</a>
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo $product['stock_quantity'] + 5; ?>">+5</a>
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo $product['stock_quantity'] + 10; ?>">+10</a>
                                                    </div>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-warning dropdown-toggle" data-toggle="dropdown">
                                                        <i class="material-icons" style="font-size:16px;">remove</i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo max(0, $product['stock_quantity'] - 1); ?>">-1</a>
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo max(0, $product['stock_quantity'] - 5); ?>">-5</a>
                                                        <a class="dropdown-item" href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo max(0, $product['stock_quantity'] - 10); ?>">-10</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center py-4">No products found.</td></tr>';
                                    }

                                    function getStockStatus($stock) {
                                        if ($stock == 0) {
                                            return ['text' => 'Out of Stock', 'badge' => 'danger', 'color' => '#dc3545'];
                                        } elseif ($stock <= 5) {
                                            return ['text' => 'Low Stock', 'badge' => 'warning', 'color' => '#ffc107'];
                                        } else {
                                            return ['text' => 'In Stock', 'badge' => 'success', 'color' => '#28a745'];
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Tab -->
                    <div class="tab-pane fade" id="low-stock" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Current Stock</th>
                                        <th>Threshold</th>
                                        <th>Urgency</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $low_stock_query = "SELECT p.*, c.cat_title 
                                                       FROM products p 
                                                       LEFT JOIN categories c ON p.product_cat = c.cat_id 
                                                       WHERE p.stock_quantity <= 5 AND p.stock_quantity > 0 
                                                       ORDER BY p.stock_quantity ASC";
                                    $low_stock_result = mysqli_query($con, $low_stock_query);
                                    
                                    if ($low_stock_result && mysqli_num_rows($low_stock_result) > 0) {
                                        while($product = mysqli_fetch_assoc($low_stock_result)):
                                            $urgency = $product['stock_quantity'] == 1 ? 'High' : ($product['stock_quantity'] <= 3 ? 'Medium' : 'Low');
                                            $urgency_class = $product['stock_quantity'] == 1 ? 'danger' : ($product['stock_quantity'] <= 3 ? 'warning' : 'info');
                                    ?>
                                    <tr>
                                        <td>#<?php echo $product['product_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($product['cat_title'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-<?php echo $urgency_class; ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['low_stock_threshold'] ?? 5; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $urgency_class; ?>">
                                                <?php echo $urgency; ?> Priority
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo $product['stock_quantity'] + 25; ?>" 
                                                   class="btn btn-success">
                                                    <i class="material-icons mr-1" style="font-size:16px;">add</i>Restock
                                                </a>
                                                <a href="inventory.php?action=low_stock_alert&id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-outline-<?php echo $product['low_stock_alert'] ? 'warning' : 'secondary'; ?>">
                                                    <i class="material-icons" style="font-size:16px;"><?php echo $product['low_stock_alert'] ? 'notifications_active' : 'notifications_off'; ?></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center py-4">No low stock products found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Out of Stock Tab -->
                    <div class="tab-pane fade" id="out-of-stock" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Last Stock Date</th>
                                        <th>Restock Needed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $out_of_stock_query = "SELECT p.*, c.cat_title 
                                                          FROM products p 
                                                          LEFT JOIN categories c ON p.product_cat = c.cat_id 
                                                          WHERE p.stock_quantity = 0 
                                                          ORDER BY p.updated_at DESC";
                                    $out_of_stock_result = mysqli_query($con, $out_of_stock_query);
                                    
                                    if ($out_of_stock_result && mysqli_num_rows($out_of_stock_result) > 0) {
                                        while($product = mysqli_fetch_assoc($out_of_stock_result)):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $product['product_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($product['cat_title'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['cat_title'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($product['updated_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-danger">Urgent</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="inventory.php?action=update_stock&id=<?php echo $product['product_id']; ?>&stock=<?php echo $product['restock_quantity'] ?? 25; ?>" 
                                                   class="btn btn-success">
                                                    <i class="material-icons mr-1" style="font-size:16px;">add</i>Restock
                                                </a>
                                                <a href="products_list.php?action=edit&id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="material-icons" style="font-size:16px;">edit</i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center py-4">No out of stock products found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Stock Update</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>New Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $bulk_query = "SELECT product_id, product_title, stock_quantity FROM products ORDER BY product_title LIMIT 10";
                                $bulk_result = mysqli_query($con, $bulk_query);
                                
                                if ($bulk_result && mysqli_num_rows($bulk_result) > 0) {
                                    while($product = mysqli_fetch_assoc($bulk_result)):
                                ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="product_ids[]" value="<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_title']); ?>
                                    </td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <input type="number" name="stock_values[]" class="form-control form-control-sm" value="<?php echo $product['stock_quantity']; ?>" min="0">
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="bulk_update" class="btn btn-danger">Update All</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Inventory Modal -->
<div class="modal fade" id="updateInventoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Inventory</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="update_product_id">
                    <div class="form-group">
                        <label>Product</label>
                        <input type="text" class="form-control" id="update_product_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Current Stock *</label>
                        <input type="number" name="stock_quantity" class="form-control" id="update_stock_quantity" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control" id="update_low_stock_threshold" min="1" value="5">
                        <small class="form-text text-muted">Alert when stock falls below this number</small>
                    </div>
                    <div class="form-group">
                        <label>Restock Quantity</label>
                        <input type="number" name="restock_quantity" class="form-control" id="update_restock_quantity" min="1" value="25">
                        <small class="form-text text-muted">Default quantity to restock</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_inventory" class="btn btn-primary">Update Inventory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update inventory counts
document.addEventListener('DOMContentLoaded', function() {
    // These would normally come from server-side calculations
    const lowStockCount = <?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM products WHERE stock_quantity <= 5 AND stock_quantity > 0")); ?>;
    const outOfStockCount = <?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM products WHERE stock_quantity = 0")); ?>;
    
    document.getElementById('lowStockCount').textContent = lowStockCount;
    document.getElementById('outOfStockCount').textContent = outOfStockCount;
});

// Update inventory modal handler
$('#updateInventoryModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var productId = button.data('product-id');
    var productName = button.data('product-name');
    var productStock = button.data('product-stock');
    var productThreshold = button.data('product-threshold');
    var productRestock = button.data('product-restock');
    
    var modal = $(this);
    modal.find('#update_product_id').val(productId);
    modal.find('#update_product_name').val(productName);
    modal.find('#update_stock_quantity').val(productStock);
    modal.find('#update_low_stock_threshold').val(productThreshold);
    modal.find('#update_restock_quantity').val(productRestock);
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search products...';
    searchInput.className = 'form-control form-control-sm';
    searchInput.style.marginBottom = '15px';
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#inventoryTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    document.querySelector('.card-header').appendChild(searchInput);
});
</script>

<?php
include "footer.php";
?>