
   <div class="main main-raised">
        <div class="container mainn-raised" style="width:100%;">
  
  <div id="myCarousel" class="carousel slide" data-ride="carousel">
       <?php
       include_once 'db.php';
       $banner_ads = [];
       $banner_table = mysqli_query($con, "SHOW TABLES LIKE 'store_banners'");
       if (!$banner_table) {
           throw new RuntimeException('Unable to check storefront banner configuration: ' . mysqli_error($con));
       }
       if (mysqli_num_rows($banner_table) > 0) {
           $banner_result = mysqli_query(
               $con,
               "SELECT title, caption, image_path, target_url, button_text
                FROM store_banners
                WHERE status = 'published'
                  AND (starts_at IS NULL OR starts_at <= NOW())
                  AND (ends_at IS NULL OR ends_at >= NOW())
                ORDER BY display_order ASC, id DESC"
           );
           if (!$banner_result) {
               throw new RuntimeException('Unable to load storefront banners: ' . mysqli_error($con));
           }
           while ($banner = mysqli_fetch_assoc($banner_result)) {
               $banner_ads[] = $banner;
           }
       }

       if (!$banner_ads) {
           $banner_ads = [
               ['title' => '', 'caption' => '', 'image_path' => 'img/banner3.jpg', 'target_url' => '', 'button_text' => ''],
               ['title' => '', 'caption' => '', 'image_path' => 'img/banner2.jpg', 'target_url' => '', 'button_text' => ''],
               ['title' => '', 'caption' => '', 'image_path' => 'img/banner4.jpg', 'target_url' => '', 'button_text' => ''],
               ['title' => '', 'caption' => '', 'image_path' => 'img/banner1.jpg', 'target_url' => '', 'button_text' => ''],
               ['title' => '', 'caption' => '', 'image_path' => 'img/banner3.jpg', 'target_url' => '', 'button_text' => '']
           ];
       }
       ?>
       <div class="carousel-inner">
         <?php foreach ($banner_ads as $banner_index => $banner): ?>
           <?php
           $banner_title = htmlspecialchars($banner['title'], ENT_QUOTES, 'UTF-8');
           $banner_caption = htmlspecialchars((string)$banner['caption'], ENT_QUOTES, 'UTF-8');
           $banner_image = htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8');
           $banner_link = htmlspecialchars((string)$banner['target_url'], ENT_QUOTES, 'UTF-8');
           $banner_button = htmlspecialchars((string)$banner['button_text'], ENT_QUOTES, 'UTF-8');
           ?>
           <div class="item<?php echo $banner_index === 0 ? ' active' : ''; ?>">
             <?php if ($banner_link !== ''): ?><a class="store-banner-link" href="<?php echo $banner_link; ?>"><?php endif; ?>
               <div class="store-banner">
                 <img src="<?php echo $banner_image; ?>" alt="<?php echo $banner_title !== '' ? $banner_title : 'Store promotion'; ?>" class="store-banner-image">
                 <?php if ($banner_caption !== '' || $banner_button !== ''): ?>
                   <div class="store-banner-copy">
                     <?php if ($banner_title !== ''): ?><strong><?php echo $banner_title; ?></strong><?php endif; ?>
                     <?php if ($banner_caption !== ''): ?><span><?php echo $banner_caption; ?></span><?php endif; ?>
                     <?php if ($banner_button !== ''): ?><span class="store-banner-button"><?php echo $banner_button; ?> <i class="fa fa-arrow-right"></i></span><?php endif; ?>
                   </div>
                 <?php endif; ?>
               </div>
             <?php if ($banner_link !== ''): ?></a><?php endif; ?>
           </div>
         <?php endforeach; ?>
       </div>

       <?php if (count($banner_ads) > 1): ?>
         <a class="left carousel-control _26sdfg" href="#myCarousel" data-slide="prev" aria-label="Previous advert">
           <span class="glyphicon glyphicon-chevron-left"></span>
           <span class="sr-only">Previous</span>
         </a>
         <a class="right carousel-control _26sdfg" href="#myCarousel" data-slide="next" aria-label="Next advert">
           <span class="glyphicon glyphicon-chevron-right"></span>
           <span class="sr-only">Next</span>
         </a>
       <?php endif; ?>
     </div>
   </div>
     


		<!-- SECTION -->
		<div class="section mainn mainn-raised">
		
		
			<!-- container -->
			<div class="container">
			
				<!-- row -->
				<div class="row">
					<!-- shop -->
					<div class="col-md-4 col-xs-6">
						<a href="product.php?p=78"><div class="shop">
							<div class="shop-img">
								<img src="./img/shop01.png" alt="">
							</div>
							<div class="shop-body">
								<h3>Laptop<br>Collection</h3>
								<a href="product.php?p=78" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
							</div>
						</div></a>
					</div>
					<!-- /shop -->

					<!-- shop -->
					<div class="col-md-4 col-xs-6">
						<a href="product.php?p=72"><div class="shop">
							<div class="shop-img">
								<img src="./img/shop03.png" alt="">
							</div>
							<div class="shop-body">
								<h3>Accessories<br>Collection</h3>
								<a href="product.php?p=72" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
							</div>
						</div></a>
					</div>
					<!-- /shop -->

					<!-- shop -->
					<div class="col-md-4 col-xs-6">
						<a href="product.php?p=79"><div class="shop">
							<div class="shop-img">
								<img src="./img/shop02.png" alt="">
							</div>
							<div class="shop-body">
								<h3>Cameras<br>Collection</h3>
								<a href="product.php?p=79" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
							</div>
                            </div></a>
					</div>
					<!-- /shop -->
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->
		  
		

		<!-- SECTION -->
		<div class="section">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">

					<!-- section title -->
					<div class="col-md-12">
						<div class="section-title">
							<h3 class="title">New Products</h3>
							<div class="section-nav">
								<ul class="section-tab-nav tab-nav">
									<li class="active"><a data-toggle="tab" href="#tab1">Laptops</a></li>
									<li><a data-toggle="tab" href="#tab1">Smartphones</a></li>
									<li><a data-toggle="tab" href="#tab1">Cameras</a></li>
									<li><a data-toggle="tab" href="#tab1">Accessories</a></li>
								</ul>
							</div>
						</div>
					</div>
					<!-- /section title -->

					<!-- Products tab & slick -->
					<div class="col-md-12 mainn mainn-raised">
						<div class="row">
							<div class="products-tabs">
								<!-- tab -->
								<div id="tab1" class="tab-pane active">
									<div class="products-slick" data-nav="#slick-nav-1" >
									
									<?php
                    include 'db.php';
                    include_once 'currency.php';
								
                    
					$product_query = "SELECT * FROM products,categories WHERE product_cat=cat_id AND product_id BETWEEN 70 AND 75";
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
				
                        
                                
								<div class='product'>
									<a href='product.php?p=$pro_id'><div class='product-img'>
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
                               
							
                        
			";
		}
        ;
      
}
?>
										<!-- product -->
										
	
										<!-- /product -->
										
										
										<!-- /product -->
									</div>
									<div id="slick-nav-1" class="products-slick-nav"></div>
								</div>
								<!-- /tab -->
							</div>
						</div>
					</div>
					<!-- Products tab & slick -->
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->

		<!-- HOT DEAL SECTION -->
		<div id="hot-deal" class="section mainn mainn-raised">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">
					<div class="col-md-12">
						<div class="hot-deal">
							<ul class="hot-deal-countdown">
								<li>
									<div>
										<h3>02</h3>
										<span>Days</span>
									</div>
								</li>
								<li>
									<div>
										<h3>10</h3>
										<span>Hours</span>
									</div>
								</li>
								<li>
									<div>
										<h3>34</h3>
										<span>Mins</span>
									</div>
								</li>
								<li>
									<div>
										<h3>60</h3>
										<span>Secs</span>
									</div>
								</li>
							</ul>
							<h2 class="text-uppercase">hot deal this week</h2>
							<p>New Collection Up to 50% OFF</p>
							<a class="primary-btn cta-btn" href="store.php">Shop now</a>
						</div>
					</div>
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /HOT DEAL SECTION -->
		

		<!-- SECTION -->
		<div class="section">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">

					<!-- section title -->
					<div class="col-md-12">
						<div class="section-title">
							<h3 class="title">Top selling</h3>
							<div class="section-nav">
								<ul class="section-tab-nav tab-nav">
									<li class="active"><a data-toggle="tab" href="#tab2">Formals</a></li>
									<li><a data-toggle="tab" href="#tab2">Shirts</a></li>
									<li><a data-toggle="tab" href="#tab2">T-Shirts</a></li>
									<li><a data-toggle="tab" href="#tab2">Pants</a></li>
								</ul>
							</div>
						</div>
					</div>
					<!-- /section title -->

					<!-- Products tab & slick -->
					<div class="col-md-12 mainn mainn-raised">
						<div class="row">
							<div class="products-tabs">
								<!-- tab -->
								<div id="tab2" class="tab-pane fade in active">
									<div class="products-slick" data-nav="#slick-nav-2">
										<!-- product -->
										<?php
                    include 'db.php';
                    include_once 'currency.php';
								
                    
					$product_query = "SELECT * FROM products,categories WHERE product_cat=cat_id AND product_id BETWEEN 59 AND 65";
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
				
                        
                                
								<div class='product'>
									<a href='product.php?p=$pro_id'><div class='product-img'>
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
                               
							
                        
			";
		}
        ;
      
}
?>
										
										<!-- /product -->
									</div>
									<div id="slick-nav-2" class="products-slick-nav"></div>
								</div>
								<!-- /tab -->
							</div>
						</div>
					</div>
					<!-- /Products tab & slick -->
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->

		<!-- SECTION -->
		<div class="section">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">
					<div class="col-md-4 col-xs-6">
						<div class="section-title">
							<h4 class="title">Top selling</h4>
							<div class="section-nav">
								<div id="slick-nav-3" class="products-slick-nav"></div>
							</div>
						</div>
						

						<div class="products-widget-slick" data-nav="#slick-nav-3">
							<div id="get_product_home">
								<!-- product widget -->
								
								<!-- product widget -->
							</div>

							<div id="get_product_home2">
								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product01.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product02.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product03.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- product widget -->
							</div>
						</div>
					</div>

					<div class="col-md-4 col-xs-6">
						<div class="section-title">
							<h4 class="title">Top selling</h4>
							<div class="section-nav">
								<div id="slick-nav-4" class="products-slick-nav"></div>
							</div>
						</div>

						<div class="products-widget-slick" data-nav="#slick-nav-4">
							<div>
								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product04.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product05.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product06.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- product widget -->
							</div>

							<div>
								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product07.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product08.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product09.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- product widget -->
							</div>
						</div>
					</div>

					<div class="clearfix visible-sm visible-xs">
					    
					</div>

					<div class="col-md-4 col-xs-6">
						<div class="section-title">
							<h4 class="title">Top selling</h4>
							<div class="section-nav">
								<div id="slick-nav-5" class="products-slick-nav"></div>
							</div>
						</div>

						<div class="products-widget-slick" data-nav="#slick-nav-5">
							<div>
								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product01.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product02.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product03.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- product widget -->
							</div>

							<div>
								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product04.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->
								

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product05.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- /product widget -->

								<!-- product widget -->
								<div class="product-widget">
									<div class="product-img">
										<img src="./img/product06.png" alt="">
									</div>
									<div class="product-body">
										<p class="product-category">Category</p>
										<h3 class="product-name"><a href="#">product name goes here</a></h3>
										<h4 class="product-price"><?php echo format_store_price_from_usd(980, $con); ?> <del class="product-old-price"><?php echo format_store_price_from_usd(990, $con); ?></del></h4>
									</div>
								</div>
								<!-- product widget -->
							</div>
						</div>
					</div>

				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->
</div>
		