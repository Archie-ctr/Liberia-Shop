<?php
$admin_page_titles = [
    'index.php' => 'Overview',
    'products_list.php' => 'Products',
    'categories.php' => 'Categories',
    'brands.php' => 'Brands',
    'inventory.php' => 'Inventory',
    'orders.php' => 'Orders',
    'pending_orders.php' => 'Pending orders',
    'completed_orders.php' => 'Completed orders',
    'manageuser.php' => 'Users',
    'customers.php' => 'Customers',
    'salesofday.php' => 'Sales report',
    'revenue.php' => 'Revenue',
    'coupons.php' => 'Coupons',
    'promotions.php' => 'Promotions',
    'banner_ads.php' => 'Banner adverts',
    'profile.php' => 'Admin profile',
    'shop_settings.php' => 'Shop settings',
    'shipping_settings.php' => 'Shipping',
    'tax_settings.php' => 'Tax settings',
    'backup.php' => 'Backup & restore',
    'analytics.php' => 'Analytics',
    'reports.php' => 'Reports',
    'support.php' => 'Support',
    'help.php' => 'Help center'
];
$admin_page_title = $admin_page_titles[basename($_SERVER['PHP_SELF'])] ?? ucwords(str_replace(['_', '.php'], [' ', ''], basename($_SERVER['PHP_SELF'])));
?>
<main class="main-panel">
    <header class="admin-topbar">
        <button type="button" class="topbar-menu" id="mobileSidebarToggle" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
            <i class="material-icons">menu</i>
        </button>
        <div class="topbar-title">
            <span class="topbar-eyebrow">TechShop Liberia / Admin</span>
            <h1><?php echo htmlspecialchars($admin_page_title); ?></h1>
        </div>
        <div class="topbar-actions">
            <a class="store-preview" href="../../index.php" target="_blank" rel="noopener">
                <i class="material-icons">open_in_new</i><span>View store</span>
            </a>
            <div class="topbar-divider"></div>
            <div class="topbar-user">
                <span class="topbar-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1))); ?></span>
                <span class="topbar-user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></span>
                <a class="topbar-logout" href="?logout=1" title="Sign out" aria-label="Sign out">
                    <i class="material-icons">logout</i>
                </a>
            </div>
        </div>
    </header>
