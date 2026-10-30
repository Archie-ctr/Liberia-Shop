<?php
// payment.php
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
    $payment_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'view':
            viewPayment($con, $payment_id);
            break;
        case 'refund':
            refundPayment($con, $payment_id);
            break;
        case 'delete':
            deletePayment($con, $payment_id);
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment'])) {
        updatePayment($con);
    } elseif (isset($_POST['add_payment'])) {
        addPayment($con);
    }
}

function viewPayment($con, $payment_id) {
    $query = "SELECT p.*, o.order_id, o.total_amount, u.first_name, u.last_name, u.email
             FROM payments p
             JOIN orders_info o ON p.order_id = o.order_id
             JOIN user_info u ON o.user_id = u.user_id
             WHERE p.payment_id = '$payment_id'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Payment not found.";
        return;
    }
    
    $payment = mysqli_fetch_assoc($result);
    
    // Display payment details in a modal or separate page
    // Implementation depends on your UI preference
}

function refundPayment($con, $payment_id) {
    $query = "UPDATE payments SET payment_status = 'refunded' WHERE payment_id = '$payment_id'";
    if (mysqli_query($con, $query)) {
        // Update order status as well
        $payment_query = "SELECT order_id FROM payments WHERE payment_id = '$payment_id'";
        $result = mysqli_query($con, $payment_query);
        $payment = mysqli_fetch_assoc($result);
        
        $update_order = "UPDATE orders_info SET payment_status = 'refunded' WHERE order_id = '{$payment['order_id']}'";
        mysqli_query($con, $update_order);
        
        $_SESSION['success'] = "Payment refunded successfully!";
    } else {
        $_SESSION['error'] = "Error refunding payment: " . mysqli_error($con);
    }
}

function deletePayment($con, $payment_id) {
    $query = "DELETE FROM payments WHERE payment_id = '$payment_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Payment deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting payment: " . mysqli_error($con);
    }
}

function updatePayment($con) {
    $payment_id = mysqli_real_escape_string($con, $_POST['payment_id']);
    $payment_status = mysqli_real_escape_string($con, $_POST['payment_status']);
    $transaction_id = mysqli_real_escape_string($con, $_POST['transaction_id']);
    
    $query = "UPDATE payments SET payment_status = '$payment_status', transaction_id = '$transaction_id' 
              WHERE payment_id = '$payment_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Payment updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating payment: " . mysqli_error($con);
    }
}

function addPayment($con) {
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    $payment_amount = mysqli_real_escape_string($con, $_POST['payment_amount']);
    $transaction_id = mysqli_real_escape_string($con, $_POST['transaction_id']);
    $payment_status = mysqli_real_escape_string($con, $_POST['payment_status']);
    
    $payment_date = $payment_status === 'completed' ? date('Y-m-d H:i:s') : null;
    $stmt = mysqli_prepare($con, "INSERT INTO payments (order_id, payment_method, payment_amount, transaction_id, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isdsss', $order_id, $payment_method, $payment_amount, $transaction_id, $payment_status, $payment_date);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        // Keep the order status compatible with the live payment enum.
        $order_payment_status = $payment_status === 'completed' ? 'paid' : $payment_status;
        $update_stmt = mysqli_prepare($con, "UPDATE orders_info SET payment_status=? WHERE order_id=?");
        mysqli_stmt_bind_param($update_stmt, 'si', $order_payment_status, $order_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        
        $_SESSION['success'] = "Payment added successfully!";
    } else {
        $_SESSION['error'] = "Error adding payment: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
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
                        <div class="header-icon-container bg-success rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">payment</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Payment Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage customer payments and transactions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addPaymentModal">
                        <i class="material-icons mr-2">add</i>Add Payment
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

        <!-- Payments Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM payments WHERE payment_status = 'completed'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Completed Payments</p>
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
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM payments WHERE payment_status = 'pending'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Pending Payments</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">schedule</i>
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
                                    $query = "SELECT COUNT(*) as total FROM payments WHERE payment_status = 'failed'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Failed Payments</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">error</i>
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
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM payments WHERE payment_status = 'refunded'";
                                    $result = mysqli_query($con, $query);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Refunded Payments</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">undo</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-primary">list</i>
                    All Payments
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT p.*, o.order_id, o.f_name, o.email
                                     FROM payments p
                                     LEFT JOIN orders_info o ON p.order_id = o.order_id
                                     ORDER BY p.created_at DESC";
                            $result = mysqli_query($con, $query);
                            
                            while($payment = mysqli_fetch_assoc($result)):
                                $status_class = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info'
                                ][$payment['payment_status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>#<?php echo $payment['payment_id']; ?></td>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($payment['f_name'] ?: $payment['email']); ?></td>
                                <td>
                                    <span class="badge badge-light text-capitalize">
                                        <?php echo str_replace('_', ' ', $payment['payment_method']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($payment['payment_amount'], 2); ?></td>
                                <td>
                                    <small class="text-muted"><?php echo $payment['transaction_id'] ?: 'N/A'; ?></small>
                                </td>
                                <td><?php echo $payment['payment_date'] ?: $payment['created_at']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                data-toggle="modal" 
                                                data-target="#editPaymentModal" 
                                                data-payment-id="<?php echo $payment['payment_id']; ?>"
                                                data-payment-status="<?php echo $payment['payment_status']; ?>"
                                                data-transaction-id="<?php echo $payment['transaction_id']; ?>">
                                            <i class="material-icons" style="font-size:16px;">edit</i>
                                        </button>
                                        <?php if ($payment['payment_status'] === 'completed'): ?>
                                        <a href="payment.php?action=refund&id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-outline-warning" 
                                           onclick="return confirm('Are you sure you want to refund this payment?')">
                                            <i class="material-icons" style="font-size:16px;">undo</i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="payment.php?action=delete&id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this payment?')">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Payment</h5>
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
                            $orders_query = "SELECT o.order_id, o.f_name, o.email, o.total_amt
                                           FROM orders_info o
                                           WHERE o.payment_status IN ('pending', 'failed')
                                           ORDER BY o.order_id DESC";
                            $orders_result = mysqli_query($con, $orders_query);
                            while($order = mysqli_fetch_assoc($orders_result)) {
                                $customer_name = trim($order['f_name']) !== '' ? $order['f_name'] : $order['email'];
                                echo "<option value='" . (int)$order['order_id'] . "'>#" . (int)$order['order_id'] . " - " . htmlspecialchars($customer_name) . " - $" . number_format((float)$order['total_amt'], 2) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="credit_card">Credit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Amount *</label>
                        <input type="number" name="payment_amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Payment Status *</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_payment" class="btn btn-success">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="edit_payment_id">
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control" id="edit_payment_status">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" id="edit_transaction_id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#addPaymentModal, #editPaymentModal { z-index: 1055; }
.modal-backdrop.fade.show { opacity: 0.5; }
</style>
<script>
(function () {
    function openModal(modal) {
        if (!modal) return;
        modal.style.display = 'block';
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.setAttribute('data-payment-modal-backdrop', '');
        document.body.appendChild(backdrop);
    }
    function closeModal(modal) {
        if (!modal) return;
        modal.style.display = '';
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('[data-payment-modal-backdrop]').forEach(function (backdrop) { backdrop.remove(); });
        document.body.classList.remove('modal-open');
    }
    document.querySelectorAll('[data-target="#addPaymentModal"], [data-target="#editPaymentModal"]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            var modal = document.querySelector(trigger.getAttribute('data-target'));
            if (modal && typeof window.jQuery !== 'undefined' && window.jQuery.fn.modal) return;
            event.preventDefault();
            event.stopPropagation();
            openModal(modal);
        }, true);
    });
    document.querySelectorAll('#addPaymentModal [data-dismiss="modal"], #editPaymentModal [data-dismiss="modal"], .modal-backdrop').forEach(function (button) {
        button.addEventListener('click', function () { closeModal(button.closest('.modal')); });
    });
})();
// Edit payment modal handler
$('#editPaymentModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var paymentId = button.data('payment-id');
    var paymentStatus = button.data('payment-status');
    var transactionId = button.data('transaction-id');
    
    var modal = $(this);
    modal.find('#edit_payment_id').val(paymentId);
    modal.find('#edit_payment_status').val(paymentStatus);
    modal.find('#edit_transaction_id').val(transactionId);
});
</script>

<?php
include "footer.php";
?>