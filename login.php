<?php
session_start();
require_once __DIR__ . '/db.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function finish_login(string $message, ?string $location = null, bool $is_ajax = false): void
{
    if ($location !== null && !$is_ajax) {
        header('Location: ' . $location);
        exit;
    }

    if ($location !== null) {
        echo $message;
        exit;
    }

    if (!$is_ajax) {
        header('Location: admin/login.php?error=1');
        exit;
    }

    echo $message;
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    finish_login('Enter a valid email address and password.', null, $is_ajax);
}

$admin_query = mysqli_prepare($con, 'SELECT admin_id, admin_name, admin_email, admin_password, is_active, role, permissions FROM admin_info WHERE admin_email = ? LIMIT 1');
if (!$admin_query) {
    http_response_code(500);
    exit('Unable to sign in right now.');
}
mysqli_stmt_bind_param($admin_query, 's', $email);
if (!mysqli_stmt_execute($admin_query)) {
    http_response_code(500);
    exit('Unable to sign in right now.');
}
$admin_result = mysqli_stmt_get_result($admin_query);
$admin = $admin_result ? mysqli_fetch_assoc($admin_result) : null;

if ($admin) {
    if ((int)($admin['is_active'] ?? 1) !== 1) {
        finish_login('This staff account is disabled. Contact the administrator.', null, $is_ajax);
    }
    if (!hash_equals((string)$admin['admin_password'], md5($password))) {
        finish_login('Email or password is incorrect.', null, $is_ajax);
    }

    session_regenerate_id(true);
    unset($_SESSION['uid'], $_SESSION['name']);
    $_SESSION['admin_id'] = (int)$admin['admin_id'];
    $_SESSION['admin_name'] = $admin['admin_name'];
    $_SESSION['admin_email'] = $admin['admin_email'];
    $_SESSION['admin_role'] = $admin['role'] ?: 'admin';
    $_SESSION['role'] = $admin['role'] === 'editor' ? 'e' : 'a';
    $_SESSION['permissions'] = $admin['permissions'] ?: '';
    mysqli_query($con, "UPDATE admin_info SET last_login=NOW() WHERE admin_id='" . (int)$admin['admin_id'] . "'");
    finish_login('admin_login_success', 'admin/admin/index.php', $is_ajax);
}

$user_query = mysqli_prepare($con, 'SELECT user_id, first_name, password FROM user_info WHERE email = ? LIMIT 1');
if (!$user_query) {
    http_response_code(500);
    exit('Unable to sign in right now.');
}
mysqli_stmt_bind_param($user_query, 's', $email);
if (!mysqli_stmt_execute($user_query)) {
    http_response_code(500);
    exit('Unable to sign in right now.');
}
$user_result = mysqli_stmt_get_result($user_query);
$user = $user_result ? mysqli_fetch_assoc($user_result) : null;

if (!$user || !hash_equals((string)$user['password'], (string)$password)) {
    finish_login('Email or password is incorrect.', null, $is_ajax);
}

session_regenerate_id(true);
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
$_SESSION['uid'] = (int)$user['user_id'];
$_SESSION['name'] = $user['first_name'];

$ip_add = getenv('REMOTE_ADDR') ?: '';
$cart_update = mysqli_prepare($con, 'UPDATE cart SET user_id = ? WHERE ip_add = ? AND user_id = -1');
$wishlist_update = mysqli_prepare($con, 'UPDATE wishlist SET user_id = ? WHERE ip_add = ? AND user_id = -1');
if (!$cart_update || !$wishlist_update) {
    http_response_code(500);
    exit('Your account was verified, but your cart could not be connected. Please try again.');
}

mysqli_stmt_bind_param($cart_update, 'is', $_SESSION['uid'], $ip_add);
mysqli_stmt_bind_param($wishlist_update, 'is', $_SESSION['uid'], $ip_add);
if (!mysqli_stmt_execute($cart_update) || !mysqli_stmt_execute($wishlist_update)) {
    http_response_code(500);
    exit('Your account was verified, but your cart could not be connected. Please try again.');
}

$has_checkout_items = !empty($_COOKIE['product_list']);
if ($has_checkout_items) {
    setcookie('product_list', '', time() - 3600, '/');
}

$user_destination = $has_checkout_items ? 'cart.php' : 'index.php';
finish_login($has_checkout_items ? 'cart_login' : 'login_success', $user_destination, $is_ajax);
