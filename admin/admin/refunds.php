<?php
// refunds.php
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

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $refund_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'process_refund':
            processRefund($con, $refund_id);
            break;
        case 'reject_refund':
            rejectRefund($con, $refund_id);
            break;
        case 'view':
            viewRefund($con, $refund_id);
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_refund'])) {
        createRefund($con);
    } elseif (isset($_POST['update_refund'])) {
        updateRefund($con);
    }
}

function processRefund($con, $refund_id) {
    $query = "UPDATE refunds SET status = 'processed', processed_at = NOW() WHERE refund_id = '$refund_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Refund processed successfully!";
    } else {
        $_SESSION['error'] = "Error processing refund: " . mysqli_error($con);
    }
}

function rejectRefund($con, $refund_id) {
    $reason = $_GET['reason'] ?? 'No reason provided';
    $query = "UPDATE refunds SET status = 'rejected', rejection_reason = '$reason', processed_at = NOW() WHERE refund_id = '$refund_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Refund rejected successfully!";
    } else {
        $_SESSION['error'] = "Error rejecting refund: " . mysqli_error($con);
    }
}

function viewRefund($con, $refund_id) {
    $query = "SELECT r.*, o.order_id, u.first_name, u.last_name, u.email 
             FROM refunds r 
             JOIN orders_info o ON r.order_id = o.order_id 
             JOIN user_info u ON o.user_id = u.user_id 
             WHERE r.refund_id = '$refund_id'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Refund not found.";
        return;
    }
    
    $refund = mysqli_fetch_assoc($result);
    // Display refund details in modal
}

function createRefund($con) {
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $refund_amount = mysqli_real_escape_string($con, $_POST['refund_amount']);
    $refund_reason = mysqli_real_escape_string($con, $_POST['refund_reason']);
    $refund_method = mysqli_real_escape_string($con, $_POST['refund_method']);
    
    $query = "INSERT INTO refunds (order_id, refund_amount, refund_reason, refund_method, status) 
              VALUES ('$order_id', '$refund_amount', '$refund_reason', '$refund_method', 'pending')";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Refund request created successfully!";
    } else {
        $_SESSION['error'] = "Error creating refund: " . mysqli_error($con);
    }
}

function updateRefund($con) {
    $refund_id = mysqli_real_escape_string($con, $_POST['refund_id']);
    $refund_amount = mysqli_real_escape_string($con, $_POST['refund_amount']);
    $refund_reason = mysqli_real_escape_string($con, $_POST['refund_reason']);
    
    $query = "UPDATE refunds SET refund_amount = '$refund_amount', refund_reason = '$refund_reason' 
              WHERE refund_id = '$refund_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Refund updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating refund: " . mysqli_error($con);
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
                            <i class="material-icons text-white">money_off</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Refunds Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage customer refunds and returns</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-danger" data-toggle="modal" data-target="#createRefundModal">
                        <i class="material-icons mr-2">add</i>Create Refund
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

        <!-- Refunds Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">pending_actions</i>
                        </div>
                        <p class="card-category">Pending Refunds</p>
                        <h3 class="card-title">
                            <?php
                            $pending_query = "SELECT COUNT(*) as total FROM refunds WHERE status = 'pending'";
                            $pending_result = mysqli_query($con, $pending_query);
                            $pending_data = mysqli_fetch_assoc($pending_result);
                            echo $pending_data['total'];
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
                        <p class="card-category">Processed</p>
                        <h3 class="card-title">
                            <?php
                            $processed_query = "SELECT COUNT(*) as total FROM refunds WHERE status = 'processed'";
                            $processed_result = mysqli_query($con, $processed_query);
                            $processed_data = mysqli_fetch_assoc($processed_result);
                            echo $processed_data['total'];
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
                        <p class="card-category">Rejected</p>
                        <h3 class="card-title">
                            <?php
                            $rejected_query = "SELECT COUNT(*) as total FROM refunds WHERE status = 'rejected'";
                            $rejected_result = mysqli_query($con, $rejected_query);
                            $rejected_data = mysqli_fetch_assoc($rejected_result);
                            echo $rejected_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">attach_money</i>
                        </div>
                        <p class="card-category">Total Refunded</p>
                        <h3 class="card-title">
                            <?php
                            $total_refunded_query = "SELECT SUM(refund_amount) as total FROM refunds WHERE status = 'processed'";
                            $total_refunded_result = mysqli_query($con, $total_refunded_query);
                            $total_refunded_data = mysqli_fetch_assoc($total_refunded_result);
                            echo '$' . number_format($total_refunded_data['total'] ?? 0, 0);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refunds Tabs -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-tabs card-header-tabs" id="refundsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                            Pending Refunds <span class="badge badge-warning" id="pendingCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="processed-tab" data-toggle="tab" href="#processed" role="tab">
                            Processed <span class="badge badge-success" id="processedCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab">
                            Rejected <span class="badge badge-danger" id="rejectedCount">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="refundsTabsContent">
                    <!-- Pending Refunds Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Requested</th>
                                        <th>Urgency</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pending_refunds_query = "SELECT r.*, o.order_id, u.first_name, u.last_name, u.email,
                                                            TIMESTAMPDIFF(DAY, r.created_at, NOW()) as days_pending
                                                            FROM refunds r 
                                                            JOIN orders_info o ON r.order_id = o.order_id 
                                                            JOIN user_info u ON o.user_id = u.user_id 
                                                            WHERE r.status = 'pending'
                                                            ORDER BY r.created_at ASC";
                                    $pending_refunds_result = mysqli_query($con, $pending_refunds_query);
                                    
                                    if ($pending_refunds_result && mysqli_num_rows($pending_refunds_result) > 0) {
                                        while($refund = mysqli_fetch_assoc($refund)):
                                            $urgency_class = $refund['days_pending'] > 7 ? 'danger' : ($refund['days_pending'] > 3 ? 'warning' : 'info');
                                    ?>
                                    <tr>
                                        <td>#<?php echo $refund['refund_id']; ?></td>
                                        <td>
                                            <a href="orders.php?action=view&id=<?php echo $refund['order_id']; ?>" class="text-primary">
                                                #<?php echo $refund['order_id']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($refund['first_name'] . ' ' . $refund['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($refund['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-danger">$<?php echo number_format($refund['refund_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($refund['refund_reason']); ?></small>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($refund['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $urgency_class; ?>">
                                                <?php echo $refund['days_pending']; ?> days
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#viewRefundModal"
                                                        data-refund-id="<?php echo $refund['refund_id']; ?>">
                                                    <i class="material-icons" style="font-size:16px;">visibility</i>
                                                </button>
                                                <a href="refunds.php?action=process_refund&id=<?php echo $refund['refund_id']; ?>" 
                                                   class="btn btn-success"
                                                   onclick="return confirm('Process this refund?')">
                                                    <i class="material-icons" style="font-size:16px;">check</i>
                                                </a>
                                                <button class="btn btn-outline-danger" 
                                                        data-toggle="modal" 
                                                        data-target="#rejectRefundModal"
                                                        data-refund-id="<?php echo $refund['refund_id']; ?>">
                                                    <i class="material-icons" style="font-size:16px;">close</i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center py-4">No pending refunds found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Processed Refunds Tab -->
                    <div class="tab-pane fade" id="processed" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Processed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $processed_refunds_query = "SELECT r.*, o.order_id, u.first_name, u.last_name, u.email
                                                              FROM refunds r 
                                                              JOIN orders_info o ON r.order_id = o.order_id 
                                                              JOIN user_info u ON o.user_id = u.user_id 
                                                              WHERE r.status = 'processed'
                                                              ORDER BY r.processed_at DESC";
                                    $processed_refunds_result = mysqli_query($con, $processed_refunds_query);
                                    
                                    if ($processed_refunds_result && mysqli_num_rows($processed_refunds_result) > 0) {
                                        while($refund = mysqli_fetch_assoc($processed_refunds_result)):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $refund['refund_id']; ?></td>
                                        <td>#<?php echo $refund['order_id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($refund['first_name'] . ' ' . $refund['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($refund['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-success">$<?php echo number_format($refund['refund_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-light text-capitalize">
                                                <?php echo str_replace('_', ' ', $refund['refund_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($refund['processed_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    data-toggle="modal" 
                                                    data-target="#viewRefundModal"
                                                    data-refund-id="<?php echo $refund['refund_id']; ?>">
                                                <i class="material-icons" style="font-size:16px;">visibility</i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center py-4">No processed refunds found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rejected Refunds Tab -->
                    <div class="tab-pane fade" id="rejected" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Rejection Reason</th>
                                        <th>Rejected Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rejected_refunds_query = "SELECT r.*, o.order_id, u.first_name, u.last_name, u.email
                                                             FROM refunds r 
                                                             JOIN orders_info o ON r.order_id = o.order_id 
                                                             JOIN user_info u ON o.user_id = u.user_id 
                                                             WHERE r.status = 'rejected'
                                                             ORDER BY r.processed_at DESC";
                                    $rejected_refunds_result = mysqli_query($con, $rejected_refunds_query);
                                    
                                    if ($rejected_refunds_result && mysqli_num_rows($rejected_refunds_result) > 0) {
                                        while($refund = mysqli_fetch_assoc($rejected_refunds_result)):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $refund['refund_id']; ?></td>
                                        <td>#<?php echo $refund['order_id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($refund['first_name'] . ' ' . $refund['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($refund['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-danger">$<?php echo number_format($refund['refund_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($refund['rejection_reason']); ?></small>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($refund['processed_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    data-toggle="modal" 
                                                    data-target="#viewRefundModal"
                                                    data-refund-id="<?php echo $refund['refund_id']; ?>">
                                                <i class="material-icons" style="font-size:16px;">visibility</i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center py-4">No rejected refunds found.</td></tr>';
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

<!-- Create Refund Modal -->
<div class="modal fade" id="createRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Refund</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Order ID *</label>
                        <select name="order_id" class="form-control" required>
                            <option value="">Select Order</option>
                            <?php
                            $orders_query = "SELECT o.order_id, u.first_name, u.last_name, o.total_amt AS total_amount
                                           FROM orders_info o 
                                           JOIN user_info u ON o.user_id = u.user_id 
                                           WHERE o.payment_status = 'paid'
                                           ORDER BY o.created_at DESC";
                            $orders_result = mysqli_query($con, $orders_query);
                            while($order = mysqli_fetch_assoc($orders_result)) {
                                echo "<option value='{$order['order_id']}'>#{$order['order_id']} - {$order['first_name']} {$order['last_name']} - $" . number_format($order['total_amount'], 2) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Refund Amount *</label>
                        <input type="number" name="refund_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Refund Reason *</label>
                        <textarea name="refund_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Refund Method *</label>
                        <select name="refund_method" class="form-control" required>
                            <option value="original_payment">Original Payment Method</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="store_credit">Store Credit</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="create_refund" class="btn btn-danger">Create Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Refund Modal -->
<div class="modal fade" id="viewRefundModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Refund Details - #<span id="modal-refund-id"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Refund Information</h6>
                        <p><strong>Status:</strong> <span id="modal-refund-status"></span></p>
                        <p><strong>Amount:</strong> <span id="modal-refund-amount"></span></p>
                        <p><strong>Method:</strong> <span id="modal-refund-method"></span></p>
                        <p><strong>Reason:</strong> <span id="modal-refund-reason"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p><strong>Order ID:</strong> <span id="modal-order-id"></span></p>
                        <p><strong>Customer:</strong> <span id="modal-customer-name"></span></p>
                        <p><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                        <p><strong>Order Total:</strong> <span id="modal-order-total"></span></p>
                    </div>
                </div>
                <div class="mt-4" id="modal-additional-info">
                    <!-- Additional refund information -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printRefund()">
                    <i class="material-icons mr-1">print</i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Refund Modal -->
<div class="modal fade" id="rejectRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Refund</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject-refund-id">
                <div class="form-group">
                    <label>Rejection Reason *</label>
                    <textarea id="rejection-reason" class="form-control" rows="3" placeholder="Provide a reason for rejecting this refund..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmRejection()">Reject Refund</button>
            </div>
        </div>
    </div>
</div>

<script>
// Update refund counts
document.addEventListener('DOMContentLoaded', function() {
    const pendingCount = <?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM refunds WHERE status = 'pending'")); ?>;
    const processedCount = <?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM refunds WHERE status = 'processed'")); ?>;
    const rejectedCount = <?php echo mysqli_num_rows(mysqli_query($con, "SELECT * FROM refunds WHERE status = 'rejected'")); ?>;
    
    document.getElementById('pendingCount').textContent = pendingCount;
    document.getElementById('processedCount').textContent = processedCount;
    document.getElementById('rejectedCount').textContent = rejectedCount;
});

// View refund modal handler
$('#viewRefundModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var refundId = button.data('refund-id');
    
    var modal = $(this);
    modal.find('#modal-refund-id').text(refundId);
    
    // Load refund details (simulated)
    loadRefundDetails(refundId);
});

// Reject refund modal handler
$('#rejectRefundModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var refundId = button.data('refund-id');
    
    var modal = $(this);
    modal.find('#reject-refund-id').val(refundId);
});

function loadRefundDetails(refundId) {
    // Simulate loading refund details
    $('#modal-refund-status').html('<span class="badge badge-warning">Loading...</span>');
    $('#modal-refund-amount').text('Loading...');
    $('#modal-refund-method').text('Loading...');
    $('#modal-refund-reason').text('Loading...');
    $('#modal-order-id').text('Loading...');
    $('#modal-customer-name').text('Loading...');
    $('#modal-customer-email').text('Loading...');
    $('#modal-order-total').text('Loading...');
    
    setTimeout(() => {
        // Simulated data
        $('#modal-refund-status').html('<span class="badge badge-warning">Pending</span>');
        $('#modal-refund-amount').html('<strong class="text-danger">$45.00</strong>');
        $('#modal-refund-method').text('Original Payment Method');
        $('#modal-refund-reason').text('Product damaged during shipping');
        $('#modal-order-id').html('<a href="orders.php?action=view&id=1001" class="text-primary">#1001</a>');
        $('#modal-customer-name').text('John Doe');
        $('#modal-customer-email').text('john.doe@example.com');
        $('#modal-order-total').text('$245.00');
    }, 1000);
}

function confirmRejection() {
    const refundId = document.getElementById('reject-refund-id').value;
    const reason = document.getElementById('rejection-reason').value;
    
    if (!reason.trim()) {
        alert('Please provide a rejection reason.');
        return;
    }
    
    if (confirm('Are you sure you want to reject this refund?')) {
        window.location.href = 'refunds.php?action=reject_refund&id=' + refundId + '&reason=' + encodeURIComponent(reason);
    }
}

function printRefund() {
    window.print();
}

// Export functionality
function exportRefundsReport() {
    alert('Refunds report export would be implemented here');
}
</script>

<?php
include "footer.php";
?>