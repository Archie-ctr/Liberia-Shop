<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['uid'])) {
	header('Location: cart.php');
	exit;
}

$user_id = (int)$_SESSION['uid'];
$name = trim((string)($_POST['firstname'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$city = trim((string)($_POST['city'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$zip_input = trim((string)($_POST['zip'] ?? ''));
$zip = filter_var($zip_input, FILTER_VALIDATE_INT);
$payment_method = (string)($_POST['payment_method'] ?? '');
$transaction_started = false;

$fail_checkout = static function (string $message, string $log_message = '') use ($con, &$transaction_started): void {
	if ($transaction_started) {
		mysqli_rollback($con);
		$transaction_started = false;
	}
	if ($log_message !== '') {
		error_log('Checkout error: ' . $log_message);
	}
	$_SESSION['checkout_error'] = $message;
	header('Location: checkout.php');
	exit;
};

if (
	$name === '' ||
	!filter_var($email, FILTER_VALIDATE_EMAIL) ||
	$address === '' ||
	$city === '' ||
	$state === '' ||
	$zip === false ||
	$zip < 0 ||
	$zip > 2147483647 ||
	strlen($zip_input) > 10 ||
	$payment_method !== 'cash_on_delivery' ||
	($_POST['confirm_shipping'] ?? '') !== '1'
) {
	$_SESSION['checkout_error'] = 'Please check the delivery details and confirm your address.';
	header('Location: checkout.php');
	exit;
}

$user_statement = mysqli_prepare($con, 'SELECT email FROM user_info WHERE user_id = ? AND is_active = 1 LIMIT 1');
if (!$user_statement) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
mysqli_stmt_bind_param($user_statement, 'i', $user_id);
if (!mysqli_stmt_execute($user_statement)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_stmt_error($user_statement));
}
$user_result = mysqli_stmt_get_result($user_statement);
$checkout_user = mysqli_fetch_assoc($user_result);
if (!$checkout_user) {
	header('Location: signin_form.php');
	exit;
}
if (strtolower($email) !== strtolower((string)$checkout_user['email'])) {
	$_SESSION['checkout_error'] = 'Use the email address linked to your customer account.';
	header('Location: checkout.php');
	exit;
}
$email = (string)$checkout_user['email'];

$is_local_test_customer = $checkout_user['email'] === 'local.test.customer.20261029@example.com';
$local_test_note = $is_local_test_customer ? 'LOCAL TEST ORDER — DO NOT FULFILL OR SHIP.' : '';

if (!mysqli_begin_transaction($con)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
$transaction_started = true;

$cart_statement = mysqli_prepare(
	$con,
	'SELECT c.id AS cart_id, p.product_id, p.product_title, p.product_price, p.stock_quantity, c.qty
	 FROM cart c
	 JOIN products p ON p.product_id = c.p_id
	 WHERE c.user_id = ? AND p.is_active = 1
	 ORDER BY c.id DESC
	 FOR UPDATE'
);
if (!$cart_statement) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
mysqli_stmt_bind_param($cart_statement, 'i', $user_id);
if (!mysqli_stmt_execute($cart_statement)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_stmt_error($cart_statement));
}
$cart_result = mysqli_stmt_get_result($cart_statement);
$items = [];
$total_amount = 0;
while ($item = mysqli_fetch_assoc($cart_result)) {
	$quantity = (int)$item['qty'];
	$stock = (int)$item['stock_quantity'];
	if ($quantity < 1 || $quantity > $stock || $stock < 1) {
		$fail_checkout('A cart item is no longer available in the requested quantity. Please review your cart.', $item['product_title']);
	}
	$item['qty'] = $quantity;
	$item['product_price'] = (int)$item['product_price'];
	$total_amount += $item['product_price'] * $quantity;
	$items[] = $item;
}
$items = $is_local_test_customer ? array_slice($items, 0, 1) : $items;
if (!$items) {
	$fail_checkout('Your cart is empty. Add an item before checking out.');
}
$total_amount = 0;
foreach ($items as $item) {
	$total_amount += (int)$item['product_price'] * (int)$item['qty'];
}

$order_id_result = mysqli_query($con, 'SELECT COALESCE(MAX(order_id), 0) + 1 AS next_id FROM orders_info FOR UPDATE');
if (!$order_id_result) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
$order_id = (int)mysqli_fetch_assoc($order_id_result)['next_id'];
$order_product_id_result = mysqli_query($con, 'SELECT COALESCE(MAX(order_pro_id), 0) + 1 AS next_id FROM order_products FOR UPDATE');
if (!$order_product_id_result) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
$order_product_id = (int)mysqli_fetch_assoc($order_product_id_result)['next_id'];

$full_address = $address . ', ' . $city . ', ' . $state . ' ' . $zip_input;
$card_name = '';
$card_number = '';
$expiry_date = '';
$cvv = 0;
$line_count = count($items);
$paid = 0;
$order_status = 'pending';
$payment_status = 'pending';
$order_statement = mysqli_prepare(
	$con,
	'INSERT INTO orders_info
	 (order_id, user_id, f_name, email, address, city, state, shipping_address, billing_address,
	  customer_notes, admin_notes, zip, cardname, cardnumber, expdate, prod_count, total_amt, cvv,
	  payment_method, paid, order_status, payment_status)
	 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$order_statement) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
mysqli_stmt_bind_param(
	$order_statement,
	'iisssssssssisssiiisiss',
	$order_id,
	$user_id,
	$name,
	$email,
	$address,
	$city,
	$state,
	$full_address,
	$full_address,
	$local_test_note,
	$local_test_note,
	$zip,
	$card_name,
	$card_number,
	$expiry_date,
	$line_count,
	$total_amount,
	$cvv,
	$payment_method,
	$paid,
	$order_status,
	$payment_status
);
if (!mysqli_stmt_execute($order_statement)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_stmt_error($order_statement));
}

$item_statement = mysqli_prepare($con, 'INSERT INTO order_products (order_pro_id, order_id, product_id, qty, amt) VALUES (?, ?, ?, ?, ?)');
if (!$item_statement) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
foreach ($items as $item) {
	$product_id = (int)$item['product_id'];
	$quantity = (int)$item['qty'];
	$line_amount = (int)$item['product_price'] * $quantity;
	mysqli_stmt_bind_param($item_statement, 'iiiii', $order_product_id, $order_id, $product_id, $quantity, $line_amount);
	if (!mysqli_stmt_execute($item_statement)) {
		$fail_checkout('Checkout could not be completed. Please try again.', mysqli_stmt_error($item_statement));
	}
	$order_product_id++;
}

if ($is_local_test_customer) {
	$cart_id = (int)$items[0]['cart_id'];
	$delete_cart_statement = mysqli_prepare($con, 'DELETE FROM cart WHERE id = ? AND user_id = ?');
	if (!$delete_cart_statement) {
		$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
	}
	mysqli_stmt_bind_param($delete_cart_statement, 'ii', $cart_id, $user_id);
} else {
	$delete_cart_statement = mysqli_prepare($con, 'DELETE FROM cart WHERE user_id = ?');
	if (!$delete_cart_statement) {
		$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
	}
	mysqli_stmt_bind_param($delete_cart_statement, 'i', $user_id);
}
if (!mysqli_stmt_execute($delete_cart_statement)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_stmt_error($delete_cart_statement));
}
if (!mysqli_commit($con)) {
	$fail_checkout('Checkout could not be completed. Please try again.', mysqli_error($con));
}
$transaction_started = false;

$_SESSION['last_order_id'] = $order_id;
$_SESSION['local_test_order'] = $is_local_test_customer;
header('Location: order_successful.php');
exit;
