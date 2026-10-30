<?php
session_start();
include("../../db.php");

// Enhanced delete functionality with confirmation
if(isset($_GET['action']) && $_GET['action']!="" && $_GET['action']=='delete')
{
    $user_id = mysqli_real_escape_string($con, $_GET['user_id']);
    
    /*this is delete query*/
    mysqli_query($con,"delete from user_info where user_id='$user_id'")or die("query is incorrect...");
    
    // Success message for deletion
    $_SESSION['success_msg'] = "User deleted successfully!";
}

include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
          <!-- Success Message -->
          <?php if(isset($_SESSION['success_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="material-icons">check_circle</i>
            <?php echo $_SESSION['success_msg']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <?php unset($_SESSION['success_msg']); ?>
          <?php endif; ?>

          <div class="col-md-14">
            <div class="card">
              <div class="card-header card-header-primary">
                <div class="row align-items-center">
                  <div class="col-md-6">
                    <h4 class="card-title">Manage Users</h4>
                    <p class="card-category">View and manage all registered users</p>
                  </div>
                  <div class="col-md-6 text-right">
                    <a href="addsuppliers.php" class="btn btn-success btn-sm">
                      <i class="material-icons">person_add</i> Add New User
                    </a>
                    <button class="btn btn-info btn-sm" onclick="exportUsers()">
                      <i class="material-icons">file_download</i> Export
                    </button>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <!-- Search and Filter Section -->
                <div class="row mb-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <input type="text" class="form-control" id="searchInput" placeholder="Search users..." onkeyup="searchUsers()">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <select class="form-control" id="sortSelect" onchange="sortUsers()">
                      <option value="">Sort by...</option>
                      <option value="name_asc">Name (A-Z)</option>
                      <option value="name_desc">Name (Z-A)</option>
                      <option value="id_asc">ID (Low-High)</option>
                      <option value="id_desc">ID (High-Low)</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <div class="form-text text-muted">
                      Total Users: 
                      <?php 
                        $count_result = mysqli_query($con, "SELECT COUNT(*) as total FROM user_info");
                        $count_data = mysqli_fetch_assoc($count_result);
                        echo '<span class="badge badge-primary">' . $count_data['total'] . '</span>';
                      ?>
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover" id="usersTable">
                    <thead class="text-primary">
                      <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                        $result = mysqli_query($con, "SELECT user_id, first_name, last_name, email, password, mobile, address1, address2 FROM user_info") 
                        or die("query 2 incorrect.......");

                        while($row = mysqli_fetch_array($result)):
                          $user_id = $row['user_id'];
                          $first_name = htmlspecialchars($row['first_name']);
                          $last_name = htmlspecialchars($row['last_name']);
                          $email = htmlspecialchars($row['email']);
                          $mobile = htmlspecialchars($row['mobile']);
                          $address1 = htmlspecialchars($row['address1']);
                          $address2 = htmlspecialchars($row['address2']);
                      ?>
                      <tr>
                        <td><?php echo $user_id; ?></td>
                        <td><?php echo $first_name; ?></td>
                        <td><?php echo $last_name; ?></td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="material-icons mr-2" style="font-size: 16px;">email</i>
                            <?php echo $email; ?>
                          </div>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="material-icons mr-2" style="font-size: 16px;">phone</i>
                            <?php echo $mobile ?: 'N/A'; ?>
                          </div>
                        </td>
                        <td><?php echo $address1 ?: 'N/A'; ?></td>
                        <td><?php echo $address2 ?: 'N/A'; ?></td>
                        <td>
                          <span class="badge badge-success">Active</span>
                        </td>
                        <td>
                          <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info btn-sm" onclick="viewUser(<?php echo $user_id; ?>)"
                                    data-toggle="tooltip" data-placement="top" title="View Details">
                              <i class="material-icons">visibility</i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user_id; ?>)"
                                    data-toggle="tooltip" data-placement="top" title="Edit User">
                              <i class="material-icons">edit</i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" 
                                    onclick="confirmDelete(<?php echo $user_id; ?>, '<?php echo $first_name . ' ' . $last_name; ?>')"
                                    data-toggle="tooltip" data-placement="top" title="Delete User">
                              <i class="material-icons">delete</i>
                            </button>
                          </div>
                        </td>
                      </tr>
                      <?php endwhile; 
                      mysqli_close($con);
                      ?>
                    </tbody>
                  </table>
                </div>

                <!-- No Results Message -->
                <div id="noResults" class="text-center py-4" style="display: none;">
                  <i class="material-icons" style="font-size: 48px; color: #ccc;">search_off</i>
                  <h5 class="text-muted mt-2">No users found</h5>
                  <p class="text-muted">Try adjusting your search criteria</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete user: <strong id="userName"></strong>?</p>
              <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Delete User</a>
            </div>
          </div>
        </div>
      </div>

<style>
.card-header {
  border-bottom: 1px solid rgba(0,0,0,0.1);
}

.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.875rem;
}

.btn-group .btn {
  margin: 0 2px;
  border-radius: 4px;
}

.badge {
  font-size: 0.75rem;
  padding: 4px 8px;
}

.table-responsive {
  border-radius: 4px;
}

.form-control {
  border-radius: 4px;
}

.alert {
  border: none;
  border-left: 4px solid;
}

.alert-success {
  border-left-color: #4caf50;
}

.material-icons {
  vertical-align: middle;
}

.table-hover tbody tr:hover {
  background-color: rgba(0,0,0,0.02);
  transform: translateY(-1px);
  transition: all 0.2s ease;
}

.card-category {
  opacity: 0.8;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .btn-group {
    display: flex;
    flex-direction: column;
  }
  
  .btn-group .btn {
    margin: 2px 0;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>

<script>
// Search functionality
function searchUsers() {
  const input = document.getElementById('searchInput');
  const filter = input.value.toLowerCase();
  const table = document.getElementById('usersTable');
  const tr = table.getElementsByTagName('tr');
  const noResults = document.getElementById('noResults');
  let found = false;

  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName('td');
    let show = false;
    
    for (let j = 0; j < td.length; j++) {
      if (td[j]) {
        const txtValue = td[j].textContent || td[j].innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
          show = true;
          break;
        }
      }
    }
    
    tr[i].style.display = show ? '' : 'none';
    if (show) found = true;
  }
  
  noResults.style.display = found ? 'none' : 'block';
}

// Sort functionality
function sortUsers() {
  // This would be implemented with more complex sorting logic
  // For now, it's a placeholder for future enhancement
  const sortValue = document.getElementById('sortSelect').value;
  if (sortValue) {
    alert('Sorting by: ' + sortValue + '\nThis feature will be implemented in the next update.');
  }
}

// Delete confirmation
function confirmDelete(userId, userName) {
  document.getElementById('userName').textContent = userName;
  document.getElementById('confirmDeleteBtn').href = 'manageuser.php?user_id=' + userId + '&action=delete';
  $('#deleteModal').modal('show');
}

// View user details
function viewUser(userId) {
  alert('View user details for ID: ' + userId + '\nThis feature will open a user details modal.');
  // Implementation for viewing user details would go here
}

// Edit user
function editUser(userId) {
  alert('Edit user with ID: ' + userId + '\nThis will redirect to user edit page.');
  // window.location.href = 'edit_user.php?id=' + userId;
}

// Export users
function exportUsers() {
  alert('Exporting users list...\nThis feature will generate an Excel/CSV file.');
  // Implementation for exporting users would go here
}

// Initialize tooltips
$(function () {
  $('[data-toggle="tooltip"]').tooltip();
});

// Auto-hide success message after 5 seconds
setTimeout(function() {
  $('.alert').alert('close');
}, 5000);
</script>

<?php
include "footer.php";
?>