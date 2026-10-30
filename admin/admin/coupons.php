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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_coupon'])) {
        $code = mysqli_real_escape_string($con, $_POST['code']);
        $type = mysqli_real_escape_string($con, $_POST['type']);
        $value = mysqli_real_escape_string($con, $_POST['value']);
        $min_order = mysqli_real_escape_string($con, $_POST['min_order']);
        $max_discount = mysqli_real_escape_string($con, $_POST['max_discount']);
        $usage_limit = mysqli_real_escape_string($con, $_POST['usage_limit']);
        $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
        $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
        $status = mysqli_real_escape_string($con, $_POST['status']);

        // Check if coupon code already exists
        $check_query = "SELECT * FROM coupons WHERE code = '$code'";
        $check_result = mysqli_query($con, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error'] = "Coupon code already exists!";
        } else {
            $query = "INSERT INTO coupons (code, type, value, min_order, max_discount, usage_limit, used_count, start_date, end_date, status) 
                     VALUES ('$code', '$type', '$value', '$min_order', '$max_discount', '$usage_limit', 0, '$start_date', '$end_date', '$status')";
            
            if (mysqli_query($con, $query)) {
                $_SESSION['success'] = "Coupon created successfully!";
            } else {
                $_SESSION['error'] = "Error creating coupon: " . mysqli_error($con);
            }
        }
    }
    
    if (isset($_POST['update_coupon'])) {
        $coupon_id = mysqli_real_escape_string($con, $_POST['coupon_id']);
        $status = mysqli_real_escape_string($con, $_POST['status']);
        
        $query = "UPDATE coupons SET status = '$status' WHERE id = '$coupon_id'";
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Coupon updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating coupon: " . mysqli_error($con);
        }
    }
    
    if (isset($_POST['delete_coupon'])) {
        $coupon_id = mysqli_real_escape_string($con, $_POST['coupon_id']);
        $query = "DELETE FROM coupons WHERE id = '$coupon_id'";
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Coupon deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting coupon: " . mysqli_error($con);
        }
    }
}

// Create coupons table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT 0,
    usage_limit INT DEFAULT 1,
    used_count INT DEFAULT 0,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);
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
                            <i class="material-icons text-white">local_offer</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Coupons & Discounts</h1>
                            <p class="page-subtitle text-muted mb-0">Manage discount coupons and promotional offers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-warning" data-toggle="modal" data-target="#createCouponModal">
                        <i class="material-icons mr-2">add</i>Create Coupon
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">local_offer</i>
                        </div>
                        <p class="card-category">Active Coupons</p>
                        <h3 class="card-title">
                            <?php
                            $result = mysqli_query($con, "SELECT COUNT(*) as total FROM coupons WHERE status = 'active' AND (end_date >= CURDATE() OR end_date IS NULL)");
                            $data = mysqli_fetch_assoc($result);
                            echo $data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">people</i>
                        </div>
                        <p class="card-category">Total Usage</p>
                        <h3 class="card-title">
                            <?php
                            $result = mysqli_query($con, "SELECT SUM(used_count) as total FROM coupons");
                            $data = mysqli_fetch_assoc($result);
                            echo $data['total'] ?: '0';
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">trending_up</i>
                        </div>
                        <p class="card-category">Expired</p>
                        <h3 class="card-title">
                            <?php
                            $result = mysqli_query($con, "SELECT COUNT(*) as total FROM coupons WHERE status = 'expired' OR end_date < CURDATE()");
                            $data = mysqli_fetch_assoc($result);
                            echo $data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">block</i>
                        </div>
                        <p class="card-category">Inactive</p>
                        <h3 class="card-title">
                            <?php
                            $result = mysqli_query($con, "SELECT COUNT(*) as total FROM coupons WHERE status = 'inactive'");
                            $data = mysqli_fetch_assoc($result);
                            echo $data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coupons List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-warning">list</i>
                    All Coupons
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Coupon Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Usage</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM coupons ORDER BY created_at DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($coupon = mysqli_fetch_assoc($result)):
                                    $status_class = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'expired' => 'danger'
                                    ][$coupon['status']] ?? 'secondary';
                                    
                                    // Check if coupon is expired
                                    if ($coupon['end_date'] && strtotime($coupon['end_date']) < time()) {
                                        $status_class = 'danger';
                                    }
                            ?>
                            <tr>
                                <td>
                                    <strong class="text-warning"><?php echo $coupon['code']; ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-light text-capitalize">
                                        <?php echo $coupon['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($coupon['type'] === 'percentage') {
                                        echo $coupon['value'] . '%';
                                    } else {
                                        echo '$' . number_format($coupon['value'], 2);
                                    }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($coupon['min_order'], 2); ?></td>
                                <td>
                                    <small><?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?></small>
                                </td>
                                <td>
                                    <?php echo $coupon['end_date'] ? date('M j, Y', strtotime($coupon['end_date'])) : 'No limit'; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($coupon['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                data-toggle="modal" 
                                                data-target="#editCouponModal"
                                                data-coupon-id="<?php echo $coupon['id']; ?>"
                                                data-status="<?php echo $coupon['status']; ?>">
                                            <i class="material-icons" style="font-size:16px;">edit</i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" name="delete_coupon" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this coupon?')">
                                                <i class="material-icons" style="font-size:16px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4">No coupons found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Coupon Modal -->
<div class="modal fade" id="createCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Coupon</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Coupon Code *</label>
                                <input type="text" name="code" class="form-control" required 
                                       placeholder="e.g., SUMMER2024">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount ($)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Value *</label>
                                <input type="number" name="value" class="form-control" step="0.01" required 
                                       placeholder="e.g., 10 for 10% or $10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Order Amount</label>
                                <input type="number" name="min_order" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Discount</label>
                                <input type="number" name="max_discount" class="form-control" step="0.01" value="0">
                                <small class="form-text text-muted">0 for no limit</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Usage Limit</label>
                                <input type="number" name="usage_limit" class="form-control" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="create_coupon" class="btn btn-warning">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Coupon Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="coupon_id" id="edit_coupon_id">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_coupon" class="btn btn-primary">Update Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit coupon modal handler
$('#editCouponModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var couponId = button.data('coupon-id');
    var status = button.data('status');
    
    var modal = $(this);
    modal.find('#edit_coupon_id').val(couponId);
    modal.find('#edit_status').val(status);
});

// Auto-generate coupon code
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.querySelector('input[name="code"]');
    const generateBtn = document.createElement('button');
    generateBtn.type = 'button';
    generateBtn.className = 'btn btn-outline-secondary btn-sm mt-1';
    generateBtn.innerHTML = '<i class="material-icons mr-1" style="font-size:16px;">autorenew</i> Generate Code';
    
    generateBtn.addEventListener('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        codeInput.value = code;
    });
    
    if (codeInput) {
        codeInput.parentNode.appendChild(generateBtn);
    }
});
</script>

<?php
include "footer.php";
?>