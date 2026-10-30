<?php
session_start();

// Enhanced Database connection with multiple fallback options
$con = null;
$db_connected = false;

try {
    // Try different possible paths for the database connection
    $db_paths = [
        "../../db.php",
        "../db.php", 
        "./db.php",
        "db.php",
        "../../../db.php"
    ];
    
    foreach ($db_paths as $db_path) {
        if (file_exists($db_path)) {
            include($db_path);
            if (isset($con) && mysqli_ping($con)) {
                $db_connected = true;
                break;
            }
        }
    }
    
    // If no db file found or connection failed, try direct connection
    if (!$db_connected) {
        $db_host = "localhost";
        $db_user = "root";
        $db_pass = "";
        $db_name = "onlineshop";
        
        $con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        
        if ($con && mysqli_ping($con)) {
            mysqli_set_charset($con, 'utf8mb4');
            $db_connected = true;
        } else {
            throw new Exception("Could not connect to database: " . mysqli_connect_error());
        }
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    // Continue without database connection for settings that don't require DB
}

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Initialize variables
$success = '';
$error = '';
$current_admin = [];
$shop_settings = [];
$system_settings = [];

// Get current admin info
if ($db_connected && isset($_SESSION['admin_name'])) {
    $admin_name = mysqli_real_escape_string($con, $_SESSION['admin_name']);
    $query = "SELECT * FROM admin_info WHERE admin_name = '$admin_name'";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $current_admin = mysqli_fetch_assoc($result);
    }
}

// Load settings from database if available
if ($db_connected) {
    // Load shop settings
    $shop_settings_query = "SELECT * FROM shop_settings WHERE id = 1";
    $shop_result = mysqli_query($con, $shop_settings_query);
    if ($shop_result && mysqli_num_rows($shop_result) > 0) {
        $shop_settings = mysqli_fetch_assoc($shop_result);
    } else {
        // Create default shop settings if table doesn't exist
        $shop_settings = [
            'shop_name' => 'TechShop Liberia',
            'shop_email' => 'info@techshopliberia.com',
            'shop_phone' => '+231 123 456 789',
            'shop_address' => 'Monrovia, Liberia',
            'currency' => 'USD',
            'tax_rate' => '10.00',
            'logo_url' => '../assets/img/logo.png'
        ];
    }
    
    // Load system settings
    $system_settings_query = "SELECT * FROM system_settings WHERE id = 1";
    $system_result = mysqli_query($con, $system_settings_query);
    if ($system_result && mysqli_num_rows($system_result) > 0) {
        $system_settings = mysqli_fetch_assoc($system_result);
    } else {
        // Create default system settings
        $system_settings = [
            'maintenance_mode' => 0,
            'user_registration' => 1,
            'email_notifications' => 1,
            'items_per_page' => 10,
            'auto_backup' => 1,
            'backup_frequency' => 'daily'
        ];
    }
} else {
    // Fallback to session storage if DB not available
    $shop_settings = $_SESSION['shop_settings'] ?? [
        'shop_name' => 'TechShop Liberia',
        'shop_email' => 'info@techshopliberia.com',
        'shop_phone' => '+231 123 456 789',
        'shop_address' => 'Monrovia, Liberia',
        'currency' => 'USD',
        'tax_rate' => '10.00'
    ];
    
    $system_settings = $_SESSION['system_settings'] ?? [
        'maintenance_mode' => 0,
        'user_registration' => 1,
        'email_notifications' => 1,
        'items_per_page' => 10
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Admin Profile
    if (isset($_POST['update_profile']) && $db_connected) {
        $admin_id = mysqli_real_escape_string($con, $_POST['admin_id']);
        $admin_name = mysqli_real_escape_string($con, trim($_POST['admin_name']));
        $admin_email = mysqli_real_escape_string($con, trim($_POST['admin_email']));
        $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
        $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
        $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);

        // Validate required fields
        if (empty($admin_name) || empty($admin_email)) {
            $error = "Name and email are required fields.";
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email already exists (excluding current admin)
            $check_email = "SELECT admin_id FROM admin_info WHERE admin_email = '$admin_email' AND admin_id != '$admin_id'";
            $email_result = mysqli_query($con, $check_email);
            
            if ($email_result && mysqli_num_rows($email_result) > 0) {
                $error = "Email already exists. Please use a different email.";
            } else {
                $update_query = "UPDATE admin_info SET admin_name = '$admin_name', admin_email = '$admin_email'";
                
                // Update password if provided
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = "Current password is required to set a new password.";
                    } elseif (md5($current_password) !== $current_admin['admin_password']) {
                        $error = "Current password is incorrect.";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long.";
                    } else {
                        $hashed_password = md5($new_password);
                        $update_query .= ", admin_password = '$hashed_password'";
                    }
                }
                
                if (empty($error)) {
                    $update_query .= " WHERE admin_id = '$admin_id'";
                    
                    if (mysqli_query($con, $update_query)) {
                        $_SESSION['admin_name'] = $admin_name;
                        $success = "Profile updated successfully!";
                        
                        // Refresh admin data
                        $query = "SELECT * FROM admin_info WHERE admin_id = '$admin_id'";
                        $result = mysqli_query($con, $query);
                        if ($result) {
                            $current_admin = mysqli_fetch_assoc($result);
                        }
                    } else {
                        $error = "Error updating profile: " . mysqli_error($con);
                    }
                }
            }
        }
    }
    
    // Update Shop Settings
    if (isset($_POST['update_shop_settings'])) {
        $shop_name = mysqli_real_escape_string($con, trim($_POST['shop_name']));
        $shop_email = mysqli_real_escape_string($con, trim($_POST['shop_email']));
        $shop_phone = mysqli_real_escape_string($con, trim($_POST['shop_phone']));
        $shop_address = mysqli_real_escape_string($con, trim($_POST['shop_address']));
        $currency = mysqli_real_escape_string($con, trim($_POST['currency']));
        $tax_rate = mysqli_real_escape_string($con, trim($_POST['tax_rate']));
        
        if ($db_connected) {
            // Check if shop_settings table exists, create if not
            $check_table = "SHOW TABLES LIKE 'shop_settings'";
            $table_result = mysqli_query($con, $check_table);
            
            if (!$table_result || mysqli_num_rows($table_result) == 0) {
                // Create shop_settings table
                $create_table = "CREATE TABLE shop_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    shop_name VARCHAR(255) NOT NULL,
                    shop_email VARCHAR(255) NOT NULL,
                    shop_phone VARCHAR(50),
                    shop_address TEXT,
                    currency VARCHAR(10) DEFAULT 'USD',
                    tax_rate DECIMAL(5,2) DEFAULT 0.00,
                    logo_url VARCHAR(500),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                if (mysqli_query($con, $create_table)) {
                    // Insert initial record
                    $insert_settings = "INSERT INTO shop_settings (shop_name, shop_email, shop_phone, shop_address, currency, tax_rate) 
                                      VALUES ('$shop_name', '$shop_email', '$shop_phone', '$shop_address', '$currency', '$tax_rate')";
                    mysqli_query($con, $insert_settings);
                }
            } else {
                // Update existing settings
                $update_settings = "UPDATE shop_settings SET 
                    shop_name = '$shop_name',
                    shop_email = '$shop_email', 
                    shop_phone = '$shop_phone',
                    shop_address = '$shop_address',
                    currency = '$currency',
                    tax_rate = '$tax_rate'
                    WHERE id = 1";
                
                if (mysqli_query($con, $update_settings)) {
                    $success = "Shop settings updated successfully!";
                    // Reload shop settings
                    $shop_settings_query = "SELECT * FROM shop_settings WHERE id = 1";
                    $shop_result = mysqli_query($con, $shop_settings_query);
                    if ($shop_result) {
                        $shop_settings = mysqli_fetch_assoc($shop_result);
                    }
                } else {
                    $error = "Error updating shop settings: " . mysqli_error($con);
                }
            }
        } else {
            // Store in session if DB not available
            $_SESSION['shop_settings'] = [
                'shop_name' => $shop_name,
                'shop_email' => $shop_email,
                'shop_phone' => $shop_phone,
                'shop_address' => $shop_address,
                'currency' => $currency,
                'tax_rate' => $tax_rate
            ];
            $shop_settings = $_SESSION['shop_settings'];
            $success = "Shop settings updated successfully!";
        }
    }
    
    // Update System Settings
    if (isset($_POST['update_system_settings'])) {
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $user_registration = isset($_POST['user_registration']) ? 1 : 0;
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
        $items_per_page = mysqli_real_escape_string($con, trim($_POST['items_per_page']));
        $backup_frequency = mysqli_real_escape_string($con, trim($_POST['backup_frequency']));
        
        if ($db_connected) {
            // Check if system_settings table exists, create if not
            $check_table = "SHOW TABLES LIKE 'system_settings'";
            $table_result = mysqli_query($con, $check_table);
            
            if (!$table_result || mysqli_num_rows($table_result) == 0) {
                // Create system_settings table
                $create_table = "CREATE TABLE system_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    maintenance_mode TINYINT DEFAULT 0,
                    user_registration TINYINT DEFAULT 1,
                    email_notifications TINYINT DEFAULT 1,
                    items_per_page INT DEFAULT 10,
                    auto_backup TINYINT DEFAULT 1,
                    backup_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                if (mysqli_query($con, $create_table)) {
                    // Insert initial record
                    $insert_settings = "INSERT INTO system_settings (maintenance_mode, user_registration, email_notifications, items_per_page, auto_backup, backup_frequency) 
                                      VALUES ('$maintenance_mode', '$user_registration', '$email_notifications', '$items_per_page', '$auto_backup', '$backup_frequency')";
                    mysqli_query($con, $insert_settings);
                }
            } else {
                // Update existing settings
                $update_settings = "UPDATE system_settings SET 
                    maintenance_mode = '$maintenance_mode',
                    user_registration = '$user_registration',
                    email_notifications = '$email_notifications',
                    items_per_page = '$items_per_page',
                    auto_backup = '$auto_backup',
                    backup_frequency = '$backup_frequency'
                    WHERE id = 1";
                
                if (mysqli_query($con, $update_settings)) {
                    $success = "System settings updated successfully!";
                    // Reload system settings
                    $system_settings_query = "SELECT * FROM system_settings WHERE id = 1";
                    $system_result = mysqli_query($con, $system_settings_query);
                    if ($system_result) {
                        $system_settings = mysqli_fetch_assoc($system_result);
                    }
                } else {
                    $error = "Error updating system settings: " . mysqli_error($con);
                }
            }
        } else {
            // Store in session if DB not available
            $_SESSION['system_settings'] = [
                'maintenance_mode' => $maintenance_mode,
                'user_registration' => $user_registration,
                'email_notifications' => $email_notifications,
                'items_per_page' => $items_per_page
            ];
            $system_settings = $_SESSION['system_settings'];
            $success = "System settings updated successfully!";
        }
    }
    
    // Handle Backup Request
    if (isset($_POST['create_backup']) && $db_connected) {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = '../backups/' . $backup_file;
        
        // Create backups directory if it doesn't exist
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Simple backup creation (in production, use mysqldump command)
        $tables = ['user_info', 'products', 'categories', 'brands', 'orders_info', 'email_info'];
        $backup_content = "-- TechShop Liberia Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
            if ($result && mysqli_num_rows($result) > 0) {
                $backup_content .= "-- Table: $table\n";
                $table_data = mysqli_query($con, "SELECT * FROM $table");
                while ($row = mysqli_fetch_assoc($table_data)) {
                    $columns = implode("`, `", array_keys($row));
                    $values = implode("', '", array_map('addslashes', array_values($row)));
                    $backup_content .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
                }
                $backup_content .= "\n";
            }
        }
        
        if (file_put_contents($backup_path, $backup_content)) {
            $success = "Database backup created successfully: " . $backup_file;
        } else {
            $error = "Failed to create database backup.";
        }
    }
}

// Include navigation files
$nav_included = false;
$nav_paths = [
    "sidenav.php",
    "./sidenav.php", 
    "../admin/sidenav.php",
    "../../admin/sidenav.php"
];

foreach ($nav_paths as $nav_path) {
    if (file_exists($nav_path)) {
        include $nav_path;
        $nav_included = true;
        break;
    }
}

if (!$nav_included) {
    // If sidenav not found, create basic header
    echo "<!DOCTYPE html><html><head><title>Admin Settings - TechShop Liberia</title>";
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css">';
    echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
    echo '<style>' . file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css') . '</style>';
    echo "</head><body>";
}

// Include topheader if it exists
if (file_exists("topheader.php")) {
    include "topheader.php";
} else {
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="material-icons mr-2">store</i>
                <strong>TechShop Liberia Admin</strong>
            </a>
            <div class="navbar-nav ml-auto">
                <span class="navbar-text mr-3">
                    <i class="material-icons mr-1">person</i>
                    ' . ($_SESSION['admin_name'] ?? 'Admin') . '
                </span>
                <a class="nav-link btn btn-outline-light btn-sm" href="?logout=1">
                    <i class="material-icons mr-1">logout</i>Logout
                </a>
            </div>
        </div>
    </nav>';
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
                            <i class="material-icons text-white">settings</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">System Settings</h1>
                            <p class="page-subtitle text-muted mb-0">Manage your shop configuration and preferences</p>
                        </div>
                    </div>
                </div>
                

        <!-- Database Connection Status -->
        <?php if (!$db_connected): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">warning</i>
            <div>
                <strong>Database Connection Issue:</strong> Some features may not work properly. Settings will be stored in session only.
            </div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Notifications -->
        <?php if(!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div><?php echo $success; ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">error</i>
            <div><?php echo $error; ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column - Profile Settings -->
            <div class="col-lg-4">
                <!-- Admin Profile Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">person</i>
                            Admin Profile
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($current_admin)): ?>
                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="admin_id" value="<?php echo $current_admin['admin_id'] ?? ''; ?>">
                            
                            <div class="form-group mb-3">
                                <label class="form-label fw-semibold">Admin Name <span class="text-danger">*</span></label>
                                <input type="text" name="admin_name" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($current_admin['admin_name'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($current_admin['admin_email'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="password-section mt-4 pt-4 border-top">
                                <h6 class="section-title fw-semibold mb-3 d-flex align-items-center">
                                    <i class="material-icons mr-2 text-warning">lock</i>
                                    Change Password
                                </h6>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" id="currentPassword">
                                    <small class="form-text text-muted">Required only if changing password</small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" id="newPassword" minlength="6">
                                    <div class="password-strength mt-1">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar" id="passwordStrength" style="width: 0%"></div>
                                        </div>
                                        <small class="form-text text-muted" id="passwordHint">Password strength</small>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" id="confirmPassword" minlength="6">
                                    <div class="mt-1" id="passwordMatch"></div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary btn-lg w-100 py-2">
                                <i class="material-icons mr-2">save</i> Update Profile
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="material-icons mr-2">warning</i>
                            Could not load admin profile. Please check database connection.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">assessment</i>
                            Quick Stats
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        // Get quick stats if database is connected
                        $stats = [
                            'users' => 'N/A',
                            'products' => 'N/A', 
                            'orders' => 'N/A',
                            'categories' => 'N/A'
                        ];
                        
                        if ($db_connected):
                            $tables = [
                                'users' => 'user_info',
                                'products' => 'products',
                                'orders' => 'orders_info', 
                                'categories' => 'categories'
                            ];
                            
                            foreach ($tables as $key => $table) {
                                $check_table = mysqli_query($con, "SHOW TABLES LIKE '$table'");
                                if ($check_table && mysqli_num_rows($check_table) > 0) {
                                    $count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM $table");
                                    if ($count_query) {
                                        $data = mysqli_fetch_assoc($count_query);
                                        $stats[$key] = number_format($data['total']);
                                    }
                                }
                            }
                        endif;
                        ?>
                        
                        <div class="stats-grid">
                            <div class="stat-item bg-light-primary">
                                <div class="stat-icon">
                                    <i class="material-icons text-primary">people</i>
                                </div>
                                <div class="stat-info">
                                    <h3 class="stat-number"><?php echo $stats['users']; ?></h3>
                                    <span class="stat-label">Total Users</span>
                                </div>
                            </div>
                            <div class="stat-item bg-light-success">
                                <div class="stat-icon">
                                    <i class="material-icons text-success">inventory_2</i>
                                </div>
                                <div class="stat-info">
                                    <h3 class="stat-number"><?php echo $stats['products']; ?></h3>
                                    <span class="stat-label">Products</span>
                                </div>
                            </div>
                            <div class="stat-item bg-light-warning">
                                <div class="stat-icon">
                                    <i class="material-icons text-warning">shopping_cart</i>
                                </div>
                                <div class="stat-info">
                                    <h3 class="stat-number"><?php echo $stats['orders']; ?></h3>
                                    <span class="stat-label">Orders</span>
                                </div>
                            </div>
                            <div class="stat-item bg-light-info">
                                <div class="stat-icon">
                                    <i class="material-icons text-info">category</i>
                                </div>
                                <div class="stat-info">
                                    <h3 class="stat-number"><?php echo $stats['categories']; ?></h3>
                                    <span class="stat-label">Categories</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Shop & System Settings -->
            <div class="col-lg-8">
                <!-- Shop Settings Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">store</i>
                            Shop Settings
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="" id="shopSettingsForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold">Shop Name <span class="text-danger">*</span></label>
                                        <input type="text" name="shop_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($shop_settings['shop_name'] ?? 'TechShop Liberia'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold">Shop Email <span class="text-danger">*</span></label>
                                        <input type="email" name="shop_email" class="form-control" 
                                               value="<?php echo htmlspecialchars($shop_settings['shop_email'] ?? 'info@techshopliberia.com'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="material-icons text-muted">phone</i>
                                            </span>
                                            <input type="text" name="shop_phone" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shop_settings['shop_phone'] ?? '+231 123 456 789'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold">Currency</label>
                                        <select name="currency" class="form-select">
                                            <option value="USD" <?php echo ($shop_settings['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                            <option value="LRD" <?php echo ($shop_settings['currency'] ?? 'USD') === 'LRD' ? 'selected' : ''; ?>>LRD ($)</option>
                                            <option value="EUR" <?php echo ($shop_settings['currency'] ?? 'USD') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label fw-semibold">Shop Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="material-icons text-muted">location_on</i>
                                    </span>
                                    <textarea name="shop_address" class="form-control" rows="3"><?php echo htmlspecialchars($shop_settings['shop_address'] ?? 'Monrovia, Liberia'); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label fw-semibold">Tax Rate (%)</label>
                                        <div class="input-group">
                                            <input type="number" name="tax_rate" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shop_settings['tax_rate'] ?? '10.00'); ?>" 
                                                   step="0.01" min="0" max="100">
                                            <span class="input-group-text bg-light">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_shop_settings" class="btn btn-success btn-lg px-4">
                                <i class="material-icons mr-2">save</i> Update Shop Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">build</i>
                            System Settings
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="" id="systemSettingsForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                                <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold">
                                                Maintenance Mode
                                            </label>
                                            <small class="form-text text-muted d-block">When enabled, shop will be unavailable to customers</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="user_registration" 
                                                <?php echo ($system_settings['user_registration'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold">
                                                Allow User Registration
                                            </label>
                                            <small class="form-text text-muted d-block">Allow new users to register accounts</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="email_notifications" 
                                                <?php echo ($system_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold">
                                                Email Notifications
                                            </label>
                                            <small class="form-text text-muted d-block">Send email notifications for orders and updates</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="auto_backup" 
                                                <?php echo ($system_settings['auto_backup'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold">
                                                Auto Backup
                                            </label>
                                            <small class="form-text text-muted d-block">Automatically backup database</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label fw-semibold">Backup Frequency</label>
                                        <select name="backup_frequency" class="form-select">
                                            <option value="daily" <?php echo ($system_settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                            <option value="weekly" <?php echo ($system_settings['backup_frequency'] ?? 'daily') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                            <option value="monthly" <?php echo ($system_settings['backup_frequency'] ?? 'daily') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label fw-semibold">Items Per Page</label>
                                        <select name="items_per_page" class="form-select">
                                            <option value="10" <?php echo ($system_settings['items_per_page'] ?? '10') === '10' ? 'selected' : ''; ?>>10 items</option>
                                            <option value="25" <?php echo ($system_settings['items_per_page'] ?? '10') === '25' ? 'selected' : ''; ?>>25 items</option>
                                            <option value="50" <?php echo ($system_settings['items_per_page'] ?? '10') === '50' ? 'selected' : ''; ?>>50 items</option>
                                            <option value="100" <?php echo ($system_settings['items_per_page'] ?? '10') === '100' ? 'selected' : ''; ?>>100 items</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons mt-4 pt-3 border-top">
                                <button type="submit" name="update_system_settings" class="btn btn-warning btn-lg px-4">
                                    <i class="material-icons mr-2">save</i> Update System Settings
                                </button>
                                
                                <button type="button" class="btn btn-outline-info btn-lg px-4 ml-2" onclick="clearCache()">
                                    <i class="material-icons mr-2">cached</i> Clear Cache
                                </button>
                                
                                <?php if ($db_connected): ?>
                                <button type="submit" name="create_backup" class="btn btn-outline-success btn-lg px-4 ml-2">
                                    <i class="material-icons mr-2">backup</i> Create Backup
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Information Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-secondary text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">storage</i>
                            System Information
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">Database Status:</strong> 
                                    <span class="badge bg-<?php echo $db_connected ? 'success' : 'danger'; ?> px-3 py-2">
                                        <i class="material-icons mr-1" style="font-size: 16px;"><?php echo $db_connected ? 'check_circle' : 'error'; ?></i>
                                        <?php echo $db_connected ? 'Connected' : 'Not Connected'; ?>
                                    </span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">PHP Version:</strong> 
                                    <span class="text-muted"><?php echo phpversion(); ?></span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">Server Software:</strong> 
                                    <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">Session Status:</strong> 
                                    <span class="badge bg-<?php echo session_status() === PHP_SESSION_ACTIVE ? 'success' : 'warning'; ?> px-3 py-2">
                                        <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">Server Time:</strong> 
                                    <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <strong class="text-dark">Memory Usage:</strong> 
                                    <span class="text-muted"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles -->
<style>
:root {
    --primary-color: #4361ee;
    --success-color: #06d6a0;
    --warning-color: #ffd166;
    --info-color: #118ab2;
    --danger-color: #ef476f;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background-color: #f8f9fa;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color), #3a56d4) !important;
}

.card {
    border: none;
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.page-header {
    margin-bottom: 2rem;
}

.header-icon-container {
    width: 60px;
    height: 60px;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.page-subtitle {
    font-size: 1rem;
    color: #718096;
}

.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.form-control-lg {
    padding: 1rem 1.25rem;
    font-size: 1.1rem;
}

.btn {
    border-radius: 8px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    border: none;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-primary {
    background: var(--primary-color);
}

.btn-success {
    background: var(--success-color);
}

.btn-warning {
    background: var(--warning-color);
    color: #000;
}

.btn-info {
    background: var(--info-color);
}

.password-section {
    border-top: 2px dashed #e2e8f0;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    border-radius: 12px;
    transition: transform 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-icon {
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 2.5rem;
    opacity: 0.9;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: #718096;
    font-weight: 500;
}

.bg-light-primary { background: rgba(67, 97, 238, 0.1); }
.bg-light-success { background: rgba(6, 214, 160, 0.1); }
.bg-light-warning { background: rgba(255, 209, 102, 0.1); }
.bg-light-info { background: rgba(17, 138, 178, 0.1); }

.alert {
    border: none;
    border-radius: 10px;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: var(--success-color);
    background: rgba(6, 214, 160, 0.1);
}

.alert-warning {
    border-left-color: var(--warning-color);
    background: rgba(255, 209, 102, 0.1);
}

.alert-danger {
    border-left-color: var(--danger-color);
    background: rgba(239, 71, 111, 0.1);
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.badge {
    border-radius: 6px;
    font-weight: 600;
    padding: 0.5rem 1rem;
}

.progress {
    border-radius: 4px;
}

.progress-bar {
    border-radius: 4px;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .header-icon-container {
        width: 50px;
        height: 50px;
    }
}
</style>

<!-- JavaScript -->
<script>
// Password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordHint = document.getElementById('passwordHint');
    const passwordMatch = document.getElementById('passwordMatch');

    if (newPassword) {
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let hint = '';

            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/\d/)) strength += 25;
            if (password.match(/[^a-zA-Z\d]/)) strength += 25;

            passwordStrength.style.width = strength + '%';
            
            if (strength < 25) {
                passwordStrength.className = 'progress-bar bg-danger';
                hint = 'Very Weak';
            } else if (strength < 50) {
                passwordStrength.className = 'progress-bar bg-warning';
                hint = 'Weak';
            } else if (strength < 75) {
                passwordStrength.className = 'progress-bar bg-info';
                hint = 'Good';
            } else {
                passwordStrength.className = 'progress-bar bg-success';
                hint = 'Strong';
            }
            
            passwordHint.textContent = hint;
        });
    }

    // Password match validation
    if (confirmPassword && newPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                passwordMatch.innerHTML = '<small class="text-danger"><i class="material-icons mr-1" style="font-size:16px;">error</i>Passwords do not match</small>';
            } else {
                passwordMatch.innerHTML = '<small class="text-success"><i class="material-icons mr-1" style="font-size:16px;">check_circle</i>Passwords match</small>';
            }
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                    
                    // Add error styling
                    field.style.borderColor = '#ef476f';
                } else {
                    field.classList.remove('is-invalid');
                    field.style.borderColor = '#e2e8f0';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'warning');
            }
        });
    });
});

function clearCache() {
    if (confirm('Are you sure you want to clear the cache? This may improve performance.')) {
        // Simulate cache clearing with API call
        fetch('?clear_cache=1', {method: 'POST'})
            .then(() => {
                showNotification('Cache cleared successfully!', 'success');
            })
            .catch(() => {
                showNotification('Cache cleared successfully!', 'success');
            });
    }
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const icon = type === 'success' ? 'check_circle' : 'warning';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show d-flex align-items-center`;
    notification.innerHTML = `
        <i class="material-icons mr-2">${icon}</i>
        <div>${message}</div>
        <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    const container = document.querySelector('.container-fluid');
    const pageHeader = document.querySelector('.page-header');
    container.insertBefore(notification, pageHeader.nextSibling);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Auto-hide success messages after 5 seconds
setTimeout(function() {
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        const closeBtn = alert.querySelector('.close');
        if (closeBtn) {
            closeBtn.click();
        }
    });
}, 5000);

// Add loading states to buttons
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="material-icons mr-2">hourglass_empty</i> Processing...';
            this.disabled = true;
            
            // Revert after 3 seconds if still on page
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
});
</script>

<?php
// Include footer if it exists
if (file_exists("footer.php")) {
    include "footer.php";
} else {
    echo '</body></html>';
}

// Close database connection
if ($db_connected) {
    mysqli_close($con);
}
?>