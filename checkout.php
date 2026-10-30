<?php
session_start();
if (!isset($_SESSION['uid'])) {
	header('Location: signin_form.php');
	exit;
}

include 'db.php';
include_once 'currency.php';

$user_id = (int)$_SESSION['uid'];
$user_statement = mysqli_prepare($con, 'SELECT first_name, last_name, email, address1, address2 FROM user_info WHERE user_id = ? AND is_active = 1 LIMIT 1');
if (!$user_statement) {
	throw new RuntimeException('Unable to prepare checkout customer lookup: ' . mysqli_error($con));
}
mysqli_stmt_bind_param($user_statement, 'i', $user_id);
if (!mysqli_stmt_execute($user_statement)) {
	throw new RuntimeException('Unable to load checkout customer: ' . mysqli_stmt_error($user_statement));
}
$user_result = mysqli_stmt_get_result($user_statement);
$checkout_user = mysqli_fetch_assoc($user_result);
if (!$checkout_user) {
	session_destroy();
	header('Location: signin_form.php');
	exit;
}

$is_local_test_customer = ($checkout_user['email'] ?? '') === 'local.test.customer.20261029@example.com';
$cart_statement = mysqli_prepare($con, 'SELECT c.id AS cart_id, p.product_id, p.product_title, p.product_price, p.stock_quantity, c.qty FROM cart c JOIN products p ON p.product_id = c.p_id WHERE c.user_id = ? AND p.is_active = 1 ORDER BY c.id DESC');
if (!$cart_statement) {
	throw new RuntimeException('Unable to prepare checkout cart lookup: ' . mysqli_error($con));
}
mysqli_stmt_bind_param($cart_statement, 'i', $user_id);
if (!mysqli_stmt_execute($cart_statement)) {
	throw new RuntimeException('Unable to load checkout cart: ' . mysqli_stmt_error($cart_statement));
}
$cart_result = mysqli_stmt_get_result($cart_statement);
$checkout_items = [];
$checkout_total = 0;
while ($item = mysqli_fetch_assoc($cart_result)) {
	$item['qty'] = (int)$item['qty'];
	$item['product_price'] = (int)$item['product_price'];
	$item['stock_quantity'] = (int)$item['stock_quantity'];
	$checkout_items[] = $item;
}
$checkout_items = $is_local_test_customer ? array_slice($checkout_items, 0, 1) : $checkout_items;
$checkout_total = 0;
foreach ($checkout_items as $item) {
	$checkout_total += $item['product_price'] * $item['qty'];
}
if (!$checkout_items) {
	header('Location: cart.php');
	exit;
}

$checkout_error = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);
$escape = static function ($value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
include 'header.php';
?>
<style>
.cod-checkout { max-width: 1120px; margin: 0 auto; }
.cod-checkout .checkout-card { padding: 26px; border: 1px solid #e4e7ed; border-radius: 12px; background: #fff; box-shadow: 0 8px 26px rgba(21, 22, 29, .06); }
.cod-checkout h1, .cod-checkout h2 { margin-top: 0; color: #1e1f29; }
.cod-checkout .checkout-columns { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr); gap: 24px; align-items: start; }
.cod-checkout .form-field { margin-bottom: 16px; }
.cod-checkout .form-field label { display: block; margin-bottom: 6px; font-weight: 600; }
.cod-checkout .form-field input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #d6dbe4; border-radius: 6px; }
.cod-checkout .address-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
.cod-checkout .order-items { width: 100%; border-collapse: collapse; }
.cod-checkout .order-items th, .cod-checkout .order-items td { padding: 12px 8px; border-bottom: 1px solid #e4e7ed; text-align: left; }
.cod-checkout .order-items th:last-child, .cod-checkout .order-items td:last-child { text-align: right; white-space: nowrap; }
.cod-checkout .order-total { display: flex; justify-content: space-between; gap: 12px; margin: 16px 0; font-size: 18px; font-weight: 700; }
.cod-checkout .cod-notice { margin: 16px 0; padding: 14px; border: 1px solid #b9dfc8; border-radius: 8px; background: #f0faf3; color: #245c39; line-height: 1.55; }
.cod-checkout .cod-notice.test-order { border-color: #efcf83; background: #fff9e8; color: #694d13; }
.cod-checkout .shipping-confirm { display: flex; align-items: flex-start; gap: 9px; margin: 18px 0; font-weight: 500; }
.cod-checkout .shipping-confirm input { margin-top: 4px; }
.cod-checkout .checkout-btn { width: 100%; min-height: 48px; border: 0; border-radius: 8px; background: #16834a; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
.cod-checkout .checkout-btn:hover { background: #126d3e; }
.cod-checkout .checkout-alert { margin-bottom: 18px; padding: 12px 14px; border-radius: 6px; background: #fff1f0; color: #a12622; }
@media (max-width: 767px) {
	.cod-checkout .checkout-columns { grid-template-columns: 1fr; }
	.cod-checkout .checkout-card { padding: 18px; }
	.cod-checkout .address-fields { grid-template-columns: 1fr; }
}
</style>

<section class="section">
	<div class="container cod-checkout">
		<h1>Checkout</h1>
		<?php if ($checkout_error !== ''): ?>
			<div class="checkout-alert" role="alert"><?php echo $escape($checkout_error); ?></div>
		<?php endif; ?>
		<?php if ($is_local_test_customer): ?>
			<div class="cod-notice test-order"><strong>LOCAL TEST ORDER — DO NOT FULFILL OR SHIP.</strong> This test order uses cash on delivery and will not charge a payment method.</div>
		<?php endif; ?>

		<div class="checkout-columns">
			<div class="checkout-card">
				<h2>Delivery details</h2>
				<div class="cod-notice"><strong>Cash on delivery</strong><br>Pay when your order is delivered. This checkout does not request, process, or store card numbers or security codes.</div>
				<form id="checkout_form" action="checkout_process.php" method="POST">
					<div class="form-field">
						<label for="checkout-name">Full name</label>
						<input type="text" id="checkout-name" name="firstname" maxlength="255" value="<?php echo $escape(trim($checkout_user['first_name'] . ' ' . $checkout_user['last_name'])); ?>" required>
					</div>
					<div class="form-field">
						<label for="checkout-email">Email</label>
						<input type="email" id="checkout-email" name="email" maxlength="255" value="<?php echo $escape($checkout_user['email']); ?>" required>
					</div>
					<div class="form-field">
						<label for="checkout-address">Delivery address</label>
						<input type="text" id="checkout-address" name="address" maxlength="255" value="<?php echo $escape($checkout_user['address1']); ?>" required>
					</div>
					<div class="address-fields">
						<div class="form-field">
							<label for="checkout-city">City</label>
							<input type="text" id="checkout-city" name="city" maxlength="255" value="<?php echo $escape($checkout_user['address2']); ?>" required>
						</div>
						<div class="form-field">
							<label for="checkout-state">County / district</label>
							<input type="text" id="checkout-state" name="state" maxlength="255" value="<?php echo $is_local_test_customer ? 'Montserrado' : ''; ?>" required>
						</div>
						<div class="form-field">
							<label for="checkout-zip">Postal code</label>
							<input type="text" id="checkout-zip" name="zip" inputmode="numeric" pattern="[0-9]{1,10}" maxlength="10" value="<?php echo $is_local_test_customer ? '100001' : ''; ?>" required>
						</div>
					</div>
					<input type="hidden" name="payment_method" value="cash_on_delivery">
					<label class="shipping-confirm"><input type="checkbox" name="confirm_shipping" value="1" required><span>Confirm that the delivery address above is correct.</span></label>
					<button type="submit" class="checkout-btn"><?php echo $is_local_test_customer ? 'Place local test order — cash on delivery' : 'Place order — cash on delivery'; ?></button>
				</form>
			</div>

			<div class="checkout-card">
				<h2>Your order</h2>
				<div style="overflow-x:auto">
					<table class="order-items">
						<thead><tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr></thead>
						<tbody>
						<?php foreach ($checkout_items as $item): ?>
							<tr>
								<td><?php echo $escape($item['product_title']); ?></td>
								<td><?php echo (int)$item['qty']; ?></td>
								<td><?php echo $escape(format_store_price($item['product_price'] * $item['qty'], $con)); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="order-total"><span>Total</span><span><?php echo $escape(format_store_price($checkout_total, $con)); ?></span></div>
				<p>Payment is collected on delivery. Orders remain pending until confirmed by the store.</p>
			</div>
		</div>
	</div>
</section>

<?php
include 'newslettter.php';
include 'footer.php';
?>
