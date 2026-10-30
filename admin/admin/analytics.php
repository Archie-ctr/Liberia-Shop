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

// Get analytics data (simulated for demo)
$analytics_data = [
    'visitors_today' => rand(150, 300),
    'visitors_week' => rand(1200, 2000),
    'page_views' => rand(5000, 8000),
    'bounce_rate' => rand(35, 65),
    'avg_session' => '00:' . str_pad(rand(2, 8), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(10, 59), 2, '0', STR_PAD_LEFT),
    'conversion_rate' => rand(2, 8) . '%'
];

// Get popular products (simulated)
$popular_products = [
    ['name' => 'iPhone 14 Pro', 'views' => 1245, 'sales' => 89],
    ['name' => 'Samsung Galaxy S23', 'views' => 987, 'sales' => 67],
    ['name' => 'MacBook Air M2', 'views' => 856, 'sales' => 45],
    ['name' => 'Sony WH-1000XM4', 'views' => 723, 'sales' => 78],
    ['name' => 'iPad Pro 12.9"', 'views' => 654, 'sales' => 34]
];

// Get traffic sources (simulated)
$traffic_sources = [
    ['source' => 'Direct', 'visitors' => 845, 'percentage' => 42],
    ['source' => 'Google', 'visitors' => 567, 'percentage' => 28],
    ['source' => 'Facebook', 'visitors' => 234, 'percentage' => 12],
    ['source' => 'Instagram', 'visitors' => 178, 'percentage' => 9],
    ['source' => 'Other', 'visitors' => 176, 'percentage' => 9]
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
                        <div class="header-icon-container bg-success rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">analytics</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Analytics Dashboard</h1>
                            <p class="page-subtitle text-muted mb-0">Track website performance and user behavior</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary active" data-period="today">Today</button>
                        <button class="btn btn-outline-secondary" data-period="week">Week</button>
                        <button class="btn btn-outline-secondary" data-period="month">Month</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['visitors_today']; ?></h3>
                        <small>Visitors Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['visitors_week']; ?></h3>
                        <small>This Week</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['page_views']; ?></h3>
                        <small>Page Views</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['bounce_rate']; ?>%</h3>
                        <small>Bounce Rate</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['avg_session']; ?></h3>
                        <small>Avg. Session</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $analytics_data['conversion_rate']; ?></h3>
                        <small>Conversion Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Visitor Statistics -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">trending_up</i>
                            Visitor Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="visitorChart" style="height: 300px;">
                            <!-- Chart would be rendered here with a JavaScript library -->
                            <div class="text-center py-5">
                                <i class="material-icons display-4 text-muted">bar_chart</i>
                                <p class="text-muted mt-3">Visitor chart would be displayed here</p>
                                <p class="text-muted">(Integration with Chart.js or similar library)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Products -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-success">shopping_cart</i>
                            Popular Products
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Page Views</th>
                                        <th>Sales</th>
                                        <th>Conversion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $product['name']; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $product['views']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?php echo $product['sales']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?php echo round(($product['sales'] / $product['views']) * 100, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Traffic Sources -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-info">pie_chart</i>
                            Traffic Sources
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($traffic_sources as $source): ?>
                        <div class="traffic-source mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $source['source']; ?></span>
                                <span><?php echo $source['visitors']; ?> (<?php echo $source['percentage']; ?>%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo $source['percentage']; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Real-time Activity -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-warning">access_time</i>
                            Real-time Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-primary rounded-circle d-flex align-items-center justify-content-center mr-3">
                                    <i class="material-icons text-white">person</i>
                                </div>
                                <div>
                                    <small class="d-block">New user registered</small>
                                    <small class="text-muted">2 minutes ago</small>
                                </div>
                            </div>
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-success rounded-circle d-flex align-items-center justify-content-center mr-3">
                                    <i class="material-icons text-white">shopping_cart</i>
                                </div>
                                <div>
                                    <small class="d-block">Order #1234 completed</small>
                                    <small class="text-muted">5 minutes ago</small>
                                </div>
                            </div>
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-info rounded-circle d-flex align-items-center justify-content-center mr-3">
                                    <i class="material-icons text-white">visibility</i>
                                </div>
                                <div>
                                    <small class="d-block">Product page viewed</small>
                                    <small class="text-muted">8 minutes ago</small>
                                </div>
                            </div>
                            <div class="activity-item d-flex align-items-center">
                                <div class="activity-icon bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3">
                                    <i class="material-icons text-white">search</i>
                                </div>
                                <div>
                                    <small class="d-block">Search for "laptop"</small>
                                    <small class="text-muted">12 minutes ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device Breakdown -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-danger">devices</i>
                            Device Breakdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="device-stats">
                            <div class="device-item text-center mb-3">
                                <i class="material-icons display-4 text-primary">smartphone</i>
                                <h5 class="mt-2">62%</h5>
                                <small class="text-muted">Mobile</small>
                            </div>
                            <div class="device-item text-center mb-3">
                                <i class="material-icons display-4 text-info">computer</i>
                                <h5 class="mt-2">28%</h5>
                                <small class="text-muted">Desktop</small>
                            </div>
                            <div class="device-item text-center">
                                <i class="material-icons display-4 text-success">tablet</i>
                                <h5 class="mt-2">10%</h5>
                                <small class="text-muted">Tablet</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.activity-icon {
    width: 40px;
    height: 40px;
}
.device-stats {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
}
.traffic-source .progress-bar {
    background-color: #4361ee;
}
</style>

<script>
// Period selector functionality
document.querySelectorAll('[data-period]').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('[data-period]').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        this.classList.add('active');
        
        // In a real application, you would fetch data for the selected period
        const period = this.dataset.period;
        console.log('Loading analytics for period:', period);
        
        // Simulate loading new data
        // This would typically be an AJAX call to fetch period-specific data
    });
});

// Simulate real-time updates
setInterval(function() {
    // In a real application, this would fetch fresh analytics data
    console.log('Refreshing analytics data...');
}, 30000); // Refresh every 30 seconds
</script>

<?php
include "footer.php";
?>