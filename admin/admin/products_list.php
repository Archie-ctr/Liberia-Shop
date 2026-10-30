<?php
session_start();
include("../../db.php");
include_once __DIR__ . '/../../currency.php';
error_reporting(0);

// Existing delete functionality
if(isset($_GET['action']) && $_GET['action']!="" && $_GET['action']=='delete')
{
$product_id=$_GET['product_id'];
///////picture delete/////////
$result=mysqli_query($con,"select product_image from products where product_id='$product_id'")
or die("query is incorrect...");

list($picture)=mysqli_fetch_array($result);
$path="../product_images/$picture";

if(file_exists($path)==true)
{
  unlink($path);
}
else
{}
/*this is delet query*/
mysqli_query($con,"delete from products where product_id='$product_id'")or die("query is incorrect...");
}

///pagination

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if($page=="" || $page=="1")
{
$page1=0;	
}
else
{
$page1=($page-1)*12;	
} 
include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
        
        <!-- Success/Error Messages -->
        <?php if(isset($_GET['delete_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            Product deleted successfully!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['product_added'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            Product added successfully.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['product_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            Product updated successfully.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="material-icons">search</i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control" placeholder="Search products..." id="searchInput">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <?php
                                    $categories = mysqli_query($con, "SELECT * FROM categories");
                                    while($cat = mysqli_fetch_array($categories)) {
                                        echo "<option value='{$cat['cat_id']}'>{$cat['cat_title']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php
                        $total_products = mysqli_query($con, "SELECT COUNT(*) as total FROM products");
                        $total_data = mysqli_fetch_assoc($total_products);
                        ?>
                        <h4 class="card-title"><?php echo $total_data['total']; ?></h4>
                        <p class="card-category">Total Products</p>
                    </div>
                </div>
            </div>
        </div>
        
         <div class="col-md-14">
            <div class="card ">
              <div class="card-header card-header-primary">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="card-title">Products Management</h4>
                        <p class="card-category">Manage your product inventory</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if ($_SESSION['role'] != 'e'): ?>
                        <button class="btn btn-white btn-round" type="button" data-toggle="modal" data-target="#addProductModal">
                            <i class="material-icons">add</i> Add New Product
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-white btn-round" onclick="refreshProducts()">
                            <i class="material-icons">refresh</i> Refresh
                        </button>
                    </div>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover" id="productsTable">
                    <thead class="text-primary">
                      <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                        $result = mysqli_query($con, "
                            SELECT p.product_id, p.product_image, p.product_title, p.product_price, p.product_cat, c.cat_title 
                            FROM products p 
                            LEFT JOIN categories c ON p.product_cat = c.cat_id 
                            LIMIT $page1, 12
                        ") or die("query 1 incorrect.....");

                        while($row = mysqli_fetch_array($result))
                        {
                            $product_id = $row['product_id'];
                            $image = $row['product_image'];
                            $product_name = $row['product_title'];
                            $price = $row['product_price'];
                            $category = $row['cat_title'];
                            $substr = substr($product_name, 0, 36);
                            
                            echo "
                            <tr>
                                <td>
                                    <div class='product-image-container'>
                                        <img src='../../product_images/$image' class='product-image' 
                                             onerror=\"this.src='assets/img/default-product.png'\">
                                    </div>
                                </td>
                                <td>
                                    <div class='product-info'>
                                        <div class='product-name'>$substr</div>
                                        <small class='text-muted'>ID: $product_id</small>
                                    </div>
                                </td>
                                <td>
                                    <span class='badge badge-info'>$category</span>
                                </td>
                                <td>
                                    <div class='price-container'>
                                        <span class='price'>".format_store_price($price, $con)."</span>
                                    </div>
                                </td>
                                <td>
                                    <span class='badge badge-success'>Active</span>
                                </td>
                                <td>
                                    <div class='btn-group' role='group'>";
                                    
                                    if ($_SESSION['role'] != 'e') {
                                        echo "
                                        <button type='button' class='btn btn-sm btn-success edit-product-btn' data-product-id='$product_id' title='Edit'>
                                            <i class='material-icons'>edit</i>
                                        </button>
                                        <button class='btn btn-sm btn-danger delete-btn' 
                                                data-product-id='$product_id' 
                                                data-product-name='$product_name'
                                                title='Delete'>
                                            <i class='material-icons'>delete</i>
                                        </button>";
                                    }
                                    
                                    echo "
                                        <button class='btn btn-sm btn-info view-btn' 
                                                data-product-id='$product_id'
                                                title='View Details'>
                                            <i class='material-icons'>visibility</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>";
                        }
                      ?>
                    </tbody>
                  </table>
                  
                  <!-- Empty State -->
                  <?php if(mysqli_num_rows($result) == 0): ?>
                  <div class="text-center py-5">
                    <i class="material-icons" style="font-size: 64px; color: #ccc;">inventory_2</i>
                    <h4 class="text-muted mt-3">No products found</h4>
                    <p class="text-muted">Get started by adding your first product</p>
                    <?php if ($_SESSION['role'] != 'e'): ?>
                    <button type="button" class="btn btn-primary mt-2" data-toggle="modal" data-target="#addProductModal">
                        <i class="material-icons">add</i> Add New Product
                    </button>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <nav aria-label="Products pagination">
              <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                  <a class="page-link" href="products_list.php?page=<?php echo max(1, $page-1); ?>" aria-label="Previous">
                    <i class="material-icons">chevron_left</i>
                    <span class="sr-only">Previous</span>
                  </a>
                </li>
                 <?php 
                //counting paging
                $paging = mysqli_query($con, "SELECT product_id FROM products");
                $count = mysqli_num_rows($paging);
                $total_pages = max(1, (int)ceil($count/12));
                
                // Show limited pagination
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for($b = $start_page; $b <= $end_page; $b++): 
                ?>
                <li class="page-item <?php echo ($b == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="products_list.php?page=<?php echo $b; ?>">
                        <?php echo $b; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                  <a class="page-link" href="products_list.php?page=<?php echo min($total_pages, $page+1); ?>" aria-label="Next">
                    <i class="material-icons">chevron_right</i>
                    <span class="sr-only">Next</span>
                  </a>
                </li>
              </ul>
              <div class="text-center text-muted mt-2">
                Page <?php echo $page ?: 1; ?> of <?php echo $total_pages; ?> • Total <?php echo $count; ?> products
              </div>
            </nav>
          </div>
        </div>
      </div>

      <!-- Add Product Modal -->
      <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="addProductModalLabel">Add product</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body p-0">
              <iframe src="add_products.php?embedded=1" title="Add product form"
                      style="display:block;width:100%;height:min(64vh,560px);border:0;"></iframe>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Product Modal -->
      <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editProductModalLabel">Edit product</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body p-0">
              <iframe id="editProductFrame" title="Edit product form"
                      style="display:block;width:100%;height:min(64vh,560px);border:0;"></iframe>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete <strong id="productName"></strong>?</p>
              <p class="text-danger"><small>This action cannot be undone and the product image will also be deleted.</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <a href="#" class="btn btn-danger" id="confirmDelete">Delete Product</a>
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
    max-width: 200px;
}

.product-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.price-container {
    font-weight: 600;
    color: #4caf50;
}

.btn-group .btn {
    margin: 0 2px;
    border-radius: 4px;
}

.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.pagination .page-item.active .page-link {
    background-color: #9c27b0;
    border-color: #9c27b0;
}

.page-link {
    color: #6c757d;
}

.page-link:hover {
    color: #9c27b0;
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.form-control:focus + .input-group-text {
    border-color: #9c27b0;
}
</style>

<script>
// Delete confirmation modal
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = window.jQuery('#deleteModal');
    const productNameElement = document.getElementById('productName');
    const confirmDeleteButton = document.getElementById('confirmDelete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            productNameElement.textContent = productName;
            confirmDeleteButton.href = `products_list.php?product_id=${productId}&action=delete`;
            
            deleteModal.modal('show');
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                const productName = row.querySelector('.product-name').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Auto-hide success message
    setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);

    var addProductModal = document.getElementById('addProductModal');
    if (addProductModal) {
        addProductModal.addEventListener('hidden.bs.modal', function() {
            var iframe = addProductModal.querySelector('iframe');
            iframe.src = 'add_products.php?embedded=1';
        });
    }

    if (new URLSearchParams(window.location.search).get('open_add') === '1') {
        window.jQuery('#addProductModal').modal('show');
    }

    const editModal = window.jQuery('#editProductModal');
    const editFrame = document.getElementById('editProductFrame');
    document.querySelectorAll('.edit-product-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            editFrame.src = `edit_product.php?embedded=1&product_id=${encodeURIComponent(productId)}`;
            editModal.modal('show');
        });
    });

    const openEditId = new URLSearchParams(window.location.search).get('open_edit');
    if (openEditId) {
        editFrame.src = `edit_product.php?embedded=1&product_id=${encodeURIComponent(openEditId)}`;
        editModal.modal('show');
    }
});

window.addEventListener('message', function(event) {
    if (event.origin !== window.location.origin || !event.data) {
        return;
    }
    if (event.data.type === 'product-created') {
        window.location.href = 'products_list.php?product_added=1';
    } else if (event.data.type === 'product-updated') {
        window.location.href = 'products_list.php?product_updated=1';
    }
});

function refreshProducts() {
    window.location.reload();
}

// View product details (placeholder function)
function viewProductDetails(productId) {
    alert('View details for product ID: ' + productId);
    // Implement actual view functionality here
}
</script>

<?php
include "footer.php";
?>