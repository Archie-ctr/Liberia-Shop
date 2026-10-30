<?php
// customers.php
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

// Check and add missing columns if they don't exist
$check_columns = [
    'status' => "ALTER TABLE user_info ADD COLUMN status TINYINT DEFAULT 1",
    'created_at' => "ALTER TABLE user_info ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    'phone' => "ALTER TABLE user_info ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
    'address1' => "ALTER TABLE user_info ADD COLUMN address1 TEXT DEFAULT NULL",
    'address2' => "ALTER TABLE user_info ADD COLUMN address2 TEXT DEFAULT NULL"
];

foreach ($check_columns as $column => $alter_query) {
    $check_column = "SHOW COLUMNS FROM user_info LIKE '$column'";
    $result = mysqli_query($con, $check_column);
    if ($result && mysqli_num_rows($result) == 0) {
        mysqli_query($con, $alter_query);
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'view':
            viewCustomer($con, $user_id);
            break;
        case 'edit':
            editCustomer($con, $user_id);
            break;
        case 'delete':
            deleteCustomer($con, $user_id);
            break;
        case 'activate':
            toggleCustomerStatus($con, $user_id, 1);
            break;
        case 'deactivate':
            toggleCustomerStatus($con, $user_id, 0);
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_customer'])) {
        updateCustomer($con);
    } elseif (isset($_POST['add_customer'])) {
        addCustomer($con);
    } elseif (isset($_POST['search_customers'])) {
        // Search functionality handled in display
    }
}

function viewCustomer($con, $user_id) {
    $query = "SELECT * FROM user_info WHERE user_id = '$user_id'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Customer not found.";
        return;
    }
    
    $customer = mysqli_fetch_assoc($result);
    
    // Get customer orders count
    $orders_query = "SELECT COUNT(*) as order_count FROM orders_info WHERE user_id = '$user_id'";
    $orders_result = mysqli_query($con, $orders_query);
    $orders_data = $orders_result ? mysqli_fetch_assoc($orders_result) : ['order_count' => 0];
    $order_count = $orders_data['order_count'];
    
    // Get total spent
    $spent_query = "SELECT SUM(total_amount) as total_spent FROM orders_info WHERE user_id = '$user_id' AND payment_status = 'paid'";
    $spent_result = mysqli_query($con, $spent_query);
    $spent_data = $spent_result ? mysqli_fetch_assoc($spent_result) : ['total_spent' => 0];
    $total_spent = $spent_data['total_spent'] ?: 0;
    
    ?>
    <div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo isset($customer['phone']) && !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Address Information</h6>
                            <p><strong>Address 1:</strong> <?php echo isset($customer['address1']) && !empty($customer['address1']) ? htmlspecialchars($customer['address1']) : 'N/A'; ?></p>
                            <p><strong>Address 2:</strong> <?php echo isset($customer['address2']) && !empty($customer['address2']) ? htmlspecialchars($customer['address2']) : 'N/A'; ?></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Account Information</h6>
                            <p><strong>Total Orders:</strong> <?php echo $order_count; ?></p>
                            <p><strong>Total Spent:</strong> $<?php echo number_format($total_spent, 2); ?></p>
                            <p><strong>Member Since:</strong> 
                                <?php 
                                if (isset($customer['created_at'])) {
                                    echo date('M j, Y', strtotime($customer['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Account Status</h6>
                            <p>
                                <strong>Status:</strong> 
                                <span class="badge badge-<?php echo ($customer['status'] ?? 1) == 1 ? 'success' : 'secondary'; ?>">
                                    <?php echo ($customer['status'] ?? 1) == 1 ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a href="customers.php?action=edit&id=<?php echo $user_id; ?>" class="btn btn-primary">Edit Customer</a>
                </div>
            </div>
        </div>
    </div>
    <script>$("#customerModal").modal("show");</script>
    <?php
}

function editCustomer($con, $user_id) {
    $query = "SELECT * FROM user_info WHERE user_id = '$user_id'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Customer not found.";
        return;
    }
    
    $customer = mysqli_fetch_assoc($result);
    ?>
    <div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo isset($customer['phone']) ? htmlspecialchars($customer['phone']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Address 1</label>
                                    <input type="text" name="address1" class="form-control" value="<?php echo isset($customer['address1']) ? htmlspecialchars($customer['address1']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Address 2</label>
                                    <input type="text" name="address2" class="form-control" value="<?php echo isset($customer['address2']) ? htmlspecialchars($customer['address2']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Account Status</label>
                                    <select name="status" class="form-control">
                                        <option value="1" <?php echo ($customer['status'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo ($customer['status'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_customer" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>$("#editCustomerModal").modal("show");</script>
    <?php
}

function updateCustomer($con) {
    $user_id = mysqli_real_escape_string($con, $_POST['user_id']);
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone'] ?? '');
    $address1 = mysqli_real_escape_string($con, $_POST['address1'] ?? '');
    $address2 = mysqli_real_escape_string($con, $_POST['address2'] ?? '');
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Check if email already exists (excluding current user)
    $check_email = "SELECT user_id FROM user_info WHERE email = '$email' AND user_id != '$user_id'";
    $email_result = mysqli_query($con, $check_email);
    
    if ($email_result && mysqli_num_rows($email_result) > 0) {
        $_SESSION['error'] = "Email already exists. Please use a different email.";
        return;
    }
    
    // Build update query dynamically based on available columns
    $update_fields = [
        "first_name = '$first_name'",
        "last_name = '$last_name'",
        "email = '$email'",
        "status = '$status'"
    ];
    
    // Check if phone column exists and add to update if it does
    $check_phone = "SHOW COLUMNS FROM user_info LIKE 'phone'";
    $phone_result = mysqli_query($con, $check_phone);
    if ($phone_result && mysqli_num_rows($phone_result) > 0) {
        $update_fields[] = "phone = '$phone'";
    }
    
    // Check if address1 column exists and add to update if it does
    $check_address1 = "SHOW COLUMNS FROM user_info LIKE 'address1'";
    $address1_result = mysqli_query($con, $check_address1);
    if ($address1_result && mysqli_num_rows($address1_result) > 0) {
        $update_fields[] = "address1 = '$address1'";
    }
    
    // Check if address2 column exists and add to update if it does
    $check_address2 = "SHOW COLUMNS FROM user_info LIKE 'address2'";
    $address2_result = mysqli_query($con, $check_address2);
    if ($address2_result && mysqli_num_rows($address2_result) > 0) {
        $update_fields[] = "address2 = '$address2'";
    }
    
    $query = "UPDATE user_info SET " . implode(', ', $update_fields) . " WHERE user_id = '$user_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Customer updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating customer: " . mysqli_error($con);
    }
}

function deleteCustomer($con, $user_id) {
    // Check if customer has orders
    $check_orders = "SELECT COUNT(*) as order_count FROM orders_info WHERE user_id = '$user_id'";
    $result = mysqli_query($con, $check_orders);
    $data = $result ? mysqli_fetch_assoc($result) : ['order_count' => 0];
    
    if ($data['order_count'] > 0) {
        $_SESSION['error'] = "Cannot delete customer with existing orders. Please deactivate instead.";
        return;
    }
    
    $query = "DELETE FROM user_info WHERE user_id = '$user_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Customer deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting customer: " . mysqli_error($con);
    }
}

function toggleCustomerStatus($con, $user_id, $status) {
    $query = "UPDATE user_info SET status = '$status' WHERE user_id = '$user_id'";
    if (mysqli_query($con, $query)) {
        $status_text = $status ? 'activated' : 'deactivated';
        $_SESSION['success'] = "Customer $status_text successfully!";
    } else {
        $_SESSION['error'] = "Error updating customer status: " . mysqli_error($con);
    }
}

function addCustomer($con) {
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone'] ?? '');
    $address1 = mysqli_real_escape_string($con, $_POST['address1'] ?? '');
    $address2 = mysqli_real_escape_string($con, $_POST['address2'] ?? '');
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        return;
    }
    
    // Check if email already exists
    $check_email = "SELECT user_id FROM user_info WHERE email = '$email'";
    $email_result = mysqli_query($con, $check_email);
    
    if ($email_result && mysqli_num_rows($email_result) > 0) {
        $_SESSION['error'] = "Email already exists. Please use a different email.";
        return;
    }
    
    // Hash password
    $hashed_password = md5($password);
    
    // Build insert query dynamically based on available columns
    $columns = ["first_name", "last_name", "email", "password", "status"];
    $values = ["'$first_name'", "'$last_name'", "'$email'", "'$hashed_password'", "1"];
    
    // Check if phone column exists and add to insert if it does
    $check_phone = "SHOW COLUMNS FROM user_info LIKE 'phone'";
    $phone_result = mysqli_query($con, $check_phone);
    if ($phone_result && mysqli_num_rows($phone_result) > 0 && !empty($phone)) {
        $columns[] = "phone";
        $values[] = "'$phone'";
    }
    
    // Check if address1 column exists and add to insert if it does
    $check_address1 = "SHOW COLUMNS FROM user_info LIKE 'address1'";
    $address1_result = mysqli_query($con, $check_address1);
    if ($address1_result && mysqli_num_rows($address1_result) > 0 && !empty($address1)) {
        $columns[] = "address1";
        $values[] = "'$address1'";
    }
    
    // Check if address2 column exists and add to insert if it does
    $check_address2 = "SHOW COLUMNS FROM user_info LIKE 'address2'";
    $address2_result = mysqli_query($con, $check_address2);
    if ($address2_result && mysqli_num_rows($address2_result) > 0 && !empty($address2)) {
        $columns[] = "address2";
        $values[] = "'$address2'";
    }
    
    $query = "INSERT INTO user_info (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Customer added successfully!";
    } else {
        $_SESSION['error'] = "Error adding customer: " . mysqli_error($con);
    }
}

// Function to safely get customer count without created_at dependency
function getCustomerCount($con, $type = 'total') {
    switch ($type) {
        case 'total':
            $query = "SELECT COUNT(*) as total FROM user_info";
            break;
        case 'active':
            $query = "SELECT COUNT(*) as total FROM user_info WHERE status = 1";
            break;
        case 'new_week':
            // Check if created_at exists, if not use a simple count
            $check_column = "SHOW COLUMNS FROM user_info LIKE 'created_at'";
            $result = mysqli_query($con, $check_column);
            if ($result && mysqli_num_rows($result) > 0) {
                $query = "SELECT COUNT(*) as total FROM user_info WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } else {
                $query = "SELECT COUNT(*) as total FROM user_info";
            }
            break;
        case 'with_orders':
            $query = "SELECT COUNT(DISTINCT user_id) as total FROM orders_info";
            break;
        default:
            return 0;
    }
    
    $result = mysqli_query($con, $query);
    return $result ? mysqli_fetch_assoc($result)['total'] : 0;
}
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        
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

        <!-- Customer Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php echo getCustomerCount($con, 'total'); ?>
                                </h4>
                                <p class="mb-0">Total Customers</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">group</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php echo getCustomerCount($con, 'active'); ?>
                                </h4>
                                <p class="mb-0">Active Customers</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">check_circle</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php echo getCustomerCount($con, 'new_week'); ?>
                                </h4>
                                <p class="mb-0">New This Week</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">new_releases</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php echo getCustomerCount($con, 'with_orders'); ?>
                                </h4>
                                <p class="mb-0">Customers with Orders</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">shopping_cart</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">list</i>
                            All Customers
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="" class="d-flex">
                            <div class="input-group">
                                <input type="text" name="search_query" class="form-control" placeholder="Search customers..." value="<?php echo $_POST['search_query'] ?? ''; ?>">
                                <div class="input-group-append">
                                    <button type="submit" name="search_customers" class="btn btn-outline-primary">
                                        <i class="material-icons">search</i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Orders</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build search query
                            $search_query = "";
                            if (isset($_POST['search_query']) && !empty($_POST['search_query'])) {
                                $search_term = mysqli_real_escape_string($con, $_POST['search_query']);
                                $search_query = " WHERE (first_name LIKE '%$search_term%' OR 
                                                       last_name LIKE '%$search_term%' OR 
                                                       email LIKE '%$search_term%')";
                                
                                // Add phone to search if the column exists
                                $check_phone = "SHOW COLUMNS FROM user_info LIKE 'phone'";
                                $phone_result = mysqli_query($con, $check_phone);
                                if ($phone_result && mysqli_num_rows($phone_result) > 0) {
                                    $search_query .= " OR phone LIKE '%$search_term%'";
                                }
                            }
                            
                            $query = "SELECT u.*, 
                                     (SELECT COUNT(*) FROM orders_info o WHERE o.user_id = u.user_id) as order_count
                                     FROM user_info u 
                                     $search_query
                                     ORDER BY u.user_id DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($customer = mysqli_fetch_assoc($result)):
                                    $status = $customer['status'] ?? 1;
                            ?>
                            <tr>
                                <td>#<?php echo $customer['user_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo isset($customer['phone']) && !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A'; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        $address = isset($customer['address1']) ? $customer['address1'] : '';
                                        if (isset($customer['address2']) && !empty($customer['address2'])) {
                                            $address .= ', ' . $customer['address2'];
                                        }
                                        echo $address ? htmlspecialchars(substr($address, 0, 30)) . (strlen($address) > 30 ? '...' : '') : 'N/A';
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $customer['order_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($status == 1): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        if (isset($customer['created_at'])) {
                                            echo date('M j, Y', strtotime($customer['created_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="viewCustomer(<?php echo $customer['user_id']; ?>)">
                                            <i class="material-icons" style="font-size:16px;">visibility</i>
                                        </button>
                                        <button class="btn btn-outline-warning" 
                                                onclick="editCustomer(<?php echo $customer['user_id']; ?>)">
                                            <i class="material-icons" style="font-size:16px;">edit</i>
                                        </button>
                                        <?php if ($status == 1): ?>
                                            <a href="customers.php?action=deactivate&id=<?php echo $customer['user_id']; ?>" 
                                               class="btn btn-outline-secondary" 
                                               onclick="return confirm('Deactivate this customer?')">
                                                <i class="material-icons" style="font-size:16px;">pause</i>
                                            </a>
                                        <?php else: ?>
                                            <a href="customers.php?action=activate&id=<?php echo $customer['user_id']; ?>" 
                                               class="btn btn-outline-success" 
                                               onclick="return confirm('Activate this customer?')">
                                                <i class="material-icons" style="font-size:16px;">play_arrow</i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="customers.php?action=delete&id=<?php echo $customer['user_id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="9" class="text-center py-4">No customers found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="" id="addCustomerForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" class="form-control" required minlength="6" id="password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6" id="confirm_password">
                                <small class="text-danger" id="passwordError" style="display: none;">Passwords do not match</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Address 1</label>
                                <input type="text" name="address1" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Address 2</label>
                                <input type="text" name="address2" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewCustomer(userId) {
    window.location.href = 'customers.php?action=view&id=' + userId;
}

function editCustomer(userId) {
    window.location.href = 'customers.php?action=edit&id=' + userId;
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordError = document.getElementById('passwordError');
    const form = document.getElementById('addCustomerForm');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            passwordError.style.display = 'block';
            confirmPassword.setCustomValidity("Passwords don't match");
            return false;
        } else {
            passwordError.style.display = 'none';
            confirmPassword.setCustomValidity('');
            return true;
        }
    }
    
    if (password && confirmPassword) {
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    }
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php
include "footer.php";
?>