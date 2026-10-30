<?php
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>TechShop Liberia | Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700|Roboto+Slab:400,700">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="assets/css/Material+Icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/material-dashboard.css?v=2.1.0">
    <link rel="stylesheet" href="assets/css/admin-overrides.css?v=1.2">
    <script src="assets/js/core/jquery.min.js"></script>
</head>
<body class="admin-app">
<div class="wrapper">
    <aside class="sidebar" id="adminSidebar" data-color="purple" data-background-color="black">
        <div class="sidebar-brand">
            <a class="brand-lockup" href="index.php" aria-label="TechShop Liberia dashboard">
                <span class="brand-mark">TS</span>
                <span class="brand-name">TECHSHOP<small>LIBERIA</small></span>
            </a>
            <button type="button" class="sidebar-toggle" id="sidebarMinimizer" aria-label="Collapse sidebar">
                <i class="material-icons">menu_open</i>
            </button>
        </div>
        <div class="sidebar-account">
            <div class="account-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['admin_name'], 0, 1))); ?></div>
            <div class="account-copy">
                <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
                <span><?php echo (($_SESSION['admin_role'] ?? '') === 'editor') ? 'Staff / Seller' : 'Administrator'; ?></span>
            </div>
            <a class="account-logout" href="?logout=1" title="Sign out" aria-label="Sign out">
                <i class="material-icons">logout</i>
            </a>
        </div>

        <nav class="sidebar-nav" aria-label="Admin navigation">
            <span class="nav-section">Workspace</span>
            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="material-icons">space_dashboard</i><span>Overview</span>
            </a>

            <span class="nav-section">Catalog</span>
            <a class="nav-link <?php echo in_array($current_page, ['products_list.php', 'edit_product.php'], true) ? 'active' : ''; ?>" href="products_list.php">
                <i class="material-icons">inventory_2</i><span>Products</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                <i class="material-icons">category</i><span>Categories</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'brands.php' ? 'active' : ''; ?>" href="brands.php">
                <i class="material-icons">sell</i><span>Brands</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                <i class="material-icons">warehouse</i><span>Inventory</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'electroniclist.php' ? 'active' : ''; ?>" href="electroniclist.php">
                <i class="material-icons">devices</i><span>Electronics</span>
            </a>

            <span class="nav-section">Orders &amp; customers</span>
            <a class="nav-link <?php echo $current_page === 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                <i class="material-icons">point_of_sale</i><span>In-person sale</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                <i class="material-icons">shopping_bag</i><span>Orders</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'pending_orders.php' ? 'active' : ''; ?>" href="pending_orders.php">
                <i class="material-icons">pending_actions</i><span>Pending orders</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'completed_orders.php' ? 'active' : ''; ?>" href="completed_orders.php">
                <i class="material-icons">task_alt</i><span>Completed orders</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'manageuser.php' ? 'active' : ''; ?>" href="manageuser.php">
                <i class="material-icons">people</i><span>Users</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                <i class="material-icons">groups</i><span>Customers</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'addsuppliers.php' ? 'active' : ''; ?>" href="addsuppliers.php">
                <i class="material-icons">person_add</i><span>Add customer</span>
            </a>

            <span class="nav-section">Sales &amp; growth</span>
            <a class="nav-link <?php echo $current_page === 'salesofday.php' ? 'active' : ''; ?>" href="salesofday.php">
                <i class="material-icons">query_stats</i><span>Sales report</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'revenue.php' ? 'active' : ''; ?>" href="revenue.php">
                <i class="material-icons">monitoring</i><span>Revenue</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'invoice.php' ? 'active' : ''; ?>" href="invoice.php">
                <i class="material-icons">receipt_long</i><span>Invoices</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'payment.php' ? 'active' : ''; ?>" href="payment.php">
                <i class="material-icons">payments</i><span>Payments</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'refunds.php' ? 'active' : ''; ?>" href="refunds.php">
                <i class="material-icons">currency_exchange</i><span>Refunds</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'coupons.php' ? 'active' : ''; ?>" href="coupons.php">
                <i class="material-icons">local_offer</i><span>Coupons</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'promotions.php' ? 'active' : ''; ?>" href="promotions.php">
                <i class="material-icons">campaign</i><span>Promotions</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'banner_ads.php' ? 'active' : ''; ?>" href="banner_ads.php">
                <i class="material-icons">view_carousel</i><span>Banner adverts</span>
            </a>

            <span class="nav-section">Store content</span>
            <a class="nav-link <?php echo $current_page === 'pages.php' ? 'active' : ''; ?>" href="pages.php">
                <i class="material-icons">web</i><span>Pages</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'blog.php' ? 'active' : ''; ?>" href="blog.php">
                <i class="material-icons">article</i><span>Blog</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'testimonials.php' ? 'active' : ''; ?>" href="testimonials.php">
                <i class="material-icons">reviews</i><span>Testimonials</span>
            </a>

            <span class="nav-section">System</span>
            <?php if ((($_SESSION['admin_role'] ?? '') === 'super_admin') || (($_SESSION['admin_role'] ?? '') === 'admin')): ?>
            <a class="nav-link <?php echo $current_page === 'staff.php' ? 'active' : ''; ?>" href="staff.php">
                <i class="material-icons">badge</i><span>Staff users</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'staff_reports.php' ? 'active' : ''; ?>" href="staff_reports.php">
                <i class="material-icons">monitoring</i><span>Staff reports</span>
            </a>
            <?php endif; ?>
            <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                <i class="material-icons">manage_accounts</i><span>Admin profile</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'shop_settings.php' ? 'active' : ''; ?>" href="shop_settings.php">
                <i class="material-icons">storefront</i><span>Shop settings</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'shipping_settings.php' ? 'active' : ''; ?>" href="shipping_settings.php">
                <i class="material-icons">local_shipping</i><span>Shipping</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'tax_settings.php' ? 'active' : ''; ?>" href="tax_settings.php">
                <i class="material-icons">request_quote</i><span>Tax settings</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'backup.php' ? 'active' : ''; ?>" href="backup.php">
                <i class="material-icons">backup</i><span>Backup &amp; restore</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>" href="analytics.php">
                <i class="material-icons">analytics</i><span>Analytics</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="material-icons">assessment</i><span>Reports</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                <i class="material-icons">history</i><span>System logs</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'support.php' ? 'active' : ''; ?>" href="support.php">
                <i class="material-icons">support_agent</i><span>Support</span>
            </a>
            <a class="nav-link <?php echo $current_page === 'help.php' ? 'active' : ''; ?>" href="help.php">
                <i class="material-icons">help_outline</i><span>Help center</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Store systems online</span>
        </div>
    </aside>
    <script>
        (function () {
            var isMobile = function () { return window.matchMedia('(max-width: 991.98px)').matches; };
            var desktopToggle = document.getElementById('sidebarMinimizer');
            var mobileToggle = null;

            function syncMobileSidebar(open) {
                document.body.classList.toggle('sidebar-open', open && isMobile());
                if (mobileToggle) {
                    mobileToggle.setAttribute('aria-expanded', open && isMobile() ? 'true' : 'false');
                }
            }

            if (desktopToggle) {
                desktopToggle.addEventListener('click', function () {
                    if (isMobile()) {
                        syncMobileSidebar(!document.body.classList.contains('sidebar-open'));
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                });
            }
            function bindMobileToggle() {
                mobileToggle = document.getElementById('mobileSidebarToggle');
                if (mobileToggle && !mobileToggle.dataset.bound) {
                    mobileToggle.dataset.bound = '1';
                    mobileToggle.addEventListener('click', function () {
                        syncMobileSidebar(!document.body.classList.contains('sidebar-open'));
                    });
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindMobileToggle);
            } else {
                bindMobileToggle();
            }

            document.addEventListener('click', function (event) {
                if (!isMobile() || !document.body.classList.contains('sidebar-open')) return;
                if (!event.target.closest('#adminSidebar') && !event.target.closest('#mobileSidebarToggle')) {
                    syncMobileSidebar(false);
                }
            });
            document.querySelectorAll('#adminSidebar a').forEach(function (link) {
                link.addEventListener('click', function () { syncMobileSidebar(false); });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') syncMobileSidebar(false);
            });
            window.addEventListener('resize', function () {
                if (!isMobile()) syncMobileSidebar(false);
            });
            syncMobileSidebar(false);
        }());
    </script>
