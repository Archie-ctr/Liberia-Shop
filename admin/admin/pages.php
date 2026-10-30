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
    if (isset($_POST['create_page'])) {
        $title = mysqli_real_escape_string($con, $_POST['title']);
        $slug = mysqli_real_escape_string($con, $_POST['slug']);
        $content = mysqli_real_escape_string($con, $_POST['content']);
        $meta_title = mysqli_real_escape_string($con, $_POST['meta_title']);
        $meta_description = mysqli_real_escape_string($con, $_POST['meta_description']);
        $status = mysqli_real_escape_string($con, $_POST['status']);

        $query = "INSERT INTO pages (title, slug, content, meta_title, meta_description, status) 
                 VALUES ('$title', '$slug', '$content', '$meta_title', '$meta_description', '$status')";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Page created successfully!";
        } else {
            $_SESSION['error'] = "Error creating page: " . mysqli_error($con);
        }
    }
}

// Create pages table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    status ENUM('published', 'draft') DEFAULT 'draft',
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
                        <div class="header-icon-container bg-info rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">web</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Pages Management</h1>
                            <p class="page-subtitle text-muted mb-0">Create and manage website pages</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-info" data-toggle="modal" data-target="#createPageModal">
                        <i class="material-icons mr-2">add</i>Create Page
                    </button>
                </div>
            </div>
        </div>

        <!-- Pages List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-info">list</i>
                    All Pages
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM pages ORDER BY updated_at DESC";
                            $result = mysqli_query($con, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($page = mysqli_fetch_assoc($result)):
                                    $status_class = [
                                        'published' => 'success',
                                        'draft' => 'warning'
                                    ][$page['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $page['title']; ?></strong>
                                    <?php if ($page['meta_title']): ?>
                                    <br><small class="text-muted"><?php echo $page['meta_title']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code>/<?php echo $page['slug']; ?></code>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($page['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($page['updated_at'])); ?>
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
                                echo '<tr><td colspan="5" class="text-center py-4">No pages found. Create your first page to get started.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Page Modal -->
<div class="modal fade" id="createPageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Page</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Page Title *</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="Enter page title">
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" class="form-control" required 
                               placeholder="page-slug">
                        <small class="form-text text-muted">URL-friendly version of the title</small>
                    </div>
                    <div class="form-group">
                        <label>Content *</label>
                        <textarea name="content" class="form-control" rows="10" required 
                                  placeholder="Enter page content..."></textarea>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="create_page" class="btn btn-info">Create Page</button>
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
</script>

<?php
include "footer.php";
?>