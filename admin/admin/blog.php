<?php
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
    if (isset($_POST['create_post'])) {
        $title = mysqli_real_escape_string($con, $_POST['title']);
        $slug = mysqli_real_escape_string($con, $_POST['slug']);
        $content = mysqli_real_escape_string($con, $_POST['content']);
        $excerpt = mysqli_real_escape_string($con, $_POST['excerpt']);
        $meta_title = mysqli_real_escape_string($con, $_POST['meta_title']);
        $meta_description = mysqli_real_escape_string($con, $_POST['meta_description']);
        $status = mysqli_real_escape_string($con, $_POST['status']);
        $featured = isset($_POST['featured']) ? 1 : 0;

        $query = "INSERT INTO blog_posts (title, slug, content, excerpt, meta_title, meta_description, status, featured) 
                 VALUES ('$title', '$slug', '$content', '$excerpt', '$meta_title', '$meta_description', '$status', '$featured')";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Blog post created successfully!";
        } else {
            $_SESSION['error'] = "Error creating blog post: " . mysqli_error($con);
        }
    }
}

// Create blog table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    featured_image VARCHAR(500),
    status ENUM('published', 'draft') DEFAULT 'draft',
    featured TINYINT DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);
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
                            <i class="material-icons text-white">article</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Blog Management</h1>
                            <p class="page-subtitle text-muted mb-0">Create and manage blog posts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-success" data-toggle="modal" data-target="#createPostModal">
                        <i class="material-icons mr-2">add</i>New Post
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM blog_posts");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Total Posts</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">article</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Published</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">visibility</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'draft'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Drafts</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">edit</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT SUM(view_count) as total FROM blog_posts");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'] ?: '0';
                                    ?>
                                </h4>
                                <p class="mb-0">Total Views</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">visibility</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blog Posts -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-success">list</i>
                    Blog Posts
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Views</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM blog_posts ORDER BY created_at DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($post = mysqli_fetch_assoc($post)):
                                    $status_class = [
                                        'published' => 'success',
                                        'draft' => 'warning'
                                    ][$post['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong><?php echo $post['title']; ?></strong>
                                            <br>
                                            <small class="text-muted">/blog/<?php echo $post['slug']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($post['featured']): ?>
                                    <i class="material-icons text-warning">star</i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-light"><?php echo $post['view_count']; ?></span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($post['updated_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-outline-primary">
                                            <i class="material-icons" style="font-size:16px;">visibility</i>
                                        </a>
                                        <a href="#" class="btn btn-outline-warning">
                                            <i class="material-icons" style="font-size:16px;">edit</i>
                                        </a>
                                        <a href="#" class="btn btn-outline-danger">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-4">No blog posts found. Create your first post to get started.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Blog Post</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Post Title *</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="Enter post title">
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" class="form-control" required 
                               placeholder="post-slug">
                    </div>
                    <div class="form-group">
                        <label>Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="3" 
                                  placeholder="Brief description of the post"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Content *</label>
                        <textarea name="content" class="form-control" rows="10" required 
                                  placeholder="Write your blog post content..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       placeholder="SEO meta title">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3" 
                                  placeholder="SEO meta description"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="featured" class="form-check-input" id="featured">
                        <label class="form-check-label" for="featured">Featured Post</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="create_post" class="btn btn-success">Create Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from title
document.querySelector('input[name="title"]').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    document.querySelector('input[name="slug"]').value = slug;
});

// Auto-generate meta title from post title
document.querySelector('input[name="title"]').addEventListener('blur', function() {
    const title = this.value;
    if (title && !document.querySelector('input[name="meta_title"]').value) {
        document.querySelector('input[name="meta_title"]').value = title + ' - TechShop Liberia';
    }
});
</script>

<?php
include "footer.php";
?>