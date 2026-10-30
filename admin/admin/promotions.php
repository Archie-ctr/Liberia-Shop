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
    if (isset($_POST['create_promotion'])) {
        $title = mysqli_real_escape_string($con, $_POST['title']);
        $description = mysqli_real_escape_string($con, $_POST['description']);
        $type = mysqli_real_escape_string($con, $_POST['type']);
        $discount_value = mysqli_real_escape_string($con, $_POST['discount_value']);
        $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
        $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
        $status = mysqli_real_escape_string($con, $_POST['status']);

        $query = "INSERT INTO promotions (title, description, type, discount_value, start_date, end_date, status) 
                 VALUES ('$title', '$description', '$type', '$discount_value', '$start_date', '$end_date', '$status')";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Promotion created successfully!";
        } else {
            $_SESSION['error'] = "Error creating promotion: " . mysqli_error($con);
        }
    }
}

// Create promotions table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed', 'bogo') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'scheduled') DEFAULT 'active',
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
                        <div class="header-icon-container bg-info rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">campaign</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Promotions</h1>
                            <p class="page-subtitle text-muted mb-0">Manage promotional campaigns and special offers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-info" data-toggle="modal" data-target="#createPromotionModal">
                        <i class="material-icons mr-2">add</i>Create Promotion
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM promotions WHERE status = 'active'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Active Promotions</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">campaign</i>
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
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM promotions WHERE status = 'scheduled'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Scheduled</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">schedule</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM promotions WHERE end_date >= CURDATE()");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Upcoming</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">upcoming</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM promotions WHERE status = 'inactive'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Inactive</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">pause</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promotions Grid -->
        <div class="row">
            <?php
            $query = "SELECT * FROM promotions ORDER BY created_at DESC";
            $result = mysqli_query($con, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($promotion = mysqli_fetch_assoc($result)):
                    $status_class = [
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'scheduled' => 'warning'
                    ][$promotion['status']] ?? 'secondary';
                    
                    $type_icon = [
                        'percentage' => 'percent',
                        'fixed' => 'attach_money',
                        'bogo' => 'card_giftcard'
                    ][$promotion['type']] ?? 'campaign';
            ?>
            <div class="col-md-4 mb-4">
                <div class="card promotion-card h-100">
                    <div class="card-header bg-<?php echo $status_class; ?> text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo $promotion['title']; ?></h6>
                            <span class="badge badge-light"><?php echo ucfirst($promotion['type']); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo $promotion['description']; ?></p>
                        <div class="promotion-details">
                            <div class="detail-item">
                                <i class="material-icons mr-2"><?php echo $type_icon; ?></i>
                                <span>
                                    <?php 
                                    if ($promotion['type'] === 'percentage') {
                                        echo $promotion['discount_value'] . '% OFF';
                                    } elseif ($promotion['type'] === 'fixed') {
                                        echo '$' . number_format($promotion['discount_value'], 2) . ' OFF';
                                    } else {
                                        echo 'Buy One Get One';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <i class="material-icons mr-2">date_range</i>
                                <span>
                                    <?php 
                                    echo date('M j, Y', strtotime($promotion['start_date'])) . ' - ' . 
                                         date('M j, Y', strtotime($promotion['end_date']));
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <i class="material-icons mr-2">status</i>
                                <span class="badge badge-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($promotion['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-primary btn-sm">Edit</button>
                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            } else {
                echo '<div class="col-12 text-center py-5">
                        <i class="material-icons display-4 text-muted">campaign</i>
                        <h4 class="text-muted mt-3">No promotions found</h4>
                        <p class="text-muted">Create your first promotion to get started</p>
                      </div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Create Promotion Modal -->
<div class="modal fade" id="createPromotionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Promotion</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Promotion Title *</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="e.g., Summer Sale 2024">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Describe the promotion..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Promotion Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="percentage">Percentage Discount</option>
                                    <option value="fixed">Fixed Amount Discount</option>
                                    <option value="bogo">Buy One Get One</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Value *</label>
                                <input type="number" name="discount_value" class="form-control" step="0.01" required 
                                       placeholder="e.g., 10 for 10% or $10">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date *</label>
                                <input type="date" name="end_date" class="form-control" required 
                                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="create_promotion" class="btn btn-info">Create Promotion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.promotion-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.promotion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.promotion-details .detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}
.promotion-details .detail-item i {
    font-size: 18px;
    color: #666;
}
</style>

<?php
include "footer.php";
?>