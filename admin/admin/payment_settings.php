<?php
session_start();
include("../../db.php");
include "sidenav.php";
include "topheader.php";

// Create payment_settings table before reading or saving its settings.
$create_table = "CREATE TABLE IF NOT EXISTS payment_settings (
    id INT PRIMARY KEY DEFAULT 1,
    paypal_enabled TINYINT DEFAULT 0,
    paypal_client_id VARCHAR(255),
    paypal_secret VARCHAR(255),
    stripe_enabled TINYINT DEFAULT 0,
    stripe_publishable_key VARCHAR(255),
    stripe_secret_key VARCHAR(255),
    bank_transfer_enabled TINYINT DEFAULT 1,
    bank_details TEXT,
    cod_enabled TINYINT DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if (!mysqli_query($con, $create_table)) {
    throw new RuntimeException('Unable to initialize payment settings: ' . mysqli_error($con));
}

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment_settings'])) {
        $paypal_enabled = isset($_POST['paypal_enabled']) ? 1 : 0;
        $paypal_client_id = mysqli_real_escape_string($con, $_POST['paypal_client_id']);
        $paypal_secret = mysqli_real_escape_string($con, $_POST['paypal_secret']);
        
        $stripe_enabled = isset($_POST['stripe_enabled']) ? 1 : 0;
        $stripe_publishable_key = mysqli_real_escape_string($con, $_POST['stripe_publishable_key']);
        $stripe_secret_key = mysqli_real_escape_string($con, $_POST['stripe_secret_key']);
        
        $bank_transfer_enabled = isset($_POST['bank_transfer_enabled']) ? 1 : 0;
        $bank_details = mysqli_real_escape_string($con, $_POST['bank_details']);
        
        $cod_enabled = isset($_POST['cod_enabled']) ? 1 : 0;

        // Update payment settings
        $query = "REPLACE INTO payment_settings (
            paypal_enabled, paypal_client_id, paypal_secret,
            stripe_enabled, stripe_publishable_key, stripe_secret_key,
            bank_transfer_enabled, bank_details, cod_enabled
        ) VALUES (
            '$paypal_enabled', '$paypal_client_id', '$paypal_secret',
            '$stripe_enabled', '$stripe_publishable_key', '$stripe_secret_key',
            '$bank_transfer_enabled', '$bank_details', '$cod_enabled'
        )";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Payment settings updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating payment settings: " . mysqli_error($con);
        }
    }
}

// Get current payment settings
$payment_settings = [
    'paypal_enabled' => 0,
    'paypal_client_id' => '',
    'paypal_secret' => '',
    'stripe_enabled' => 0,
    'stripe_publishable_key' => '',
    'stripe_secret_key' => '',
    'bank_transfer_enabled' => 1,
    'bank_details' => 'Bank of Liberia\nAccount: 1234567890\nRouting: 021000021',
    'cod_enabled' => 1
];

$result = mysqli_query($con, "SELECT * FROM payment_settings LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $payment_settings = mysqli_fetch_assoc($result);
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
                            <i class="material-icons text-white">credit_card</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Payment Methods</h1>
                            <p class="page-subtitle text-muted mb-0">Configure payment gateway settings</p>
                        </div>
                    </div>
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

        <form method="POST" action="">
            <div class="row">
                <!-- PayPal Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-primary text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="material-icons mr-2">account_balance_wallet</i>
                                    PayPal
                                </h6>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="paypal_enabled" class="form-check-input" 
                                           id="paypal_enabled" <?php echo $payment_settings['paypal_enabled'] ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Client ID</label>
                                <input type="text" name="paypal_client_id" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['paypal_client_id']); ?>"
                                       placeholder="PayPal Client ID">
                            </div>
                            <div class="form-group">
                                <label>Secret Key</label>
                                <input type="password" name="paypal_secret" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['paypal_secret']); ?>"
                                       placeholder="PayPal Secret Key">
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">info</i>
                                    Configure PayPal settings to accept online payments
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stripe Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-info text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="material-icons mr-2">payment</i>
                                    Stripe
                                </h6>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="stripe_enabled" class="form-check-input" 
                                           id="stripe_enabled" <?php echo $payment_settings['stripe_enabled'] ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Publishable Key</label>
                                <input type="text" name="stripe_publishable_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['stripe_publishable_key']); ?>"
                                       placeholder="Stripe Publishable Key">
                            </div>
                            <div class="form-group">
                                <label>Secret Key</label>
                                <input type="password" name="stripe_secret_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['stripe_secret_key']); ?>"
                                       placeholder="Stripe Secret Key">
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">info</i>
                                    Configure Stripe settings to accept credit card payments
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-warning text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="material-icons mr-2">account_balance</i>
                                    Bank Transfer
                                </h6>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="bank_transfer_enabled" class="form-check-input" 
                                           id="bank_transfer_enabled" <?php echo $payment_settings['bank_transfer_enabled'] ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Bank Details</label>
                                <textarea name="bank_details" class="form-control" rows="5" 
                                          placeholder="Enter bank account details for transfers"><?php echo htmlspecialchars($payment_settings['bank_details']); ?></textarea>
                            </div>
                            <div class="alert alert-warning">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">info</i>
                                    These details will be shown to customers who choose bank transfer
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash on Delivery -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-success text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="material-icons mr-2">local_atm</i>
                                    Cash on Delivery
                                </h6>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="cod_enabled" class="form-check-input" 
                                           id="cod_enabled" <?php echo $payment_settings['cod_enabled'] ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="material-icons mr-2">local_shipping</i>
                                <strong>Cash on Delivery</strong>
                                <p class="mb-0 mt-2">Allow customers to pay when they receive their orders</p>
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">info</i>
                                    Popular payment method for local deliveries in Liberia
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <button type="submit" name="update_payment_settings" class="btn btn-success btn-lg">
                        <i class="material-icons mr-2">save</i>Save Payment Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle payment method sections based on enabled status
function togglePaymentSection(checkboxId, cardSelector) {
    const checkbox = document.getElementById(checkboxId);
    const card = document.querySelector(cardSelector);
    
    function updateCardStyle() {
        if (!checkbox.checked) {
            card.style.opacity = '0.7';
            card.querySelectorAll('input, textarea').forEach(input => {
                input.disabled = true;
            });
        } else {
            card.style.opacity = '1';
            card.querySelectorAll('input, textarea').forEach(input => {
                input.disabled = false;
            });
        }
    }
    
    checkbox.addEventListener('change', updateCardStyle);
    updateCardStyle(); // Initial call
}

// Initialize toggles
document.addEventListener('DOMContentLoaded', function() {
    togglePaymentSection('paypal_enabled', '.card:has(#paypal_enabled)');
    togglePaymentSection('stripe_enabled', '.card:has(#stripe_enabled)');
    togglePaymentSection('bank_transfer_enabled', '.card:has(#bank_transfer_enabled)');
    togglePaymentSection('cod_enabled', '.card:has(#cod_enabled)');
});
</script>

<?php
include "footer.php";
?>