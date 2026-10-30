<?php
$product_id = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
if (!$product_id) {
	$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
}
$product_id = $product_id ?: 0;
include "header.php";
?>
		<!-- /BREADCRUMB -->
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function () {
				document.querySelectorAll('.scroll').forEach(function (link) {
					link.addEventListener('click', function(event) {
					event.preventDefault();
						var target = document.querySelector(link.hash);
						if (target) {
							target.scrollIntoView({behavior: 'smooth'});
						}
					});
				});
			});
</script>

		<!-- SECTION -->
		<div class="section main main-raised">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row product-page-layout">
					<!-- Product main img -->
					
					<?php 
								include 'db.php';
								include_once 'currency.php';
								if (!$con) {
									die("Connection failed: " . mysqli_connect_error());
								}
								$sql = "SELECT P.*, C.cat_title, B.brand_title FROM products AS P INNER JOIN categories AS C ON P.product_cat = C.cat_id LEFT JOIN brands AS B ON P.product_brand = B.brand_id WHERE P.product_id = ?";
								$product_statement = mysqli_prepare($con, $sql);
								mysqli_stmt_bind_param($product_statement, 'i', $product_id);
								mysqli_stmt_execute($product_statement);
								$result = mysqli_stmt_get_result($product_statement);
								if (mysqli_num_rows($result) > 0) 
								{
									while($row = mysqli_fetch_assoc($result)) 
									{
									$product_row = $row;
									$product_description = trim((string)$row['product_desc']);
									$description_parts = preg_split('/\sPhoto:\s/', $product_description, 2);
									$product_description = trim($description_parts[0]);
									$photo_credit = isset($description_parts[1]) ? 'Photo: ' . trim($description_parts[1]) : '';
									$stock_quantity = max(0, (int)($row['stock_quantity'] ?? 0));
									$is_in_stock = $stock_quantity > 0;
									$gallery_images = [];
									$gallery_table_check = mysqli_query($con, "SHOW TABLES LIKE 'product_images'");
									if ($gallery_table_check && mysqli_num_rows($gallery_table_check) > 0) {
									    $gallery_result = mysqli_query($con, "SELECT image_name FROM product_images WHERE product_id='" . (int)$product_id . "' ORDER BY sort_order,image_id");
									    if ($gallery_result) {
									        while ($gallery_row = mysqli_fetch_assoc($gallery_result)) {
									            $gallery_images[] = $gallery_row['image_name'];
									        }
									    }
									}
									$all_product_images = array_values(array_unique(array_merge([$row['product_image']], $gallery_images)));
									$gallery_thumbnails = '';
									foreach ($all_product_images as $image_index => $gallery_image) {
									    $gallery_thumbnails .= '<button type="button" class="product-gallery-thumb'.($image_index === 0 ? ' is-active' : '').'" data-image="'.htmlspecialchars($gallery_image, ENT_QUOTES, 'UTF-8').'" aria-label="View product image '.($image_index + 1).'" title="View image '.($image_index + 1).'">'.
									        '<img src="product_images/'.htmlspecialchars($gallery_image, ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($row['product_title'], ENT_QUOTES, 'UTF-8').' image '.($image_index + 1).'">'.
									        '</button>';
									}
									$whatsapp_number = '250794162823';
									$messenger_username = 'archiekgonwoejr';
									$contact_product_title = htmlspecialchars($row['product_title'], ENT_QUOTES, 'UTF-8');
									$contact_product_price = htmlspecialchars(format_store_price($row['product_price'], $con), ENT_QUOTES, 'UTF-8');
									$color_palette = [
										'red' => ['Red', '#d10024', 0],
										'navy' => ['Navy', '#1f3a93', 220],
										'blue' => ['Blue', '#1976d2', 210],
										'black' => ['Black', '#1d1d1f', -10],
										'white' => ['White', '#f4f4f4', 0],
										'silver' => ['Silver', '#c4c9d0', 190],
										'gray' => ['Gray', '#777777', 0],
										'grey' => ['Grey', '#777777', 0],
										'beige' => ['Beige', '#d7c3a1', 25],
										'brown' => ['Brown', '#795548', 20],
										'green' => ['Green', '#388e3c', 95],
										'olive' => ['Olive', '#808000', 70],
										'pink' => ['Pink', '#e89ab5', 325],
										'purple' => ['Purple', '#7e57c2', 275],
										'yellow' => ['Yellow', '#fbc02d', 45],
										'orange' => ['Orange', '#ef6c00', 25],
										'cream' => ['Cream', '#f5ead3', 35],
										'gold' => ['Gold', '#c9a227', 35],
										'teal' => ['Teal', '#008b8b', 170],
										'burgundy' => ['Burgundy', '#800020', 345],
										'graphite' => ['Graphite', '#42464b', 0],
										'lavender' => ['Lavender', '#b39ddb', 280],
										'midnight' => ['Midnight', '#202b45', 220],
										'multicolor' => ['Multicolor', '#7b61ff', 240],
										'oak' => ['Oak', '#b58b5a', 25],
										'starlight' => ['Starlight', '#ded5c5', 35],
										'walnut' => ['Walnut', '#5d4037', 20]
									];
									$available_colors = array_values(array_filter(array_map('trim', explode(',', (string)($row['product_colors'] ?? '')))));
									$visual_color_swatches = '';
									$detail_color_swatches = '';
									foreach ($available_colors as $color_index => $color_name) {
										$color_key = strtolower($color_name);
										$color_data = $color_palette[$color_key] ?? [$color_name, '#777777', 0];
										$escaped_color = htmlspecialchars($color_data[0], ENT_QUOTES, 'UTF-8');
										$swatch_class = $color_index === 0 ? ' is-active' : '';
										$swatch_attributes = ' data-color="' . htmlspecialchars($color_key, ENT_QUOTES, 'UTF-8') . '" data-hue="' . (int)$color_data[2] . '" data-label="' . $escaped_color . '" aria-pressed="' . ($color_index === 0 ? 'true' : 'false') . '" style="--swatch:' . htmlspecialchars($color_data[1], ENT_QUOTES, 'UTF-8') . '"';
										$visual_color_swatches .= '<button type="button" class="color-swatch' . $swatch_class . '"' . $swatch_attributes . ' aria-label="' . $escaped_color . '"></button>';
										$detail_color_swatches .= '<button type="button" class="mini-color-swatch' . $swatch_class . '"' . $swatch_attributes . ' aria-label="Select ' . $escaped_color . '"></button>';
									}
									$selected_color_label = $available_colors
										? htmlspecialchars($color_palette[strtolower($available_colors[0])][0] ?? $available_colors[0], ENT_QUOTES, 'UTF-8')
										: '';
									$product_description_html = $product_description !== ''
										? nl2br(htmlspecialchars($product_description, ENT_QUOTES, 'UTF-8'))
										: '<p class="product-description-empty">A description has not been added for this product yet.</p>';
									echo '
									
                                    
                                
                                <div class="col-md-6 product-viewer-column">
                                	<div class="product-viewer-shell">
							<div class="product-rotation-toolbar">
								<button class="product-rotate-btn" type="button" data-rotate="-35" aria-label="Rotate product preview left" title="Rotate left"><i class="fa fa-rotate-left"></i></button>
								<button class="product-rotate-btn" type="button" data-rotate="35" aria-label="Rotate product preview right" title="Rotate right"><i class="fa fa-rotate-right"></i></button>
								<button class="product-rotate-btn product-reset-view" type="button" aria-label="Reset product preview" title="Reset view"><i class="fa fa-refresh"></i></button>
							</div>
							<div class="product-visual-stage" id="product-visual-stage" aria-label="Product image preview. Drag to tilt the image.">
												<div class="product-visual-glow"></div>
								<img class="product-visual-image" src="product_images/'.htmlspecialchars($row['product_image'], ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($row['product_title'], ENT_QUOTES, 'UTF-8').'">
							</div>
							'.($gallery_thumbnails !== '' ? '<div class="product-gallery-thumbnails" aria-label="Product images">'.$gallery_thumbnails.'</div>' : '').'
							<div class="product-color-options">
								'.$visual_color_swatches.'
							</div>
                                	</div>
                                </div>
									';
									?>
									<!-- FlexSlider -->
										
									<?php 
									echo '
										
                                    
                                   
							<div class="col-md-6 product-detail-column">
								<div class="product-details">
									<h1 class="product-name">'.htmlspecialchars($row['product_title'], ENT_QUOTES, 'UTF-8').'</h1>
									<div id = "rating_reviews">
																
									</div>
									<div>
										<h3 class="product-price">'.format_store_price($row['product_price'], $con).'</h3>
										<span class="product-available'.($is_in_stock ? '' : ' is-out-of-stock').'">'.($is_in_stock ? 'In Stock · '.$stock_quantity.' available' : 'Out of Stock').'</span>
									</div>
									<div class="product-description">'.$product_description_html.'</div>

									<div class="product-options">
										'.($available_colors ? '<div class="product-option-label color-option-group">
											<span>Color</span>
											<div class="color-option-inline">
												'.$detail_color_swatches.'
											</div>
											<span class="selected-color-name">'.$selected_color_label.'</span>
										</div>' : '').'
									</div>

									<div class="add-to-cart">
										<div class="qty-label">
											Qty
											<div class="input-number">
												<button class="qty-down" type="button" aria-label="Decrease quantity"'.(!$is_in_stock ? ' disabled' : '').'>−</button>
												<input id="product-quantity" type="number" min="1" max="'.$stock_quantity.'" value="1" inputmode="numeric" aria-label="Product quantity"'.(!$is_in_stock ? ' disabled' : '').'>
												<button class="qty-up" type="button" aria-label="Increase quantity"'.(!$is_in_stock ? ' disabled' : '').'>+</button>
											</div>
										</div>
										<div class="btn-group product-cart-action">
										<button class="add-to-cart-btn" pid="'.(int)$row['product_id'].'" id="product"'.(!$is_in_stock ? ' disabled' : '').'><i class="fa fa-shopping-cart"></i> '.($is_in_stock ? 'Add to cart' : 'Out of stock').'</button>
										</div>
										<div class="contact-buy-actions">
											<a class="contact-buy-btn whatsapp-buy-btn" href="https://wa.me/'.$whatsapp_number.'" target="_blank" rel="noopener noreferrer" data-contact-buy="whatsapp" data-phone="'.$whatsapp_number.'" data-product-id="'.(int)$row['product_id'].'" data-product-title="'.$contact_product_title.'" data-product-price="'.$contact_product_price.'"><i class="fa fa-whatsapp" aria-hidden="true"></i> Buy via WhatsApp</a>
											<a class="contact-buy-btn messenger-buy-btn" href="https://m.me/'.$messenger_username.'" target="_blank" rel="noopener noreferrer" data-contact-buy="messenger" data-messenger="'.$messenger_username.'" data-product-title="'.$contact_product_title.'"><i class="fa fa-facebook" aria-hidden="true"></i> Ask on Messenger</a>
										</div>
										<p class="contact-buy-hint">WhatsApp opens a ready-to-send order message. On Messenger, mention the product name.</p>
																
																
									</div>

									<ul class="product-btns">
										<li><a href="#" pid="'.$row['product_id'].'"  id="wishlist" ><i class="fa fa-heart-o"></i> add to wishlist</a></li>
										<li><a href="#"><i class="fa fa-exchange"></i> add to compare</a></li>
									</ul>

									<ul class="product-links">
										<li>Category:</li>
										<li><a href="#">'.$row["cat_title"].'</a></li>
									</ul>

									<ul class="product-links">
										<li>Share:</li>
										<li><a href="#"><i class="fa fa-facebook"></i></a></li>
										<li><a href="#"><i class="fa fa-twitter"></i></a></li>
										<li><a href="#"><i class="fa fa-google-plus"></i></a></li>
										<li><a href="#"><i class="fa fa-envelope"></i></a></li>
									</ul>

								</div>
							</div>
							';
							$_SESSION['product_id'] = $row['product_id'];
							}
						} 
						?>		
					
					
					<div class="col-md-12">
						<div id="product-tab">
							<!-- product tab nav -->
							<ul class="tab-nav">
								<li class="active"><a data-toggle="tab" href="#tab1">Description</a></li>
								<li><a data-toggle="tab" href="#tab2">Details</a></li>
								<?php
												include 'db.php';
																$review_statement = mysqli_prepare($con, "SELECT COUNT(*) AS count FROM reviews WHERE product_id = ?");
																mysqli_stmt_bind_param($review_statement, 'i', $product_id);
																mysqli_stmt_execute($review_statement);
																$review_result = mysqli_stmt_get_result($review_statement);
																$review_row = mysqli_fetch_assoc($review_result);
												$reviews_count=$review_row["count"];
												echo '<li><a data-toggle="tab" href="#tab3">Reviews ('.$reviews_count.')</a></li>';
								?>
								
							</ul>
							<!-- /product tab nav -->

							<!-- product tab content -->
							<div class="tab-content">
								<!-- tab1  -->
								<div id="tab1" class="tab-pane fade in active">
									<div class="row">
										<div class="col-md-12">
											<div class="product-description"><?php echo $product_description_html; ?></div>
										</div>
									</div>
								</div>
								<!-- /tab1  -->

								<!-- tab2  -->
								<div id="tab2" class="tab-pane fade in">
									<div class="row">
										<div class="col-md-12">
											<dl class="product-detail-list">
												<dt>Product</dt>
												<dd><?php echo htmlspecialchars($product_row['product_title'], ENT_QUOTES, 'UTF-8'); ?></dd>
												<dt>Category</dt>
												<dd><?php echo htmlspecialchars($product_row['cat_title'], ENT_QUOTES, 'UTF-8'); ?></dd>
												<?php if (!empty($product_row['brand_title'])): ?>
													<dt>Brand</dt>
													<dd><?php echo htmlspecialchars($product_row['brand_title'], ENT_QUOTES, 'UTF-8'); ?></dd>
												<?php endif; ?>
												<?php if ($photo_credit !== ''): ?>
													<dt>Image credit</dt>
													<dd><?php echo htmlspecialchars($photo_credit, ENT_QUOTES, 'UTF-8'); ?></dd>
												<?php endif; ?>
											</dl>
										</div>
									</div>
								</div>
								<!-- /tab2  -->

								<!-- tab3  -->
								<div id="tab3" class="tab-pane fade in">
									<div class="row">
										<!-- Rating -->
										<div id="review_action" pid='<?php echo"$product_id"; ?>'></div>
										
										<!-- Review Form -->
										<div class="col-md-3 mainn">
											<div id="review-form">
												<form class="review-form" onsubmit="return false" id="review_form" required>
													<input class="input" type="text" name="name" placeholder="Your Name" required>
													<input class="input" type="email" name="email" placeholder="Your Email" required>
													<?php 
														echo'<input name="product_id" value="'.(int)$product_id.'" hidden required>'
													?>
													
													<textarea class="input" name="review" placeholder="Your Review"></textarea>
													<div class="input-rating">
														<span>Your Rating: </span>
														<div class="stars">
															<input id="star5" name="rating" value="5" type="radio" required><label for="star5"></label>
															<input id="star4" name="rating" value="4" type="radio" required><label for="star4"></label>
															<input id="star3" name="rating" value="3" type="radio" required><label for="star3"></label>
															<input id="star2" name="rating" value="2" type="radio" required><label for="star2"></label>
															<input id="star1" name="rating" value="1" type="radio" required><label for="star1"></label>
														</div>
													</div>
													<button class="primary-btn" name="review_submit">Submit</button>
												</form>
											</div>
										</div>
										<!-- /Review Form -->
									</div>
								</div>
								<!-- /tab3  -->
							</div>
							<!-- /product tab content  -->
						</div>
					</div>
					<!-- /product tab -->
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->

		<!-- Section -->
		<div class="section main main-raised">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">
                    
					<div class="col-md-12">
						<div class="section-title text-center">
							<h3 class="title">Related Products</h3>
							
						</div>
					</div>
                    
								<?php
                    include 'db.php';
					$product_query = "SELECT * FROM products,categories WHERE product_cat=cat_id AND product_id BETWEEN $product_id AND $product_id+3";
                $run_query = mysqli_query($con,$product_query);
                if(mysqli_num_rows($run_query) > 0){

                    while($row = mysqli_fetch_array($run_query)){
                        $pro_id    = $row['product_id'];
                        $pro_cat   = $row['product_cat'];
                        $pro_brand = $row['product_brand'];
                        $pro_title = $row['product_title'];
                        $pro_price = $row['product_price'];
                        $pro_price = format_store_price($pro_price, $con);
                        $pro_image = $row['product_image'];

                        $cat_name = $row["cat_title"];

                        echo "
				
                        
                                <div class='col-md-3 col-xs-6'>
								<a href='product.php?p=$pro_id'><div class='product'>
									<div class='product-img'>
										<img src='product_images/$pro_image' style='max-height: 170px;' alt=''>
										<div class='product-label'>
											<span class='sale'>-30%</span>
											<span class='new'>NEW</span>
										</div>
									</div></a>
									<div class='product-body'>
										<p class='product-category'>$cat_name</p>
										<h3 class='product-name header-cart-item-name'><a href='product.php?p=$pro_id'>$pro_title</a></h3>
										<h4 class='product-price header-cart-item-info'>$pro_price</h4>
										<div class='product-rating'>";
										$rating_query = "SELECT ROUND(AVG(rating),1) AS avg_rating  FROM reviews WHERE product_id='$pro_id '";
										$run_review_query = mysqli_query($con,$rating_query);
										$review_row = mysqli_fetch_array($run_review_query);
										
										if($review_row > 0){
											$avg_count=$review_row["avg_rating"];
												$i=1;
												while($i <= round($avg_count)){
													$i++;
													echo'
													<i class="fa fa-star"></i>';
												}
												$i=1;
												while($i <= 5-round($avg_count)){
													$i++;
													echo'
													<i class="fa fa-star-o empty"></i>';
												}
											
										}
										echo "</div>
										<div class='product-btns'>
											<button pid='$pro_id' id='wishlist' class='add-to-wishlist'><i class='fa fa-heart-o'></i><span class='tooltipp'>add to wishlist</span></button>
											<button class='add-to-compare'><i class='fa fa-exchange'></i><span class='tooltipp'>add to compare</span></button>
											<button class='quick-view'><i class='fa fa-eye'></i><span class='tooltipp'>quick view</span></button>
										</div>
									</div>
									<div class='add-to-cart'>
										<button pid='$pro_id' id='product' class='add-to-cart-btn block2-btn-towishlist' href='#'><i class='fa fa-shopping-cart'></i> add to cart</button>
									</div>
								</div>
                                </div>
							
                        
			";
		}
        ;
      
}
?>
					<!-- product -->
					
					<!-- /product -->

				</div>
				<!-- /row -->
                
			</div>
			<!-- /container -->
		</div>
		<!-- /Section -->

		<!-- NEWSLETTER -->
		
		<!-- /NEWSLETTER -->

		<!-- FOOTER -->
<?php
include "newslettter.php";
include "footer.php";
?>

<script>
(function () {
    const stage = document.getElementById('product-visual-stage');
    const image = stage ? stage.querySelector('.product-visual-image') : null;
    const swatches = Array.from(document.querySelectorAll('.color-swatch, .mini-color-swatch'));
    const selectedColorName = document.querySelector('.selected-color-name');
    const rotateButtons = document.querySelectorAll('.product-rotate-btn');
    let rotationY = 0;
    let rotationX = 5;
    let dragStartX = 0;
    let dragStartY = 0;
    let isDragging = false;

    if (!stage || !image) {
        return;
    }

    const applyColor = function (swatch) {
        if (!swatch) {
            return;
        }
        const hue = Number(swatch.dataset.hue || 0);
        image.style.filter = 'hue-rotate(' + hue + 'deg) saturate(1.2) brightness(1.05) contrast(1.08)';
        stage.style.setProperty('--product-accent', swatch.style.getPropertyValue('--swatch') || '#d10024');
        stage.style.boxShadow = '0 18px 40px rgba(29, 31, 41, 0.12)';

        const label = swatch.dataset.label || 'Red';
        if (selectedColorName) {
            selectedColorName.textContent = label;
        }

        swatches.forEach(function (item) {
            const isSelected = item.dataset.color === swatch.dataset.color;
            item.classList.toggle('is-active', isSelected);
            item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    };

    swatches.forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            applyColor(swatch);
        });
    });

    document.querySelectorAll('.product-gallery-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            const nextImage = thumb.dataset.image;
            if (nextImage) {
                image.src = 'product_images/' + nextImage;
            }
            document.querySelectorAll('.product-gallery-thumb').forEach(function (item) {
                item.classList.toggle('is-active', item === thumb);
            });
        });
    });

    const updateTransform = function () {
        image.style.transform = 'rotateX(' + rotationX + 'deg) rotateY(' + rotationY + 'deg) scale(1.02)';
    };

    rotateButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.classList.contains('product-reset-view')) {
                rotationY = 0;
                rotationX = 5;
                updateTransform();
                return;
            }
            const delta = Number(button.dataset.rotate || 0);
            rotationY += delta;
            updateTransform();
        });
    });

    stage.addEventListener('pointerdown', function (event) {
        isDragging = true;
        stage.classList.add('is-dragging');
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        stage.setPointerCapture(event.pointerId);
    });

    stage.addEventListener('pointermove', function (event) {
        if (!isDragging) return;
        const deltaX = event.clientX - dragStartX;
        const deltaY = event.clientY - dragStartY;
        rotationY += deltaX * 0.4;
        rotationX = Math.max(-20, Math.min(20, 5 - deltaY * 0.2));
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        updateTransform();
    });

    ['pointerup', 'pointerleave', 'pointercancel'].forEach(function (eventName) {
        stage.addEventListener(eventName, function () {
            isDragging = false;
            stage.classList.remove('is-dragging');
        });
    });

    updateTransform();
    applyColor(document.querySelector('.color-swatch.is-active') || document.querySelector('.color-swatch'));
})();

(function () {
    document.querySelectorAll('[data-contact-buy="whatsapp"]').forEach(function (link) {
        link.addEventListener('click', function () {
            const productUrl = new URL('product.php?p=' + encodeURIComponent(link.dataset.productId), window.location.href).href;
            const selectedColor = document.querySelector('.selected-color-name');
            const message = [
                'Hello, I would like to buy this product:',
                link.dataset.productTitle,
                'Price: ' + link.dataset.productPrice,
                selectedColor ? 'Selected color: ' + selectedColor.textContent.trim() : '',
                'Product link: ' + productUrl
            ].filter(Boolean).join('\n');

            link.href = 'https://wa.me/' + link.dataset.phone + '?text=' + encodeURIComponent(message);
        });
    });
})();
</script>
