<?php
// revenue.php
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

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $period = $_GET['period'] ?? 'month';
    
    if ($export_type === 'excel') {
        exportToExcel($con, $period);
        exit;
    } elseif ($export_type === 'pdf') {
        exportToPDF($con, $period);
        exit;
    }
}

// Function to check if column exists
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '$column'");
    return ($result && mysqli_num_rows($result) > 0);
}

// Get revenue data with maximum compatibility
function getRevenueData($con, $period = 'month') {
    $data = ['revenue' => 0, 'orders' => 0];
    
    // Build date condition based on period - always use orders_info table
    switch ($period) {
        case 'today':
            $date_condition = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $date_condition = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $date_condition = "YEAR(o.created_at) = YEAR(CURDATE())";
            break;
        default:
            $date_condition = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
    }
    
    // Check if we have order_items table and calculate from there
    $has_order_items = mysqli_query($con, "SHOW TABLES LIKE 'order_items'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'order_items'")) > 0;
    
    if ($has_order_items) {
        // Calculate revenue from order_items table
        $query = "SELECT 
                    COALESCE(SUM(oi.total_price), 0) as revenue,
                    COUNT(DISTINCT oi.order_id) as orders
                 FROM order_items oi
                 JOIN orders_info o ON oi.order_id = o.order_id
                 WHERE $date_condition";
    } else {
        // Fallback: just count orders if order_items doesn't exist
        $query = "SELECT 
                    0 as revenue,
                    COUNT(*) as orders
                 FROM orders_info o
                 WHERE $date_condition";
    }
    
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
    }
    
    // Ensure we have numeric values
    $data['revenue'] = floatval($data['revenue'] ?? 0);
    $data['orders'] = intval($data['orders'] ?? 0);
    
    return $data;
}

// Get monthly revenue data for comparison
function getPreviousMonthRevenue($con) {
    $date_condition = "MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                      AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    
    // Check if we have order_items table
    $has_order_items = mysqli_query($con, "SHOW TABLES LIKE 'order_items'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'order_items'")) > 0;
    
    if ($has_order_items) {
        $query = "SELECT 
                    COALESCE(SUM(oi.total_price), 0) as revenue,
                    COUNT(DISTINCT oi.order_id) as orders
                 FROM order_items oi
                 JOIN orders_info o ON oi.order_id = o.order_id
                 WHERE $date_condition";
    } else {
        $query = "SELECT 
                    0 as revenue,
                    COUNT(*) as orders
                 FROM orders_info o
                 WHERE $date_condition";
    }
    
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return [
            'revenue' => floatval($data['revenue'] ?? 0),
            'orders' => intval($data['orders'] ?? 0)
        ];
    }
    
    return ['revenue' => 0, 'orders' => 0];
}

// Calculate growth percentage
function calculateGrowth($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return (($current - $previous) / $previous) * 100;
}

// Get total customers count
function getTotalCustomers($con) {
    $query = "SELECT COUNT(DISTINCT user_id) as total FROM orders_info";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return intval($data['total'] ?? 0);
    }
    return 0;
}

// Get detailed revenue data for export
function getDetailedRevenueData($con, $period = 'month') {
    $data = [];
    
    switch ($period) {
        case 'today':
            $date_condition = "DATE(o.created_at) = CURDATE()";
            $group_by = "HOUR(o.created_at)";
            break;
        case 'week':
            $date_condition = "YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
            $group_by = "DATE(o.created_at)";
            break;
        case 'month':
            $date_condition = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            $group_by = "DATE(o.created_at)";
            break;
        case 'year':
            $date_condition = "YEAR(o.created_at) = YEAR(CURDATE())";
            $group_by = "MONTH(o.created_at)";
            break;
        default:
            $date_condition = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            $group_by = "DATE(o.created_at)";
    }
    
    // Check if we have order_items table
    $has_order_items = mysqli_query($con, "SHOW TABLES LIKE 'order_items'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'order_items'")) > 0;
    
    if ($has_order_items) {
        $query = "SELECT 
                    $group_by as period,
                    COALESCE(SUM(oi.total_price), 0) as revenue,
                    COUNT(DISTINCT oi.order_id) as orders,
                    COUNT(oi.item_id) as items_sold
                 FROM order_items oi
                 JOIN orders_info o ON oi.order_id = o.order_id
                 WHERE $date_condition
                 GROUP BY $group_by
                 ORDER BY period";
    } else {
        $query = "SELECT 
                    $group_by as period,
                    0 as revenue,
                    COUNT(*) as orders,
                    0 as items_sold
                 FROM orders_info o
                 WHERE $date_condition
                 GROUP BY $group_by
                 ORDER BY period";
    }
    
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Export to Excel function (No external libraries required)
function exportToExcel($con, $period) {
    // Get the data
    $revenue_data = getRevenueData($con, $period);
    $detailed_data = getDetailedRevenueData($con, $period);
    $total_customers = getTotalCustomers($con);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="revenue_report_' . $period . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start Excel content
    echo "<html>";
    echo "<head>";
    echo "<meta charset=\"UTF-8\">";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "th { background-color: #f2f2f2; font-weight: bold; }";
    echo ".header { text-align: center; margin-bottom: 20px; }";
    echo ".summary { margin-bottom: 30px; }";
    echo ".details { margin-top: 20px; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    // Header
    echo "<div class=\"header\">";
    echo "<h1>TechShop Liberia - Revenue Report</h1>";
    echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Period:</strong> " . ucfirst($period) . "</p>";
    echo "</div>";
    
    // Summary section
    echo "<div class=\"summary\">";
    echo "<h2>SUMMARY</h2>";
    echo "<table>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    echo "<tr><td>Total Revenue</td><td>$" . number_format($revenue_data['revenue'], 2) . "</td></tr>";
    echo "<tr><td>Total Orders</td><td>" . $revenue_data['orders'] . "</td></tr>";
    echo "<tr><td>Average Order Value</td><td>$" . number_format($revenue_data['orders'] > 0 ? $revenue_data['revenue'] / $revenue_data['orders'] : 0, 2) . "</td></tr>";
    echo "<tr><td>Total Customers</td><td>" . $total_customers . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Detailed data section
    echo "<div class=\"details\">";
    echo "<h2>DETAILED BREAKDOWN</h2>";
    echo "<table>";
    echo "<tr><th>Period</th><th>Revenue</th><th>Orders</th><th>Items Sold</th><th>Average Order</th></tr>";
    
    foreach ($detailed_data as $data) {
        $period_label = formatPeriodLabel($data['period'], $period);
        $avg_order = $data['orders'] > 0 ? $data['revenue'] / $data['orders'] : 0;
        
        echo "<tr>";
        echo "<td>" . $period_label . "</td>";
        echo "<td>$" . number_format($data['revenue'], 2) . "</td>";
        echo "<td>" . $data['orders'] . "</td>";
        echo "<td>" . $data['items_sold'] . "</td>";
        echo "<td>$" . number_format($avg_order, 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // Footer
    echo "<div style=\"margin-top: 30px; text-align: center; font-size: 12px; color: #666;\">";
    echo "<p>TechShop Liberia - Monrovia, Liberia</p>";
    echo "<p>Email: info@techshopliberia.com | Phone: +231 123 456 789</p>";
    echo "</div>";
    
    echo "</body>";
    echo "</html>";
    exit;
}

// Export to PDF function (Simple HTML to PDF)
function exportToPDF($con, $period) {
    // Get the data
    $revenue_data = getRevenueData($con, $period);
    $detailed_data = getDetailedRevenueData($con, $period);
    $total_customers = getTotalCustomers($con);
    
    // Create HTML content that can be printed as PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Revenue Report - TechShop Liberia</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                line-height: 1.6;
                color: #333;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 2px solid #333; 
                padding-bottom: 10px; 
            }
            .summary { 
                margin-bottom: 30px; 
            }
            .summary-table, .details-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px; 
            }
            .summary-table th, .summary-table td, 
            .details-table th, .details-table td { 
                border: 1px solid #ddd; 
                padding: 12px; 
                text-align: left; 
            }
            .summary-table th, .details-table th { 
                background-color: #f2f2f2; 
                font-weight: bold;
            }
            .details { 
                margin-top: 30px; 
            }
            .footer { 
                margin-top: 40px; 
                text-align: center; 
                font-size: 12px; 
                color: #666; 
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            h1 { color: #2c3e50; margin-bottom: 10px; }
            h2 { color: #34495e; margin-bottom: 15px; border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; }
            .highlight { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>TechShop Liberia - Revenue Report</h1>
            <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Period:</strong> ' . ucfirst($period) . '</p>
        </div>
        
        <div class="highlight">
            <div class="summary">
                <h2>Executive Summary</h2>
                <table class="summary-table">
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Total Revenue</td>
                        <td><strong>$' . number_format($revenue_data['revenue'], 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Total Orders</td>
                        <td><strong>' . $revenue_data['orders'] . '</strong></td>
                    </tr>
                    <tr>
                        <td>Average Order Value</td>
                        <td><strong>$' . number_format($revenue_data['orders'] > 0 ? $revenue_data['revenue'] / $revenue_data['orders'] : 0, 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Total Customers</td>
                        <td><strong>' . $total_customers . '</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="details">
            <h2>Detailed Revenue Breakdown</h2>
            <table class="details-table">
                <tr>
                    <th>Period</th>
                    <th>Revenue</th>
                    <th>Orders</th>
                    <th>Items Sold</th>
                    <th>Average Order</th>
                </tr>';
    
    foreach ($detailed_data as $data) {
        $period_label = formatPeriodLabel($data['period'], $period);
        $avg_order = $data['orders'] > 0 ? $data['revenue'] / $data['orders'] : 0;
        
        $html .= '
                <tr>
                    <td>' . $period_label . '</td>
                    <td>$' . number_format($data['revenue'], 2) . '</td>
                    <td>' . $data['orders'] . '</td>
                    <td>' . $data['items_sold'] . '</td>
                    <td>$' . number_format($avg_order, 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </table>
        </div>
        
        <div class="footer">
            <p><strong>TechShop Liberia</strong> - Monrovia, Liberia</p>
            <p>Email: info@techshopliberia.com | Phone: +231 123 456 789</p>
            <p>This report was generated automatically on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
    </body>
    </html>';
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="revenue_report_' . $period . '_' . date('Y-m-d') . '.pdf"');
    
    // For now, output HTML that users can "Print as PDF"
    // In production, you can implement a proper PDF generator
    echo $html;
    exit;
}

// Helper function to format period labels
function formatPeriodLabel($period_value, $period_type) {
    switch ($period_type) {
        case 'today':
            return date('g A', mktime($period_value, 0, 0));
        case 'week':
            return date('M j', strtotime($period_value));
        case 'month':
            return date('M j', strtotime($period_value));
        case 'year':
            return date('F', mktime(0, 0, 0, $period_value, 1));
        default:
            return $period_value;
    }
}

// Get current period from URL or default to month
$current_period = $_GET['period'] ?? 'month';

// Get data for different periods
$today_revenue = getRevenueData($con, 'today');
$week_revenue = getRevenueData($con, 'week');
$month_revenue = getRevenueData($con, 'month');
$year_revenue = getRevenueData($con, 'year');
$prev_month_revenue = getPreviousMonthRevenue($con);
$total_customers = getTotalCustomers($con);

// Calculate growth rates
$revenue_growth = calculateGrowth($month_revenue['revenue'], $prev_month_revenue['revenue']);
$orders_growth = calculateGrowth($month_revenue['orders'], $prev_month_revenue['orders']);
$avg_order_growth = calculateGrowth(
    $month_revenue['orders'] > 0 ? $month_revenue['revenue'] / $month_revenue['orders'] : 0,
    $prev_month_revenue['orders'] > 0 ? $prev_month_revenue['revenue'] / $prev_month_revenue['orders'] : 0
);
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
                            <i class="material-icons text-white">trending_up</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Revenue Analytics</h1>
                            <p class="page-subtitle text-muted mb-0">Track and analyze your sales performance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown">
                            <i class="material-icons mr-1">date_range</i> 
                            <span id="periodLabel"><?php echo ucfirst($current_period); ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item period-filter <?php echo $current_period === 'today' ? 'active' : ''; ?>" href="#" data-period="today">Today</a>
                            <a class="dropdown-item period-filter <?php echo $current_period === 'week' ? 'active' : ''; ?>" href="#" data-period="week">This Week</a>
                            <a class="dropdown-item period-filter <?php echo $current_period === 'month' ? 'active' : ''; ?>" href="#" data-period="month">This Month</a>
                            <a class="dropdown-item period-filter <?php echo $current_period === 'year' ? 'active' : ''; ?>" href="#" data-period="year">This Year</a>
                        </div>
                    </div>
                    <div class="btn-group ml-2">
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                            <i class="material-icons mr-1">download</i> Export
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item export-btn" href="#" data-type="excel">
                                <i class="material-icons mr-1">table_chart</i> Export to Excel
                            </a>
                            <a class="dropdown-item export-btn" href="#" data-type="pdf">
                                <i class="material-icons mr-1">picture_as_pdf</i> Export to PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message for Export -->
        <?php if(isset($_GET['export_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div>Report exported successfully! The download should start automatically.</div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Revenue Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">attach_money</i>
                        </div>
                        <p class="card-category">Total Revenue</p>
                        <h3 class="card-title" id="totalRevenue">
                            $<?php echo number_format($month_revenue['revenue'], 0); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons <?php echo $revenue_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $revenue_growth >= 0 ? 'arrow_upward' : 'arrow_downward'; ?>
                            </i>
                            <span id="revenueGrowth">
                                <?php echo number_format(abs($revenue_growth), 1); ?>% 
                                <?php echo $revenue_growth >= 0 ? 'increase' : 'decrease'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">shopping_cart</i>
                        </div>
                        <p class="card-category">Total Orders</p>
                        <h3 class="card-title" id="totalOrders">
                            <?php echo number_format($month_revenue['orders']); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons <?php echo $orders_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $orders_growth >= 0 ? 'arrow_upward' : 'arrow_downward'; ?>
                            </i>
                            <span id="ordersGrowth">
                                <?php echo number_format(abs($orders_growth), 1); ?>% 
                                <?php echo $orders_growth >= 0 ? 'increase' : 'decrease'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">local_offer</i>
                        </div>
                        <p class="card-category">Average Order</p>
                        <h3 class="card-title" id="averageOrder">
                            $<?php echo $month_revenue['orders'] > 0 ? number_format($month_revenue['revenue'] / $month_revenue['orders'], 2) : '0.00'; ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons <?php echo $avg_order_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $avg_order_growth >= 0 ? 'arrow_upward' : 'arrow_downward'; ?>
                            </i>
                            <span id="avgOrderGrowth">
                                <?php echo number_format(abs($avg_order_growth), 1); ?>% 
                                <?php echo $avg_order_growth >= 0 ? 'increase' : 'decrease'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">people</i>
                        </div>
                        <p class="card-category">Total Customers</p>
                        <h3 class="card-title" id="totalCustomers">
                            <?php echo number_format($total_customers); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons text-info">group</i>
                            <span id="customersGrowth">All Time Customers</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rest of your content remains exactly the same -->
        <div class="row">
            <!-- Revenue Chart -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">bar_chart</i>
                            Revenue Trend (Last 7 Days)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get last 7 days revenue
                        $weekly_data = [];
                        $max_revenue = 0;
                        
                        for ($i = 6; $i >= 0; $i--) {
                            $date = date('Y-m-d', strtotime("-$i days"));
                            $day_name = date('D', strtotime("-$i days"));
                            
                            // Check if order_items table exists
                            $has_order_items = mysqli_query($con, "SHOW TABLES LIKE 'order_items'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'order_items'")) > 0;
                            
                            if ($has_order_items) {
                                $query = "SELECT 
                                            COALESCE(SUM(oi.total_price), 0) as revenue,
                                            COUNT(DISTINCT oi.order_id) as orders
                                         FROM order_items oi
                                         JOIN orders_info o ON oi.order_id = o.order_id
                                         WHERE DATE(o.created_at) = '$date'";
                            } else {
                                $query = "SELECT 
                                            0 as revenue,
                                            COUNT(*) as orders
                                         FROM orders_info o
                                         WHERE DATE(o.created_at) = '$date'";
                            }
                            
                            $result = mysqli_query($con, $query);
                            $data = $result ? mysqli_fetch_assoc($result) : ['revenue' => 0, 'orders' => 0];
                            
                            $revenue = floatval($data['revenue'] ?? 0);
                            $max_revenue = max($max_revenue, $revenue);
                            
                            $weekly_data[] = [
                                'day' => $day_name,
                                'revenue' => $revenue,
                                'orders' => intval($data['orders'] ?? 0)
                            ];
                        }
                        ?>
                        <div style="height: 300px; display: flex; align-items: end; justify-content: space-between; padding: 20px 0;">
                            <?php foreach ($weekly_data as $data): ?>
                            <div class="text-center" style="flex: 1;">
                                <div class="mb-2" style="font-size: 12px; color: #666;"><?php echo $data['day']; ?></div>
                                <div class="bg-primary rounded revenue-bar" style="
                                    height: <?php echo $max_revenue > 0 ? max(10, ($data['revenue'] / $max_revenue * 200)) : 10; ?>px;
                                    margin: 0 5px;
                                    position: relative;
                                    cursor: pointer;
                                " data-revenue="<?php echo $data['revenue']; ?>" data-orders="<?php echo $data['orders']; ?>">
                                    <div class="revenue-tooltip" style="
                                        position: absolute;
                                        top: -35px;
                                        left: 50%;
                                        transform: translateX(-50%);
                                        background: #333;
                                        color: white;
                                        padding: 5px 10px;
                                        border-radius: 4px;
                                        font-size: 12px;
                                        white-space: nowrap;
                                        display: none;
                                        z-index: 100;
                                    ">
                                        <strong>$<?php echo number_format($data['revenue'], 0); ?></strong><br>
                                        <small><?php echo $data['orders']; ?> orders</small>
                                    </div>
                                </div>
                                <div class="mt-1" style="font-size: 11px; color: #999;">
                                    $<?php echo number_format($data['revenue'], 0); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-success">list_alt</i>
                            Recent Orders
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Items</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Check if order_items table exists
                                    $has_order_items = mysqli_query($con, "SHOW TABLES LIKE 'order_items'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'order_items'")) > 0;
                                    
                                    if ($has_order_items) {
                                        $recent_orders_query = "SELECT 
                                            o.order_id,
                                            u.first_name,
                                            u.last_name,
                                            o.created_at,
                                            (SELECT SUM(oi.total_price) FROM order_items oi WHERE oi.order_id = o.order_id) as total_amount,
                                            (SELECT COUNT(oi.item_id) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
                                        FROM orders_info o
                                        JOIN user_info u ON o.user_id = u.user_id
                                        ORDER BY o.created_at DESC
                                        LIMIT 5";
                                    } else {
                                        $recent_orders_query = "SELECT 
                                            o.order_id,
                                            u.first_name,
                                            u.last_name,
                                            o.created_at,
                                            0 as total_amount,
                                            0 as item_count
                                        FROM orders_info o
                                        JOIN user_info u ON o.user_id = u.user_id
                                        ORDER BY o.created_at DESC
                                        LIMIT 5";
                                    }
                                    
                                    $recent_orders_result = mysqli_query($con, $recent_orders_query);
                                    
                                    if ($recent_orders_result && mysqli_num_rows($recent_orders_result) > 0) {
                                        while($order = mysqli_fetch_assoc($recent_orders_result)):
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $order['item_count']; ?> items</span>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center py-4">No recent orders found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="col-md-4">
                <!-- Period Comparison -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">compare</i>
                            Period Comparison
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="comparison-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Today</span>
                                <strong>$<?php echo number_format($today_revenue['revenue'], 0); ?></strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: <?php echo min(100, ($today_revenue['revenue'] / max($month_revenue['revenue'], 1)) * 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $today_revenue['orders']; ?> orders</small>
                        </div>
                        <div class="comparison-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>This Week</span>
                                <strong>$<?php echo number_format($week_revenue['revenue'], 0); ?></strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($week_revenue['revenue'] / max($month_revenue['revenue'], 1)) * 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $week_revenue['orders']; ?> orders</small>
                        </div>
                        <div class="comparison-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>This Month</span>
                                <strong>$<?php echo number_format($month_revenue['revenue'], 0); ?></strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: 100%"></div>
                            </div>
                            <small class="text-muted"><?php echo $month_revenue['orders']; ?> orders</small>
                        </div>
                        <div class="comparison-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>This Year</span>
                                <strong>$<?php echo number_format($year_revenue['revenue'], 0); ?></strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo min(100, ($month_revenue['revenue'] / max($year_revenue['revenue'], 1)) * 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $year_revenue['orders']; ?> orders</small>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">flash_on</i>
                            Quick Insights
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="insight-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="material-icons text-<?php echo $revenue_growth >= 0 ? 'success' : 'danger'; ?> mr-2">
                                    <?php echo $revenue_growth >= 0 ? 'arrow_upward' : 'arrow_downward'; ?>
                                </i>
                                <div>
                                    <strong>Monthly Growth:</strong> <?php echo number_format($revenue_growth, 1); ?>%
                                    <br><small class="text-muted">Compared to last month</small>
                                </div>
                            </div>
                        </div>
                        <div class="insight-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="material-icons text-primary mr-2">trending_up</i>
                                <div>
                                    <strong>Average Order:</strong> $<?php echo $month_revenue['orders'] > 0 ? number_format($month_revenue['revenue'] / $month_revenue['orders'], 2) : '0.00'; ?>
                                    <br><small class="text-muted">Value per order</small>
                                </div>
                            </div>
                        </div>
                        <div class="insight-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="material-icons text-info mr-2">people</i>
                                <div>
                                    <strong>Customer Base:</strong> <?php echo number_format($total_customers); ?>
                                    <br><small class="text-muted">Total customers</small>
                                </div>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="d-flex align-items-center">
                                <i class="material-icons text-success mr-2">shopping_cart</i>
                                <div>
                                    <strong>Order Frequency:</strong> 
                                    <?php 
                                    $avg_orders_per_customer = $total_customers > 0 ? $month_revenue['orders'] / $total_customers : 0;
                                    echo number_format($avg_orders_per_customer, 1);
                                    ?>
                                    <br><small class="text-muted">Avg orders per customer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Tips -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">lightbulb</i>
                            Revenue Tips
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="tip-item mb-3">
                            <div class="d-flex align-items-start">
                                <i class="material-icons text-warning mr-2" style="font-size: 16px;">star</i>
                                <div>
                                    <strong>Boost Average Order Value</strong>
                                    <br><small class="text-muted">Offer product bundles and upsells</small>
                                </div>
                            </div>
                        </div>
                        <div class="tip-item mb-3">
                            <div class="d-flex align-items-start">
                                <i class="material-icons text-info mr-2" style="font-size: 16px;">email</i>
                                <div>
                                    <strong>Email Marketing</strong>
                                    <br><small class="text-muted">Re-engage past customers</small>
                                </div>
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="d-flex align-items-start">
                                <i class="material-icons text-primary mr-2" style="font-size: 16px;">local_offer</i>
                                <div>
                                    <strong>Seasonal Promotions</strong>
                                    <br><small class="text-muted">Create limited-time offers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Period filter functionality
document.querySelectorAll('.period-filter').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const period = this.getAttribute('data-period');
        
        // Update active class
        document.querySelectorAll('.period-filter').forEach(link => {
            link.classList.remove('active');
        });
        this.classList.add('active');
        
        // Update period label
        const periodLabels = {
            'today': 'Today',
            'week': 'This Week',
            'month': 'This Month',
            'year': 'This Year'
        };
        document.getElementById('periodLabel').textContent = periodLabels[period];
        
        // Reload the page with the new period
        window.location.href = 'revenue.php?period=' + period;
    });
});

// Export functionality
document.querySelectorAll('.export-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const exportType = this.getAttribute('data-type');
        const currentPeriod = '<?php echo $current_period; ?>';
        
        // Show loading indicator
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="material-icons mr-1">hourglass_empty</i> Generating...';
        this.disabled = true;
        
        // Trigger download
        window.location.href = `revenue.php?export=${exportType}&period=${currentPeriod}`;
        
        // Restore button text after a delay
        setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;
        }, 3000);
    });
});

// Chart bar hover effects
document.addEventListener('DOMContentLoaded', function() {
    const bars = document.querySelectorAll('.revenue-bar');
    bars.forEach(bar => {
        const tooltip = bar.querySelector('.revenue-tooltip');
        
        bar.addEventListener('mouseenter', function() {
            tooltip.style.display = 'block';
        });
        
        bar.addEventListener('mouseleave', function() {
            tooltip.style.display = 'none';
        });
    });
});

// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    // Add pulse animation to stats cards on load
    const statsCards = document.querySelectorAll('.card-stats');
    statsCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.transform = 'scale(1.02)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 200);
        }, index * 100);
    });
});
</script>

<style>
.insight-item, .comparison-item, .tip-item {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.insight-item:last-child, .comparison-item:last-child, .tip-item:last-child {
    border-bottom: none;
}

.progress {
    background-color: #f8f9fa;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.5s ease;
}

.revenue-bar {
    transition: all 0.3s ease;
}

.revenue-bar:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.card-stats {
    transition: transform 0.3s ease;
}

.card-stats:hover {
    transform: translateY(-2px);
}

.revenue-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #333 transparent transparent transparent;
}

.export-btn {
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-btn:hover {
    background-color: #f8f9fa;
}
</style>

<?php
include "footer.php";
?>