<?php
// categories.php
session_start();
include("../../db.php");
include "sidenav.php";
include "topheader.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        addCategory($con);
    } elseif (isset($_POST['update_category'])) {
        updateCategory($con);
    } elseif (isset($_POST['delete_category'])) {
        deleteCategory($con);
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $category_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'delete':
            deleteCategory($con, $category_id);
            break;
        case 'toggle_status':
            toggleCategoryStatus($con, $category_id);
            break;
    }
}

function addCategory($con) {
    $cat_title = mysqli_real_escape_string($con, trim($_POST['cat_title']));
    $cat_description = mysqli_real_escape_string($con, trim($_POST['cat_description']));
    $parent_id = mysqli_real_escape_string($con, $_POST['parent_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($cat_title)) {
        $_SESSION['error'] = "Category title is required.";
        return;
    }
    
    // Check if category already exists
    $check_query = "SELECT * FROM categories WHERE cat_title = '$cat_title'";
    $result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Category already exists.";
        return;
    }
    
    $query = "INSERT INTO categories (cat_title, cat_description, parent_id, is_active) 
              VALUES ('$cat_title', '$cat_description', '$parent_id', '$is_active')";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Category added successfully!";
    } else {
        $_SESSION['error'] = "Error adding category: " . mysqli_error($con);
    }
}

function updateCategory($con) {
    $cat_id = mysqli_real_escape_string($con, $_POST['cat_id']);
    $cat_title = mysqli_real_escape_string($con, trim($_POST['cat_title']));
    $cat_description = mysqli_real_escape_string($con, trim($_POST['cat_description']));
    $parent_id = mysqli_real_escape_string($con, $_POST['parent_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $query = "UPDATE categories SET 
              cat_title = '$cat_title',
              cat_description = '$cat_description',
              parent_id = '$parent_id',
              is_active = '$is_active'
              WHERE cat_id = '$cat_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Category updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating category: " . mysqli_error($con);
    }
}

function deleteCategory($con, $category_id = null) {
    if (!$category_id) {
        $category_id = mysqli_real_escape_string($con, $_POST['category_id']);
    }
    
    // Check if category has products
    $check_products = "SELECT COUNT(*) as product_count FROM products WHERE product_cat = '$category_id'";
    $result = mysqli_query($con, $check_products);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['product_count'] > 0) {
        $_SESSION['error'] = "Cannot delete category. It has products associated with it.";
        return;
    }
    
    $query = "DELETE FROM categories WHERE cat_id = '$category_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting category: " . mysqli_error($con);
    }
}

function toggleCategoryStatus($con, $category_id) {
    $query = "UPDATE categories SET is_active = NOT is_active WHERE cat_id = '$category_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Category status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating category status: " . mysqli_error($con);
    }
}
?>

<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-container bg-success rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">category</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Categories Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage product categories and subcategories</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addCategoryModal">
                        <i class="material-icons mr-2">add</i>Add Category
                    </button>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">check_circle</i>
            <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="material-icons mr-2">error</i>
            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Categories Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">category</i>
                        </div>
                        <p class="card-category">Total Categories</p>
                        <h3 class="card-title">
                            <?php
                            $total_query = "SELECT COUNT(*) as total FROM categories";
                            $total_result = mysqli_query($con, $total_query);
                            $total_data = mysqli_fetch_assoc($total_result);
                            echo $total_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <p class="card-category">Active Categories</p>
                        <h3 class="card-title">
                            <?php
                            $active_query = "SELECT COUNT(*) as total FROM categories WHERE is_active = 1";
                            $active_result = mysqli_query($con, $active_query);
                            $active_data = mysqli_fetch_assoc($active_result);
                            echo $active_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">inventory</i>
                        </div>
                        <p class="card-category">Total Products</p>
                        <h3 class="card-title">
                            <?php
                            $products_query = "SELECT COUNT(*) as total FROM products";
                            $products_result = mysqli_query($con, $products_query);
                            $products_data = mysqli_fetch_assoc($products_result);
                            echo $products_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-primary card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">layers</i>
                        </div>
                        <p class="card-category">Main Categories</p>
                        <h3 class="card-title">
                            <?php
                            $main_query = "SELECT COUNT(*) as total FROM categories WHERE parent_id IS NULL OR parent_id = 0";
                            $main_result = mysqli_query($con, $main_query);
                            $main_data = mysqli_fetch_assoc($main_result);
                            echo $main_data['total'];
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Categories List -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">list</i>
                            All Categories
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="categoriesTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Parent</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT c.*, p.cat_title as parent_name, 
                                             (SELECT COUNT(*) FROM products WHERE product_cat = c.cat_id) as product_count
                                             FROM categories c 
                                             LEFT JOIN categories p ON c.parent_id = p.cat_id 
                                             ORDER BY c.parent_id, c.cat_title";
                                    $result = mysqli_query($con, $query);
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while($category = mysqli_fetch_assoc($result)):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $category['cat_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['cat_title']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($category['cat_description'] ?: 'No description'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $category['product_count']; ?> products</span>
                                        </td>
                                        <td>
                                            <?php if ($category['parent_name']): ?>
                                                <span class="badge badge-light"><?php echo htmlspecialchars($category['parent_name']); ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Main Category</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $category['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#editCategoryModal"
                                                        data-category-id="<?php echo $category['cat_id']; ?>"
                                                        data-category-title="<?php echo htmlspecialchars($category['cat_title']); ?>"
                                                        data-category-description="<?php echo htmlspecialchars($category['cat_description']); ?>"
                                                        data-category-parent="<?php echo $category['parent_id']; ?>"
                                                        data-category-active="<?php echo $category['is_active']; ?>">
                                                    <i class="material-icons" style="font-size:16px;">edit</i>
                                                </button>
                                                <a href="categories.php?action=toggle_status&id=<?php echo $category['cat_id']; ?>" 
                                                   class="btn btn-outline-<?php echo $category['is_active'] ? 'warning' : 'success'; ?>">
                                                    <i class="material-icons" style="font-size:16px;"><?php echo $category['is_active'] ? 'pause' : 'play_arrow'; ?></i>
                                                </a>
                                                <a href="categories.php?action=delete&id=<?php echo $category['cat_id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="material-icons" style="font-size:16px;">delete</i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center py-4">No categories found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">flash_on</i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" data-toggle="modal" data-target="#addCategoryModal">
                                <i class="material-icons mr-2">add</i>Add New Category
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportCategories()">
                                <i class="material-icons mr-2">download</i>Export Categories
                            </button>
                            <button class="btn btn-outline-info" onclick="importCategories()">
                                <i class="material-icons mr-2">upload</i>Import Categories
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category Hierarchy -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">account_tree</i>
                            Category Hierarchy
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="categoryTree">
                            <?php
                            function buildCategoryTree($con, $parent_id = null, $level = 0) {
                                $query = "SELECT * FROM categories WHERE parent_id " . ($parent_id ? "= '$parent_id'" : "IS NULL OR parent_id = 0") . " ORDER BY cat_title";
                                $result = mysqli_query($con, $query);
                                
                                if (mysqli_num_rows($result) > 0) {
                                    echo '<ul class="category-tree">';
                                    while($category = mysqli_fetch_assoc($result)) {
                                        $has_children = mysqli_num_rows(mysqli_query($con, "SELECT * FROM categories WHERE parent_id = '{$category['cat_id']}'")) > 0;
                                        echo '<li>';
                                        echo '<span class="' . ($has_children ? 'category-with-children' : 'category-leaf') . '">';
                                        echo str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                                        echo '<i class="material-icons" style="font-size:16px;">' . ($has_children ? 'folder' : 'category') . '</i> ';
                                        echo htmlspecialchars($category['cat_title']);
                                        echo '</span>';
                                        if ($has_children) {
                                            buildCategoryTree($con, $category['cat_id'], $level + 1);
                                        }
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                }
                            }
                            buildCategoryTree($con);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="cat_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="cat_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- No Parent (Main Category) --</option>
                            <?php
                            $parent_query = "SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY cat_title";
                            $parent_result = mysqli_query($con, $parent_query);
                            while($parent = mysqli_fetch_assoc($parent_result)) {
                                echo "<option value='{$parent['cat_id']}'>{$parent['cat_title']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive" checked>
                        <label class="form-check-label" for="addIsActive">
                            Active Category
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="cat_id" id="edit_cat_id">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="cat_title" class="form-control" id="edit_cat_title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="cat_description" class="form-control" rows="3" id="edit_cat_description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Parent Category</label>
                        <select name="parent_id" class="form-control" id="edit_parent_id">
                            <option value="">-- No Parent (Main Category) --</option>
                            <?php
                            $parent_query = "SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY cat_title";
                            $parent_result = mysqli_query($con, $parent_query);
                            while($parent = mysqli_fetch_assoc($parent_result)) {
                                echo "<option value='{$parent['cat_id']}'>{$parent['cat_title']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">
                            Active Category
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function exportCategories() {
    alert('Export categories feature would be implemented here');
}

function importCategories() {
    alert('Import categories feature would be implemented here');
}

// Edit category modal handler
$('#editCategoryModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var categoryId = button.data('category-id');
    var categoryTitle = button.data('category-title');
    var categoryDescription = button.data('category-description');
    var categoryParent = button.data('category-parent');
    var categoryActive = button.data('category-active');
    
    var modal = $(this);
    modal.find('#edit_cat_id').val(categoryId);
    modal.find('#edit_cat_title').val(categoryTitle);
    modal.find('#edit_cat_description').val(categoryDescription || '');
    modal.find('#edit_parent_id').val(categoryParent || '');
    modal.find('#edit_is_active').prop('checked', categoryActive == 1);
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search categories...';
    searchInput.className = 'form-control form-control-sm';
    searchInput.style.marginBottom = '15px';
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#categoriesTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    document.querySelector('.card-header').appendChild(searchInput);
});

// Category tree styling
document.addEventListener('DOMContentLoaded', function() {
    const categoryTree = document.querySelector('.category-tree');
    if (categoryTree) {
        categoryTree.style.listStyle = 'none';
        categoryTree.style.paddingLeft = '0';
        
        const items = categoryTree.querySelectorAll('li');
        items.forEach(item => {
            item.style.marginBottom = '5px';
        });
        
        const categoryLeaves = categoryTree.querySelectorAll('.category-leaf');
        categoryLeaves.forEach(leaf => {
            leaf.style.color = '#666';
        });
    }
});
</script>

<style>
.category-tree ul {
    list-style: none;
    padding-left: 20px;
}
.category-tree li {
    margin: 5px 0;
}
.category-with-children {
    font-weight: bold;
    color: #333;
}
.category-leaf {
    color: #666;
}
</style>

<?php
include "footer.php";
?>