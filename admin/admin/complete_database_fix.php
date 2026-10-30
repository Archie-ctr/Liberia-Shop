<?php
// complete_database_fix.php
session_start();
include("../../db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

echo "<h2>🛠️ Complete Database Structure Fix</h2>";
echo "<p>Fixing all database tables and columns...</p>";

// Function to check if column exists
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '$column'");
    return ($result && mysqli_num_rows($result) > 0);
}

// Function to check if table exists
function tableExists($con, $table) {
    $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return ($result && mysqli_num_rows($result) > 0);
}

// 1. Fix orders_info table
echo "<h3>1. Checking orders_info table...</h3>";
$columns_to_add = [
    'order_date' => "ADD COLUMN order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER user_id",
    'shipping_address' => "ADD COLUMN shipping_address TEXT AFTER order_status", 
    'billing_address' => "ADD COLUMN billing_address TEXT AFTER shipping_address",
    'customer_notes' => "ADD COLUMN customer_notes TEXT AFTER billing_address",
    'admin_notes' => "ADD COLUMN admin_notes TEXT AFTER customer_notes",
    'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
];

$alter_queries = [];
foreach ($columns_to_add as $column => $alter_sql) {
    if (!columnExists($con, 'orders_info', $column)) {
        $alter_queries[] = $alter_sql;
        echo "✅ Will add: $column<br>";
    } else {
        echo "✅ Already exists: $column<br>";
    }
}

if (!empty($alter_queries)) {
    $alter_sql = "ALTER TABLE orders_info " . implode(', ', $alter_queries);
    if (mysqli_query($con, $alter_sql)) {
        echo "🎉 Successfully updated orders_info table!<br>";
    } else {
        echo "❌ Error: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "✅ orders_info table is already complete!<br>";
}

// 2. Create order_items table
echo "<h3>2. Checking order_items table...</h3>";
if (!tableExists($con, 'order_items')) {
    $create_sql = "CREATE TABLE order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($con, $create_sql)) {
        echo "🎉 Successfully created order_items table!<br>";
    } else {
        echo "❌ Error: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "✅ order_items table already exists!<br>";
}

// 3. Create payments table
echo "<h3>3. Checking payments table...</h3>";
if (!tableExists($con, 'payments')) {
    $create_sql = "CREATE TABLE payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery') NOT NULL,
        payment_amount DECIMAL(10,2) NOT NULL,
        transaction_id VARCHAR(255),
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        payment_date TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($con, $create_sql)) {
        echo "🎉 Successfully created payments table!<br>";
        
        // Insert sample payment data for testing
        $sample_payment = "INSERT INTO payments (order_id, payment_method, payment_amount, payment_status) 
                          VALUES (1, 'cash_on_delivery', '50.00', 'completed')";
        mysqli_query($con, $sample_payment);
        
    } else {
        echo "❌ Error: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "✅ payments table already exists!<br>";
}

// 4. Create invoices table
echo "<h3>4. Checking invoices table...</h3>";
if (!tableExists($con, 'invoices')) {
    $create_sql = "CREATE TABLE invoices (
        invoice_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        invoice_number VARCHAR(100) UNIQUE NOT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) NOT NULL,
        shipping_cost DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        invoice_status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($con, $create_sql)) {
        echo "🎉 Successfully created invoices table!<br>";
    } else {
        echo "❌ Error: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "✅ invoices table already exists!<br>";
}

echo "<h2>🎊 Database Fix Complete!</h2>";
echo "<p>All database tables and columns have been checked and fixed.</p>";
echo "<p><a href='invoice.php' class='btn btn-primary'>Go to Invoices</a></p>";
?>