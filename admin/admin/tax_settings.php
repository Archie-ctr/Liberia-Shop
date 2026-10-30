<?php
session_start();
include("../../db.php");
include "sidenav.php";
include "topheader.php";

// Keep global tax configuration separate from tax rates by location.
$create_table = "CREATE TABLE IF NOT EXISTS tax_configuration (
    id INT PRIMARY KEY DEFAULT 1,
    tax_enabled TINYINT DEFAULT 1,
    tax_rate DECIMAL(5,2) DEFAULT 10.00,
    prices_include_tax TINYINT DEFAULT 0,
    tax_number VARCHAR(100),
    tax_based_on ENUM('shipping', 'billing') DEFAULT 'shipping',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if (!mysqli_query($con, $create_table)) {
    throw new RuntimeException('Unable to initialize tax configuration: ' . mysqli_error($con));
}

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_tax_settings'])) {
        $tax_enabled = isset($_POST['tax_enabled']) ? 1 : 0;
        $tax_rate = mysqli_real_escape_string($con, $_POST['tax_rate']);
        $prices_include_tax = isset($_POST['prices_include_tax']) ? 1 : 0;
        $tax_number = mysqli_real_escape_string($con, $_POST['tax_number']);
        $tax_based_on = mysqli_real_escape_string($con, $_POST['tax_based_on']);

        // Update tax settings
        $query = "REPLACE INTO tax_configuration (
            tax_enabled, tax_rate, prices_include_tax, tax_number, tax_based_on
        ) VALUES (
            '$tax_enabled', '$tax_rate', '$prices_include_tax', '$tax_number', '$tax_based_on'
        )";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Tax settings updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating tax settings: " . mysqli_error($con);
        }
    }
}

// Get current tax settings
$tax_settings = [
    'tax_enabled' => 1,
    'tax_rate' => '10.00',
    'prices_include_tax' => 0,
    'tax_number' => 'TAX-123456',
    'tax_based_on' => 'shipping'
];

$result = mysqli_query($con, "SELECT * FROM tax_configuration LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $tax_settings = array_merge($tax_settings, mysqli_fetch_assoc($result));
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
                            <i class="material-icons text-white">calculate</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Tax Settings</h1>
                            <p class="page-subtitle text-muted mb-0">Configure tax rates and calculations</p>
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
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="material-icons mr-2 text-danger">calculate</i>
                                Tax Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="tax_enabled" class="form-check-input" 
                                           id="tax_enabled" <?php echo $tax_settings['tax_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tax_enabled">
                                        <strong>Enable Taxes</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Enable tax calculation for orders
                                    </small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100"
                                           value="<?php echo $tax_settings['tax_rate']; ?>"
                                           placeholder="10.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Standard tax rate applied to orders</small>
                            </div>

                            <div class="form-group">
                                <label>Tax Based On</label>
                                <select name="tax_based_on" class="form-control">
                                    <option value="shipping" <?php echo $tax_settings['tax_based_on'] === 'shipping' ? 'selected' : ''; ?>>Customer Shipping Address</option>
                                    <option value="billing" <?php echo $tax_settings['tax_based_on'] === 'billing' ? 'selected' : ''; ?>>Customer Billing Address</option>
                                </select>
                                <small class="form-text text-muted">Determine tax rate based on customer location</small>
                            </div>

                            <div class="form-group">
                                <label>Tax Number / VAT ID</label>
                                <input type="text" name="tax_number" class="form-control"
                                       value="<?php echo htmlspecialchars($tax_settings['tax_number']); ?>"
                                       placeholder="TAX-123456">
                                <small class="form-text text-muted">Your business tax identification number</small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="prices_include_tax" class="form-check-input" 
                                           id="prices_include_tax" <?php echo $tax_settings['prices_include_tax'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="prices_include_tax">
                                        Prices Include Tax
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        When enabled, product prices shown to customers include tax
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Rates by Location -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="material-icons mr-2 text-info">location_on</i>
                                Tax Rates by Location
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Country</th>
                                            <th>State/Region</th>
                                            <th>Tax Rate</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Liberia</td>
                                            <td>All</td>
                                            <td><?php echo $tax_settings['tax_rate']; ?>%</td>
                                            <td><span class="badge badge-success">Active</span></td>
                                        </tr>
                                        <tr>
                                            <td>United States</td>
                                            <td>All</td>
                                            <td>0%</td>
                                            <td><span class="badge badge-secondary">No Tax</span></td>
                                        </tr>
                                        <tr>
                                            <td>European Union</td>
                                            <td>All</td>
                                            <td>0%</td>
                                            <td><span class="badge badge-secondary">No Tax</span></td>
                                        </tr>
                                        <tr>
                                            <td>Other Countries</td>
                                            <td>All</td>
                                            <td>0%</td>
                                            <td><span class="badge badge-secondary">No Tax</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">info</i>
                                    Currently, tax is only applied to orders within Liberia. International orders are tax-free.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Tax Calculator -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white py-3">
                            <h6 class="card-title mb-0 d-flex align-items-center">
                                <i class="material-icons mr-2">calculate</i>
                                Tax Calculator
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Order Amount</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" id="order_amount" class="form-control" value="100.00" step="0.01">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tax Rate</label>
                                <div class="input-group">
                                    <input type="number" id="calc_tax_rate" class="form-control" 
                                           value="<?php echo $tax_settings['tax_rate']; ?>" step="0.01" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="tax-breakdown">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <strong id="subtotal">$100.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (<?php echo $tax_settings['tax_rate']; ?>%):</span>
                                    <strong id="tax_amount">$10.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total:</span>
                                    <strong id="total_amount" class="text-success">$110.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success text-white py-3">
                            <h6 class="card-title mb-0 d-flex align-items-center">
                                <i class="material-icons mr-2">flash_on</i>
                                Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="shop_settings.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="material-icons mr-3 text-primary">store</i>
                                    Shop Settings
                                </a>
                                <a href="payment_settings.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="material-icons mr-3 text-success">payment</i>
                                    Payment Methods
                                </a>
                                <a href="shipping_settings.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="material-icons mr-3 text-warning">local_shipping</i>
                                    Shipping Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body text-center">
                    <button type="submit" name="update_tax_settings" class="btn btn-danger btn-lg">
                        <i class="material-icons mr-2">save</i>Save Tax Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Tax calculator functionality
function calculateTax() {
    const orderAmount = parseFloat(document.getElementById('order_amount').value) || 0;
    const taxRate = parseFloat(document.getElementById('calc_tax_rate').value) || 0;
    
    const taxAmount = (orderAmount * taxRate) / 100;
    const totalAmount = orderAmount + taxAmount;
    
    document.getElementById('subtotal').textContent = '$' + orderAmount.toFixed(2);
    document.getElementById('tax_amount').textContent = '$' + taxAmount.toFixed(2);
    document.getElementById('total_amount').textContent = '$' + totalAmount.toFixed(2);
}

// Update tax rate when settings change
document.querySelector('input[name="tax_rate"]').addEventListener('input', function() {
    document.getElementById('calc_tax_rate').value = this.value;
    calculateTax();
});

// Initialize calculator
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('order_amount').addEventListener('input', calculateTax);
    calculateTax();
});

// Toggle tax settings based on enabled status
document.getElementById('tax_enabled').addEventListener('change', function() {
    const taxInputs = document.querySelectorAll('input[name="tax_rate"], input[name="tax_number"], select[name="tax_based_on"], input[name="prices_include_tax"]');
    taxInputs.forEach(input => {
        input.disabled = !this.checked;
    });
});
</script>

<?php
include "footer.php";
?>