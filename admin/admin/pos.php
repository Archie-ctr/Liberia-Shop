<?php
session_start();
include('../../db.php');
include_once __DIR__ . '/../../currency.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = 'You must log in first';
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_sale'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cash');

    if (!$product_id || !$quantity || $quantity < 1) {
        $message = 'Select a valid product and quantity.';
        $message_type = 'danger';
    } elseif (!in_array($payment_method, ['cash', 'mobile_money', 'card'], true)) {
        $message = 'Select a valid payment method.';
        $message_type = 'danger';
    } else {
        mysqli_begin_transaction($con);
        $product_result = mysqli_query($con, "SELECT product_id, product_title, product_price, stock_quantity FROM products WHERE product_id='$product_id' AND is_active=1 FOR UPDATE");
        $product = $product_result ? mysqli_fetch_assoc($product_result) : null;

        if (!$product) {
            $message = 'The selected product is not available.';
            $message_type = 'danger';
            mysqli_rollback($con);
        } elseif ((int)$product['stock_quantity'] < $quantity) {
            $message = 'Only ' . (int)$product['stock_quantity'] . ' item(s) are available for this product.';
            $message_type = 'danger';
            mysqli_rollback($con);
        } else {
            $next_order_result = mysqli_query($con, 'SELECT COALESCE(MAX(order_id), 0) + 1 AS next_id FROM orders_info');
            $order_id = (int)mysqli_fetch_assoc($next_order_result)['next_id'];
            $next_line_result = mysqli_query($con, 'SELECT COALESCE(MAX(order_pro_id), 0) + 1 AS next_id FROM order_products');
            $line_id = (int)mysqli_fetch_assoc($next_line_result)['next_id'];
            $total = (int)$product['product_price'] * $quantity;
            $note = 'IN-PERSON SALE. Staff: ' . ($_SESSION['admin_name'] ?? 'Staff') . '. Phone: ' . $customer_phone;
            $email = 'walkin@local.shop';
            $address = 'In-store purchase';
            $city = 'Monrovia';
            $state = 'Montserrado';
            $zip = 1000;

            $stmt = mysqli_prepare($con, 'INSERT INTO orders_info (order_id,user_id,f_name,email,address,city,state,shipping_address,billing_address,customer_notes,admin_notes,zip,cardname,cardnumber,expdate,prod_count,total_amt,cvv,paid,payment_method,payment_status,order_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $paid = 1;
            $user_id = 0;
            $payment_status = 'paid';
            $order_status = 'delivered';
            $cvv = 0;
            $card_number = '';
            $expiry_date = '';
            mysqli_stmt_bind_param($stmt, 'iisssssssssisssiiiisss',
                $order_id, $user_id, $customer_name, $email, $address, $city, $state, $address, $address,
                $note, $note, $zip, $customer_name, $card_number, $expiry_date, $quantity, $total, $cvv, $paid, $payment_method, $payment_status, $order_status);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $line_stmt = mysqli_prepare($con, 'INSERT INTO order_products (order_pro_id,order_id,product_id,qty,amt) VALUES (?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($line_stmt, 'iiiii', $line_id, $order_id, $product_id, $quantity, $total);
                $line_stmt_ok = mysqli_stmt_execute($line_stmt);
                mysqli_stmt_close($line_stmt);

                $new_stock = (int)$product['stock_quantity'] - $quantity;
                $stock_stmt = mysqli_prepare($con, 'UPDATE products SET stock_quantity=? WHERE product_id=?');
                mysqli_stmt_bind_param($stock_stmt, 'ii', $new_stock, $product_id);
                $stock_ok = mysqli_stmt_execute($stock_stmt);
                mysqli_stmt_close($stock_stmt);

                $log_available = mysqli_query($con, "SHOW TABLES LIKE 'inventory_logs'");
                if ($log_available && mysqli_num_rows($log_available) === 1) {
                    $log_stmt = mysqli_prepare($con, "INSERT INTO inventory_logs (product_id,user_id,action,quantity_change,previous_quantity,new_quantity,reason,reference_id,reference_type) VALUES (?, ?, 'sold', ?, ?, ?, ?, ?, 'order')");
                    $change = -$quantity;
                    mysqli_stmt_bind_param($log_stmt, 'iiiiisi', $product_id, $_SESSION['admin_id'], $change, $product['stock_quantity'], $new_stock, $note, $order_id);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }

                if ($line_stmt_ok && $stock_ok) {
                    mysqli_commit($con);
                    $message = 'Sale completed successfully — Order #' . $order_id . ' / Total: ' . format_store_price($total, $con);
                } else {
                    mysqli_rollback($con);
                    $message = 'The sale could not be saved completely.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Could not create the sale: ' . mysqli_stmt_error($stmt);
                $message_type = 'danger';
                mysqli_stmt_close($stmt);
                mysqli_rollback($con);
            }
        }
    }
}

$products_result = mysqli_query($con, "SELECT product_id, product_title, product_price, stock_quantity, product_image FROM products WHERE is_active=1 AND stock_quantity>0 ORDER BY product_title ASC");
include 'sidenav.php';
include 'topheader.php';
?>
<div class="content">
  <div class="container-fluid">
    <?php if ($message !== ''): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <div class="row">
      <div class="col-xl-7 col-lg-8">
        <div class="card">
          <div class="card-header card-header-success">
            <h4 class="card-title mb-0"><i class="material-icons mr-1">point_of_sale</i> In-Person Sale</h4>
            <p class="card-category mb-0">Record a customer purchase made at the shop</p>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="form-group">
                <label>Product *</label>
                <select name="product_id" class="form-control" required>
                  <option value="">Choose a product</option>
                  <?php if ($products_result): while ($product = mysqli_fetch_assoc($products_result)): ?>
                  <option value="<?php echo (int)$product['product_id']; ?>"><?php echo htmlspecialchars($product['product_title']); ?> — <?php echo htmlspecialchars(format_store_price($product['product_price'], $con)); ?> — Stock: <?php echo (int)$product['stock_quantity']; ?></option>
                  <?php endwhile; endif; ?>
                </select>
              </div>
              <div class="form-row">
                <div class="form-group col-md-4"><label>Quantity *</label><input type="number" name="quantity" class="form-control" min="1" value="1" required></div>
                <div class="form-group col-md-8"><label>Payment Method *</label><select name="payment_method" class="form-control" required><option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option></select></div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" value="Walk-in Customer" maxlength="255"></div>
                <div class="form-group col-md-6"><label>Customer Phone</label><input type="text" name="customer_phone" class="form-control" maxlength="30"></div>
              </div>
              <button type="submit" name="complete_sale" class="btn btn-success"><i class="material-icons mr-1">payments</i> Complete Sale</button>
              <a href="orders.php" class="btn btn-light">View Sales</a>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-5 col-lg-4">
        <div class="card bg-dark text-white">
          <div class="card-body">
            <h5><i class="material-icons mr-1">storefront</i> In-Store Checkout</h5>
            <p class="mb-2">This creates a completed, paid delivered sale and immediately reduces inventory.</p>
            <ul class="mb-0"><li>Select the product and quantity</li><li>Choose the payment received</li><li>Complete the sale to print or record the receipt later</li></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>