<?php
// brands.php
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
    if (isset($_POST['add_brand'])) {
        addBrand($con);
    } elseif (isset($_POST['update_brand'])) {
        updateBrand($con);
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $brand_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'delete':
            deleteBrand($con, $brand_id);
            break;
        case 'toggle_status':
            toggleBrandStatus($con, $brand_id);
            break;
    }
}

function addBrand($con) {
    $brand_title = mysqli_real_escape_string($con, trim($_POST['brand_title']));
    $brand_description = mysqli_real_escape_string($con, trim($_POST['brand_description']));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($brand_title)) {
        $_SESSION['error'] = "Brand title is required.";
        return;
    }
    
    // Check if brand already exists
    $check_query = "SELECT * FROM brands WHERE brand_title = '$brand_title'";
    $result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Brand already exists.";
        return;
    }
    
    $query = "INSERT INTO brands (brand_title, brand_description, is_active) 
              VALUES ('$brand_title', '$brand_description', '$is_active')";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Brand added successfully!";
    } else {
        $_SESSION['error'] = "Error adding brand: " . mysqli_error($con);
    }
}

function updateBrand($con) {
    $brand_id = mysqli_real_escape_string($con, $_POST['brand_id']);
    $brand_title = mysqli_real_escape_string($con, trim($_POST['brand_title']));
    $brand_description = mysqli_real_escape_string($con, trim($_POST['brand_description']));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $query = "UPDATE brands SET 
              brand_title = '$brand_title',
              brand_description = '$brand_description',
              is_active = '$is_active'
              WHERE brand_id = '$brand_id'";
    
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Brand updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating brand: " . mysqli_error($con);
    }
}

function deleteBrand($con, $brand_id) {
    // Check if brand has products
    $check_products = "SELECT COUNT(*) as product_count FROM products WHERE product_brand = '$brand_id'";
    $result = mysqli_query($con, $check_products);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['product_count'] > 0) {
        $_SESSION['error'] = "Cannot delete brand. It has products associated with it.";
        return;
    }
    
    $query = "DELETE FROM brands WHERE brand_id = '$brand_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Brand deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting brand: " . mysqli_error($con);
    }
}

function toggleBrandStatus($con, $brand_id) {
    $query = "UPDATE brands SET is_active = NOT is_active WHERE brand_id = '$brand_id'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Brand status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating brand status: " . mysqli_error($con);
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
                        <div class="header-icon-container bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">branding_watermark</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Brands Management</h1>
                            <p class="page-subtitle text-muted mb-0">Manage product brands and manufacturers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-warning" data-toggle="modal" data-target="#addBrandModal">
                        <i class="material-icons mr-2">add</i>Add Brand
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

        <!-- Brands Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">branding_watermark</i>
                        </div>
                        <p class="card-category">Total Brands</p>
                        <h3 class="card-title">
                            <?php
                            $total_query = "SELECT COUNT(*) as total FROM brands";
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
                        <p class="card-category">Active Brands</p>
                        <h3 class="card-title">
                            <?php
                            $active_query = "SELECT COUNT(*) as total FROM brands WHERE is_active = 1";
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
                        <p class="card-category">Branded Products</p>
                        <h3 class="card-title">
                            <?php
                            $products_query = "SELECT COUNT(*) as total FROM products WHERE product_brand IS NOT NULL";
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
                            <i class="material-icons">star</i>
                        </div>
                        <p class="card-category">Top Brand</p>
                        <h3 class="card-title">Apple</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Brands List -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary">list</i>
                            All Brands
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="brandsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Brand Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT b.*, 
                                             (SELECT COUNT(*) FROM products WHERE product_brand = b.brand_id) as product_count
                                             FROM brands b 
                                             ORDER BY b.brand_title";
                                    $result = mysqli_query($con, $query);
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while($brand = mysqli_fetch_assoc($result)):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $brand['brand_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($brand['brand_title']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($brand['brand_description'] ?: 'No description'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $brand['product_count']; ?> products</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $brand['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#editBrandModal"
                                                        data-brand-id="<?php echo $brand['brand_id']; ?>"
                                                        data-brand-title="<?php echo htmlspecialchars($brand['brand_title']); ?>"
                                                        data-brand-description="<?php echo htmlspecialchars($brand['brand_description']); ?>"
                                                        data-brand-active="<?php echo $brand['is_active']; ?>">
                                                    <i class="material-icons" style="font-size:16px;">edit</i>
                                                </button>
                                                <a href="brands.php?action=toggle_status&id=<?php echo $brand['brand_id']; ?>" 
                                                   class="btn btn-outline-<?php echo $brand['is_active'] ? 'warning' : 'success'; ?>">
                                                    <i class="material-icons" style="font-size:16px;"><?php echo $brand['is_active'] ? 'pause' : 'play_arrow'; ?></i>
                                                </a>
                                                <a href="brands.php?action=delete&id=<?php echo $brand['brand_id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this brand?')">
                                                    <i class="material-icons" style="font-size:16px;">delete</i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center py-4">No brands found.</td></tr>';
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
                            <button class="btn btn-warning" data-toggle="modal" data-target="#addBrandModal">
                                <i class="material-icons mr-2">add</i>Add New Brand
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportBrands()">
                                <i class="material-icons mr-2">download</i>Export Brands
                            </button>
                            <button class="btn btn-outline-info" onclick="importBrands()">
                                <i class="material-icons mr-2">upload</i>Import Brands
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Popular Brands -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2">trending_up</i>
                            Popular Brands
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $popular_query = "SELECT b.brand_title, COUNT(p.product_id) as product_count 
                                         FROM brands b 
                                         LEFT JOIN products p ON b.brand_id = p.product_brand 
                                         GROUP BY b.brand_id 
                                         ORDER BY product_count DESC 
                                         LIMIT 5";
                        $popular_result = mysqli_query($con, $popular_query);
                        
                        if ($popular_result && mysqli_num_rows($popular_result) > 0) {
                            while($popular = mysqli_fetch_assoc($popular_result)) {
                                echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                                echo '<span>' . htmlspecialchars($popular['brand_title']) . '</span>';
                                echo '<span class="badge badge-primary">' . $popular['product_count'] . ' products</span>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="text-muted">No brand data available.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Brand</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Brand Name *</label>
                        <input type="text" name="brand_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="brand_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive" checked>
                        <label class="form-check-label" for="addIsActive">
                            Active Brand
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_brand" class="btn btn-warning">Add Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Brand</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <div class="form-group">
                        <label>Brand Name *</label>
                        <input type="text" name="brand_title" class="form-control" id="edit_brand_title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="brand_description" class="form-control" rows="3" id="edit_brand_description"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">
                            Active Brand
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_brand" class="btn btn-primary">Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function exportBrands() {
    alert('Export brands feature would be implemented here');
}

function importBrands() {
    alert('Import brands feature would be implemented here');
}

// Edit brand modal handler
$('#editBrandModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var brandId = button.data('brand-id');
    var brandTitle = button.data('brand-title');
    var brandDescription = button.data('brand-description');
    var brandActive = button.data('brand-active');
    
    var modal = $(this);
    modal.find('#edit_brand_id').val(brandId);
    modal.find('#edit_brand_title').val(brandTitle);
    modal.find('#edit_brand_description').val(brandDescription || '');
    modal.find('#edit_is_active').prop('checked', brandActive == 1);
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search brands...';
    searchInput.className = 'form-control form-control-sm';
    searchInput.style.marginBottom = '15px';
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#brandsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    document.querySelector('.card-header').appendChild(searchInput);
});
</script>

<?php
include "footer.php";
?>