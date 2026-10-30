<?php
session_start();
include('../../db.php');
include_once __DIR__ . '/../../currency.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = 'You must log in first';
    header('Location: ../login.php');
    exit();
}

$role = $_SESSION['admin_role'] ?? '';
if (!in_array($role, ['super_admin', 'admin'], true)) {
    header('Location: index.php');
    exit();
}

$period = $_GET['period'] ?? 'daily';
if (!in_array($period, ['daily', 'monthly', 'yearly'], true)) {
    $period = 'daily';
}
$today_result = mysqli_query($con, "SELECT CURDATE() AS today");
$today_row = $today_result ? mysqli_fetch_assoc($today_result) : null;
$today = $today_row['today'] ?? date('Y-m-d');
$selected = trim($_GET['date'] ?? '');
if ($selected === '') {
    $selected = $period === 'yearly' ? date('Y') : ($period === 'monthly' ? date('Y-m') : $today);
}

if ($period === 'daily') {
    $selected = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected) ? $selected : $today;
    $date_label = date('F j, Y', strtotime($selected));
    $date_start = $selected . ' 00:00:00';
    $date_end = $selected . ' 23:59:59';
    $range_condition = "created_at >= '" . mysqli_real_escape_string($con, $date_start) . "' AND created_at <= '" . mysqli_real_escape_string($con, $date_end) . "'";
} elseif ($period === 'monthly') {
    $selected = preg_match('/^\d{4}-\d{2}$/', $selected) ? $selected : date('Y-m');
    $first = $selected . '-01';
    $next = date('Y-m-01', strtotime($first . ' +1 month'));
    $date_label = date('F Y', strtotime($first));
    $range_condition = "created_at >= '" . mysqli_real_escape_string($con, $first . ' 00:00:00') . "' AND created_at < '" . mysqli_real_escape_string($con, $next . ' 00:00:00') . "'";
} else {
    $selected = preg_match('/^\d{4}$/', $selected) ? $selected : date('Y');
    $next_year = ((int)$selected + 1) . '-01-01';
    $date_label = $selected;
    $range_condition = "created_at >= '" . mysqli_real_escape_string($con, $selected . '-01-01 00:00:00') . "' AND created_at < '" . mysqli_real_escape_string($con, $next_year . ' 00:00:00') . "'";
}

$staff_result = mysqli_query($con, "SELECT admin_id, admin_name, admin_email, is_active, role, last_login FROM admin_info WHERE role IN ('editor','admin') ORDER BY admin_name ASC");
$staff_rows = [];
$total_sales = 0;
$total_orders = 0;
$total_confirmations = 0;
$total_staff = 0;

while ($staff = mysqli_fetch_assoc($staff_result ?: [])) {
    $total_staff++;
    $staff_name = $staff['admin_name'];
    $name_for_sale = mysqli_real_escape_string($con, 'IN-PERSON SALE. Staff: ' . $staff_name . '.');
    $name_for_confirm = mysqli_real_escape_string($con, 'Confirmed by staff: ' . $staff_name);

    $sales_result = mysqli_query($con, "SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amt),0) AS sale_total FROM orders_info WHERE $range_condition AND admin_notes LIKE '%$name_for_sale%' AND INSTR(admin_notes, 'IN-PERSON SALE') > 0");
    $sales = $sales_result ? mysqli_fetch_assoc($sales_result) : ['sale_count' => 0, 'sale_total' => 0];

    $confirm_result = mysqli_query($con, "SELECT COUNT(*) AS confirmation_count FROM orders_info WHERE $range_condition AND admin_notes LIKE '%$name_for_confirm%' AND INSTR(admin_notes, 'Confirmed by staff') > 0");
    $confirmations = $confirm_result ? mysqli_fetch_assoc($confirm_result) : ['confirmation_count' => 0];

    $staff['sales_count'] = (int)$sales['sale_count'];
    $staff['sales_total'] = (float)$sales['sale_total'];
    $staff['confirmation_count'] = (int)$confirmations['confirmation_count'];
    $staff_rows[] = $staff;
    $total_orders += $staff['sales_count'];
    $total_sales += $staff['sales_total'];
    $total_confirmations += $staff['confirmation_count'];
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="staff-report-' . $selected . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Staff Name', 'Email', 'Role', 'Status', 'Period Sales Orders', 'Sales Total', 'Confirmed Orders', 'Last Login']);
    foreach ($staff_rows as $staff) {
        fputcsv($output, [
            $staff['admin_name'], $staff['admin_email'], $staff['role'] === 'editor' ? 'Staff / Seller' : 'Admin',
            $staff['is_active'] ? 'Active' : 'Disabled', $staff['sales_count'], $staff['sales_total'],
            $staff['confirmation_count'], $staff['last_login'] ?: 'Never'
        ]);
    }
    fclose($output);
    exit();
}

include 'sidenav.php';
include 'topheader.php';
?>
<div class="content">
  <div class="container-fluid">
    <div class="page-header pb-3">
      <div class="row align-items-center">
        <div class="col-md-7">
          <h1 class="page-title mb-1">Staff Performance Reports</h1>
          <p class="page-subtitle text-muted mb-0">Monitor staff sales, confirmations, account activity, and performance for <?php echo htmlspecialchars($date_label); ?>.</p>
        </div>
        <div class="col-md-5 text-md-right">
          <a class="btn btn-outline-primary" href="staff_reports.php?period=<?php echo urlencode($period); ?>&amp;date=<?php echo urlencode($selected); ?>&amp;export=csv"><i class="material-icons mr-1">download</i> Export CSV</a>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="get" class="form-row align-items-end">
          <div class="form-group col-md-3 mb-md-0">
            <label>Report Period</label>
            <select name="period" class="form-control" onchange="this.form.submit()">
              <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
              <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
              <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
            </select>
          </div>
          <div class="form-group col-md-3 mb-md-0">
            <label><?php echo $period === 'daily' ? 'Date' : ($period === 'monthly' ? 'Month' : 'Year'); ?></label>
            <input type="<?php echo $period === 'daily' ? 'date' : ($period === 'monthly' ? 'month' : 'number'); ?>" name="date" class="form-control" value="<?php echo htmlspecialchars($selected); ?>" <?php echo $period === 'yearly' ? 'min="2020" max="2100"' : ''; ?>>
          </div>
          <div class="form-group col-md-2 mb-md-0"><button type="submit" class="btn btn-primary btn-block">View Report</button></div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Staff Accounts</small><h3><?php echo number_format($total_staff); ?></h3></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">In-Person Sales</small><h3><?php echo number_format($total_orders); ?></h3></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Sales Revenue</small><h3><?php echo htmlspecialchars(format_store_price($total_sales, $con)); ?></h3></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Orders Confirmed</small><h3><?php echo number_format($total_confirmations); ?></h3></div></div></div>
    </div>

    <div class="card">
      <div class="card-header card-header-primary"><h4 class="card-title mb-0">Staff Performance — <?php echo htmlspecialchars($date_label); ?></h4></div>
      <div class="card-body table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Staff</th><th>Account</th><th>Status</th><th>In-Person Sales</th><th>Sales Revenue</th><th>Confirmed Orders</th><th>Last Login</th></tr></thead>
          <tbody>
          <?php if (count($staff_rows) === 0): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No staff accounts found.</td></tr>
          <?php else: foreach ($staff_rows as $staff): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($staff['admin_name']); ?></strong></td>
              <td><span class="d-block"><?php echo htmlspecialchars($staff['admin_email']); ?></span><small class="text-muted"><?php echo $staff['role'] === 'editor' ? 'Staff / Seller' : 'Administrator'; ?></small></td>
              <td><span class="badge badge-<?php echo $staff['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $staff['is_active'] ? 'Active' : 'Disabled'; ?></span></td>
              <td><?php echo number_format($staff['sales_count']); ?></td>
              <td><strong><?php echo htmlspecialchars(format_store_price($staff['sales_total'], $con)); ?></strong></td>
              <td><?php echo number_format($staff['confirmation_count']); ?></td>
              <td><?php echo $staff['last_login'] ? date('M j, Y g:i A', strtotime($staff['last_login'])) : 'Never'; ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>