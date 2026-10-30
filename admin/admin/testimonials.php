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
    if (isset($_POST['add_testimonial'])) {
        $name = mysqli_real_escape_string($con, $_POST['name']);
        $position = mysqli_real_escape_string($con, $_POST['position']);
        $company = mysqli_real_escape_string($con, $_POST['company']);
        $content = mysqli_real_escape_string($con, $_POST['content']);
        $rating = mysqli_real_escape_string($con, $_POST['rating']);
        $status = mysqli_real_escape_string($con, $_POST['status']);

        $query = "INSERT INTO testimonials (name, position, company, content, rating, status) 
                 VALUES ('$name', '$position', '$company', '$content', '$rating', '$status')";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Testimonial added successfully!";
        } else {
            $_SESSION['error'] = "Error adding testimonial: " . mysqli_error($con);
        }
    }
    
    if (isset($_POST['update_status'])) {
        $testimonial_id = mysqli_real_escape_string($con, $_POST['testimonial_id']);
        $status = mysqli_real_escape_string($con, $_POST['status']);
        
        $query = "UPDATE testimonials SET status = '$status' WHERE id = '$testimonial_id'";
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Testimonial status updated!";
        } else {
            $_SESSION['error'] = "Error updating testimonial: " . mysqli_error($con);
        }
    }
}

// Create testimonials table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    company VARCHAR(255),
    content TEXT NOT NULL,
    rating INT DEFAULT 5,
    status ENUM('approved', 'pending', 'rejected') DEFAULT 'pending',
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
                        <div class="header-icon-container bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3">
                            <i class="material-icons text-white">format_quote</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Testimonials</h1>
                            <p class="page-subtitle text-muted mb-0">Manage customer testimonials and reviews</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-warning" data-toggle="modal" data-target="#addTestimonialModal">
                        <i class="material-icons mr-2">add</i>Add Testimonial
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM testimonials WHERE status = 'approved'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Approved</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">check_circle</i>
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
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM testimonials WHERE status = 'pending'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">schedule</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">
                                    <?php
                                    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM testimonials WHERE status = 'rejected'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h4>
                                <p class="mb-0">Rejected</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">cancel</i>
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
                                    $result = mysqli_query($con, "SELECT AVG(rating) as avg FROM testimonials WHERE status = 'approved'");
                                    $data = mysqli_fetch_assoc($result);
                                    echo round($data['avg'], 1);
                                    ?>
                                </h4>
                                <p class="mb-0">Avg Rating</p>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="material-icons" style="font-size: 40px;">star</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testimonials Grid -->
        <div class="row">
            <?php
            $query = "SELECT * FROM testimonials ORDER BY created_at DESC";
            $result = mysqli_query($con, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while($testimonial = mysqli_fetch_assoc($testimonial)):
                    $status_class = [
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger'
                    ][$testimonial['status']] ?? 'secondary';
            ?>
            <div class="col-md-6 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="card-title mb-1"><?php echo $testimonial['name']; ?></h6>
                                <p class="text-muted mb-0">
                                    <?php 
                                    echo $testimonial['position'];
                                    if ($testimonial['company']) {
                                        echo ' at ' . $testimonial['company'];
                                    }
                                    ?>
                                </p>
                            </div>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst($testimonial['status']); ?>
                            </span>
                        </div>
                        
                        <!-- Star Rating -->
                        <div class="mb-3">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $testimonial['rating']) {
                                    echo '<i class="material-icons text-warning" style="font-size: 18px;">star</i>';
                                } else {
                                    echo '<i class="material-icons text-muted" style="font-size: 18px;">star_border</i>';
                                }
                            }
                            ?>
                        </div>
                        
                        <p class="card-text">
                            <i class="material-icons text-muted mr-2" style="font-size: 16px;">format_quote</i>
                            <?php echo $testimonial['content']; ?>
                        </p>
                        
                        <div class="testimonial-meta">
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-primary btn-sm" 
                                    data-toggle="modal" 
                                    data-target="#editTestimonialModal"
                                    data-testimonial-id="<?php echo $testimonial['id']; ?>"
                                    data-status="<?php echo $testimonial['status']; ?>">
                                Edit Status
                            </button>
                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            } else {
                echo '<div class="col-12 text-center py-5">
                        <i class="material-icons display-4 text-muted">format_quote</i>
                        <h4 class="text-muted mt-3">No testimonials found</h4>
                        <p class="text-muted">Add your first testimonial to get started</p>
                      </div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Add Testimonial Modal -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Testimonial</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="Customer name">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="position" class="form-control" 
                                       placeholder="Job position">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Company</label>
                                <input type="text" name="company" class="form-control" 
                                       placeholder="Company name">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating" class="form-control">
                            <option value="5">5 Stars - Excellent</option>
                            <option value="4">4 Stars - Very Good</option>
                            <option value="3">3 Stars - Good</option>
                            <option value="2">2 Stars - Fair</option>
                            <option value="1">1 Star - Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Testimonial Content *</label>
                        <textarea name="content" class="form-control" rows="4" required 
                                  placeholder="What did the customer say?"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending Review</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_testimonial" class="btn btn-warning">Add Testimonial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editTestimonialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Testimonial Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="testimonial_id" id="edit_testimonial_id">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" id="edit_status">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.testimonial-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>

<script>
// Edit testimonial modal handler
$('#editTestimonialModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var testimonialId = button.data('testimonial-id');
    var status = button.data('status');
    
    var modal = $(this);
    modal.find('#edit_testimonial_id').val(testimonialId);
    modal.find('#edit_status').val(status);
});
</script>

<?php
include "footer.php";
?>