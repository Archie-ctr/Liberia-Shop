<?php
session_start();
include('../../db.php');

if (!isset($_SESSION['admin_name'])) {
    header('Location: ../login.php');
    exit();
}

$is_super_admin = (($_SESSION['admin_role'] ?? '') === 'super_admin' || ($_SESSION['admin_role'] ?? '') === 'admin');
if (!$is_super_admin) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_staff'])) {
        $name = trim($_POST['staff_name'] ?? '');
        $email = trim($_POST['staff_email'] ?? '');
        $password = $_POST['staff_password'] ?? '';
        $role = ($_POST['staff_role'] ?? '') === 'admin' ? 'admin' : 'editor';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $message = 'Enter a name, valid email address, and password of at least 8 characters.';
            $message_type = 'danger';
        } else {
            $existing = mysqli_prepare($con, 'SELECT admin_id FROM admin_info WHERE admin_email = ? LIMIT 1');
            mysqli_stmt_bind_param($existing, 's', $email);
            mysqli_stmt_execute($existing);
            mysqli_stmt_store_result($existing);

            if (mysqli_stmt_num_rows($existing) > 0) {
                $message = 'That email address is already assigned to an account.';
                $message_type = 'danger';
            } else {
                $permissions = 'products,orders,invoices,customers,sales,inventory';
                $password_hash = md5($password);
                $stmt = mysqli_prepare($con, 'INSERT INTO admin_info (admin_name, admin_email, admin_password, is_active, role, permissions) VALUES (?, ?, ?, 1, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $password_hash, $role, $permissions);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Staff account created successfully. They can now sign in and use the selling workspace.';
                } else {
                    $message = 'Could not create the staff account: ' . mysqli_stmt_error($stmt);
                    $message_type = 'danger';
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($existing);
        }
    } elseif (isset($_POST['toggle_staff'])) {
        $staff_id = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
        if ($staff_id) {
            $staff_result = mysqli_query($con, "SELECT admin_id FROM admin_info WHERE admin_id='$staff_id' AND role IN ('editor','admin') AND admin_id != '" . (int)($_SESSION['admin_id'] ?? 0) . "'");
            if ($staff_result && mysqli_num_rows($staff_result) === 1) {
                mysqli_query($con, "UPDATE admin_info SET is_active=IF(is_active=1,0,1) WHERE admin_id='$staff_id'");
                $message = 'Staff account status updated.';
            }
        }
    }
}

$staff_result = mysqli_query($con, "SELECT admin_id, admin_name, admin_email, is_active, role, permissions, last_login, created_at FROM admin_info WHERE role IN ('editor','admin') ORDER BY created_at DESC, admin_id DESC");

include 'sidenav.php';
include 'topheader.php';
?>
<div class="content">
  <div class="container-fluid">
    <?php if ($message !== ''): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary d-flex justify-content-between align-items-center">
            <div>
              <h4 class="card-title mb-0">Staff Users</h4>
              <p class="card-category mb-0">Create accounts for staff who sell and manage orders</p>
            </div>
            <div>
              <a href="staff_reports.php" class="btn btn-white btn-round mr-1"><i class="material-icons">monitoring</i> Staff Reports</a>
              <button type="button" class="btn btn-white btn-round" data-toggle="modal" data-target="#staffModal">
                <i class="material-icons">person_add</i> Add Staff
              </button>
            </div>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-hover">
              <thead class="text-primary">
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Action</th></tr>
              </thead>
              <tbody>
              <?php if ($staff_result && mysqli_num_rows($staff_result) > 0): while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($staff['admin_name']); ?></td>
                  <td><?php echo htmlspecialchars($staff['admin_email']); ?></td>
                  <td><span class="badge badge-info"><?php echo $staff['role'] === 'editor' ? 'Staff / Seller' : 'Admin'; ?></span></td>
                  <td><span class="badge badge-<?php echo $staff['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $staff['is_active'] ? 'Active' : 'Disabled'; ?></span></td>
                  <td><?php echo $staff['last_login'] ? htmlspecialchars($staff['last_login']) : 'Never'; ?></td>
                  <td>
                    <?php if ((int)$staff['admin_id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="staff_id" value="<?php echo (int)$staff['admin_id']; ?>">
                      <button type="submit" name="toggle_staff" class="btn btn-sm btn-outline-<?php echo $staff['is_active'] ? 'warning' : 'success'; ?>">
                        <?php echo $staff['is_active'] ? 'Disable' : 'Enable'; ?>
                      </button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No staff accounts found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="staffModal" tabindex="-1" role="dialog" aria-labelledby="staffModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="staffModalLabel">Create staff account</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <div class="form-group"><label>Staff Name *</label><input type="text" name="staff_name" class="form-control" required maxlength="100"></div>
          <div class="form-group"><label>Email Address *</label><input type="email" name="staff_email" class="form-control" required maxlength="300"></div>
          <div class="form-group"><label>Password *</label><input type="password" name="staff_password" class="form-control" required minlength="8" autocomplete="new-password"></div>
          <div class="form-group"><label>Account Role</label><select name="staff_role" class="form-control"><option value="editor">Staff / Seller</option><option value="admin">Administrator</option></select></div>
          <small class="text-muted">Staff / Seller accounts can manage products, orders, invoices, customers, sales, and inventory.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
          <button type="submit" name="create_staff" class="btn btn-primary">Create Staff Account</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>