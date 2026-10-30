<?php
// help.php
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

// Handle search
$search_query = '';
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($con, $_GET['search']);
}

// Sample documentation data (in a real system, this would be in a database)
$documentation_categories = [
    'getting-started' => [
        'title' => 'Getting Started',
        'icon' => 'rocket_launch',
        'articles' => [
            [
                'id' => 1,
                'title' => 'Welcome to TechShop Admin',
                'content' => '
                    <h3>Welcome to TechShop Liberia Admin Panel</h3>
                    <p>This comprehensive guide will help you navigate and manage your online store effectively.</p>
                    
                    <h4>Key Features:</h4>
                    <ul>
                        <li><strong>Product Management</strong> - Add, edit, and manage your product catalog</li>
                        <li><strong>Order Processing</strong> - Handle customer orders and shipments</li>
                        <li><strong>Customer Management</strong> - Manage customer accounts and information</li>
                        <li><strong>Sales Analytics</strong> - Track sales performance and revenue</li>
                        <li><strong>Marketing Tools</strong> - Run promotions and manage coupons</li>
                    </ul>
                    
                    <h4>Getting Started Checklist:</h4>
                    <ol>
                        <li>Set up your shop settings</li>
                        <li>Add your product categories</li>
                        <li>Create your first products</li>
                        <li>Configure payment methods</li>
                        <li>Set up shipping options</li>
                    </ol>
                ',
                'tags' => ['welcome', 'setup', 'introduction']
            ],
            [
                'id' => 2,
                'title' => 'Admin Dashboard Overview',
                'content' => '
                    <h3>Admin Dashboard Overview</h3>
                    <p>Your dashboard provides a comprehensive overview of your store\'s performance.</p>
                    
                    <h4>Dashboard Sections:</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Sales Overview</h5>
                            <ul>
                                <li>Today\'s revenue</li>
                                <li>Monthly sales trends</li>
                                <li>Top selling products</li>
                                <li>Revenue comparison</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Quick Stats</h5>
                            <ul>
                                <li>New orders</li>
                                <li>Pending orders</li>
                                <li>Customer registrations</li>
                                <li>Low stock alerts</li>
                            </ul>
                        </div>
                    </div>
                    
                    <h4>Key Metrics:</h4>
                    <ul>
                        <li><strong>Conversion Rate:</strong> Percentage of visitors who make a purchase</li>
                        <li><strong>Average Order Value:</strong> Average amount spent per order</li>
                        <li><strong>Customer Lifetime Value:</strong> Total revenue from a customer</li>
                    </ul>
                ',
                'tags' => ['dashboard', 'analytics', 'overview']
            ]
        ]
    ],
    'products' => [
        'title' => 'Product Management',
        'icon' => 'inventory_2',
        'articles' => [
            [
                'id' => 3,
                'title' => 'Adding New Products',
                'content' => '
                    <h3>Adding New Products</h3>
                    <p>Learn how to add products to your store effectively.</p>
                    
                    <h4>Step-by-Step Guide:</h4>
                    <ol>
                        <li>Navigate to <strong>Products → Add Products</strong></li>
                        <li>Fill in the basic product information:
                            <ul>
                                <li>Product Name</li>
                                <li>Description</li>
                                <li>Price</li>
                                <li>Category</li>
                                <li>Brand</li>
                            </ul>
                        </li>
                        <li>Upload product images</li>
                        <li>Set inventory levels</li>
                        <li>Configure shipping options</li>
                        <li>Add product attributes (size, color, etc.)</li>
                        <li>Save and publish</li>
                    </ol>
                    
                    <h4>Best Practices:</h4>
                    <ul>
                        <li>Use high-quality images (minimum 800x800 pixels)</li>
                        <li>Write detailed, SEO-friendly descriptions</li>
                        <li>Set competitive pricing</li>
                        <li>Use relevant categories and tags</li>
                    </ul>
                ',
                'tags' => ['products', 'add', 'inventory']
            ],
            [
                'id' => 4,
                'title' => 'Managing Product Inventory',
                'content' => '
                    <h3>Managing Product Inventory</h3>
                    <p>Effective inventory management is crucial for your store\'s success.</p>
                    
                    <h4>Inventory Features:</h4>
                    <ul>
                        <li><strong>Stock Tracking:</strong> Monitor product quantities in real-time</li>
                        <li><strong>Low Stock Alerts:</strong> Get notified when stock runs low</li>
                        <li><strong>Inventory Reports:</strong> Generate stock level reports</li>
                        <li><strong>Bulk Updates:</strong> Update multiple products at once</li>
                    </ul>
                    
                    <h4>Inventory Tips:</h4>
                    <ul>
                        <li>Set reorder points for popular items</li>
                        <li>Regularly update stock levels</li>
                        <li>Use barcode scanning for accuracy</li>
                        <li>Monitor sales velocity</li>
                    </ul>
                ',
                'tags' => ['inventory', 'stock', 'management']
            ]
        ]
    ],
    'orders' => [
        'title' => 'Order Management',
        'icon' => 'shopping_cart',
        'articles' => [
            [
                'id' => 5,
                'title' => 'Processing Orders',
                'content' => '
                    <h3>Order Processing Guide</h3>
                    <p>Learn how to efficiently process customer orders.</p>
                    
                    <h4>Order Statuses:</h4>
                    <ul>
                        <li><span class="badge badge-warning">Pending</span> - New order awaiting processing</li>
                        <li><span class="badge badge-info">Processing</span> - Order being prepared</li>
                        <li><span class="badge badge-primary">Shipped</span> - Order has been shipped</li>
                        <li><span class="badge badge-success">Delivered</span> - Order delivered to customer</li>
                        <li><span class="badge badge-danger">Cancelled</span> - Order cancelled</li>
                    </ul>
                    
                    <h4>Order Processing Steps:</h4>
                    <ol>
                        <li>Review order details</li>
                        <li>Check inventory availability</li>
                        <li>Process payment</li>
                        <li>Prepare shipment</li>
                        <li>Update tracking information</li>
                        <li>Mark as shipped</li>
                    </ol>
                ',
                'tags' => ['orders', 'processing', 'shipping']
            ]
        ]
    ],
    'marketing' => [
        'title' => 'Marketing & Promotions',
        'icon' => 'campaign',
        'articles' => [
            [
                'id' => 6,
                'title' => 'Creating Effective Coupons',
                'content' => '
                    <h3>Creating Effective Coupons</h3>
                    <p>Drive sales with strategic coupon campaigns.</p>
                    
                    <h4>Coupon Types:</h4>
                    <ul>
                        <li><strong>Percentage Discount:</strong> X% off the total order</li>
                        <li><strong>Fixed Amount:</strong> $X off the total order</li>
                        <li><strong>Free Shipping:</strong> Free shipping on orders</li>
                        <li><strong>Buy X Get Y:</strong> Purchase requirements</li>
                    </ul>
                    
                    <h4>Best Practices:</h4>
                    <ul>
                        <li>Set clear expiration dates</li>
                        <li>Create limited-use codes</li>
                        <li>Target specific customer segments</li>
                        <li>Test different discount amounts</li>
                        <li>Promote through multiple channels</li>
                    </ul>
                    
                    <h4>Campaign Ideas:</h4>
                    <ul>
                        <li>Welcome discounts for new customers</li>
                        <li>Seasonal promotions</li>
                        <li>Loyalty rewards</li>
                        <li>Abandoned cart recovery</li>
                    </ul>
                ',
                'tags' => ['coupons', 'discounts', 'marketing']
            ],
            [
                'id' => 7,
                'title' => 'Email Marketing Strategies',
                'content' => '
                    <h3>Email Marketing Strategies</h3>
                    <p>Build customer relationships through effective email marketing.</p>
                    
                    <h4>Email Campaign Types:</h4>
                    <ul>
                        <li><strong>Welcome Series:</strong> Onboard new subscribers</li>
                        <li><strong>Promotional Emails:</strong> Share offers and sales</li>
                        <li><strong>Newsletters:</strong> Regular updates and content</li>
                        <li><strong>Abandoned Cart:</strong> Recover lost sales</li>
                        <li><strong>Re-engagement:</strong> Win back inactive customers</li>
                    </ul>
                    
                    <h4>Best Practices:</h4>
                    <ul>
                        <li>Personalize subject lines</li>
                        <li>Segment your audience</li>
                        <li>Mobile-optimized design</li>
                        <li>Clear call-to-action</li>
                        <li>Test send times</li>
                        <li>Monitor open and click rates</li>
                    </ul>
                ',
                'tags' => ['email', 'marketing', 'newsletter']
            ]
        ]
    ]
];

// Search functionality
if (!empty($search_query)) {
    foreach ($documentation_categories as $category) {
        foreach ($category['articles'] as $article) {
            if (stripos($article['title'], $search_query) !== false || 
                stripos($article['content'], $search_query) !== false ||
                in_array(strtolower($search_query), array_map('strtolower', $article['tags']))) {
                $search_results[] = $article;
            }
        }
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
                        <div class="header-icon-container bg-info rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">help</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Help & Documentation</h1>
                            <p class="page-subtitle text-muted mb-0">Complete guide to using TechShop Admin Panel</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#contactSupportModal">
                        <i class="material-icons mr-2">support_agent</i>Contact Support
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <form method="GET" action="">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form-control-lg" 
                                       placeholder="Search documentation..." value="<?php echo htmlspecialchars($search_query); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="material-icons">search</i> Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="quick-links">
                            <a href="#getting-started" class="btn btn-outline-primary btn-sm mr-2">Getting Started</a>
                            <a href="#products" class="btn btn-outline-success btn-sm mr-2">Products</a>
                            <a href="#orders" class="btn btn-outline-warning btn-sm">Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($search_query)): ?>
        <!-- Search Results -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2">search</i>
                    Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($search_results)): ?>
                    <div class="search-results">
                        <?php foreach ($search_results as $article): ?>
                        <div class="search-result-item mb-3 p-3 border rounded">
                            <h5>
                                <a href="help.php?article=<?php echo $article['id']; ?>" class="text-primary">
                                    <?php echo $article['title']; ?>
                                </a>
                            </h5>
                            <p class="text-muted mb-2">
                                <?php echo strip_tags(substr($article['content'], 0, 200)); ?>...
                            </p>
                            <div class="tags">
                                <?php foreach ($article['tags'] as $tag): ?>
                                    <span class="badge badge-light"><?php echo $tag; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="material-icons text-muted" style="font-size: 48px;">search_off</i>
                        <h5 class="text-muted">No results found</h5>
                        <p>Try different keywords or browse the categories below.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documentation Categories -->
        <div class="row">
            <?php foreach ($documentation_categories as $category_key => $category): ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-primary"><?php echo $category['icon']; ?></i>
                            <?php echo $category['title']; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="article-list">
                            <?php foreach ($category['articles'] as $article): ?>
                            <div class="article-item mb-3">
                                <h6>
                                    <a href="help.php?article=<?php echo $article['id']; ?>" class="text-dark">
                                        <?php echo $article['title']; ?>
                                    </a>
                                </h6>
                                <p class="text-muted small mb-2">
                                    <?php echo strip_tags(substr($article['content'], 0, 100)); ?>...
                                </p>
                                <div class="tags">
                                    <?php foreach ($article['tags'] as $tag): ?>
                                        <span class="badge badge-light badge-sm"><?php echo $tag; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Resources -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-warning">bolt</i>
                            Quick Resources
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="resource-item p-3">
                                    <i class="material-icons text-primary" style="font-size: 48px;">video_library</i>
                                    <h6>Video Tutorials</h6>
                                    <p class="text-muted small">Step-by-step video guides</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="resource-item p-3">
                                    <i class="material-icons text-success" style="font-size: 48px;">description</i>
                                    <h6>PDF Guides</h6>
                                    <p class="text-muted small">Downloadable documentation</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="resource-item p-3">
                                    <i class="material-icons text-info" style="font-size: 48px;">forum</i>
                                    <h6>Community Forum</h6>
                                    <p class="text-muted small">Connect with other users</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="resource-item p-3">
                                    <i class="material-icons text-danger" style="font-size: 48px;">update</i>
                                    <h6>Latest Updates</h6>
                                    <p class="text-muted small">What's new in the system</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="material-icons mr-2 text-info">help_outline</i>
                            Frequently Asked Questions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="card">
                                <div class="card-header" id="faq1">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse1">
                                            How do I reset my admin password?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse1" class="collapse show" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        To reset your admin password, contact the system administrator or use the "Forgot Password" feature on the login page. You'll receive an email with instructions to reset your password.
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header" id="faq2">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse2">
                                            How can I backup my store data?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse2" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Navigate to <strong>Tools & Utilities → Backup & Restore</strong>. From there, you can create manual backups or schedule automatic backups. We recommend backing up your data regularly.
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header" id="faq3">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse3">
                                            How do I process refunds?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse3" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Go to <strong>Sales & Finance → Payments</strong>, find the payment you want to refund, and click the refund button. Make sure to communicate with the customer before processing any refunds.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Support Modal -->
<div class="modal fade" id="contactSupportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Support</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="support-info">
                            <h6><i class="material-icons mr-2 text-primary">email</i> Email Support</h6>
                            <p>support@techshopliberia.com</p>
                            
                            <h6><i class="material-icons mr-2 text-success">phone</i> Phone Support</h6>
                            <p>+231 123 456 789</p>
                            
                            <h6><i class="material-icons mr-2 text-info">schedule</i> Support Hours</h6>
                            <p>Monday - Friday: 8:00 AM - 6:00 PM</p>
                            <p>Saturday: 9:00 AM - 2:00 PM</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form id="supportForm">
                            <div class="form-group">
                                <label>Subject *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Description *</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Priority</label>
                                <select class="form-control">
                                    <option>Low</option>
                                    <option>Medium</option>
                                    <option>High</option>
                                    <option>Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Attachment</label>
                                <input type="file" class="form-control-file">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" form="supportForm" class="btn btn-primary">Send Message</button>
            </div>
        </div>
    </div>
</div>

<style>
.article-list .article-item {
    border-left: 3px solid #4361ee;
    padding-left: 15px;
    transition: all 0.3s ease;
}

.article-list .article-item:hover {
    border-left-color: #ff4081;
    background: rgba(67, 97, 238, 0.05);
    padding-left: 20px;
}

.resource-item {
    transition: all 0.3s ease;
    border-radius: 8px;
}

.resource-item:hover {
    background: rgba(0,0,0,0.02);
    transform: translateY(-2px);
}

.search-result-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.search-result-item:hover {
    border-left-color: #4361ee;
    background: rgba(67, 97, 238, 0.05);
}

.badge-sm {
    font-size: 0.7rem;
    padding: 3px 6px;
}

.accordion .card-header {
    background: rgba(0,0,0,0.02);
}

.accordion .btn-link {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.accordion .btn-link:hover {
    color: #4361ee;
}
</style>

<script>
// Smooth scroll for quick links
document.addEventListener('DOMContentLoaded', function() {
    // Handle article viewing
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('article');
    
    if (articleId) {
        // In a real system, you would fetch the article content
        showArticleModal(articleId);
    }

    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

function showArticleModal(articleId) {
    // In a real system, you would fetch the article content from the server
    // For now, we'll show a placeholder modal
    alert('Article ID: ' + articleId + ' - This would show the full article in a real system.');
}

// Support form handling
document.getElementById('supportForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // In a real system, you would send this to your support system
    alert('Support request submitted! We will get back to you soon.');
    $('#contactSupportModal').modal('hide');
});
</script>

<?php
include "footer.php";
?>