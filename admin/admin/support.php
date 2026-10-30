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

// Handle support ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_response'])) {
        $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
        $response = trim($_POST['response'] ?? '');
        $status = $_POST['status'] ?? '';
        $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (!$ticket_id || $response === '' || !in_array($status, $valid_statuses, true)) {
            $_SESSION['error'] = "Enter a response and choose a valid ticket status.";
        } else {
            $admin_name = mysqli_real_escape_string($con, $_SESSION['admin_name']);
            $admin_result = mysqli_query($con, "SELECT admin_id FROM admin_info WHERE admin_name = '$admin_name' LIMIT 1");
            $admin = $admin_result ? mysqli_fetch_assoc($admin_result) : null;
            $admin_id = $admin ? (int)$admin['admin_id'] : 'NULL';
            $ticket_id = (int)$ticket_id;
            $escaped_response = mysqli_real_escape_string($con, $response);
            $escaped_status = mysqli_real_escape_string($con, $status);
            $query = "INSERT INTO support_replies (ticket_id, admin_id, message, created_at)
                      VALUES ('$ticket_id', $admin_id, '$escaped_response', NOW())";

            if (mysqli_query($con, $query)) {
                $update_query = "UPDATE support_tickets
                                 SET status = '$escaped_status', last_reply_at = NOW(), updated_at = NOW()
                                 WHERE ticket_id = '$ticket_id'";
                if (mysqli_query($con, $update_query)) {
                    $_SESSION['success'] = "Response added successfully!";
                } else {
                    $_SESSION['error'] = "Response was saved, but ticket status could not be updated: " . mysqli_error($con);
                }
            } else {
                $_SESSION['error'] = "Error adding response: " . mysqli_error($con);
            }
        }
    }
}

$ticket_query = "SELECT t.ticket_id AS id, t.subject, t.description AS message, t.status, t.priority,
                        t.created_at, u.first_name, u.last_name, u.email
                 FROM support_tickets t
                 LEFT JOIN user_info u ON t.user_id = u.user_id
                 ORDER BY t.created_at DESC";
$ticket_result = mysqli_query($con, $ticket_query);
$support_tickets = [];
while ($ticket = mysqli_fetch_assoc($ticket_result)) {
    $ticket['customer_name'] = trim(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? ''));
    $ticket['customer_email'] = $ticket['email'] ?? '';
    $ticket['responses'] = [];
    $support_tickets[$ticket['id']] = $ticket;
}

$response_query = "SELECT r.ticket_id, r.message AS response, r.created_at, a.admin_name
                   FROM support_replies r
                   LEFT JOIN admin_info a ON r.admin_id = a.admin_id
                   ORDER BY r.created_at";
$response_result = mysqli_query($con, $response_query);
while ($response = mysqli_fetch_assoc($response_result)) {
    if (isset($support_tickets[$response['ticket_id']])) {
        $response['admin_name'] = $response['admin_name'] ?? 'Admin';
        $support_tickets[$response['ticket_id']]['responses'][] = $response;
    }
}
$support_tickets = array_values($support_tickets);
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
                            <i class="material-icons text-white">support_agent</i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">Customer Support</h1>
                            <p class="page-subtitle text-muted mb-0">Manage customer support tickets and inquiries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-danger badge-pill p-2">
                        <i class="material-icons mr-1" style="font-size:16px;">warning</i>
                        <?php echo count(array_filter($support_tickets, fn($ticket) => $ticket['status'] === 'open')); ?> Open Tickets
                    </span>
                </div>
            </div>
        </div>

        <!-- Support Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?php echo count($support_tickets); ?></h4>
                        <p class="mb-0">Total Tickets</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?php echo count(array_filter($support_tickets, fn($ticket) => $ticket['status'] === 'open')); ?></h4>
                        <p class="mb-0">Open</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?php echo count(array_filter($support_tickets, fn($ticket) => $ticket['status'] === 'in_progress')); ?></h4>
                        <p class="mb-0">In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?php echo count(array_filter($support_tickets, fn($ticket) => $ticket['status'] === 'resolved')); ?></h4>
                        <p class="mb-0">Resolved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Tickets -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="material-icons mr-2 text-primary">list</i>
                    Support Tickets
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Ticket ID</th>
                                <th>Customer</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($support_tickets as $ticket): 
                                $status_class = [
                                    'open' => 'warning',
                                    'in_progress' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary'
                                ][$ticket['status']] ?? 'secondary';
                                
                                $priority_class = [
                                    'low' => 'secondary',
                                    'medium' => 'info',
                                    'high' => 'warning',
                                    'urgent' => 'danger'
                                ][$ticket['priority']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>#<?php echo (int)$ticket['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($ticket['customer_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ticket['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $priority_class; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                data-toggle="modal" 
                                                data-target="#viewTicketModal"
                                                data-ticket-id="<?php echo $ticket['id']; ?>"
                                                data-customer-name="<?php echo htmlspecialchars($ticket['customer_name'], ENT_QUOTES); ?>"
                                                data-subject="<?php echo htmlspecialchars($ticket['subject'], ENT_QUOTES); ?>"
                                                data-message="<?php echo htmlspecialchars($ticket['message'], ENT_QUOTES); ?>"
                                                data-status="<?php echo $ticket['status']; ?>"
                                                data-responses="<?php echo htmlspecialchars(json_encode($ticket['responses']), ENT_QUOTES); ?>">
                                            <i class="material-icons" style="font-size:16px;">visibility</i>
                                        </button>
                                        <button class="btn btn-outline-success">
                                            <i class="material-icons" style="font-size:16px;">email</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Ticket Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Support Ticket Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="ticket-header mb-4">
                    <h6 id="ticketSubject" class="text-primary"></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small><strong>Customer:</strong> <span id="ticketCustomer"></span></small>
                        </div>
                        <div class="col-md-6">
                            <small><strong>Status:</strong> <span id="ticketStatus" class="badge"></span></small>
                        </div>
                    </div>
                </div>

                <!-- Original Message -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Original Message</h6>
                    </div>
                    <div class="card-body">
                        <p id="ticketMessage" class="mb-0"></p>
                    </div>
                </div>

                <!-- Responses -->
                <div id="ticketResponses">
                    <!-- Responses will be loaded here -->
                </div>

                <!-- Add Response Form -->
                <form method="POST" action="">
                    <input type="hidden" name="ticket_id" id="responseTicketId">
                    <div class="form-group">
                        <label>Add Response</label>
                        <textarea name="response" class="form-control" rows="4" 
                                  placeholder="Type your response here..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Update Status</label>
                                <select name="status" class="form-control" id="responseStatus">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="add_response" class="btn btn-primary btn-block">
                                    <i class="material-icons mr-2">send</i>Send Response
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(value) {
    return $('<div>').text(value || '').html();
}

// View ticket modal handler
$('#viewTicketModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var ticketId = button.data('ticket-id');
    var customerName = button.data('customer-name');
    var subject = button.data('subject');
    var message = button.data('message');
    var status = button.data('status');
    var responses = button.data('responses');
    
    var modal = $(this);
    modal.find('#ticketSubject').text(subject);
    modal.find('#ticketCustomer').text(customerName);
    modal.find('#ticketMessage').text(message);
    modal.find('#responseTicketId').val(ticketId);
    modal.find('#responseStatus').val(status);
    
    // Update status badge
    var statusClass = {
        'open': 'warning',
        'in_progress': 'info',
        'resolved': 'success',
        'closed': 'secondary'
    }[status] || 'secondary';
    
    modal.find('#ticketStatus').text(status.replace('_', ' ')).addClass('badge-' + statusClass);
    
    // Load responses
    var responsesHtml = '';
    if (responses && responses.length > 0) {
        responsesHtml = '<h6>Response History</h6>';
        responses.forEach(function(response) {
            responsesHtml += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>${escapeHtml(response.admin_name)}</strong>
                            <small class="text-muted">${escapeHtml(new Date(response.created_at).toLocaleString())}</small>
                        </div>
                        <p class="mb-0">${escapeHtml(response.response)}</p>
                    </div>
                </div>
            `;
        });
    } else {
        responsesHtml = '<div class="alert alert-info">No responses yet.</div>';
    }
    modal.find('#ticketResponses').html(responsesHtml);
});

// Auto-save response draft
let responseDraft = '';
const responseTextarea = document.querySelector('textarea[name="response"]');
if (responseTextarea) {
    responseTextarea.addEventListener('input', function() {
        responseDraft = this.value;
        // In real application, save to localStorage or send via AJAX
    });
    
    // Load draft if exists
    if (responseDraft) {
        responseTextarea.value = responseDraft;
    }
}
</script>

<?php
include "footer.php";
?>