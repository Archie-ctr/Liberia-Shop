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

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = mysqli_real_escape_string($con, $_POST['report_type']);
    $date_range = mysqli_real_escape_string($con, $_POST['date_range']);
    $format = mysqli_real_escape_string($con, $_POST['format']);
    
    // Simulate report generation
    $_SESSION['success'] = "Report generated successfully!";
    
    // In a real application, you would generate and download the report
    if ($format === 'pdf') {
        // Generate PDF report
    } elseif ($format === 'excel') {
        // Generate Excel report
    }
}

// Sample report data
$sales_data = [
    ['month' => 'Jan', 'revenue' => 12500, 'orders' => 89, 'growth' => 12],
    ['month' => 'Feb', 'revenue' => 14200, 'orders' => 102, 'growth' => 15],
    ['month' => 'Mar', 'revenue' => 13800, 'orders' => 97, 'growth' => -3],
    ['month' => 'Apr', 'revenue' => 15600, 'orders' => 115, 'growth' => 13],
    ['month' => 'May', 'revenue' => 16800, 'orders' => 124, 'growth' => 8],
    ['month' => 'Jun', 'revenue' => 18200, 'orders' => 135, 'growth' => 9]
];

$top_customers = [
    ['name' => 'John Doe', 'email' => 'john@example.com', 'orders' => 12, 'total' => 2450],
    ['name' => 'Sarah Smith', 'email' => 'sarah@example.com', 'orders' => 8, 'total' => 1890],
    ['name' => 'Mike Johnson', 'email' => 'mike@example.com', 'orders' => 6, 'total' => 1560],
    ['name' => 'Emily Brown', 'email' => 'emily@example.com', 'orders' => 5, 'total' => 1340],
    ['name' => 'David Wilson', 'email' => 'david@example.com', 'orders' => 4, 'total' => 980]
];
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
                            <i class="material-icons text-white">assessment</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Reports & Analytics</h1>
                            <p class="page-subtitle text-muted mb-0">Generate detailed business reports</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Generator -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-primary">description</i>
                    Generate Report
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Report Type</label>
                                <select name="report_type" class="form-control" required>
                                    <option value="">Select Report Type</option>
                                    <option value="sales">Sales Report</option>
                                    <option value="customers">Customer Report</option>
                                    <option value="products">Product Performance</option>
                                    <option value="inventory">Inventory Report</option>
                                    <option value="financial">Financial Summary</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date Range</label>
                                <select name="date_range" class="form-control" required>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="week" selected>This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="quarter">This Quarter</option>
                                    <option value="year">This Year</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Export Format</label>
                                <select name="format" class="form-control" required>
                                    <option value="pdf">PDF Document</option>
                                    <option value="excel">Excel Spreadsheet</option>
                                    <option value="csv">CSV File</option>
                                    <option value="html">HTML Report</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" name="generate_report" class="btn btn-primary">
                                <i class="material-icons mr-2">get_app</i>Generate Report
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="previewReport()">
                                <i class="material-icons mr-2">visibility</i>Preview
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Reports -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white report-card">
                    <div class="card-body text-center">
                        <i class="material-icons display-4 mb-3">trending_up</i>
                        <h5>Sales Report</h5>
                        <p class="mb-0">Daily sales performance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white report-card">
                    <div class="card-body text-center">
                        <i class="material-icons display-4 mb-3">people</i>
                        <h5>Customer Report</h5>
                        <p class="mb-0">Customer analytics</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white report-card">
                    <div class="card-body text-center">
                        <i class="material-icons display-4 mb-3">inventory</i>
                        <h5>Inventory Report</h5>
                        <p class="mb-0">Stock levels & alerts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white report-card">
                    <div class="card-body text-center">
                        <i class="material-icons display-4 mb-3">payments</i>
                        <h5>Financial Report</h5>
                        <p class="mb-0">Revenue & expenses</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales Report Preview -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-success">bar_chart</i>
                            Sales Report (Last 6 Months)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Month</th>
                                        <th>Revenue</th>
                                        <th>Orders</th>
                                        <th>Growth</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_data as $data): ?>
                                    <tr>
                                        <td><strong><?php echo $data['month']; ?></strong></td>
                                        <td>$<?php echo number_format($data['revenue']); ?></td>
                                        <td><?php echo $data['orders']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $data['growth'] >= 0 ? 'success' : 'danger'; ?>">
                                                <?php echo $data['growth'] >= 0 ? '+' : ''; ?><?php echo $data['growth']; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="material-icons" style="font-size:16px;">visibility</i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td><strong>$<?php echo number_format(array_sum(array_column($sales_data, 'revenue'))); ?></strong></td>
                                        <td><strong><?php echo array_sum(array_column($sales_data, 'orders')); ?></strong></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">star</i>
                            Top Customers
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_customers as $customer): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $customer['name']; ?></h6>
                                        <small class="text-muted"><?php echo $customer['email']; ?></small>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-muted"><?php echo $customer['orders']; ?> orders</small>
                                        <div class="text-success">$<?php echo number_format($customer['total']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Report Templates -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-warning">description</i>
                            Report Templates
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="material-icons mr-3 text-success">receipt</i>
                                Monthly Sales Summary
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="material-icons mr-3 text-primary">people</i>
                                Customer Acquisition Report
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="material-icons mr-3 text-info">inventory</i>
                                Inventory Status Report
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="material-icons mr-3 text-warning">trending_up</i>
                                Performance Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}
.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>

<script>
function previewReport() {
    alert('Report preview would be displayed here. In a real application, this would show a preview of the selected report.');
}

// Quick report cards
document.querySelectorAll('.report-card').forEach(card => {
    card.addEventListener('click', function() {
        const reportType = this.querySelector('h5').textContent.toLowerCase();
        alert(`Generating ${reportType}...`);
        // In real application, this would trigger report generation
    });
});

// Auto-populate date range for custom reports
document.querySelector('select[name="date_range"]').addEventListener('change', function() {
    if (this.value === 'custom') {
        // Show custom date inputs
        const customDateHTML = `
            <div class="row mt-3" id="customDateRange">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </div>
            </div>
        `;
        
        if (!document.getElementById('customDateRange')) {
            this.closest('.row').insertAdjacentHTML('afterend', customDateHTML);
        }
    } else {
        // Remove custom date inputs
        const customDateRange = document.getElementById('customDateRange');
        if (customDateRange) {
            customDateRange.remove();
        }
    }
});
</script>

<?php
include "footer.php";
?>