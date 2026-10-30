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

$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
if ($product_id <= 0) {
    header('Location: products_list.php');
    exit();
}
$embedded = isset($_GET['embedded']) && $_GET['embedded'] === '1';
if (!$embedded) {
    header('Location: products_list.php?open_edit=' . $product_id);
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

$success = '';
if (isset($_POST['delete_gallery_image'])) {
    $image_id = (int)$_POST['delete_gallery_image'];
    $image_result = mysqli_query($con, "SELECT image_name FROM product_images WHERE image_id='$image_id' AND product_id='$product_id'");
    if ($image_result && $image_row = mysqli_fetch_assoc($image_result)) {
        $gallery_path = '../../product_images/' . $image_row['image_name'];
        if (is_file($gallery_path)) unlink($gallery_path);
        mysqli_query($con, "DELETE FROM product_images WHERE image_id='$image_id' AND product_id='$product_id'");
    }
    $success = "Gallery image removed successfully!";
}

$result=mysqli_query($con,"SELECT `product_id`, `product_cat`, `product_brand`, `product_title`, `product_price`, `product_desc`, `product_image`, `product_keywords`, `product_colors` from products  where product_id='$product_id'") or die ("query 1 incorrect.......");

list($product_id,$product_type,$brand,$product_name,$price,$details,$pic_name,$tags,$product_colors)=mysqli_fetch_array($result);

if(isset($_POST['btn_save']))
{
$product_name=mysqli_real_escape_string($con, trim($_POST['product_name']));
$details=mysqli_real_escape_string($con, trim($_POST['details']));
$price=mysqli_real_escape_string($con, trim($_POST['price']));
$product_type=(int)$_POST['product_type'];
$brand=(int)$_POST['brand'];
$tags=mysqli_real_escape_string($con, trim($_POST['tags']));
$product_colors=mysqli_real_escape_string($con, trim($_POST['product_colors'] ?? ''));

$picture_name=$_FILES['picture']['name'] ?? '';
$picture_type=$_FILES['picture']['type'] ?? '';
$picture_tmp_name=$_FILES['picture']['tmp_name'] ?? '';
$picture_size=(int)($_FILES['picture']['size'] ?? 0);
$allowed_types=['image/jpeg','image/jpg','image/png','image/gif','image/webp'];

if ($picture_name !== '' && in_array($picture_type, $allowed_types, true) && $picture_size <= 5000000) {
    $extension=strtolower(pathinfo($picture_name, PATHINFO_EXTENSION));
    $new_main_name='product_' . time() . '_' . uniqid() . '.' . $extension;
    if (move_uploaded_file($picture_tmp_name, '../../product_images/' . $new_main_name)) {
        $pic_name=$new_main_name;
    }
} elseif ($picture_name !== '') {
    $success='The main image must be JPG, PNG, GIF, or WEBP and no larger than 5MB.';
}

if ($success === '') {
    mysqli_query($con,"UPDATE `products` SET `product_title` = '$product_name',
        `product_cat`='$product_type',`product_brand`='$brand',
        `product_price`='$price',`product_desc`='$details',`product_image`='$pic_name',`product_keywords`='$tags',`product_colors`='$product_colors' WHERE `product_id`='$product_id'") or die("Query 2 is incorrect..........");

    if (!empty($_FILES['gallery_images']['name'][0])) {
        $upload_dir='../../product_images/';
        foreach ($_FILES['gallery_images']['name'] as $index => $original_name) {
            if ($_FILES['gallery_images']['error'][$index] !== UPLOAD_ERR_OK) continue;
            $tmp_name=$_FILES['gallery_images']['tmp_name'][$index];
            $mime=(string)$_FILES['gallery_images']['type'][$index];
            $size=(int)$_FILES['gallery_images']['size'][$index];
            if (!in_array($mime, $allowed_types, true) || $size > 5000000) continue;
            $extension=strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $gallery_name='gallery_' . time() . '_' . uniqid() . '.' . $extension;
            if (move_uploaded_file($tmp_name, $upload_dir . $gallery_name)) {
                $safe_gallery=mysqli_real_escape_string($con, $gallery_name);
                mysqli_query($con, "INSERT INTO product_images (product_id,image_name,sort_order) VALUES ('$product_id','$safe_gallery','$index')");
            }
        }
    }
    $success='Product updated successfully!';
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit product</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/material-dashboard.css?v=2.1.0">
    <link rel="stylesheet" href="assets/css/admin-overrides.css?v=1.1">
</head>
<body class="admin-app">
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
          <?php if ($success !== ''): ?>
          <div class="alert alert-success"><i class="material-icons">check_circle</i> <?php echo htmlspecialchars($success); ?></div>
          <script>window.parent.postMessage({type:'product-updated', productId:<?php echo (int)$product_id; ?>}, window.location.origin);</script>
          <?php endif; ?>
          <form action="edit_product.php?embedded=1&amp;product_id=<?php echo (int)$product_id; ?>" method="post" type="form" name="form" enctype="multipart/form-data">
          <div class="row">
          
                
         <div class="col-md-7">
            <div class="card">
              <div class="card-header card-header-primary">
                <h5 class="title">Edit Product</h5>
              </div>
              <div class="card-body">
                
                  <div class="row">
                    
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Product Title</label>
                        <input type="text" id="product_name" required name="product_name" value="<?php echo $product_name; ?>" class="form-control">
                      </div>
                    </div>
                    
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Main Product Image</label><br>
                        <img src="<?php echo '../../product_images/'.htmlspecialchars($pic_name, ENT_QUOTES, 'UTF-8'); ?>" alt="Main product image" style="width:120px;height:120px;object-fit:contain;border:1px solid #ddd;border-radius:8px;padding:4px;background:#fff;">
                        <input type="file" name="picture" accept=".jpg,.jpeg,.png,.gif,.webp" class="form-control mt-2" id="picture">
                        <small class="text-muted">Leave empty to keep the current main image.</small>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Additional Product Images</label>
                        <input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.gif,.webp" multiple class="form-control" id="gallery_images">
                        <small class="text-muted">Add one or more images to the product gallery (maximum 5MB each).</small>
                        <?php
                        $gallery_result = mysqli_query($con, "SELECT image_id,image_name FROM product_images WHERE product_id='$product_id' ORDER BY sort_order,image_id");
                        if ($gallery_result && mysqli_num_rows($gallery_result) > 0): ?>
                        <div class="d-flex flex-wrap mt-3" style="gap:10px;">
                          <?php while ($gallery_image = mysqli_fetch_assoc($gallery_result)): ?>
                          <div style="width:110px;text-align:center;border:1px solid #ddd;border-radius:8px;padding:6px;background:#fff;">
                            <img src="../../product_images/<?php echo htmlspecialchars($gallery_image['image_name'], ENT_QUOTES, 'UTF-8'); ?>" alt="Product gallery image" style="width:96px;height:96px;object-fit:cover;border-radius:4px;">
                            <button type="submit" name="delete_gallery_image" value="<?php echo (int)$gallery_image['image_id']; ?>" class="btn btn-danger btn-sm mt-2" style="padding:4px 8px;">Remove</button>
                          </div>
                          <?php endwhile; ?>
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                     <div class="col-md-12">
                      <div class="form-group">
                        <label>Description</label>
                        <textarea rows="4" cols="80" id="details" required name="details"  class="form-control"><?php echo $details;?></textarea>
                      </div>
                    </div>
                  
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Pricing (LRD)</label>
                        <input type="text" id="price" name="price" value="<?php echo $price ?>" required class="form-control" >
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="product_colors">Available Colors</label>
                        <input type="text" id="product_colors" name="product_colors" value="<?php echo htmlspecialchars($product_colors, ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" placeholder="e.g., Black, White, Navy" class="form-control">
                        <small class="form-text text-muted">Separate colors with commas.</small>
                      </div>
                    </div>
                  </div>
                 
                  
                
              </div>
              
            </div>
          </div>
          <div class="col-md-5">
            <div class="card">
              <div class="card-header card-header-primary">
                <h5 class="title">Categories</h5>
              </div>
              <div class="card-body">
                
                  <div class="row">
                  <div class="col-md-12">
                          <div class="form-group">
                            <label for="">Product Category</label>
                            <select id="product_type" name="product_type"  required class="form-control">
                            <option value="" style="color:black;">select Category</option>
                            <?php 
                                
                                    $result1=mysqli_query($con,"SELECT * FROM `categories` ORDER BY `cat_id` ASC") or die ("query 1 incorrect.....");

                                    while(list($cat_id,$cat_title)=mysqli_fetch_array($result1))
                                    {
                                    
                                        if($cat_id==$product_type){
                                            echo "<option value='$cat_id' style='color:black;' selected>$cat_title</option>";
                                        }else{
                                            echo "<option value='$cat_id' style='color:black;'>$cat_title</option>";
                                        }
                                    }
                                    
                                ?>
                            </select>
                        </div>
                      </div>
                      <div class="col-md-12">
                          <div class="form-group">
                            <label for="">Product Type</label>
                            <select id="brand" name="brand" required class="form-control">
                            <option value="" style="color:black;">select Type</option>
                                <?php
                                    $result2=mysqli_query($con,"SELECT * FROM `brands`") or die ("query 1 incorrect.....");

                                    while(list($brand_id,$brand_title)=mysqli_fetch_array($result2))
                                    {
                                        if($brand_id==$brand){
                                            echo "<option value='$brand_id' style='color:black;' selected>$brand_title</option>";
                                        }else{
                                            echo "<option value='$brand_id' style='color:black;'>$brand_title</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                      </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Product Keywords</label>
                        <input type="text" id="tags" name="tags" value="<?php echo $tags; ?>" required class="form-control" >
                      </div>
                    </div>
                  </div>
                
              </div>
              <div class="card-footer">
                  <button type="submit" id="btn_save" name="btn_save" required class="btn btn-fill btn-primary">Update Product</button>
              </div>
            </div>
          </div>
          
        </div>
         </form>
          
        </div>
      </div>
</body>
</html>
<?php mysqli_close($con); ?>