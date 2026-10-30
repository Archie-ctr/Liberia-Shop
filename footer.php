<footer id="footer" class="liberia-footer">
    <?php
    $footer_script_directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $footer_base_path = $footer_script_directory === '.' || $footer_script_directory === '/' ? '' : $footer_script_directory;

    $footer_settings_result = mysqli_query($con, "SELECT shop_name, shop_email, shop_phone, shop_address, shop_description, social_facebook, social_twitter, social_instagram, whatsapp_number, messenger_url FROM shop_settings WHERE id = 1 LIMIT 1");
    if (!$footer_settings_result) {
        throw new RuntimeException('Unable to load footer store settings: ' . mysqli_error($con));
    }
    $footer_settings = mysqli_fetch_assoc($footer_settings_result) ?: [];
    $footer_escape = static function ($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };
    $footer_shop_name = $footer_settings['shop_name'] ?? 'TechShop Liberia';
    $footer_shop_email = $footer_settings['shop_email'] ?? '';
    $footer_shop_phone = $footer_settings['shop_phone'] ?? '';
    $footer_phone_uri = preg_replace('/[^0-9+]/', '', (string)$footer_shop_phone);
    $footer_whatsapp_digits = preg_replace('/\D+/', '', (string)($footer_settings['whatsapp_number'] ?? ''));
    $footer_whatsapp_link = $footer_whatsapp_digits !== '' ? 'https://wa.me/' . $footer_whatsapp_digits : 'https://wa.me/250794162823';
    $footer_messenger_link = trim((string)($footer_settings['messenger_url'] ?? ''));
    if ($footer_messenger_link === '') {
        $footer_messenger_link = 'https://m.me/archiekgonwoejr';
    } elseif (!preg_match('~^https?://~i', $footer_messenger_link)) {
        $footer_messenger_link = 'https://m.me/' . ltrim($footer_messenger_link, '@/');
    }
    $footer_social_links = [];
    foreach ([
        'Facebook' => $footer_settings['social_facebook'] ?? '',
        'Twitter' => $footer_settings['social_twitter'] ?? '',
        'Instagram' => $footer_settings['social_instagram'] ?? ''
    ] as $social_name => $social_url) {
        $social_scheme = strtolower((string)parse_url((string)$social_url, PHP_URL_SCHEME));
        if (filter_var($social_url, FILTER_VALIDATE_URL) && in_array($social_scheme, ['http', 'https'], true)) {
            $footer_social_links[$social_name] = $social_url;
        }
    }
    $footer_categories = [
        ['id' => 1],
        ['id' => 7],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
        ['id' => 6]
    ];
    $footer_category_result = mysqli_query($con, "SELECT cat_id, cat_title FROM categories WHERE is_active = 1 ORDER BY cat_id");
    if (!$footer_category_result) {
        throw new RuntimeException('Unable to load footer categories: ' . mysqli_error($con));
    }
    $footer_category_names = [];
    while ($footer_category = mysqli_fetch_assoc($footer_category_result)) {
        $footer_category_names[(int)$footer_category['cat_id']] = $footer_category['cat_title'];
    }
    ?>
    <style>
    /* Toast Notification Styles */
    #toast {
        visibility: hidden;
        max-width: 50px;
        height: 50px;
        margin: auto;
        background: linear-gradient(135deg, #002855 0%, #e74c3c 100%);
        color: #fff;
        text-align: center;
        border-radius: 25px;
        position: fixed;
        z-index: 1000;
        left: 0; right: 0;
        bottom: 30px;
        font-size: 17px;
        white-space: nowrap;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        border: 2px solid #fff;
    }
    
    #toast #img {
        width: 50px;
        height: 50px;
        float: left;
        padding-top: 16px;
        padding-bottom: 16px;
        box-sizing: border-box;
        background-color: #002855;
        color: #fff;
        border-radius: 25px 0 0 25px;
    }
    
    #toast #desc {
        color: #fff;
        padding: 16px;
        overflow: hidden;
        white-space: nowrap;
        font-weight: 600;
    }

    #toast.show {
        visibility: visible;
        animation: fadein 0.5s, expand 0.5s 0.5s, stay 3s 1s, shrink 0.5s 4s, fadeout 0.5s 4.5s;
    }

    @keyframes fadein {
        from { bottom: 0; opacity: 0; } 
        to { bottom: 30px; opacity: 1; }
    }

    @keyframes expand {
        from { min-width: 50px } 
        to { min-width: 350px }
    }
    
    @keyframes stay {
        from { min-width: 350px } 
        to { min-width: 350px }
    }
    
    @keyframes shrink {
        from { min-width: 350px; } 
        to { min-width: 50px; }
    }

    @keyframes fadeout {
        from { bottom: 30px; opacity: 1; } 
        to { bottom: 60px; opacity: 0; }
    }

    /* Liberia Footer Styles */
    #footer.liberia-footer {
        background: #101b2b;
        color: white;
        border-top: 4px solid #e74c3c;
        overflow: hidden;
    }

    .liberia-footer-main {
        padding: 44px 0 30px;
    }

    .liberia-footer .footer-columns {
        display: grid;
        grid-template-columns: 1.15fr 0.8fr 1fr 1.25fr;
        gap: 34px;
    }

    .liberia-footer .footer-column {
        min-width: 0;
    }

    .liberia-footer .footer {
        margin: 0;
    }

    .liberia-footer .footer-title {
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        line-height: 1.4;
        margin: 0 0 18px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .liberia-footer .footer-about .footer-title {
        color: #ffd700;
    }

    .liberia-footer .footer-about > p,
    .liberia-footer .newsletter-signup > p {
        color: #b7c3d2;
        font-size: 13px;
        line-height: 1.7;
        margin: 0 0 14px;
    }

    .liberia-footer .footer-links {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .liberia-footer .footer-links li {
        margin: 0 0 9px;
    }

    .liberia-footer .footer-links li a {
        color: #c4cfdd;
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
        display: inline-flex;
        align-items: flex-start;
        padding: 2px 0;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .liberia-footer .footer-links li a:hover {
        color: #ffd700;
        transform: translateX(5px);
    }

    .liberia-footer .footer-links li i {
        flex: 0 0 16px;
        margin: 3px 9px 0 0;
        color: #e74c3c;
    }

    .liberia-footer .footer-contact-links {
        margin-top: 16px;
    }

    .liberia-footer .newsletter-signup {
        margin: 0;
        padding: 0;
        background: transparent;
    }

    .liberia-footer .newsletter-input {
        display: flex;
        gap: 8px;
    }

    .liberia-footer .newsletter-input input {
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 0 12px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 6px;
        background: #fff;
        color: #192536;
    }

    .liberia-footer .newsletter-input button {
        flex: 0 0 auto;
        min-height: 42px;
        padding: 0 14px;
        border-radius: 6px;
        background: #e74c3c;
        font-weight: 700;
    }

    .liberia-footer .social-links {
        display: flex;
        justify-content: flex-start;
        gap: 9px;
        margin: 18px 0 0;
    }

    .liberia-footer .social-links a {
        display: inline-flex;
        flex: 0 0 38px;
        align-items: center;
        justify-content: center;
        height: 38px;
        border: 1px solid rgba(255,255,255,0.17);
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
        color: #fff;
        text-align: center;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .liberia-footer .social-links a:hover {
        background: #e74c3c;
        border-color: #e74c3c;
    }

    .liberia-footer .emergency-contact {
        margin: 18px 0 0;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
        color: #b7c3d2;
        font-size: 12px;
        line-height: 1.6;
    }

    .liberia-footer .emergency-contact strong {
        margin-bottom: 2px;
        color: #fff;
    }

    .liberia-footer .emergency-contact a {
        color: #ffd700;
        text-decoration: none;
        font-weight: 700;
    }

    .liberia-footer-bottom {
        padding: 22px 0 18px;
        border-top: 1px solid rgba(255,255,255,0.13);
    }

    .liberia-footer .liberia-badges {
        display: flex;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 16px;
    }

    .liberia-footer .liberia-badge {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 11px;
        color: #d3dce7;
    }

    .liberia-footer .copyright {
        color: #9eacbd;
        font-size: 11px;
        line-height: 1.7;
        margin: 0;
    }

    .liberia-footer .copyright a {
        color: #ffd700;
        text-decoration: none;
        font-weight: 700;
    }

    .liberia-footer .copyright a:hover {
        text-decoration: underline;
    }

    @media (max-width: 991px) {
        .liberia-footer .footer-columns {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 30px 24px;
        }
    }

    @media (max-width: 768px) {
        .liberia-footer-main {
            padding: 32px 0 24px;
        }

        .liberia-footer .footer-columns {
            gap: 26px 18px;
        }

        .liberia-footer .footer-title {
            margin-bottom: 12px;
            font-size: 12px;
        }

        .liberia-footer .footer-links li {
            margin-bottom: 7px;
        }

        .liberia-footer .footer-links li a {
            font-size: 12px;
        }

        .liberia-footer .newsletter-input {
            flex-direction: column;
        }

        .liberia-footer .newsletter-input button {
            width: 100%;
        }

        .liberia-footer .social-links {
            flex-wrap: wrap;
        }

        .liberia-footer-bottom {
            padding: 18px 0 calc(18px + env(safe-area-inset-bottom));
        }

        .liberia-footer .liberia-badges {
            justify-content: flex-start;
        }
    }
    </style>

    <!-- Toast Notification -->
    <div id="toast"><div id="desc">Welcome to Liberia Commerce Hub</div></div>

    <!-- Main Footer Content -->
    <div class="liberia-footer-main">
        <div class="container">
            <div class="footer-columns">
                <section class="footer-column footer-about">
                    <div class="footer">
                        <h3 class="footer-title">About <?php echo $footer_escape($footer_shop_name); ?></h3>
                        <p><?php echo $footer_escape($footer_settings['shop_description'] ?? 'Shop products online in Liberia.'); ?></p>
                        <ul class="footer-links footer-contact-links">
                            <?php if (!empty($footer_settings['shop_address'])): ?>
                            <li><a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo rawurlencode((string)$footer_settings['shop_address']); ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-map-marker"></i><?php echo $footer_escape($footer_settings['shop_address']); ?></a></li>
                            <?php endif; ?>
                            <?php if ($footer_shop_phone !== ''): ?>
                            <li><a href="tel:<?php echo $footer_escape($footer_phone_uri); ?>"><i class="fa fa-phone"></i><?php echo $footer_escape($footer_shop_phone); ?></a></li>
                            <?php endif; ?>
                            <?php if ($footer_shop_email !== ''): ?>
                            <li><a href="mailto:<?php echo $footer_escape($footer_shop_email); ?>"><i class="fa fa-envelope-o"></i><?php echo $footer_escape($footer_shop_email); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </section>

                <section class="footer-column">
                    <div class="footer">
                        <h3 class="footer-title">Quick Links</h3>
                        <ul class="footer-links">
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/about">About Us</a></li>
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/contact">Contact Us</a></li>
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/faq">FAQ</a></li>
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/shipping">Shipping Policy</a></li>
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/returns">Returns &amp; Refunds</a></li>
                            <li><a href="<?php echo $footer_escape($footer_base_path); ?>/privacy">Privacy Policy</a></li>
                        </ul>
                    </div>
                </section>

                <section class="footer-column">
                    <div class="footer">
                        <h3 class="footer-title">Shop Categories</h3>
                        <ul class="footer-links">
                            <?php foreach ($footer_categories as $footer_category_route): ?>
                                <?php if (isset($footer_category_names[$footer_category_route['id']])): ?>
                                    <li><a href="<?php echo $footer_escape($footer_base_path); ?>/products.php?cat_id=<?php echo (int)$footer_category_route['id']; ?>"><?php echo $footer_escape($footer_category_names[$footer_category_route['id']]); ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <section class="footer-column footer-connect">
                    <div class="footer">
                        <h3 class="footer-title">Stay Connected</h3>
                        <div class="newsletter-signup">
                            <p>Subscribe for updates on local deals</p>
                            <div class="newsletter-input">
                                <input type="email" placeholder="Your email address" id="newsletter-email" aria-label="Your email address">
                                <button type="button" onclick="subscribeNewsletter()">Subscribe</button>
                            </div>
                        </div>

                        <div class="social-links" aria-label="Social media and messaging links">
                            <?php foreach ($footer_social_links as $social_name => $social_url): ?>
                                <?php $social_icon = strtolower($social_name); ?>
                                <a href="<?php echo $footer_escape($social_url); ?>" aria-label="<?php echo $footer_escape($social_name); ?>" title="<?php echo $footer_escape($social_name); ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-<?php echo $footer_escape($social_icon); ?>"></i></a>
                            <?php endforeach; ?>
                            <a href="<?php echo $footer_escape($footer_whatsapp_link); ?>" aria-label="WhatsApp" title="WhatsApp" target="_blank" rel="noopener noreferrer"><i class="fa fa-whatsapp"></i></a>
                            <a href="<?php echo $footer_escape($footer_messenger_link); ?>" aria-label="Messenger" title="Messenger" target="_blank" rel="noopener noreferrer"><i class="fa fa-facebook"></i></a>
                        </div>

                        <div class="emergency-contact">
                            <strong>Customer Support</strong>
                            <span>Contact us about your order</span><br>
                            <?php if ($footer_shop_phone !== ''): ?>
                                <a href="tel:<?php echo $footer_escape($footer_phone_uri); ?>"><?php echo $footer_escape($footer_shop_phone); ?></a>
                            <?php else: ?>
                                <a href="<?php echo $footer_escape($footer_base_path); ?>/contact">Contact the store</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Payment Methods & Copyright -->
        </div>
    </div>
    <div class="liberia-footer-bottom">
        <div class="container">
                    <div class="liberia-badges">
                        <span class="liberia-badge">🇱🇷 Proudly Liberian</span>
                        <span class="liberia-badge">Order support available</span>
                        <span class="liberia-badge">Delivery details confirmed at checkout</span>
                        <span class="liberia-badge">💵 LRD & USD Accepted</span>
                    </div>

                    <p class="copyright">Available payment options are shown during checkout.</p>

                    <span class="copyright">
                        Copyright &copy;<script>document.write(new Date().getFullYear());</script> 
                        <?php echo $footer_escape($footer_shop_name); ?>. All rights reserved. | 
                        Made with <i class="fa fa-heart-o" style="color:#e74c3c"></i> for Liberia by 
                        <a href="https://github.com/archiegonwoe" target="_blank" rel="noopener noreferrer">Archie K Gonwoe Jr</a>
                    </span>
        </div>
    </div>
</footer>

<nav class="mobile-tabbar" aria-label="Quick navigation">
    <a href="index.php" class="mobile-tab">
        <i class="fa fa-home" aria-hidden="true"></i><span>Home</span>
    </a>
    <a href="store.php" class="mobile-tab">
        <i class="fa fa-th-large" aria-hidden="true"></i><span>Shop</span>
    </a>
    <a href="wishlist.php" class="mobile-tab">
        <i class="fa fa-heart-o" aria-hidden="true"></i>
        <span class="mobile-tab-badge" id="mobile-wishlist-badge">0</span><span>Saved</span>
    </a>
    <a href="cart.php" class="mobile-tab">
        <i class="fa fa-shopping-bag" aria-hidden="true"></i>
        <span class="badge mobile-tab-badge">0</span><span>Cart</span>
    </a>
    <a href="<?php echo isset($_SESSION['uid']) ? 'myorders.php' : 'signin_form.php'; ?>" class="mobile-tab">
        <i class="fa fa-user-o" aria-hidden="true"></i><span>Account</span>
    </a>
</nav>

<!-- JavaScript Files -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/slick.min.js"></script>
<script src="js/nouislider.min.js"></script>
<script src="js/jquery.zoom.min.js"></script>
<script src="js/main.js?v=<?php echo filemtime(__DIR__ . '/js/main.js'); ?>"></script>
<script src="js/actions.js?v=<?php echo filemtime(__DIR__ . '/js/actions.js'); ?>"></script>
<script src="js/sweetalert.min.js"></script>
<script src="js/jquery.payform.min.js" charset="utf-8"></script>
<script src="js/script.js"></script>

<!-- Enhanced JavaScript -->
<script>
// Toast Notification Function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const desc = document.getElementById('desc');
    
    // Set message and style based on type
    desc.textContent = message;
    
    switch(type) {
        case 'success':
            toast.style.background = 'linear-gradient(135deg, #27ae60 0%, #2ecc71 100%)';
            break;
        case 'error':
            toast.style.background = 'linear-gradient(135deg, #c0392b 0%, #e74c3c 100%)';
            break;
        case 'warning':
            toast.style.background = 'linear-gradient(135deg, #f39c12 0%, #f1c40f 100%)';
            break;
        default:
            toast.style.background = 'linear-gradient(135deg, #002855 0%, #e74c3c 100%)';
    }
    
    toast.className = 'show';
    setTimeout(function(){ toast.className = toast.className.replace('show', ''); }, 5000);
}

// Newsletter Subscription
function subscribeNewsletter() {
    const email = document.getElementById('newsletter-email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
    }
    
    // Simulate API call
    showToast('Thank you for subscribing!', 'success');
    document.getElementById('newsletter-email').value = '';
}

// Mobile Menu Function
var c = 0;
function menu() {
    if (c % 2 == 0) {
        document.querySelector('.cont_drobpdown_menu').className = "cont_drobpdown_menu active";    
        document.querySelector('.cont_icon_trg').className = "cont_icon_trg active";    
        c++; 
    } else {
        document.querySelector('.cont_drobpdown_menu').className = "cont_drobpdown_menu disable";        
        document.querySelector('.cont_icon_trg').className = "cont_icon_trg disable";        
        c++;
    }
}

// Enhanced Cart and Wishlist Functions
$('.block2-btn-addcart').each(function(){
    var nameProduct = $(this).parent().parent().parent().find('.block2-name').html();
    $(this).on('click', function(){
        showToast(nameProduct + ' added to cart!', 'success');
    });
});

$('.block2-btn-addwishlist').each(function(){
    var nameProduct = $(this).parent().parent().parent().find('.block2-name').html();
    $(this).on('click', function(){
        showToast(nameProduct + ' added to wishlist!', 'success');
    });
});

// Liberia Time Display
function updateLiberiaTime() {
    const now = new Date();
    const liberiaTime = now.toLocaleString('en-US', {
        timeZone: 'Africa/Monrovia',
        hour12: true,
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // You can display this somewhere in the footer if needed
    console.log('Liberia Time:', liberiaTime);
}

// Update time every minute
setInterval(updateLiberiaTime, 60000);
updateLiberiaTime();

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Show toast on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        showToast('Welcome to Liberia Commerce Hub! 🎉');
    }, 1000);
});
</script>