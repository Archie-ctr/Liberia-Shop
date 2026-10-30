<?php
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

// Create shop_settings table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS shop_settings (
    id INT PRIMARY KEY DEFAULT 1,
    shop_name VARCHAR(255) NOT NULL DEFAULT 'TechShop Liberia',
    shop_email VARCHAR(255) NOT NULL DEFAULT 'info@techshopliberia.com',
    shop_phone VARCHAR(50) DEFAULT '+231 123 456 789',
    shop_address TEXT DEFAULT 'Monrovia, Liberia',
    shop_description TEXT DEFAULT 'Your premier destination for electronics and technology in Liberia',
    currency VARCHAR(10) DEFAULT 'USD',
    inr_per_usd DECIMAL(12,4) NOT NULL DEFAULT 0,
    lrd_per_usd DECIMAL(12,4) NOT NULL DEFAULT 170,
    timezone VARCHAR(50) DEFAULT 'Africa/Monrovia',
    maintenance_mode TINYINT DEFAULT 0,
    logo_url VARCHAR(500) DEFAULT '',
    favicon_url VARCHAR(500) DEFAULT '',
    social_facebook VARCHAR(255) DEFAULT '',
    social_twitter VARCHAR(255) DEFAULT '',
    social_instagram VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);

foreach ([
    'lrd_per_usd' => 'DECIMAL(12,4) NOT NULL DEFAULT 170'
] as $column => $definition) {
    $column_result = mysqli_query($con, "SHOW COLUMNS FROM shop_settings LIKE '$column'");
    if ($column_result && mysqli_num_rows($column_result) === 0) {
        mysqli_query($con, "ALTER TABLE shop_settings ADD COLUMN `$column` $definition");
    }
}

// Initialize default settings
$default_settings = [
    'shop_name' => 'TechShop Liberia',
    'shop_email' => 'info@techshopliberia.com',
    'shop_phone' => '+231 123 456 789',
    'shop_address' => 'Monrovia, Liberia',
    'shop_description' => 'Your premier destination for electronics and technology in Liberia',
    'currency' => 'USD',
    'lrd_per_usd' => 170,
    'timezone' => 'Africa/Monrovia',
    'maintenance_mode' => 0,
    'logo_url' => '',
    'favicon_url' => '',
    'social_facebook' => '',
    'social_twitter' => '',
    'social_instagram' => ''
];

// Get current settings
$settings = $default_settings;
$result = mysqli_query($con, "SELECT * FROM shop_settings WHERE id = 1");
if ($result && mysqli_num_rows($result) > 0) {
    $db_settings = mysqli_fetch_assoc($result);
    $settings = array_merge($default_settings, $db_settings);
} else {
    // Insert default settings if not exists
    $insert_default = "INSERT INTO shop_settings (id) VALUES (1)";
    mysqli_query($con, $insert_default);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $shop_name = mysqli_real_escape_string($con, $_POST['shop_name'] ?? '');
        $shop_email = mysqli_real_escape_string($con, $_POST['shop_email'] ?? '');
        $shop_phone = mysqli_real_escape_string($con, $_POST['shop_phone'] ?? '');
        $shop_address = mysqli_real_escape_string($con, $_POST['shop_address'] ?? '');
        $shop_description = mysqli_real_escape_string($con, $_POST['shop_description'] ?? '');
        $currency = mysqli_real_escape_string($con, $_POST['currency'] ?? 'USD');
        $lrd_per_usd = filter_var($_POST['lrd_per_usd'] ?? null, FILTER_VALIDATE_FLOAT);
        $timezone = mysqli_real_escape_string($con, $_POST['timezone'] ?? 'Africa/Monrovia');
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $social_facebook = mysqli_real_escape_string($con, $_POST['social_facebook'] ?? '');
        $social_twitter = mysqli_real_escape_string($con, $_POST['social_twitter'] ?? '');
        $social_instagram = mysqli_real_escape_string($con, $_POST['social_instagram'] ?? '');

        // Validate required fields
        if (empty($shop_name) || empty($shop_email)) {
            $_SESSION['error'] = "Shop name and email are required fields.";
        } elseif (!filter_var($shop_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address.";
        } elseif ($lrd_per_usd === false || $lrd_per_usd <= 0) {
            $_SESSION['error'] = "Enter a positive LRD-per-USD exchange rate.";
        } else {
            $query = "UPDATE shop_settings SET 
                     shop_name = '$shop_name',
                     shop_email = '$shop_email',
                     shop_phone = '$shop_phone',
                     shop_address = '$shop_address',
                     shop_description = '$shop_description',
                     currency = '$currency',
                     lrd_per_usd = '$lrd_per_usd',
                     timezone = '$timezone',
                     maintenance_mode = '$maintenance_mode',
                     social_facebook = '$social_facebook',
                     social_twitter = '$social_twitter',
                     social_instagram = '$social_instagram',
                     updated_at = NOW() 
                     WHERE id = 1";
            
            if (mysqli_query($con, $query)) {
                $_SESSION['success'] = "Shop settings updated successfully!";
                // Refresh settings
                $result = mysqli_query($con, "SELECT * FROM shop_settings WHERE id = 1");
                if ($result && mysqli_num_rows($result) > 0) {
                    $settings = mysqli_fetch_assoc($result);
                }
            } else {
                $_SESSION['error'] = "Error updating settings: " . mysqli_error($con);
            }
        }
    }
    
    // Handle logo upload
    if (isset($_POST['upload_logo']) && isset($_FILES['shop_logo'])) {
        $upload_dir = '../assets/img/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $logo_file = $_FILES['shop_logo'];
        
        if ($logo_file['error'] === UPLOAD_ERR_OK) {
            // Validate file size (max 2MB)
            if ($logo_file['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = "File size too large. Maximum allowed size is 2MB.";
            } else {
                $file_extension = strtolower(pathinfo($logo_file['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = 'shop-logo-' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($logo_file['tmp_name'], $upload_path)) {
                        $logo_url = './assets/img/' . $new_filename;
                        $update_logo = "UPDATE shop_settings SET logo_url = '$logo_url' WHERE id = 1";
                        if (mysqli_query($con, $update_logo)) {
                            $_SESSION['success'] = "Shop logo uploaded successfully!";
                            $settings['logo_url'] = $logo_url;
                        } else {
                            $_SESSION['error'] = "Error updating logo in database.";
                        }
                    } else {
                        $_SESSION['error'] = "Error uploading logo file.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid file type. Please upload JPG, PNG, GIF, WEBP, or SVG files.";
                }
            }
        } else {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            $_SESSION['error'] = "Error uploading file: " . ($upload_errors[$logo_file['error']] ?? 'Unknown error');
        }
    }
    
    // Handle logo removal
    if (isset($_POST['remove_logo'])) {
        if (!empty($settings['logo_url'])) {
            // Delete physical file
            $logo_path = '../' . str_replace('./', '', $settings['logo_url']);
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
            
            $update_logo = "UPDATE shop_settings SET logo_url = '' WHERE id = 1";
            if (mysqli_query($con, $update_logo)) {
                $_SESSION['success'] = "Shop logo removed successfully!";
                $settings['logo_url'] = '';
            } else {
                $_SESSION['error'] = "Error removing logo from database.";
            }
        }
    }
}
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Compact Page Header -->
        <div class="page-header-compact mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="header-icon-sm bg-primary rounded d-flex align-items-center justify-content-center mr-3">
                        <i class="material-icons text-white">store</i>
                    </div>
                    <div>
                        <h4 class="page-title mb-0">Store Settings</h4>
                        <p class="page-subtitle text-muted mb-0">Manage your store configuration and branding</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-sm btn-outline-secondary mr-2" onclick="window.location.reload()">
                        <i class="material-icons mr-1" style="font-size: 16px;">refresh</i>
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#previewModal">
                        <i class="material-icons mr-1" style="font-size: 16px;">visibility</i>
                        Preview
                    </button>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div class="flex-grow-1"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="material-icons mr-2">error</i>
            <div class="flex-grow-1"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Store Information Card -->
                <div class="card card-settings mb-4">
                    <div class="card-header card-header-primary">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">store</i>
                            <h5 class="mb-0">Store Information</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="settingsForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Store Name <span class="text-danger">*</span></label>
                                        <input type="text" name="shop_name" class="form-control form-control-lg" required 
                                               value="<?php echo htmlspecialchars($settings['shop_name']); ?>">
                                        <small class="form-text text-muted">The name of your online store</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Store Email <span class="text-danger">*</span></label>
                                        <input type="email" name="shop_email" class="form-control form-control-lg" required 
                                               value="<?php echo htmlspecialchars($settings['shop_email']); ?>">
                                        <small class="form-text text-muted">Primary contact email</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0">
                                            <i class="material-icons text-muted">phone</i>
                                        </span>
                                    </div>
                                    <input type="text" name="shop_phone" class="form-control border-left-0" 
                                           value="<?php echo htmlspecialchars($settings['shop_phone']); ?>">
                                </div>
                                <small class="form-text text-muted">Customer service phone number</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Store Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0">
                                            <i class="material-icons text-muted">location_on</i>
                                        </span>
                                    </div>
                                    <textarea name="shop_address" class="form-control border-left-0" rows="2"><?php echo htmlspecialchars($settings['shop_address']); ?></textarea>
                                </div>
                                <small class="form-text text-muted">Physical location of your business</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Store Description</label>
                                <textarea name="shop_description" class="form-control" rows="3" placeholder="Brief description of your business..."><?php echo htmlspecialchars($settings['shop_description']); ?></textarea>
                                <small class="form-text text-muted">This appears in search engines and social media</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Currency</label>
                                        <select name="currency" class="form-control">
                                            <option value="USD" <?php echo ($settings['currency'] === 'USD') ? 'selected' : ''; ?>>US Dollar ($)</option>
                                            <option value="LRD" <?php echo ($settings['currency'] === 'LRD') ? 'selected' : ''; ?>>Liberian Dollar (L$)</option>
                                            <option value="EUR" <?php echo ($settings['currency'] === 'EUR') ? 'selected' : ''; ?>>Euro (€)</option>
                                            <option value="GBP" <?php echo ($settings['currency'] === 'GBP') ? 'selected' : ''; ?>>British Pound (£)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">LRD per USD</label>
                                        <input type="number" name="lrd_per_usd" class="form-control" min="0.0001" step="0.0001" required value="<?php echo htmlspecialchars((string)$settings['lrd_per_usd']); ?>">
                                        <small class="form-text text-muted">How many Liberian dollars equal USD 1. Product prices are stored in LRD.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Timezone</label>
                                        <select name="timezone" class="form-control">
                                            <option value="Africa/Monrovia" <?php echo ($settings['timezone'] === 'Africa/Monrovia') ? 'selected' : ''; ?>>Africa/Monrovia</option>
                                            <option value="UTC" <?php echo ($settings['timezone'] === 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo ($settings['timezone'] === 'America/New_York') ? 'selected' : ''; ?>>America/New York</option>
                                            <option value="Europe/London" <?php echo ($settings['timezone'] === 'Europe/London') ? 'selected' : ''; ?>>Europe/London</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="maintenance_mode" class="custom-control-input" id="maintenance_mode" 
                                           <?php echo ($settings['maintenance_mode'] == 1) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label font-weight-bold" for="maintenance_mode">
                                        Enable Maintenance Mode
                                    </label>
                                    <small class="form-text text-muted d-block mt-1">When enabled, customers will see a maintenance page instead of your store</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Branding & Logo Card -->
                <div class="card card-settings mb-4">
                    <div class="card-header card-header-warning">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">palette</i>
                            <h5 class="mb-0">Branding & Logo</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="logo-section">
                                    <div class="current-logo mb-4">
                                        <h6 class="section-title">Current Logo</h6>
                                        <div class="logo-display" id="logoDisplay">
                                            <?php if (!empty($settings['logo_url'])): ?>
                                                <div class="logo-container">
                                                    <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Shop Logo" class="logo-image">
                                                    <div class="logo-actions">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeLogo()">
                                                            <i class="material-icons">delete</i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-logo">
                                                    <i class="material-icons">photo_camera</i>
                                                    <span>No logo uploaded</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="upload-section">
                                    <h6 class="section-title">Upload New Logo</h6>
                                    <form method="POST" action="" enctype="multipart/form-data" id="logoForm">
                                        <div class="upload-area" id="uploadArea">
                                            <div class="upload-content">
                                                <i class="material-icons">cloud_upload</i>
                                                <h6>Drop your logo here or click to browse</h6>
                                                <p class="text-muted">Supports: JPG, PNG, GIF, SVG, WEBP (Max: 2MB)</p>
                                                <input type="file" name="shop_logo" id="shop_logo" accept=".jpg,.jpeg,.png,.gif,.svg,.webp" hidden>
                                                <button type="button" class="btn btn-outline-primary mt-2" onclick="document.getElementById('shop_logo').click()">
                                                    Choose File
                                                </button>
                                            </div>
                                            <div class="upload-preview" id="uploadPreview" style="display: none;">
                                                <img id="previewImage" src="" alt="Preview" class="preview-img">
                                                <div class="preview-info">
                                                    <div class="file-name" id="fileName"></div>
                                                    <div class="file-size" id="fileSize"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="upload-actions mt-3" id="uploadActions" style="display: none;">
                                            <button type="submit" name="upload_logo" class="btn btn-warning">
                                                <i class="material-icons mr-2">cloud_upload</i>Upload Logo
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary ml-2" onclick="clearUpload()">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                    <form method="POST" action="" id="removeLogoForm" style="display: none;">
                                        <input type="hidden" name="remove_logo" value="1">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media Card -->
                <div class="card card-settings">
                    <div class="card-header card-header-info">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">share</i>
                            <h5 class="mb-0">Social Media</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Facebook</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light">
                                                <i class="fab fa-facebook text-primary"></i>
                                            </span>
                                        </div>
                                        <input type="url" name="social_facebook" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['social_facebook']); ?>" placeholder="https://facebook.com/yourpage">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Twitter</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light">
                                                <i class="fab fa-twitter text-info"></i>
                                            </span>
                                        </div>
                                        <input type="url" name="social_twitter" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['social_twitter']); ?>" placeholder="https://twitter.com/yourhandle">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Instagram</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light">
                                                <i class="fab fa-instagram text-danger"></i>
                                            </span>
                                        </div>
                                        <input type="url" name="social_instagram" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['social_instagram']); ?>" placeholder="https://instagram.com/yourprofile">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions-sticky">
                    <div class="actions-container">
                        <button type="submit" form="settingsForm" name="update_settings" class="btn btn-primary btn-lg">
                            <i class="material-icons mr-2">save</i>Save All Changes
                        </button>
                        <button type="reset" form="settingsForm" class="btn btn-outline-secondary btn-lg ml-2">
                            <i class="material-icons mr-2">refresh</i>Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card card-settings mb-4">
                    <div class="card-header card-header-success">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">flash_on</i>
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="quick-actions-list">
                            <a href="payment_settings.php" class="action-item">
                                <div class="action-icon bg-primary">
                                    <i class="material-icons">payment</i>
                                </div>
                                <div class="action-content">
                                    <div class="action-title">Payment Methods</div>
                                    <div class="action-desc">Configure payment gateways</div>
                                </div>
                                <i class="material-icons action-arrow">chevron_right</i>
                            </a>
                            <a href="shipping_settings.php" class="action-item">
                                <div class="action-icon bg-success">
                                    <i class="material-icons">local_shipping</i>
                                </div>
                                <div class="action-content">
                                    <div class="action-title">Shipping Settings</div>
                                    <div class="action-desc">Set up shipping options</div>
                                </div>
                                <i class="material-icons action-arrow">chevron_right</i>
                            </a>
                            <a href="tax_settings.php" class="action-item">
                                <div class="action-icon bg-warning">
                                    <i class="material-icons">calculate</i>
                                </div>
                                <div class="action-content">
                                    <div class="action-title">Tax Settings</div>
                                    <div class="action-desc">Configure tax rates</div>
                                </div>
                                <i class="material-icons action-arrow">chevron_right</i>
                            </a>
                            <a href="profile.php" class="action-item">
                                <div class="action-icon bg-info">
                                    <i class="material-icons">person</i>
                                </div>
                                <div class="action-content">
                                    <div class="action-title">Admin Profile</div>
                                    <div class="action-desc">Update your profile</div>
                                </div>
                                <i class="material-icons action-arrow">chevron_right</i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Store Status -->
                <div class="card card-settings mb-4">
                    <div class="card-header card-header-dark">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">storefront</i>
                            <h6 class="mb-0">Store Status</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-icon online">
                                    <i class="material-icons">check_circle</i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">Store Status</div>
                                    <div class="status-value text-success">Online</div>
                                </div>
                            </div>
                            <div class="status-item">
                                <div class="status-icon <?php echo ($settings['maintenance_mode'] == 1) ? 'maintenance' : 'operational'; ?>">
                                    <i class="material-icons"><?php echo ($settings['maintenance_mode'] == 1) ? 'engineering' : 'build'; ?></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">Maintenance</div>
                                    <div class="status-value <?php echo ($settings['maintenance_mode'] == 1) ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo ($settings['maintenance_mode'] == 1) ? 'Enabled' : 'Disabled'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="status-item">
                                <div class="status-icon currency">
                                    <i class="material-icons">attach_money</i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">Currency</div>
                                    <div class="status-value"><?php echo htmlspecialchars($settings['currency']); ?></div>
                                </div>
                            </div>
                            <div class="status-item">
                                <div class="status-icon timezone">
                                    <i class="material-icons">schedule</i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">Last Updated</div>
                                    <div class="status-value">
                                        <?php 
                                        if (isset($settings['updated_at']) && !empty($settings['updated_at'])) {
                                            echo date('M j, Y g:i A', strtotime($settings['updated_at']));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card card-settings">
                    <div class="card-header card-header-secondary">
                        <div class="d-flex align-items-center">
                            <i class="material-icons mr-2">info</i>
                            <h6 class="mb-0">System Information</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="system-info-grid">
                            <div class="system-info-item">
                                <i class="material-icons text-primary">code</i>
                                <div>
                                    <div class="info-label">PHP Version</div>
                                    <div class="info-value"><?php echo phpversion(); ?></div>
                                </div>
                            </div>
                            <div class="system-info-item">
                                <i class="material-icons text-success">storage</i>
                                <div>
                                    <div class="info-label">Database</div>
                                    <div class="info-value">MySQL</div>
                                </div>
                            </div>
                            <div class="system-info-item">
                                <i class="material-icons text-warning">schedule</i>
                                <div>
                                    <div class="info-label">Server Time</div>
                                    <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div>
                                </div>
                            </div>
                            <div class="system-info-item">
                                <i class="material-icons text-info">memory</i>
                                <div>
                                    <div class="info-label">Memory Usage</div>
                                    <div class="info-value"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Store Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="store-preview">
                    <div class="preview-header text-center mb-4">
                        <?php if (!empty($settings['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Store Logo" class="preview-logo mb-3">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($settings['shop_name']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($settings['shop_description']); ?></p>
                    </div>
                    <div class="preview-info">
                        <div class="row">
                            <div class="col-md-6">
                                <p><i class="material-icons mr-2 text-primary">email</i> <?php echo htmlspecialchars($settings['shop_email']); ?></p>
                                <p><i class="material-icons mr-2 text-success">phone</i> <?php echo htmlspecialchars($settings['shop_phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="material-icons mr-2 text-warning">location_on</i> <?php echo htmlspecialchars($settings['shop_address']); ?></p>
                                <p><i class="material-icons mr-2 text-info">attach_money</i> Currency: <?php echo htmlspecialchars($settings['currency']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Compact Header */
.page-header-compact {
    padding: 1rem 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 1.5rem;
}

.header-icon-sm {
    width: 40px;
    height: 40px;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
}

.page-subtitle {
    font-size: 0.875rem;
}

.header-actions {
    display: flex;
    align-items: center;
}

/* Card Styles */
.card-settings {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-settings:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 1.25rem 1.5rem;
    border-radius: 12px 12px 0 0 !important;
}

.card-header-primary {
    border-left: 4px solid #4361ee;
}

.card-header-warning {
    border-left: 4px solid #ffd166;
}

.card-header-info {
    border-left: 4px solid #118ab2;
}

.card-header-success {
    border-left: 4px solid #06d6a0;
}

.card-header-dark {
    border-left: 4px solid #2d3748;
}

.card-header-secondary {
    border-left: 4px solid #6c757d;
}

.card-header h5, .card-header h6 {
    margin: 0;
    color: #2d3748;
    font-weight: 600;
}

.card-header .material-icons {
    color: #4361ee;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-control-lg {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control-lg:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #e2e8f0;
    color: #6c757d;
}

/* Logo Section */
.logo-section .section-title {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.logo-display {
    text-align: center;
}

.logo-container {
    position: relative;
    display: inline-block;
}

.logo-image {
    max-width: 200px;
    max-height: 100px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.logo-actions {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logo-container:hover .logo-actions {
    opacity: 1;
}

.no-logo {
    padding: 2rem;
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    color: #a0aec0;
    text-align: center;
}

.no-logo .material-icons {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    display: block;
}

/* Upload Section */
.upload-section .section-title {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.upload-area {
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #fafbfc;
}

.upload-area:hover {
    border-color: #4361ee;
    background: #f7f9ff;
}

.upload-area.dragover {
    border-color: #4361ee;
    background: #edf2ff;
}

.upload-content .material-icons {
    font-size: 3rem;
    color: #a0aec0;
    margin-bottom: 1rem;
    display: block;
}

.upload-content h6 {
    color: #4a5568;
    margin-bottom: 0.5rem;
}

.upload-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.preview-img {
    max-width: 80px;
    max-height: 60px;
    border-radius: 6px;
}

.preview-info {
    flex: 1;
    text-align: left;
}

.file-name {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.25rem;
}

.file-size {
    font-size: 0.875rem;
    color: #718096;
}

/* Quick Actions */
.quick-actions-list {
    display: flex;
    flex-direction: column;
}

.action-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f3f4;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.3s ease;
}

.action-item:hover {
    background-color: #f8f9fa;
    text-decoration: none;
    color: inherit;
}

.action-item:last-child {
    border-bottom: none;
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
}

.action-icon .material-icons {
    font-size: 20px;
}

.action-content {
    flex: 1;
}

.action-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.action-desc {
    font-size: 0.875rem;
    color: #718096;
}

.action-arrow {
    color: #cbd5e0;
    transition: transform 0.3s ease;
}

.action-item:hover .action-arrow {
    transform: translateX(3px);
    color: #4361ee;
}

/* Status Grid */
.status-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.status-icon.online { background: #48bb78; }
.status-icon.maintenance { background: #ed8936; }
.status-icon.operational { background: #38b2ac; }
.status-icon.currency { background: #9f7aea; }
.status-icon.timezone { background: #4299e1; }

.status-icon .material-icons {
    font-size: 24px;
}

.status-content {
    flex: 1;
}

.status-label {
    font-size: 0.875rem;
    color: #718096;
    margin-bottom: 0.25rem;
}

.status-value {
    font-weight: 600;
    color: #2d3748;
}

/* System Info */
.system-info-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.system-info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.system-info-item .material-icons {
    font-size: 20px;
}

.info-label {
    font-size: 0.875rem;
    color: #718096;
}

.info-value {
    font-weight: 600;
    color: #2d3748;
}

/* Form Actions Sticky */
.form-actions-sticky {
    position: sticky;
    bottom: 0;
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 1rem 0;
    margin-top: 2rem;
    z-index: 100;
}

.actions-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

/* Custom Switch */
.custom-control-input:checked ~ .custom-control-label::before {
    border-color: #4361ee;
    background-color: #4361ee;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-compact {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        align-self: flex-end;
    }
    
    .logo-section, .upload-section {
        margin-bottom: 2rem;
    }
    
    .actions-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .actions-container .btn {
        width: 100%;
    }
}
</style>

<script>
// File upload functionality
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('shop_logo');
    const uploadPreview = document.getElementById('uploadPreview');
    const uploadActions = document.getElementById('uploadActions');
    const uploadContent = uploadArea.querySelector('.upload-content');
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        uploadArea.classList.add('dragover');
    }
    
    function unhighlight() {
        uploadArea.classList.remove('dragover');
    }
    
    // Handle file drop
    uploadArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
    
    // Handle file input change
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF, SVG, or WEBP).');
                return;
            }
            
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB.');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatFileSize(file.size);
                
                uploadContent.style.display = 'none';
                uploadPreview.style.display = 'flex';
                uploadActions.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Form validation
    const form = document.getElementById('settingsForm');
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            showNotification('Please fill in all required fields.', 'error');
        }
    });
    
    // Maintenance mode confirmation
    const maintenanceMode = document.getElementById('maintenance_mode');
    if (maintenanceMode) {
        maintenanceMode.addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('⚠️ Enable Maintenance Mode?\n\nYour store will be temporarily unavailable to customers. They will see a maintenance page instead.')) {
                    this.checked = false;
                }
            }
        });
    }
});

function clearUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('shop_logo');
    const uploadPreview = document.getElementById('uploadPreview');
    const uploadActions = document.getElementById('uploadActions');
    const uploadContent = uploadArea.querySelector('.upload-content');
    
    fileInput.value = '';
    uploadPreview.style.display = 'none';
    uploadActions.style.display = 'none';
    uploadContent.style.display = 'block';
}

function removeLogo() {
    if (confirm('Are you sure you want to remove the current logo?')) {
        document.getElementById('removeLogoForm').submit();
    }
}

function showNotification(message, type) {
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
    const icon = type === 'error' ? 'error' : 'check_circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show d-flex align-items-center`;
    notification.innerHTML = `
        <i class="material-icons mr-2">${icon}</i>
        <div class="flex-grow-1">${message}</div>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    const container = document.querySelector('.container-fluid');
    const pageHeader = document.querySelector('.page-header-compact');
    container.insertBefore(notification, pageHeader.nextSibling);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Auto-save indicator
let isSaving = false;
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    const saveBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        if (!isSaving) {
            isSaving = true;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="material-icons mr-2">hourglass_empty</i>Saving...';
            saveBtn.disabled = true;
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                isSaving = false;
            }, 2000);
        }
    });
});
</script>

<?php
include "footer.php";
?>