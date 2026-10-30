<?php
include_once __DIR__ . '/../../currency.php';
session_start();
include("../../db.php");
error_reporting(0);

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Handle delete action
if(isset($_GET['action']) && $_GET['action']!="" && $_GET['action']=='delete') {
    $product_id = mysqli_real_escape_string($con, $_GET['product_id']);
    
    // Get product image for deletion
    $result = mysqli_query($con, "SELECT product_image FROM products WHERE product_id='$product_id'") 
             or die("Query failed: " . mysqli_error($con));

    if(mysqli_num_rows($result) > 0) {
        list($picture) = mysqli_fetch_array($result);
        $path = "../product_images/$picture";

        // Delete image file if exists
        if(file_exists($path)) {
            unlink($path);
        }
        
        // Delete product from database
        mysqli_query($con, "DELETE FROM products WHERE product_id='$product_id'") 
        or die("Delete failed: " . mysqli_error($con));
        
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Product not found!";
    }
    
    header("location: productlist.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 1;
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

if($page < 1) $page = 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build query with filters
$where_conditions = ["1=1"];
if($category_filter > 0) {
    $where_conditions[] = "product_cat = '$category_filter'";
}
if(!empty($search_term)) {
    $where_conditions[] = "(product_title LIKE '%$search_term%' OR product_desc LIKE '%$search_term%')";
}
$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products WHERE $where_clause";
$count_result = mysqli_query($con, $count_query);
$total_data = mysqli_fetch_assoc($count_result);
$total_products = $total_data['total'];
$total_pages = ceil($total_products / $items_per_page);

// Get categories for filter
$categories_result = mysqli_query($con, "SELECT * FROM categories");

include "sidenav.php";
include "topheader.php";
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="material-icons">error</i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="col-md-14">
            <div class="card">
                <div class="card-header card-header-primary">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">
                                <i class="material-icons mr-2">inventory</i>
                                Products Management
                            </h4>
                            <p class="card-category">Total Products: <?php echo $total_products; ?></p>
                        </div>
                        <div class="col-md-6 text-right">
                            <?php if (($_SESSION['role'] ?? '') !== 'e'): ?>
                            <a class="btn btn-white btn-sm" href="products_list.php?open_add=1">
                                <i class="material-icons mr-1">add</i> Add New Product
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Filters Section -->
                <div class="card-body border-bottom">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <select name="category" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="0">All Categories</option>
                                        <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $cat['cat_id']; ?>" 
                                                <?php echo $category_filter == $cat['cat_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['cat_title']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="search" class="form-control" placeholder="Search products..." 
                                               value="<?php echo htmlspecialchars($search_term); ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="material-icons">search</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php if(!empty($search_term) || $category_filter != 1): ?>
                                <a href="productlist.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">
                                Showing <?php echo min($items_per_page, $total_products - $offset); ?> of <?php echo $total_products; ?> products
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="text-primary">
                                <tr>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $products_query = "SELECT p.*, c.cat_title 
                                                 FROM products p 
                                                 LEFT JOIN categories c ON p.product_cat = c.cat_id 
                                                 WHERE $where_clause 
                                                 ORDER BY p.product_id DESC 
                                                 LIMIT $offset, $items_per_page";
                                $result = mysqli_query($con, $products_query);

                                if(mysqli_num_rows($result) > 0) {
                                    while($product = mysqli_fetch_assoc($result)) {
                                        $stock_status = $product['qty'] > 0 ? 'In Stock' : 'Out of Stock';
                                        $stock_class = $product['qty'] > 0 ? 'success' : 'danger';
                                        $status_class = $product['product_status'] == 1 ? 'success' : 'secondary';
                                        $status_text = $product['product_status'] == 1 ? 'Active' : 'Inactive';
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-image-container">
                                            <img src="../product_images/<?php echo $product['product_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                                                 class="product-image"
                                                 onerror="this.src='../assets/img/default-product.png'">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <strong><?php echo htmlspecialchars($product['product_title']); ?></strong>
                                            <?php if(!empty($product['product_desc'])): ?>
                                            <small class="text-muted d-block">
                                                <?php echo substr(strip_tags($product['product_desc']), 0, 50); ?>...
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light"><?php echo $product['cat_title']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars(format_store_price($product['product_price'], $con)); ?></strong>
                                        <?php if($product['old_price'] > 0): ?>
                                        <br><small class="text-muted text-decoration-line-through">
                                            $<?php echo number_format($product['old_price'], 2); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $stock_class; ?>">
                                            <?php echo $stock_status; ?>
                                        </span>
                                        <small class="d-block text-muted">Qty: <?php echo $product['qty']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="products_list.php?open_edit=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Edit Product">
                                                <i class="material-icons">edit</i>
                                            </a>
                                            <a href="productlist.php?action=delete&product_id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Delete Product"
                                               onclick="return confirm('Are you sure you want to delete <?php echo addslashes($product['product_title']); ?>? This action cannot be undone.')">
                                                <i class="material-icons">delete</i>
                                            </a>
                                            <a href="../product_details.php?product_id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-outline-info" 
                                               target="_blank"
                                               title="View Product">
                                                <i class="material-icons">visibility</i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4">';
                                    if(!empty($search_term)) {
                                        echo 'No products found matching "' . htmlspecialchars($search_term) . '"';
                                    } elseif (($_SESSION['role'] ?? '') !== 'e') {
                                        echo 'No products found. <a href="products_list.php?open_add=1" class="btn btn-sm btn-primary ml-2">Add New Product</a>';
                                    } else {
                                        echo 'No products found.';
                                    }
                                    echo '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <nav aria-label="Product pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                    <span class="sr-only">Previous</span>
                                </a>
                            </li>
                            
                            <?php
                            // Show page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                    <span class="sr-only">Next</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-image-container {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #f0f0f0;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    max-width: 250px;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.alert {
    border: none;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
}

.alert-danger {
    border-left-color: #dc3545;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}
</style>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert').alert('close');
}, 5000);

// Add confirmation for delete actions
function confirmDelete(productName) {
    return confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.');
}

// Quick status toggle (you can implement this with AJAX)
function toggleProductStatus(productId, currentStatus) {
    if(confirm('Are you sure you want to ' + (currentStatus == 1 ? 'deactivate' : 'activate') + ' this product?')) {
        // AJAX call to update status
        fetch('update_product_status.php?product_id=' + productId + '&status=' + (currentStatus == 1 ? 0 : 1))
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error updating product status');
                }
            });
    }
}
</script>

<?php
include "footer.php";
?>