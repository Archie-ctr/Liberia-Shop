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

// Handle log clearing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $log_type = mysqli_real_escape_string($con, $_POST['log_type']);
    
    if ($log_type === 'all') {
        $query = "TRUNCATE TABLE system_logs";
    } else {
        $query = "DELETE FROM system_logs WHERE log_type = '$log_type'";
    }
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Logs cleared successfully!";
    } else {
        $_SESSION['error'] = "Error clearing logs: " . mysqli_error($con);
    }
}

// Create system_logs table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('error', 'warning', 'info', 'security', 'user_action') DEFAULT 'info',
    message TEXT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);

// Get log statistics
$log_stats = [
    'total' => 0,
    'errors' => 0,
    'warnings' => 0,
    'today' => 0
];

$result = mysqli_query($con, "SELECT COUNT(*) as total FROM system_logs");
if ($result) $log_stats['total'] = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($con, "SELECT COUNT(*) as total FROM system_logs WHERE log_type = 'error'");
if ($result) $log_stats['errors'] = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($con, "SELECT COUNT(*) as total FROM system_logs WHERE log_type = 'warning'");
if ($result) $log_stats['warnings'] = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($con, "SELECT COUNT(*) as total FROM system_logs WHERE DATE(created_at) = CURDATE()");
if ($result) $log_stats['today'] = mysqli_fetch_assoc($result)['total'];
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
                            <i class="material-icons text-white">history</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">System Logs</h1>
                            <p class="page-subtitle text-muted mb-0">Monitor system activities and errors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-danger" data-toggle="modal" data-target="#clearLogsModal">
                        <i class="material-icons mr-2">delete_sweep</i>Clear Logs
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

        <!-- Log Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0"><?php echo $log_stats['total']; ?></h4>
                                <p class="mb-0">Total Logs</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">history</i>
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
                                <h4 class="mb-0"><?php echo $log_stats['errors']; ?></h4>
                                <p class="mb-0">Errors</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">error</i>
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
                                <h4 class="mb-0"><?php echo $log_stats['warnings']; ?></h4>
                                <p class="mb-0">Warnings</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">warning</i>
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
                                <h4 class="mb-0"><?php echo $log_stats['today']; ?></h4>
                                <p class="mb-0">Today</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">today</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Filters -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Log Type</label>
                            <select name="log_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="error">Errors</option>
                                <option value="warning">Warnings</option>
                                <option value="info">Information</option>
                                <option value="security">Security</option>
                                <option value="user_action">User Actions</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="material-icons mr-2">filter_list</i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-info">list</i>
                    System Logs
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Type</th>
                                <th>Message</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query with filters
                            $where = [];
                            $params = [];
                            
                            if (isset($_GET['log_type']) && !empty($_GET['log_type'])) {
                                $where[] = "log_type = '" . mysqli_real_escape_string($con, $_GET['log_type']) . "'";
                            }
                            
                            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                                $where[] = "DATE(created_at) >= '" . mysqli_real_escape_string($con, $_GET['date_from']) . "'";
                            }
                            
                            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                                $where[] = "DATE(created_at) <= '" . mysqli_real_escape_string($con, $_GET['date_to']) . "'";
                            }
                            
                            $where_clause = '';
                            if (!empty($where)) {
                                $where_clause = 'WHERE ' . implode(' AND ', $where);
                            }
                            
                            $query = "SELECT l.*, u.admin_name 
                                     FROM system_logs l 
                                     LEFT JOIN admin_info u ON l.user_id = u.admin_id 
                                     $where_clause 
                                     ORDER BY l.created_at DESC 
                                     LIMIT 100";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($log = mysqli_fetch_assoc($log)):
                                    $type_class = [
                                        'error' => 'danger',
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        'security' => 'secondary',
                                        'user_action' => 'success'
                                    ][$log['log_type']] ?? 'secondary';
                                    
                                    $type_icon = [
                                        'error' => 'error',
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        'security' => 'security',
                                        'user_action' => 'person'
                                    ][$log['log_type']] ?? 'info';
                            ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo $type_class; ?>">
                                        <i class="material-icons mr-1" style="font-size:14px;"><?php echo $type_icon; ?></i>
                                        <?php echo ucfirst($log['log_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($log['message']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo $log['admin_name'] ?: 'System'; ?></small>
                                </td>
                                <td>
                                    <small><?php echo $log['ip_address'] ?: 'N/A'; ?></small>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></small>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-4">No logs found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear System Logs</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Logs to Clear</label>
                        <select name="log_type" class="form-control" required>
                            <option value="all">All Logs</option>
                            <option value="error">Error Logs Only</option>
                            <option value="warning">Warning Logs Only</option>
                            <option value="info">Information Logs Only</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="material-icons mr-2">warning</i>
                        <strong>Warning:</strong> This action cannot be undone. Logs will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="clear_logs" class="btn btn-danger">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-refresh logs every 30 seconds
setTimeout(function() {
    window.location.reload();
}, 30000);

// Filter logs without page reload (basic implementation)
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[method="GET"]');
    const inputs = filterForm.querySelectorAll('select, input');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>

<?php
include "footer.php";
?>