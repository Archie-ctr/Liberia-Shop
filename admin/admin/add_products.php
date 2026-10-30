<?php
session_start();
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: ../login.php');
    exit();
}

if (($_SESSION['role'] ?? '') === 'e') {
    header('Location: products_list.php');
    exit();
}

$is_embedded = isset($_GET['embedded']) && $_GET['embedded'] === '1';
if (!$is_embedded) {
    header('Location: products_list.php?open_add=1');
    exit();
}

include("../../db.php");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_images (
    image_id INT NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_name VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (image_id),
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialize variables
$product_name = $details = $price = $c_price = $product_type = $brand = $tags = $product_colors = '';
$errors = [];
$success = '';

if(isset($_POST['btn_save'])) {
    // Sanitize and validate inputs
    $product_name = mysqli_real_escape_string($con, trim($_POST['product_name']));
    $details = mysqli_real_escape_string($con, trim($_POST['details']));
    $price = mysqli_real_escape_string($con, trim($_POST['price']));
    $c_price = mysqli_real_escape_string($con, trim($_POST['c_price'] ?? ''));
    $product_type = mysqli_real_escape_string($con, trim($_POST['product_type']));
    $brand = mysqli_real_escape_string($con, trim($_POST['brand']));
    $tags = mysqli_real_escape_string($con, trim($_POST['tags']));
    $product_colors = mysqli_real_escape_string($con, trim($_POST['product_colors'] ?? ''));

    // Validation
    if(empty($product_name)) $errors[] = "Product name is required";
    if(empty($price) || !is_numeric($price) || $price <= 0) $errors[] = "Valid price is required";
    if(!empty($c_price) && (!is_numeric($c_price) || $c_price < 0)) $errors[] = "Cost price must be a valid number";
    if(empty($product_type)) $errors[] = "Product category is required";
    if(empty($brand)) $errors[] = "Product brand is required";
    if(empty($tags)) $errors[] = "Product keywords are required";
    
    // Check if product name already exists
    $check_query = "SELECT product_title FROM products WHERE product_title = '$product_name'";
    $check_result = mysqli_query($con, $check_query);
    if(mysqli_num_rows($check_result) > 0) {
        $errors[] = "Product with this name already exists";
    }

    // Handle file upload
    $picture_name = $_FILES['picture']['name'] ?? '';
    $picture_tmp_name = $_FILES['picture']['tmp_name'] ?? '';
    $picture_size = $_FILES['picture']['size'] ?? 0;
    $pic_name = '';

    if(empty($picture_name)) {
        $errors[] = "Product image is required";
    } else {
        // Get file extension
        $file_extension = strtolower(pathinfo($picture_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        
        if(!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        } elseif($picture_size > 5000000) { // 5MB limit
            $errors[] = "Image size must be less than 5MB";
        } else {
            $pic_name = 'product_' . time() . "_" . uniqid() . "." . $file_extension;
            
            // Ensure upload directory exists
            $upload_dir = "../product_images/";
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = "Failed to create upload directory";
                }
            }
            
            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                $errors[] = "Upload directory is not writable";
            }
        }
    }

    // If no errors, proceed with database insertion
    if(empty($errors)) {
        $target_file = $upload_dir . $pic_name;
        
        if(move_uploaded_file($picture_tmp_name, $target_file)) {
            // Insert into products table
            $query = "INSERT INTO products (
                product_cat, 
                product_brand, 
                product_title, 
                product_price, 
                product_desc, 
                product_image, 
                product_keywords,
                product_colors
            ) VALUES (
                '$product_type', 
                '$brand', 
                '$product_name', 
                '$price', 
                '$details', 
                '$pic_name', 
                '$tags',
                '$product_colors'
            )";
            
            if(mysqli_query($con, $query)) {
                $product_id = mysqli_insert_id($con);
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $allowed_gallery_types=['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
                    foreach ($_FILES['gallery_images']['name'] as $index => $original_name) {
                        if ($_FILES['gallery_images']['error'][$index] !== UPLOAD_ERR_OK) continue;
                        $tmp_name=$_FILES['gallery_images']['tmp_name'][$index];
                        $mime=(string)$_FILES['gallery_images']['type'][$index];
                        $size=(int)$_FILES['gallery_images']['size'][$index];
                        if (!in_array($mime, $allowed_gallery_types, true) || $size > 5000000) continue;
                        $extension=strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                        $gallery_name='gallery_' . time() . '_' . uniqid() . '.' . $extension;
                        if (move_uploaded_file($tmp_name, '../product_images/' . $gallery_name)) {
                            $safe_gallery=mysqli_real_escape_string($con, $gallery_name);
                            mysqli_query($con, "INSERT INTO product_images (product_id,image_name,sort_order) VALUES ('$product_id','$safe_gallery','$index')");
                        }
                    }
                }
                $success = "Product added successfully! Product ID: " . $product_id;
                
                // Reset form fields
                $product_name = $details = $price = $c_price = $product_type = $brand = $tags = $product_colors = '';
                
            } else {
                $errors[] = "Database error: " . mysqli_error($con);
                // Remove uploaded file if database insert failed
                if(file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } else {
            $errors[] = "Failed to upload image. Error: " . $_FILES['picture']['error'];
        }
    }
}

// Fetch categories and brands for dropdowns
$categories_result = mysqli_query($con, "SELECT * FROM categories ORDER BY cat_title");
$brands_result = mysqli_query($con, "SELECT * FROM brands ORDER BY brand_title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add product</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="assets/css/Material+Icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/material-dashboard.css?v=2.1.0">
    <link rel="stylesheet" href="assets/css/admin-overrides.css?v=1.1">
</head>
<body class="admin-app">
<!-- End Navbar -->
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
       

        <!-- Notifications -->
        <?php if(!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="material-icons">error</i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <form action="add_products.php?embedded=1" method="post" enctype="multipart/form-data" id="productForm">
            <div class="row">
                <!-- Left Column - Product Details -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header card-header-primary">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="title">Product Information</h5>
                                <span class="badge badge-light">Step 1 of 2</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Product Basic Information -->
                            <div class="form-section">
                                <h6 class="section-title">Basic Information</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="bmd-label-floating">Product Title *</label>
                                            <input type="text" name="product_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($product_name); ?>" 
                                                   required maxlength="255" id="productName">
                                            <small class="form-text text-muted">Enter a descriptive product name (Max 255 characters)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="bmd-label-floating">Product Description *</label>
                                            <textarea name="details" class="form-control" rows="6" 
                                                      required maxlength="2000" id="productDescription"><?php echo htmlspecialchars($details); ?></textarea>
                                            <div class="d-flex justify-content-between">
                                                <small class="form-text text-muted">Describe the product features and benefits</small>
                                                <small class="form-text text-muted"><span id="charCount">0</span>/2000 characters</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div class="form-section mt-4">
                                <h6 class="section-title">Pricing Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="bmd-label-floating">Selling Price (LRD) *</label>
                                            <input type="number" name="price" class="form-control" 
                                                   value="<?php echo htmlspecialchars($price); ?>" 
                                                   step="0.01" min="0.01" required id="sellingPrice">
                                            <small class="form-text text-muted">Enter the price in Liberian dollars (LRD). Customers see converted USD and LRD using Shop Settings.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="bmd-label-floating">Cost Price (LRD)</label>
                                            <input type="number" name="c_price" class="form-control" 
                                                   value="<?php echo htmlspecialchars($c_price); ?>" 
                                                   step="0.01" min="0" id="costPrice">
                                            <small class="form-text text-muted">Your cost for this product</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Image -->
                            <div class="form-section mt-4">
                                <h6 class="section-title">Product Image *</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="image-upload-container">
                                            <div class="upload-area" id="uploadArea">
                                                <i class="material-icons">cloud_upload</i>
                                                <p>Click to upload product image</p>
                                                <small>Supported formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
                                                <input type="file" name="picture" id="picture" 
                                                       accept=".jpeg,.jpg,.png,.gif,.webp" required>
                                            </div>
                                            <div class="image-preview mt-3" id="imagePreview" style="display: none;">
                                                <div class="preview-container">
                                                    <img id="previewImage" src="" alt="Preview" class="img-thumbnail">
                                                    <div class="preview-info">
                                                        <p id="fileName"></p>
                                                        <p id="fileSize"></p>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImage()">
                                                    <i class="material-icons">delete</i> Remove Image
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label class="bmd-label-floating">Additional Gallery Images</label>
                                        <input type="file" name="gallery_images[]" accept=".jpeg,.jpg,.png,.gif,.webp" multiple class="form-control">
                                        <small class="form-text text-muted">Select multiple images to add to this product (maximum 5MB each).</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Categories & Metadata -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header card-header-primary">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="title">Categories & Metadata</h5>
                                <span class="badge badge-light">Step 2 of 2</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Category Selection -->
                            <div class="form-section">
                                <h6 class="section-title">Category & Brand *</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Product Category *</label>
                                            <select name="product_type" class="form-control" required id="productCategory">
                                                <option value="">Select Category</option>
                                                <?php 
                                                if(mysqli_num_rows($categories_result) > 0):
                                                    mysqli_data_seek($categories_result, 0);
                                                    while($category = mysqli_fetch_assoc($categories_result)): 
                                                ?>
                                                <option value="<?php echo $category['cat_id']; ?>" 
                                                    <?php echo ($product_type == $category['cat_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['cat_title']); ?>
                                                </option>
                                                <?php 
                                                    endwhile;
                                                else: 
                                                ?>
                                                <option value="">No categories found</option>
                                                <?php endif; ?>
                                            </select>
                                            <small class="form-text text-muted">Choose product category</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Product Brand *</label>
                                            <select name="brand" class="form-control" required id="productBrand">
                                                <option value="">Select Brand</option>
                                                <?php 
                                                if(mysqli_num_rows($brands_result) > 0):
                                                    mysqli_data_seek($brands_result, 0);
                                                    while($brand_item = mysqli_fetch_assoc($brands_result)): 
                                                ?>
                                                <option value="<?php echo $brand_item['brand_id']; ?>" 
                                                    <?php echo ($brand == $brand_item['brand_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($brand_item['brand_title']); ?>
                                                </option>
                                                <?php 
                                                    endwhile;
                                                else: 
                                                ?>
                                                <option value="">No brands found</option>
                                                <?php endif; ?>
                                            </select>
                                            <small class="form-text text-muted">Choose product brand</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section mt-4">
                                <h6 class="section-title">Available Colors</h6>
                                <div class="form-group">
                                    <label for="productColors">Colors</label>
                                    <input type="text" name="product_colors" id="productColors" class="form-control"
                                           value="<?php echo htmlspecialchars($product_colors); ?>"
                                           maxlength="255" placeholder="e.g., Black, White, Navy">
                                    <small class="form-text text-muted">Separate colors with commas. Customers can choose a color on the product page.</small>
                                </div>
                            </div>

                            <!-- SEO & Keywords -->
                            <div class="form-section mt-4">
                                <h6 class="section-title">SEO & Keywords *</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="bmd-label-floating">Product Keywords *</label>
                                            <input type="text" name="tags" class="form-control" 
                                                   value="<?php echo htmlspecialchars($tags); ?>" 
                                                   required placeholder="e.g., smartphone, android, 5g" id="productKeywords">
                                            <small class="form-text text-muted">Separate keywords with commas (Used for search)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="btn_save" class="btn btn-primary btn-block btn-lg">
                                <i class="material-icons">add_circle</i> Add Product
                            </button>
                            <button type="reset" class="btn btn-outline-secondary btn-block mt-2" onclick="resetForm()">
                                <i class="material-icons">refresh</i> Reset Form
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Custom Styles -->
<style>
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.page-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #666;
    font-size: 1rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.section-title {
    color: #555;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.image-upload-container {
    text-align: center;
}

.upload-area {
    border: 2px dashed #9c27b0;
    border-radius: 8px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(156, 39, 176, 0.05);
    position: relative;
}

.upload-area:hover {
    background: rgba(156, 39, 176, 0.1);
    border-color: #7b1fa2;
}

.upload-area i {
    font-size: 3rem;
    color: #9c27b0;
    margin-bottom: 1rem;
}

.upload-area p {
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.upload-area input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.image-preview {
    text-align: center;
}

.preview-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    justify-content: center;
}

.preview-container img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.preview-info {
    text-align: left;
    flex: 1;
    max-width: 200px;
}

.preview-info p {
    margin-bottom: 0.2rem;
    font-size: 0.9rem;
    word-break: break-all;
}

.form-group {
    margin-bottom: 1.5rem;
}

.btn-block {
    padding: 12px;
    font-weight: 500;
}

.alert {
    border: none;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #4caf50;
}

.alert-danger {
    border-left-color: #f44336;
}

.bmd-label-floating {
    font-weight: 500;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.card-footer {
    background: rgba(0,0,0,0.02);
    border-top: 1px solid rgba(0,0,0,0.1);
}

.is-invalid {
    border-color: #f44336 !important;
}

.char-counter {
    font-weight: 500;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image upload preview
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('picture');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    // Make the entire upload area clickable
    uploadArea.addEventListener('click', function(e) {
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = 'File: ' + file.name;
                fileSize.textContent = 'Size: ' + formatFileSize(file.size);
                imagePreview.style.display = 'block';
                uploadArea.style.display = 'none';
            }
            
            reader.readAsDataURL(file);
        }
    });

    // Character counter for description
    const descriptionTextarea = document.getElementById('productDescription');
    const charCount = document.getElementById('charCount');
    
    descriptionTextarea.addEventListener('input', function() {
        const length = this.value.length;
        const maxLength = 2000;
        charCount.textContent = length;
        
        if (length > maxLength) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    // Form validation
    const form = document.getElementById('productForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        // Check if image is uploaded
        if (!fileInput.files.length) {
            isValid = false;
            showNotification('Please upload a product image', 'danger');
            uploadArea.style.borderColor = '#f44336';
        } else {
            uploadArea.style.borderColor = '';
        }

        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields', 'danger');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Initialize character count
    charCount.textContent = descriptionTextarea.value.length;
});

function removeImage() {
    const fileInput = document.getElementById('picture');
    const imagePreview = document.getElementById('imagePreview');
    const uploadArea = document.getElementById('uploadArea');
    
    fileInput.value = '';
    imagePreview.style.display = 'none';
    uploadArea.style.display = 'block';
    uploadArea.style.borderColor = '';
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('productForm').reset();
        removeImage();
        document.getElementById('charCount').textContent = '0';
        
        // Remove invalid classes
        const invalidFields = document.querySelectorAll('.is-invalid');
        invalidFields.forEach(function(field) {
            field.classList.remove('is-invalid');
        });
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showNotification(message, type) {
    const alertClass = type === 'danger' ? 'alert-danger' : 'alert-warning';
    const icon = type === 'danger' ? 'error' : 'warning';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.innerHTML = `
        <i class="material-icons">${icon}</i>
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(notification, container.firstChild);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Auto-hide success message after 5 seconds
setTimeout(function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        successAlert.remove();
    }
}, 5000);
</script>

<?php if ($success !== ''): ?>
<script>
window.parent.postMessage({type: 'product-created'}, window.location.origin);
</script>
<?php endif; ?>
<script src="assets/js/core/jquery.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap-material-design.min.js"></script>
</body>
</html>