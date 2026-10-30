<?php
// shipping_settings.php
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

// Initialize variables
$success = '';
$error = '';
$shipping_methods = [];

// Check if table exists, if not redirect to create it
$check_table = "SHOW TABLES LIKE 'shipping_settings'";
$table_result = mysqli_query($con, $check_table);

if (!$table_result || mysqli_num_rows($table_result) == 0) {
    header('location: create_shipping_table.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_shipping_method'])) {
        addShippingMethod($con);
    } elseif (isset($_POST['update_shipping_method'])) {
        updateShippingMethod($con);
    } elseif (isset($_POST['delete_shipping_method'])) {
        deleteShippingMethod($con);
    } elseif (isset($_POST['toggle_shipping_method'])) {
        toggleShippingMethod($con);
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $method_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'delete':
            deleteShippingMethod($con, $method_id);
            break;
        case 'toggle':
            toggleShippingMethod($con, $method_id);
            break;
    }
}

function addShippingMethod($con) {
    $method_name = mysqli_real_escape_string($con, trim($_POST['method_name']));
    $method_type = mysqli_real_escape_string($con, $_POST['method_type']);
    $cost = mysqli_real_escape_string($con, $_POST['cost'] ?? 0);
    $free_shipping_threshold = mysqli_real_escape_string($con, $_POST['free_shipping_threshold'] ?? 0);
    $minimum_order_amount = mysqli_real_escape_string($con, $_POST['minimum_order_amount'] ?? 0);
    $maximum_order_amount = mysqli_real_escape_string($con, $_POST['maximum_order_amount'] ?? 0);
    $estimated_delivery_days = mysqli_real_escape_string($con, $_POST['estimated_delivery_days'] ?? 3);
    $weight_min = mysqli_real_escape_string($con, $_POST['weight_min'] ?? 0);
    $weight_max = mysqli_real_escape_string($con, $_POST['weight_max'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = mysqli_real_escape_string($con, $_POST['sort_order'] ?? 0);
    
    // Validate required fields
    if (empty($method_name) || empty($method_type)) {
        $_SESSION['error'] = "Method name and type are required fields.";
        return;
    }
    
    $query = "INSERT INTO shipping_settings (
        method_name, method_type, cost, free_shipping_threshold, 
        minimum_order_amount, maximum_order_amount, estimated_delivery_days,
        weight_min, weight_max, is_active, sort_order
    ) VALUES (
        '$method_name', '$method_type', '$cost', '$free_shipping_threshold',
        '$minimum_order_amount', '$maximum_order_amount', '$estimated_delivery_days',
        '$weight_min', '$weight_max', '$is_active', '$sort_order'
    )";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Shipping method added successfully!";
    } else {
        $_SESSION['error'] = "Error adding shipping method: " . mysqli_error($con);
    }
}

function updateShippingMethod($con) {
    $method_id = mysqli_real_escape_string($con, $_POST['method_id']);
    $method_name = mysqli_real_escape_string($con, trim($_POST['method_name']));
    $method_type = mysqli_real_escape_string($con, $_POST['method_type']);
    $cost = mysqli_real_escape_string($con, $_POST['cost'] ?? 0);
    $free_shipping_threshold = mysqli_real_escape_string($con, $_POST['free_shipping_threshold'] ?? 0);
    $minimum_order_amount = mysqli_real_escape_string($con, $_POST['minimum_order_amount'] ?? 0);
    $maximum_order_amount = mysqli_real_escape_string($con, $_POST['maximum_order_amount'] ?? 0);
    $estimated_delivery_days = mysqli_real_escape_string($con, $_POST['estimated_delivery_days'] ?? 3);
    $weight_min = mysqli_real_escape_string($con, $_POST['weight_min'] ?? 0);
    $weight_max = mysqli_real_escape_string($con, $_POST['weight_max'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = mysqli_real_escape_string($con, $_POST['sort_order'] ?? 0);
    
    $query = "UPDATE shipping_settings SET 
        method_name = '$method_name',
        method_type = '$method_type',
        cost = '$cost',
        free_shipping_threshold = '$free_shipping_threshold',
        minimum_order_amount = '$minimum_order_amount',
        maximum_order_amount = '$maximum_order_amount',
        estimated_delivery_days = '$estimated_delivery_days',
        weight_min = '$weight_min',
        weight_max = '$weight_max',
        is_active = '$is_active',
        sort_order = '$sort_order'
        WHERE id = '$method_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Shipping method updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating shipping method: " . mysqli_error($con);
    }
}

function deleteShippingMethod($con, $method_id = null) {
    if (!$method_id) {
        $method_id = mysqli_real_escape_string($con, $_POST['method_id']);
    }
    
    $query = "DELETE FROM shipping_settings WHERE id = '$method_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Shipping method deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting shipping method: " . mysqli_error($con);
    }
}

function toggleShippingMethod($con, $method_id = null) {
    if (!$method_id) {
        $method_id = mysqli_real_escape_string($con, $_POST['method_id']);
    }
    
    // Get current status
    $current_query = "SELECT is_active FROM shipping_settings WHERE id = '$method_id'";
    $result = mysqli_query($con, $current_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $method = mysqli_fetch_assoc($result);
        $new_status = $method['is_active'] ? 0 : 1;
        
        $query = "UPDATE shipping_settings SET is_active = '$new_status' WHERE id = '$method_id'";
        if (mysqli_query($con, $query)) {
            $status_text = $new_status ? 'enabled' : 'disabled';
            $_SESSION['success'] = "Shipping method $status_text successfully!";
        } else {
            $_SESSION['error'] = "Error updating shipping method: " . mysqli_error($con);
        }
    }
}

// Load shipping methods
$query = "SELECT * FROM shipping_settings ORDER BY sort_order, method_name";
$result = mysqli_query($con, $query);

if ($result) {
    $shipping_methods = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $_SESSION['error'] = "Error loading shipping methods: " . mysqli_error($con);
}

// Get shipping zones data for display
$shipping_zones = [
    'local' => [
        'cost' => '3.00',
        'delivery_time' => '1-2 days'
    ],
    'west_africa' => [
        'cost' => '15.00',
        'delivery_time' => '5-7 days'
    ],
    'international' => [
        'cost' => '25.00',
        'delivery_time' => '10-15 days'
    ]
];
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
                            <i class="material-icons text-white">local_shipping</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Shipping Settings</h1>
                            <p class="page-subtitle text-muted mb-0">Manage shipping methods and delivery options</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addShippingModal">
                        <i class="material-icons mr-2">add</i>Add Shipping Method
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

        <!-- Shipping Zones Overview -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-secondary text-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2">map</i>
                    Shipping Zones Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Zone</th>
                                <th>Countries</th>
                                <th>Shipping Method</th>
                                <th>Cost</th>
                                <th>Delivery Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Local</strong></td>
                                <td>Liberia</td>
                                <td>Local Delivery</td>
                                <td>$<?php echo isset($shipping_zones['local']['cost']) ? $shipping_zones['local']['cost'] : '3.00'; ?></td>
                                <td><?php echo isset($shipping_zones['local']['delivery_time']) ? $shipping_zones['local']['delivery_time'] : '1-2 days'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>West Africa</strong></td>
                                <td>Ghana, Nigeria, Ivory Coast, etc.</td>
                                <td>Regional Shipping</td>
                                <td>$<?php echo isset($shipping_zones['west_africa']['cost']) ? $shipping_zones['west_africa']['cost'] : '15.00'; ?></td>
                                <td><?php echo isset($shipping_zones['west_africa']['delivery_time']) ? $shipping_zones['west_africa']['delivery_time'] : '5-7 days'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>International</strong></td>
                                <td>All other countries</td>
                                <td>International Shipping</td>
                                <td>$<?php echo isset($shipping_zones['international']['cost']) ? $shipping_zones['international']['cost'] : '25.00'; ?>+</td>
                                <td><?php echo isset($shipping_zones['international']['delivery_time']) ? $shipping_zones['international']['delivery_time'] : '10-15 days'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shipping Methods List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-primary">list</i>
                    Shipping Methods
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Method Name</th>
                                <th>Type</th>
                                <th>Cost</th>
                                <th>Free Shipping Above</th>
                                <th>Delivery Days</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($shipping_methods)): ?>
                                <?php foreach ($shipping_methods as $method): 
                                    $type_badge = [
                                        'flat_rate' => 'primary',
                                        'free_shipping' => 'success',
                                        'local_pickup' => 'info',
                                        'weight_based' => 'warning',
                                        'price_based' => 'secondary'
                                    ][$method['method_type']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($method['method_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $type_badge; ?> text-capitalize">
                                            <?php echo str_replace('_', ' ', $method['method_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($method['method_type'] === 'free_shipping'): ?>
                                            <span class="text-success">FREE</span>
                                        <?php else: ?>
                                            $<?php echo number_format($method['cost'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($method['free_shipping_threshold'] > 0): ?>
                                            $<?php echo number_format($method['free_shipping_threshold'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $method['estimated_delivery_days']; ?> days
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $method['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $method['sort_order']; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#editShippingModal" 
                                                    data-method='<?php echo htmlspecialchars(json_encode($method), ENT_QUOTES); ?>'>
                                                <i class="material-icons" style="font-size:16px;">edit</i>
                                            </button>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                                <button type="submit" name="toggle_shipping_method" 
                                                        class="btn btn-outline-<?php echo $method['is_active'] ? 'warning' : 'success'; ?>">
                                                    <i class="material-icons" style="font-size:16px;">
                                                        <?php echo $method['is_active'] ? 'visibility_off' : 'visibility'; ?>
                                                    </i>
                                                </button>
                                            </form>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $method['id']; ?>, '<?php echo htmlspecialchars($method['method_name']); ?>')">
                                                <i class="material-icons" style="font-size:16px;">delete</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="material-icons text-muted" style="font-size:48px;">local_shipping</i>
                                        <p class="mt-2">No shipping methods found.</p>
                                        <button class="btn btn-primary mt-2" data-toggle="modal" data-target="#addShippingModal">
                                            Add Your First Shipping Method
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shipping Statistics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons mr-2">map</i>
                            Shipping Zones
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Currently shipping to:</p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Liberia
                                <span class="badge badge-primary badge-pill">All Regions</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                West Africa
                                <span class="badge badge-success badge-pill">Available</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                International
                                <span class="badge badge-warning badge-pill">Limited</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons mr-2">analytics</i>
                            Shipping Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <i class="material-icons text-primary">local_shipping</i>
                                <div class="stat-info">
                                    <h4><?php echo count($shipping_methods); ?></h4>
                                    <span>Active Methods</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="material-icons text-success">check_circle</i>
                                <div class="stat-info">
                                    <h4><?php echo count(array_filter($shipping_methods, function($m) { return $m['is_active']; })); ?></h4>
                                    <span>Enabled</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Shipping Method Modal -->
<div class="modal fade" id="addShippingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Shipping Method</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="" id="addShippingForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Method Name *</label>
                                <input type="text" name="method_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Method Type *</label>
                                <select name="method_type" class="form-control" id="methodType" required>
                                    <option value="flat_rate">Flat Rate</option>
                                    <option value="free_shipping">Free Shipping</option>
                                    <option value="local_pickup">Local Pickup</option>
                                    <option value="weight_based">Weight Based</option>
                                    <option value="price_based">Price Based</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="costFields">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Cost ($)</label>
                                <input type="number" name="cost" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="freeShippingFields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Free Shipping Minimum Order ($)</label>
                                <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Minimum Order Amount ($)</label>
                                <input type="number" name="minimum_order_amount" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Maximum Order Amount ($)</label>
                                <input type="number" name="maximum_order_amount" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Estimated Delivery Days</label>
                                <input type="number" name="estimated_delivery_days" class="form-control" min="1" value="3">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="weightFields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Minimum Weight (kg)</label>
                                <input type="number" name="weight_min" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Maximum Weight (kg)</label>
                                <input type="number" name="weight_max" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                    <label class="form-check-label" for="isActive">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_shipping_method" class="btn btn-primary">Add Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shipping Method Modal -->
<div class="modal fade" id="editShippingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Shipping Method</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="" id="editShippingForm">
                <div class="modal-body">
                    <input type="hidden" name="method_id" id="edit_method_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Method Name *</label>
                                <input type="text" name="method_name" class="form-control" id="edit_method_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Method Type *</label>
                                <select name="method_type" class="form-control" id="edit_method_type" required>
                                    <option value="flat_rate">Flat Rate</option>
                                    <option value="free_shipping">Free Shipping</option>
                                    <option value="local_pickup">Local Pickup</option>
                                    <option value="weight_based">Weight Based</option>
                                    <option value="price_based">Price Based</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="edit_costFields">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Cost ($)</label>
                                <input type="number" name="cost" class="form-control" id="edit_cost" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="edit_freeShippingFields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Free Shipping Minimum Order ($)</label>
                                <input type="number" name="free_shipping_threshold" class="form-control" id="edit_free_shipping_threshold" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Minimum Order Amount ($)</label>
                                <input type="number" name="minimum_order_amount" class="form-control" id="edit_minimum_order_amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Maximum Order Amount ($)</label>
                                <input type="number" name="maximum_order_amount" class="form-control" id="edit_maximum_order_amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Estimated Delivery Days</label>
                                <input type="number" name="estimated_delivery_days" class="form-control" id="edit_estimated_delivery_days" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="edit_weightFields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Minimum Weight (kg)</label>
                                <input type="number" name="weight_min" class="form-control" id="edit_weight_min" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Maximum Weight (kg)</label>
                                <input type="number" name="weight_max" class="form-control" id="edit_weight_max" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_isActive">
                                    <label class="form-check-label" for="edit_isActive">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" id="edit_sort_order">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_shipping_method" class="btn btn-primary">Update Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete shipping method: <strong id="deleteMethodName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST" action="" id="deleteForm">
                    <input type="hidden" name="method_id" id="delete_method_id">
                    <button type="submit" name="delete_shipping_method" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(0,0,0,0.02);
    border-radius: 8px;
}
.stat-item i {
    font-size: 2rem;
}
.stat-info h4 {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
}
.stat-info span {
    color: #666;
    font-size: 0.9rem;
}
</style>

<script>
// Method type change handler
document.getElementById('methodType').addEventListener('change', function() {
    toggleFields(this.value, '');
});
document.getElementById('edit_method_type').addEventListener('change', function() {
    toggleFields(this.value, 'edit_');
});

function toggleFields(methodType, prefix) {
    const costFields = document.getElementById(prefix + 'costFields');
    const freeShippingFields = document.getElementById(prefix + 'freeShippingFields');
    const weightFields = document.getElementById(prefix + 'weightFields');
    
    // Reset all fields
    costFields.style.display = 'none';
    freeShippingFields.style.display = 'none';
    weightFields.style.display = 'none';
    
    switch(methodType) {
        case 'flat_rate':
            costFields.style.display = 'flex';
            break;
        case 'free_shipping':
            freeShippingFields.style.display = 'flex';
            break;
        case 'weight_based':
            costFields.style.display = 'flex';
            weightFields.style.display = 'flex';
            break;
        case 'price_based':
            costFields.style.display = 'flex';
            break;
        case 'local_pickup':
            // No additional fields needed
            break;
    }
}

// Edit modal handler
$('#editShippingModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var method = JSON.parse(button.data('method'));
    var modal = $(this);
    
    // Populate form fields
    modal.find('#edit_method_id').val(method.id);
    modal.find('#edit_method_name').val(method.method_name);
    modal.find('#edit_method_type').val(method.method_type);
    modal.find('#edit_cost').val(method.cost);
    modal.find('#edit_free_shipping_threshold').val(method.free_shipping_threshold);
    modal.find('#edit_minimum_order_amount').val(method.minimum_order_amount);
    modal.find('#edit_maximum_order_amount').val(method.maximum_order_amount);
    modal.find('#edit_estimated_delivery_days').val(method.estimated_delivery_days);
    modal.find('#edit_weight_min').val(method.weight_min);
    modal.find('#edit_weight_max').val(method.weight_max);
    modal.find('#edit_isActive').prop('checked', method.is_active);
    modal.find('#edit_sort_order').val(method.sort_order);
    
    // Toggle fields based on method type
    toggleFields(method.method_type, 'edit_');
});

// Delete confirmation
function confirmDelete(methodId, methodName) {
    document.getElementById('deleteMethodName').textContent = methodName;
    document.getElementById('delete_method_id').value = methodId;
    $('#deleteModal').modal('show');
}

// Initialize fields on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleFields('flat_rate', '');
});
</script>

<?php
include "footer.php";
?>