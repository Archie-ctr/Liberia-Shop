<?php
session_start();
include("../../db.php");

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: ../login.php');
    exit();
}

$dashboard_notice = '';
$dashboard_notice_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_dashboard_user'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $mobile = trim($_POST['mobile'] ?? '');
        $address1 = trim($_POST['address1'] ?? '');
        $address2 = trim($_POST['address2'] ?? '');

        if ($first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 9 || !preg_match('/^[0-9]{10}$/', $mobile)
            || $address1 === '' || $address2 === '') {
            $dashboard_notice = 'Complete every field with a valid email, 10-digit mobile number, and password of at least 9 characters.';
            $dashboard_notice_type = 'danger';
        } else {
            $stmt = mysqli_prepare($con, 'INSERT INTO user_info (first_name, last_name, email, password, mobile, address1, address2) VALUES (?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssssss', $first_name, $last_name, $email, $password, $mobile, $address1, $address2);
            if (mysqli_stmt_execute($stmt)) {
                $dashboard_notice = 'Customer created successfully.';
            } else {
                $dashboard_notice = 'Could not create customer: ' . mysqli_stmt_error($stmt);
                $dashboard_notice_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['update_dashboard_user'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $mobile = trim($_POST['mobile'] ?? '');
        $address1 = trim($_POST['address1'] ?? '');
        $address2 = trim($_POST['address2'] ?? '');

        if (!$user_id || $first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !preg_match('/^[0-9]{10}$/', $mobile) || $address1 === '' || $address2 === ''
            || ($password !== '' && strlen($password) < 9)) {
            $dashboard_notice = 'Check the customer details and make sure any new password is at least 9 characters.';
            $dashboard_notice_type = 'danger';
        } else {
            if ($password !== '') {
                $stmt = mysqli_prepare($con, 'UPDATE user_info SET first_name = ?, last_name = ?, email = ?, password = ?, mobile = ?, address1 = ?, address2 = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($stmt, 'sssssssi', $first_name, $last_name, $email, $password, $mobile, $address1, $address2, $user_id);
            } else {
                $stmt = mysqli_prepare($con, 'UPDATE user_info SET first_name = ?, last_name = ?, email = ?, mobile = ?, address1 = ?, address2 = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($stmt, 'ssssssi', $first_name, $last_name, $email, $mobile, $address1, $address2, $user_id);
            }
            if (mysqli_stmt_execute($stmt)) {
                $dashboard_notice = 'Customer details updated.';
            } else {
                $dashboard_notice = 'Could not update customer: ' . mysqli_stmt_error($stmt);
                $dashboard_notice_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['delete_dashboard_user'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$user_id) {
            $dashboard_notice = 'The customer ID is invalid.';
            $dashboard_notice_type = 'danger';
        } else {
            $stmt = mysqli_prepare($con, 'DELETE FROM user_info WHERE user_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1) {
                $dashboard_notice = 'Customer removed successfully.';
            } else {
                $dashboard_notice = mysqli_stmt_error($stmt) ?: 'No customer was found with that ID.';
                $dashboard_notice_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$countResult = mysqli_query($con, 'SELECT
    (SELECT COUNT(*) FROM user_info) AS users,
    (SELECT COUNT(*) FROM products) AS products,
    (SELECT COUNT(*) FROM categories) AS categories,
    (SELECT COUNT(*) FROM email_info) AS subscribers');
if (!$countResult) {
    die('Unable to load dashboard statistics: ' . mysqli_error($con));
}
$counts = mysqli_fetch_assoc($countResult);

$usersResult = mysqli_query($con, 'SELECT user_id, first_name, last_name, email, mobile, phone, address1, address2 FROM user_info ORDER BY user_id DESC LIMIT 8');
if (!$usersResult) {
    die('Unable to load customers: ' . mysqli_error($con));
}

include "sidenav.php";
include "topheader.php";
?>
<div class="content">
    <div class="container-fluid">
        <?php if ($dashboard_notice !== ''): ?>
            <div class="alert alert-<?php echo $dashboard_notice_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($dashboard_notice); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <section class="dashboard-hero">
            <div class="dashboard-hero-content">
                <span class="hero-kicker">Store operations</span>
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>
                <p>Manage your catalog, customers, and store activity from one place.</p>
            </div>
            <span class="hero-date"><?php echo date('l, F j, Y'); ?></span>
        </section>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card dashboard-stat stat-purple">
                    <div class="dashboard-stat-top"><span>Customers</span><span class="dashboard-stat-icon"><i class="material-icons">groups</i></span></div>
                    <strong class="dashboard-stat-value"><?php echo number_format((int)$counts['users']); ?></strong>
                    <span class="dashboard-stat-note">Registered accounts</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card dashboard-stat stat-blue">
                    <div class="dashboard-stat-top"><span>Products</span><span class="dashboard-stat-icon"><i class="material-icons">inventory_2</i></span></div>
                    <strong class="dashboard-stat-value"><?php echo number_format((int)$counts['products']); ?></strong>
                    <span class="dashboard-stat-note">Items in the catalog</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card dashboard-stat stat-green">
                    <div class="dashboard-stat-top"><span>Categories</span><span class="dashboard-stat-icon"><i class="material-icons">category</i></span></div>
                    <strong class="dashboard-stat-value"><?php echo number_format((int)$counts['categories']); ?></strong>
                    <span class="dashboard-stat-note">Product groups</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card dashboard-stat stat-orange">
                    <div class="dashboard-stat-top"><span>Subscribers</span><span class="dashboard-stat-icon"><i class="material-icons">mark_email_read</i></span></div>
                    <strong class="dashboard-stat-value"><?php echo number_format((int)$counts['subscribers']); ?></strong>
                    <span class="dashboard-stat-note">Newsletter contacts</span>
                </div>
            </div>
        </div>

        <div class="dashboard-section-heading">
            <div>
                <h3>Customer management</h3>
                <p>Recently registered customers and account actions.</p>
            </div>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createCustomerModal">
                <i class="material-icons">person_add</i> Add customer
            </button>
        </div>

        <section class="card">
            <div class="dashboard-card-header">
                <h4>Recent customers</h4>
                <a class="btn btn-sm btn-outline-primary" href="customers.php">View all customers</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Customer</th><th>Email</th><th>Mobile</th><th>Location</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($usersResult) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4">No customers yet.</td></tr>
                    <?php else: ?>
                        <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                            <?php $display_mobile = $user['mobile'] ?: $user['phone']; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><small class="d-block text-muted">#<?php echo (int)$user['user_id']; ?></small></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($display_mobile ?: 'Not provided'); ?></td>
                                <td><?php echo htmlspecialchars(trim(($user['address1'] ?? '') . ', ' . ($user['address2'] ?? ''), ', ')); ?></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary js-edit-customer"
                                        data-toggle="modal" data-target="#editCustomerModal"
                                        data-user-id="<?php echo (int)$user['user_id']; ?>"
                                        data-first-name="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES); ?>"
                                        data-last-name="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                        data-mobile="<?php echo htmlspecialchars($display_mobile ?? '', ENT_QUOTES); ?>"
                                        data-address1="<?php echo htmlspecialchars($user['address1'] ?? '', ENT_QUOTES); ?>"
                                        data-address2="<?php echo htmlspecialchars($user['address2'] ?? '', ENT_QUOTES); ?>"
                                        aria-label="Edit customer">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-customer"
                                        data-toggle="modal" data-target="#deleteCustomerModal"
                                        data-user-id="<?php echo (int)$user['user_id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES); ?>"
                                        aria-label="Delete customer">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="dashboard-section-heading">
            <div><h3>Quick actions</h3><p>Shortcuts to common store tasks.</p></div>
        </div>
        <div class="row">
            <?php if (($_SESSION['role'] ?? '') !== 'e'): ?>
            <div class="col-md-4 mb-3">
                <a href="products_list.php?open_add=1" class="card p-3 d-flex flex-row align-items-center">
                    <span class="dashboard-stat-icon stat-blue mr-3"><i class="material-icons">add_box</i></span>
                    <span><strong class="d-block">Add a product</strong><small class="text-muted">Create a new catalog item</small></span>
                </a>
            </div>
            <?php endif; ?>
            <div class="col-md-4 mb-3">
                <a href="categories.php" class="card p-3 d-flex flex-row align-items-center">
                    <span class="dashboard-stat-icon stat-green mr-3"><i class="material-icons">category</i></span>
                    <span><strong class="d-block">Manage categories</strong><small class="text-muted">Organize the catalog</small></span>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="orders.php" class="card p-3 d-flex flex-row align-items-center">
                    <span class="dashboard-stat-icon stat-purple mr-3"><i class="material-icons">shopping_bag</i></span>
                    <span><strong class="d-block">Review orders</strong><small class="text-muted">View and process orders</small></span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createCustomerModal" tabindex="-1" role="dialog" aria-labelledby="createCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title" id="createCustomerTitle">Add customer</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="createFirstName">First name</label><input id="createFirstName" name="first_name" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="createLastName">Last name</label><input id="createLastName" name="last_name" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="createEmail">Email</label><input id="createEmail" name="email" type="email" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="createMobile">Mobile (10 digits)</label><input id="createMobile" name="mobile" class="form-control" pattern="[0-9]{10}" maxlength="10" required></div>
                        <div class="form-group col-md-6"><label for="createPassword">Password</label><input id="createPassword" name="password" type="password" minlength="9" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="createAddress1">Address</label><input id="createAddress1" name="address1" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="createAddress2">City</label><input id="createAddress2" name="address2" maxlength="11" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" name="create_dashboard_user" class="btn btn-primary">Create customer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title" id="editCustomerTitle">Edit customer</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="editFirstName">First name</label><input id="editFirstName" name="first_name" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="editLastName">Last name</label><input id="editLastName" name="last_name" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="editEmail">Email</label><input id="editEmail" name="email" type="email" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="editMobile">Mobile (10 digits)</label><input id="editMobile" name="mobile" class="form-control" pattern="[0-9]{10}" maxlength="10" required></div>
                        <div class="form-group col-md-6"><label for="editAddress1">Address</label><input id="editAddress1" name="address1" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="editAddress2">City</label><input id="editAddress2" name="address2" maxlength="11" class="form-control" required></div>
                        <div class="form-group col-md-6"><label for="editPassword">New password (optional)</label><input id="editPassword" name="password" type="password" minlength="9" class="form-control" autocomplete="new-password"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" name="update_dashboard_user" class="btn btn-primary">Save changes</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog" aria-labelledby="deleteCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title" id="deleteCustomerTitle">Remove customer</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body"><input type="hidden" name="user_id" id="deleteUserId"><p class="mb-0">Remove <strong id="deleteUserName"></strong>? This action cannot be undone.</p></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" name="delete_dashboard_user" class="btn btn-danger">Remove customer</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-edit-customer').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('editUserId').value = button.dataset.userId;
            document.getElementById('editFirstName').value = button.dataset.firstName;
            document.getElementById('editLastName').value = button.dataset.lastName;
            document.getElementById('editEmail').value = button.dataset.email;
            document.getElementById('editMobile').value = button.dataset.mobile;
            document.getElementById('editAddress1').value = button.dataset.address1;
            document.getElementById('editAddress2').value = button.dataset.address2;
            document.getElementById('editPassword').value = '';
        });
    });
    document.querySelectorAll('.js-delete-customer').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('deleteUserId').value = button.dataset.userId;
            document.getElementById('deleteUserName').textContent = button.dataset.userName;
        });
    });
});
</script>
<?php include "footer.php"; ?>
