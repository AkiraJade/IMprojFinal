<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Handle message status updates
if (isset($_POST['update_status']) && isset($_POST['message_id']) && isset($_POST['status'])) {
    $message_id = intval($_POST['message_id']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $message_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: messages.php?updated=1");
    exit();
}

// Handle message deletion
if (isset($_POST['delete_message']) && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: messages.php?deleted=1");
    exit();
}

// Get all messages, newest first
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-container">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">

        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Customer Messages</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="export_messages.php" class="btn btn-sm btn-outline-secondary">Export to CSV</a>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Message status updated successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Message deleted successfully!</div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($message = $messages->fetch_assoc()): ?>
                        <tr>
                            <td><?= $message['id'] ?></td>
                            <td><?= htmlspecialchars($message['name']) ?></td>
                            <td><?= htmlspecialchars($message['email']) ?></td>
                            <td><?= htmlspecialchars($message['subject']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $message['status'] === 'replied' ? 'success' : 
                                    ($message['status'] === 'read' ? 'info' : 'secondary') 
                                ?>">
                                    <?= ucfirst($message['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#messageModal<?= $message['id'] ?>">
                                    View
                                </button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <input type="hidden" name="status" value="read">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-info">Mark as Read</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <button type="submit" name="delete_message" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Message Modal -->
                        <div class="modal fade" id="messageModal<?= $message['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Message from <?= htmlspecialchars($message['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>From:</strong> <?= htmlspecialchars($message['name']) ?> &lt;<?= htmlspecialchars($message['email']) ?>&gt;<br>
                                            <?php if (!empty($message['phone'])): ?>
                                                <strong>Phone:</strong> <?= htmlspecialchars($message['phone']) ?><br>
                                            <?php endif; ?>
                                            <strong>Date:</strong> <?= date('F j, Y \a\t g:i A', strtotime($message['created_at'])) ?><br>
                                            <strong>Status:</strong> 
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                <select name="status" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="unread" <?= $message['status'] === 'unread' ? 'selected' : '' ?>>Unread</option>
                                                    <option value="read" <?= $message['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                                                    <option value="replied" <?= $message['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                                                </select>
                                            </form>
                                        </div>
                                        <div class="card">
                                            <div class="card-header">
                                                <strong>Subject:</strong> <?= htmlspecialchars($message['subject']) ?>
                                            </div>
                                            <div class="card-body">
                                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="mailto:<?= htmlspecialchars($message['email']) ?>?subject=RE: <?= urlencode($message['subject']) ?>" class="btn btn-primary">
                                            <i class="fas fa-reply"></i> Reply via Email
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
    </main>
</div>

<style>
/* Message specific styles */
.message-actions {
    white-space: nowrap;
}

.message-actions .btn {
    margin-right: 5px;
}

.unread {
    font-weight: bold;
    background-color: #f8f9fa;
}

.message-modal .modal-body {
    white-space: pre-wrap;
}

/* Responsive table */
.table-responsive {
    overflow-x: auto;
}

/* Status badges */
.badge {
    padding: 0.5em 0.8em;
    font-size: 0.8em;
    font-weight: 600;
    border-radius: 4px;
}

.badge-unread {
    background-color: #e74a3b;
    color: white;
}

.badge-read {
    background-color: #36b9cc;
    color: white;
}

.badge-replied {
    background-color: #1cc88a;
    color: white;
}

/* Action buttons */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Modal styles */
.message-details dt {
    font-weight: 600;
    margin-top: 10px;
}

.message-details dd {
    margin-bottom: 15px;
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 4px;
}
</style>

<?php include '../../includes/footer.php'; ?>
