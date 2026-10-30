<?php
session_start();
include("./includes/db.php");

// Initialize variables
$success_msg = $error_msg = '';
$admin_name = $_SESSION['admin_name'] ?? '';
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? '';
$profile_picture = '';

// Get current admin data including profile picture
if ($admin_id) {
    $admin_query = mysqli_query($con, "SELECT * FROM admin_info WHERE admin_id='$admin_id'");
    if ($admin_query && mysqli_num_rows($admin_query) > 0) {
        $admin_data = mysqli_fetch_assoc($admin_query);
        $profile_picture = $admin_data['profile_picture'] ?? '';
    }
}

// Handle profile picture upload
if (isset($_POST['upload_profile_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/profile_pictures/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_error = $_FILES['profile_picture']['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Check if extension is allowed
        if (in_array($file_ext, $allowed_ext)) {
            // Check file size (max 2MB)
            if ($file_size <= 2097152) {
                // Generate unique file name
                $new_file_name = 'admin_' . $admin_id . '_' . time() . '.' . $file_ext;
                $file_destination = $upload_dir . $new_file_name;
                
                // Delete old profile picture if exists
                if (!empty($profile_picture) && file_exists($upload_dir . $profile_picture)) {
                    unlink($upload_dir . $profile_picture);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    // Update database
                    $update_picture = mysqli_query($con, "UPDATE admin_info SET profile_picture='$new_file_name' WHERE admin_id='$admin_id'");
                    
                    if ($update_picture) {
                        $profile_picture = $new_file_name;
                        $success_msg = "Profile picture updated successfully!";
                    } else {
                        $error_msg = "Error updating profile picture in database: " . mysqli_error($con);
                    }
                } else {
                    $error_msg = "Error uploading file. Please try again.";
                }
            } else {
                $error_msg = "File size too large. Maximum size is 2MB.";
            }
        } else {
            $error_msg = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        $error_msg = "Please select a valid image file.";
    }
}

// Handle password change
if (isset($_POST['re_password'])) {
    $email = $_SESSION['admin_email'];
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $re_pass = $_POST['re_pass'];
    
    // Validate inputs
    if (empty($old_pass) || empty($new_pass) || empty($re_pass)) {
        $error_msg = "All password fields are required!";
    } else {
        $op = md5($old_pass);
        $password_query = mysqli_query($con, "SELECT * FROM admin_info WHERE admin_email='$email'");
        
        if ($password_query && mysqli_num_rows($password_query) > 0) {
            $password_row = mysqli_fetch_array($password_query);
            $database_password = $password_row['admin_password'];
            
            if ($database_password == $op) {
                if ($new_pass == $re_pass) {
                    // Validate password strength
                    if (strlen($new_pass) < 8) {
                        $error_msg = "Password must be at least 8 characters long!";
                    } else if (!preg_match('/[A-Z]/', $new_pass)) {
                        $error_msg = "Password must contain at least one uppercase letter!";
                    } else if (!preg_match('/[a-z]/', $new_pass)) {
                        $error_msg = "Password must contain at least one lowercase letter!";
                    } else if (!preg_match('/[0-9]/', $new_pass)) {
                        $error_msg = "Password must contain at least one number!";
                    } else {
                        $pass = md5($re_pass);
                        $admin_id = $password_row['admin_id'];
                        $update_pwd = mysqli_query($con, "UPDATE admin_info SET admin_password='$pass' WHERE admin_id='$admin_id'");
                        
                        if ($update_pwd) {
                            $success_msg = "Password updated successfully!";
                        } else {
                            $error_msg = "Error updating password: " . mysqli_error($con);
                        }
                    }
                } else {
                    $error_msg = "New password and confirm password do not match!";
                }
            } else {
                $error_msg = "Your current password is incorrect!";
            }
        } else {
            $error_msg = "Admin account not found!";
        }
    }
}

// Handle profile information update
if (isset($_POST['update_profile'])) {
    $new_name = mysqli_real_escape_string($con, $_POST['admin_name']);
    $new_email = mysqli_real_escape_string($con, $_POST['admin_email']);
    $admin_id = $_SESSION['admin_id'] ?? '';
    
    if (!empty($new_name) && !empty($new_email)) {
        // Check if email already exists (excluding current admin)
        $email_check = mysqli_query($con, "SELECT admin_id FROM admin_info WHERE admin_email='$new_email' AND admin_id != '$admin_id'");
        
        if (mysqli_num_rows($email_check) > 0) {
            $error_msg = "Email address is already in use by another account!";
        } else {
            $update_profile = mysqli_query($con, "UPDATE admin_info SET admin_name='$new_name', admin_email='$new_email' WHERE admin_id='$admin_id'");
            
            if ($update_profile) {
                $_SESSION['admin_name'] = $new_name;
                $_SESSION['admin_email'] = $new_email;
                $admin_name = $new_name;
                $admin_email = $new_email;
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Error updating profile: " . mysqli_error($con);
            }
        }
    } else {
        $error_msg = "Name and email are required!";
    }
}

include "sidenav.php";
include "topheader.php";
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Alert Messages -->
        <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            <?php echo $success_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="material-icons">error</i>
            <?php echo $error_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information Card -->
            <div class="col-md-4">
                <div class="card card-profile">
                    <div class="card-avatar">
                        <div class="profile-picture-container">
                            <?php if (!empty($profile_picture)): ?>
                                <img src="../assets/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile Picture" class="admin-avatar-image" id="profileImage">
                            <?php else: ?>
                                <div class="admin-avatar-large" id="defaultAvatar">
                                    <?php 
                                        $initial = isset($_SESSION['admin_name']) ? strtoupper(substr($_SESSION['admin_name'], 0, 1)) : 'A';
                                        echo $initial;
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="profile-picture-overlay">
                                <label for="profilePictureInput" class="profile-picture-upload-btn">
                                    <i class="material-icons">camera_alt</i>
                                    <span>Change Photo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Picture Upload Form -->
                    <form method="post" enctype="multipart/form-data" class="profile-picture-form">
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;">
                        <button type="submit" name="upload_profile_picture" class="btn btn-primary btn-sm mt-2" id="uploadBtn" style="display: none;">
                            Upload Picture
                        </button>
                    </form>

                    <div class="card-body">
                        <h4 class="card-title"><?php echo htmlspecialchars($admin_name); ?></h4>
                        <h6 class="card-category text-muted">Administrator</h6>
                        <p class="card-description">
                            <i class="material-icons">email</i> <?php echo htmlspecialchars($admin_email); ?>
                        </p>
                        <p class="card-description">
                            <i class="material-icons">calendar_today</i> Member since: <?php echo date('M Y'); ?>
                        </p>
                        
                        <!-- Profile Statistics -->
                        <?php
                        // Get actual statistics
                        $user_count = mysqli_query($con, "SELECT COUNT(*) as total FROM user_info");
                        $user_data = mysqli_fetch_assoc($user_count);
                        $product_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products");
                        $product_data = mysqli_fetch_assoc($product_count);
                        $category_count = mysqli_query($con, "SELECT COUNT(*) as total FROM categories");
                        $category_data = mysqli_fetch_assoc($category_count);
                        ?>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $product_data['total']; ?></span>
                                <span class="stat-label">Products</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_data['total']; ?></span>
                                <span class="stat-label">Users</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $category_data['total']; ?></span>
                                <span class="stat-label">Categories</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Edit Profile Information</h4>
                        <p class="card-category">Update your personal details</p>
                    </div>
                    <div class="card-body">
                        <form method="post" action="profile.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Full Name</label>
                                        <input type="text" class="form-control" name="admin_name" 
                                               value="<?php echo htmlspecialchars($admin_name); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Email Address</label>
                                        <input type="email" class="form-control" name="admin_email" 
                                               value="<?php echo htmlspecialchars($admin_email); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary pull-right">
                                <i class="material-icons">save</i> Update Profile
                            </button>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="card mt-4">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Change Password</h4>
                        <p class="card-category">Update your security credentials</p>
                    </div>
                    <div class="card-body">
                        <form method="post" action="profile.php" id="passwordForm">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Current Password</label>
                                        <input type="password" class="form-control" name="old_pass" id="old_pass" required>
                                        <span class="password-toggle" onclick="togglePassword('old_pass')">
                                            <i class="material-icons">visibility</i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">New Password</label>
                                        <input type="password" class="form-control" name="new_pass" id="new_pass" required>
                                        <span class="password-toggle" onclick="togglePassword('new_pass')">
                                            <i class="material-icons">visibility</i>
                                        </span>
                                        <small class="form-text text-muted password-strength" id="passwordStrength">
                                            Password must be at least 8 characters long
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Confirm New Password</label>
                                        <input type="password" class="form-control" name="re_pass" id="re_pass" required>
                                        <span class="password-toggle" onclick="togglePassword('re_pass')">
                                            <i class="material-icons">visibility</i>
                                        </span>
                                        <small class="form-text text-muted" id="passwordMatch"></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="password-requirements">
                                        <h6>Password Requirements:</h6>
                                        <ul>
                                            <li id="req-length">At least 8 characters</li>
                                            <li id="req-uppercase">One uppercase letter</li>
                                            <li id="req-lowercase">One lowercase letter</li>
                                            <li id="req-number">One number</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="re_password" class="btn btn-warning pull-right" id="submitBtn">
                                <i class="material-icons">lock</i> Change Password
                            </button>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Activity -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Recent Activity</h4>
                        <p class="card-category">Your recent account activities</p>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <i class="material-icons text-success">login</i>
                                <div class="activity-content">
                                    <h6>Login Successful</h6>
                                    <p>You logged into your account</p>
                                    <small class="text-muted"><?php echo date('M d, Y \a\t h:i A'); ?></small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <i class="material-icons text-primary">settings</i>
                                <div class="activity-content">
                                    <h6>Profile Updated</h6>
                                    <p>You updated your profile information</p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime('-1 day')); ?> at 2:30 PM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-profile {
    text-align: center;
}

.profile-picture-container {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
}

.admin-avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.admin-avatar-large {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(45deg, #9c27b0, #e91e63);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: bold;
}

.profile-picture-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 50%;
}

.profile-picture-container:hover .profile-picture-overlay {
    opacity: 1;
}

.profile-picture-upload-btn {
    color: white;
    text-align: center;
    cursor: pointer;
}

.profile-picture-upload-btn i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 5px;
}

.profile-picture-upload-btn span {
    font-size: 0.8rem;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #9c27b0;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.form-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
}

.password-toggle:hover {
    color: #9c27b0;
}

.password-requirements {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.password-requirements li {
    padding: 5px 0;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.password-requirements li.valid {
    color: #28a745;
}

.password-requirements li.invalid {
    color: #6c757d;
}

.activity-list {
    padding: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item i {
    font-size: 1.5rem;
    margin-right: 15px;
    margin-top: 5px;
}

.activity-content h6 {
    margin-bottom: 5px;
    font-weight: 600;
}

.activity-content p {
    margin-bottom: 5px;
    color: #6c757d;
    font-size: 0.875rem;
}

.alert {
    border: none;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
}

.alert-danger {
    border-left-color: #dc3545;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.profile-picture-form {
    margin-bottom: 15px;
}
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentNode.querySelector('.password-toggle i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password)
    };

    // Update requirement indicators
    document.getElementById('req-length').className = requirements.length ? 'valid' : 'invalid';
    document.getElementById('req-uppercase').className = requirements.uppercase ? 'valid' : 'invalid';
    document.getElementById('req-lowercase').className = requirements.lowercase ? 'valid' : 'invalid';
    document.getElementById('req-number').className = requirements.number ? 'valid' : 'invalid';

    // Calculate strength
    const strength = Object.values(requirements).filter(Boolean).length;
    const strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'][strength];
    const strengthColor = ['#dc3545', '#ffc107', '#17a2b8', '#28a745', '#20c997'][strength];
    
    document.getElementById('passwordStrength').textContent = `Password Strength: ${strengthText}`;
    document.getElementById('passwordStrength').style.color = strengthColor;
    
    return strength >= 3; // At least medium strength
}

function checkPasswordMatch() {
    const newPass = document.getElementById('new_pass').value;
    const confirmPass = document.getElementById('re_pass').value;
    const matchElement = document.getElementById('passwordMatch');
    
    if (confirmPass === '') {
        matchElement.textContent = '';
    } else if (newPass === confirmPass) {
        matchElement.textContent = 'Passwords match!';
        matchElement.style.color = '#28a745';
    } else {
        matchElement.textContent = 'Passwords do not match!';
        matchElement.style.color = '#dc3545';
    }
}

// Profile picture upload functionality
document.getElementById('profilePictureInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        // Check file type
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, JPEG, PNG, GIF).');
            this.value = '';
            return;
        }
        
        // Check file size
        if (file.size > maxSize) {
            alert('File size too large. Maximum size is 2MB.');
            this.value = '';
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileImage = document.getElementById('profileImage');
            const defaultAvatar = document.getElementById('defaultAvatar');
            
            if (profileImage) {
                profileImage.src = e.target.result;
            } else if (defaultAvatar) {
                defaultAvatar.style.display = 'none';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Profile Picture';
                img.className = 'admin-avatar-image';
                img.id = 'profileImage';
                document.querySelector('.profile-picture-container').appendChild(img);
            }
        };
        reader.readAsDataURL(file);
        
        // Show upload button
        document.getElementById('uploadBtn').style.display = 'inline-flex';
    }
});

// Event listeners
document.getElementById('new_pass').addEventListener('input', function() {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
});

document.getElementById('re_pass').addEventListener('input', checkPasswordMatch);

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_pass').value;
    const confirmPass = document.getElementById('re_pass').value;
    
    if (!checkPasswordStrength(newPass)) {
        e.preventDefault();
        alert('Please choose a stronger password that meets all requirements.');
        return;
    }
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        window.jQuery(alert).alert('close');
    });
}, 5000);
</script>

<?php
include "footer.php";
?>