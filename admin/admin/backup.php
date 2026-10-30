<?php
session_start();
include("../../db.php");

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../../login.php');
    exit();
}

include "sidenav.php";
include "topheader.php";

// Initialize variables
$success = '';
$error = '';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_type = mysqli_real_escape_string($con, $_POST['backup_type']);
    $include_data = isset($_POST['include_data']) ? 1 : 0;
    
    // Generate backup filename
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_dir = __DIR__ . '/../backups';
    $backup_path = $backup_dir . '/' . $backup_file;
    
    // Create backups directory if it doesn't exist
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Get list of tables
    $tables = [];
    $result = mysqli_query($con, "SHOW TABLES");
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
    }
    
    $backup_content = "-- TechShop Liberia Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Backup Type: " . $backup_type . "\n";
    $backup_content .= "-- Includes Data: " . ($include_data ? 'Yes' : 'No') . "\n\n";
    
    foreach ($tables as $table) {
        // Skip backup of backup logs if structure only
        if ($backup_type === 'structure' && $table === 'backup_logs') {
            continue;
        }
        
        $backup_content .= "-- Table: $table\n";
        
        if ($backup_type === 'structure' || $backup_type === 'full') {
            // Get table structure
            $quoted_table = '`' . str_replace('`', '``', $table) . '`';
            $create_result = mysqli_query($con, "SHOW CREATE TABLE $quoted_table");
            if ($create_result && $create_row = mysqli_fetch_row($create_result)) {
                $backup_content .= "DROP TABLE IF EXISTS $quoted_table;\n";
                $backup_content .= $create_row[1] . ";\n\n";
            }
        }
        
        if (($backup_type === 'data' || $backup_type === 'full') && $include_data) {
            $quoted_table = '`' . str_replace('`', '``', $table) . '`';
            $data_result = mysqli_query($con, "SELECT * FROM $quoted_table");
            if ($data_result && mysqli_num_rows($data_result) > 0) {
                $backup_content .= "-- Data for table $table\n";
                while ($row = mysqli_fetch_assoc($data_result)) {
                    $columns = '`' . implode('`, `', array_map(function ($column) {
                        return str_replace('`', '``', $column);
                    }, array_keys($row))) . '`';
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . mysqli_real_escape_string($con, $value) . "'";
                        }
                    }
                    $backup_content .= "INSERT INTO $quoted_table ($columns) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
    }
    
    if (file_put_contents($backup_path, $backup_content)) {
        // Log backup creation
        $file_size = filesize($backup_path);
        $log_query = "INSERT INTO backup_logs (filename, backup_type, file_size, status) 
                     VALUES ('$backup_file', '$backup_type', '$file_size', 'success')";
        if (mysqli_query($con, $log_query)) {
            $success = "Backup created successfully: " . $backup_file;
        } else {
            $error = "Backup created but failed to log: " . mysqli_error($con);
        }
    } else {
        $error = "Failed to create backup file.";
    }
}

// Handle backup restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    $backup_file = basename($_POST['backup_file'] ?? '');
    $backup_dir = __DIR__ . '/../backups';
    $backup_path = $backup_dir . '/' . $backup_file;
    
    if ($backup_file !== '' && pathinfo($backup_file, PATHINFO_EXTENSION) === 'sql' && is_file($backup_path)) {
        $sql_content = file_get_contents($backup_path);
        if ($sql_content !== false && $sql_content !== '') {
            $success_count = 0;
            $error_count = 0;
            $errors = [];
            mysqli_query($con, "SET FOREIGN_KEY_CHECKS=0");
            if (mysqli_multi_query($con, $sql_content)) {
                do {
                    if ($result = mysqli_store_result($con)) {
                        mysqli_free_result($result);
                    }
                    $success_count++;
                } while (mysqli_more_results($con) && mysqli_next_result($con));
                if ($mysqli_error = mysqli_error($con)) {
                    $error_count++;
                    $errors[] = $mysqli_error;
                }
            } else {
                $error_count++;
                $errors[] = mysqli_error($con);
            }
            mysqli_query($con, "SET FOREIGN_KEY_CHECKS=1");
            
            if ($error_count === 0) {
                $success = "Backup restored successfully! $success_count queries executed.";
            } else {
                $error = "Backup restore failed. " . ($errors[0] ?? 'Unknown database error');
            }
        } else {
            $error = "The selected backup file is empty or unreadable.";
        }
    } else {
        $error = "Backup file not found.";
    }
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_file = basename($_POST['backup_file'] ?? '');
    $log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
    $backup_dir = __DIR__ . '/../backups';
    $backup_path = $backup_dir . '/' . $backup_file;
    $deleted = false;

    if ($backup_file !== '' && pathinfo($backup_file, PATHINFO_EXTENSION) === 'sql' && is_file($backup_path)) {
        $deleted = unlink($backup_path);
    }

    if ($deleted) {
        if ($log_id) {
            $stmt = mysqli_prepare($con, "DELETE FROM backup_logs WHERE id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $log_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        $success = "Backup deleted successfully: " . $backup_file;
    } else {
        $error = "Unable to delete the backup file.";
    }
}

// Create backup logs and settings tables if they do not exist
$create_table = "CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    backup_type ENUM('full', 'structure', 'data') DEFAULT 'full',
    file_size INT,
    status ENUM('success', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);

mysqli_query($con, "CREATE TABLE IF NOT EXISTS backup_settings (
    id INT PRIMARY KEY DEFAULT 1,
    frequency VARCHAR(20) NOT NULL DEFAULT 'weekly',
    retention_days INT NOT NULL DEFAULT 30,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $frequency = $_POST['backup_frequency'] ?? 'disabled';
    $retention = filter_input(INPUT_POST, 'backup_retention', FILTER_VALIDATE_INT);
    if (!in_array($frequency, ['disabled', 'daily', 'weekly', 'monthly'], true) || !$retention) {
        $error = 'Invalid backup settings.';
    } else {
        $stmt = mysqli_prepare($con, "INSERT INTO backup_settings (id, frequency, retention_days) VALUES (1, ?, ?) ON DUPLICATE KEY UPDATE frequency=VALUES(frequency), retention_days=VALUES(retention_days)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $frequency, $retention);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Auto backup settings saved successfully.';
            } else {
                $error = 'Failed to save auto backup settings.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$backup_settings = ['frequency' => 'weekly', 'retention_days' => 30];
$settings_result = mysqli_query($con, "SELECT frequency, retention_days FROM backup_settings WHERE id=1 LIMIT 1");
if ($settings_result && $settings_row = mysqli_fetch_assoc($settings_result)) {
    $backup_settings = $settings_row;
}

$next_backup_days = ['daily' => 1, 'weekly' => 7, 'monthly' => 30];
$next_backup_date = isset($next_backup_days[$backup_settings['frequency']])
    ? strtotime('+' . $next_backup_days[$backup_settings['frequency']] . ' days')
    : null;

// Get backup history
$backup_history = [];
$history_query = "SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 10";
$history_result = mysqli_query($con, $history_query);
if ($history_result && mysqli_num_rows($history_result) > 0) {
    while($row = mysqli_fetch_assoc($history_result)) {
        $backup_history[] = $row;
    }
}

// Get available backup files
$backup_files = [];
$backup_dir = '../backups/';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = $file;
        }
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
                        <div class="header-icon-container bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">backup</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Backup & Restore</h1>
                            <p class="page-subtitle text-muted mb-0">Manage database backups and restoration</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="system-info">
                        <small class="text-muted">
                            <i class="material-icons mr-1" style="font-size:16px;">storage</i>
                            Database: <?php echo mysqli_get_host_info($con); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if(!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div><?php echo $success; ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">error</i>
            <div><?php echo $error; ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0"><?php echo count($backup_files); ?></h4>
                                <p class="mb-0">Backup Files</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">folder</i>
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
                                <h4 class="mb-0"><?php echo count($backup_history); ?></h4>
                                <p class="mb-0">Backup Records</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">history</i>
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
                                    $total_size = 0;
                                    foreach ($backup_files as $file) {
                                        $total_size += filesize($backup_dir . $file);
                                    }
                                    echo round($total_size / 1024 / 1024, 2) . ' MB';
                                    ?>
                                </h4>
                                <p class="mb-0">Total Size</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">storage</i>
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
                                    if (!empty($backup_history)) {
                                        echo date('M j', strtotime($backup_history[0]['created_at']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </h4>
                                <p class="mb-0">Last Backup</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">schedule</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Create Backup -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">backup</i>
                            Create Backup
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="backupForm">
                            <div class="form-group">
                                <label class="font-weight-bold">Backup Type</label>
                                <select name="backup_type" class="form-control" required>
                                    <option value="full">Full Backup (Structure + Data)</option>
                                    <option value="structure">Structure Only</option>
                                    <option value="data">Data Only</option>
                                </select>
                                <small class="form-text text-muted" id="backupDescription">
                                    Backup both database structure and data
                                </small>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="include_data" class="form-check-input" id="include_data" checked>
                                    <label class="form-check-label font-weight-bold" for="include_data">
                                        Include Table Data
                                    </label>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">warning</i>
                                    <strong>Note:</strong> Creating a backup may take several minutes for large databases.
                                </small>
                            </div>
                            <button type="submit" name="create_backup" class="btn btn-success btn-lg btn-block py-2">
                                <i class="material-icons mr-2">backup</i>Create Backup Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Restore Backup -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">restore</i>
                            Restore Backup
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="restoreForm">
                            <div class="form-group">
                                <label class="font-weight-bold">Select Backup File</label>
                                <select name="backup_file" class="form-control" required>
                                    <option value="">Choose a backup file...</option>
                                    <?php
                                    if (!empty($backup_files)) {
                                        foreach ($backup_files as $file) {
                                            $file_size = round(filesize($backup_dir . $file) / 1024, 2);
                                            echo "<option value=\"$file\">$file (" . $file_size . " KB)</option>";
                                        }
                                    } else {
                                        echo "<option value=\"\">No backup files found</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="alert alert-danger">
                                <small>
                                    <i class="material-icons mr-1" style="font-size:16px;">warning</i>
                                    <strong>Warning:</strong> Restoring a backup will overwrite existing data. This action cannot be undone.
                                </small>
                            </div>
                            <button type="submit" name="restore_backup" class="btn btn-primary btn-lg btn-block py-2" 
                                    onclick="return confirm('⚠️ ARE YOU SURE?\n\nRestoring this backup will OVERWRITE all current data. This action cannot be undone!')">
                                <i class="material-icons mr-2">restore</i>Restore Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup History -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-info">history</i>
                    Backup History
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Filename</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($backup_history)) {
                                foreach ($backup_history as $log) {
                                    $status_class = [
                                        'success' => 'success',
                                        'failed' => 'danger'
                                    ][$log['status']] ?? 'secondary';
                                    
                                    $file_exists = file_exists($backup_dir . $log['filename']);
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="material-icons mr-2 text-<?php echo $file_exists ? 'success' : 'danger'; ?>" style="font-size:16px;">
                                            <?php echo $file_exists ? 'folder' : 'error'; ?>
                                        </i>
                                        <small><?php echo htmlspecialchars($log['filename']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light text-capitalize">
                                        <?php echo htmlspecialchars($log['backup_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo round($log['file_size'] / 1024, 2); ?> KB</small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($file_exists): ?>
                                        <a href="../backups/<?php echo $log['filename']; ?>" 
                                           class="btn btn-outline-primary" download title="Download Backup">
                                            <i class="material-icons" style="font-size:16px;">download</i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-delete-backup="<?php echo htmlspecialchars($log['filename'], ENT_QUOTES); ?>" 
                                                data-log-id="<?php echo (int)$log['id']; ?>" 
                                                title="Delete Backup">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-outline-secondary" disabled title="File Not Found">
                                            <i class="material-icons" style="font-size:16px;">file_download_off</i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-4 text-muted">No backup history found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Auto Backup Settings -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-info text-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2">schedule</i>
                    Auto Backup Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Auto Backup Frequency</label>
                                <select name="backup_frequency" class="form-control">
                                    <option value="disabled" <?php echo $backup_settings['frequency'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="daily" <?php echo $backup_settings['frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $backup_settings['frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $backup_settings['frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Backup Retention</label>
                                <select name="backup_retention" class="form-control">
                                    <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $days => $label): ?>
                                    <option value="<?php echo $days; ?>" <?php echo (int)$backup_settings['retention_days'] === $days ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Next Scheduled Backup</label>
                                <input type="text" class="form-control" value="<?php echo $next_backup_date ? date('M j, Y', $next_backup_date) : 'Disabled'; ?>" readonly>
                                <small class="form-text text-muted">Estimated based on the selected frequency</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <i class="material-icons mr-1" style="font-size:16px;">info</i>
                            Auto backups are performed automatically at the scheduled time. Manual backups are always available.
                        </small>
                    </div>
                    <button type="submit" name="save_settings" class="btn btn-info">
                        <i class="material-icons mr-2">save</i>Save Auto Backup Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Update backup type description
document.querySelector('select[name="backup_type"]').addEventListener('change', function() {
    const descriptions = {
        'full': 'Backup both database structure and data (recommended)',
        'structure': 'Backup only the database structure (tables, columns, indexes)',
        'data': 'Backup only the data (records) without table structure'
    };
    
    const descriptionElement = document.getElementById('backupDescription');
    if (descriptionElement) {
        descriptionElement.textContent = descriptions[this.value] || '';
    }
});

document.querySelectorAll('[data-delete-backup]').forEach(function(button) {
    button.addEventListener('click', function() {
        const filename = this.getAttribute('data-delete-backup');
        const logId = this.getAttribute('data-log-id');
        if (!confirm('Are you sure you want to delete this backup file?\n\nFile: ' + filename + '\n\nThis action cannot be undone.')) {
            return;
        }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        const fileInput = document.createElement('input');
        fileInput.type = 'hidden';
        fileInput.name = 'backup_file';
        fileInput.value = filename;
        const logInput = document.createElement('input');
        logInput.type = 'hidden';
        logInput.name = 'log_id';
        logInput.value = logId;
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_backup';
        deleteInput.value = '1';
        form.append(fileInput, logInput, deleteInput);
        document.body.appendChild(form);
        form.submit();
    });
});

// Add loading state to forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons mr-2">hourglass_empty</i> Processing...';
                
                // Re-enable after 10 seconds in case of error
                setTimeout(() => {
                    submitBtn.disabled = false;
                    if (this.id === 'backupForm') {
                        submitBtn.innerHTML = '<i class="material-icons mr-2">backup</i>Create Backup Now';
                    } else if (this.id === 'restoreForm') {
                        submitBtn.innerHTML = '<i class="material-icons mr-2">restore</i>Restore Backup';
                    }
                }, 10000);
            }
        });
    });
});
</script>

<?php
include "footer.php";
?>