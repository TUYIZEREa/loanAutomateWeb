<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';
require_once __DIR__ . '/../includes/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ' . url('login.php'));
    exit;
}

$db = Database::getInstance();
$success = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $messageId = $_POST['message_id'] ?? 0;
        $newStatus = $_POST['status'] ?? '';
        
        if ($messageId && in_array($newStatus, ['new', 'read', 'replied', 'closed'])) {
            $query = "UPDATE contact_messages SET status = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("si", $newStatus, $messageId);
            
            if ($stmt->execute()) {
                $success = 'Message status updated successfully';
            } else {
                $error = 'Failed to update message status';
            }
        }
    }
}

// Get contact messages with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM contact_messages $whereClause";
if ($params) {
    $stmt = $db->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalResult = $stmt->get_result();
} else {
    $totalResult = $db->query($countQuery);
}
$totalMessages = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalMessages / $limit);

// Get messages
$query = "SELECT * FROM contact_messages $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Contact Messages</h1>
        <div>
            <span class="badge bg-primary"><?php echo $totalMessages; ?> Total Messages</span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, email, subject...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="<?php echo url('admin/contact_messages.php'); ?>" class="btn btn-secondary d-block w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Contact Messages</h5>
        </div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p class="text-muted">No contact messages found</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
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
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td>#<?php echo $message['id']; ?></td>
                                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($message['status']) {
                                                'new' => 'danger',
                                                'read' => 'warning',
                                                'replied' => 'success',
                                                'closed' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($message['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $message['id']; ?>, 'new')">Mark as New</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $message['id']; ?>, 'read')">Mark as Read</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $message['id']; ?>, 'replied')">Mark as Replied</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $message['id']; ?>, 'closed')">Mark as Closed</a></li>
                                        </ul>
                                    </td>
                                </tr>

                                <!-- Message Detail Modal -->
                                <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Message #<?php echo $message['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?><br>
                                                        <strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?><br>
                                                        <?php if ($message['phone']): ?>
                                                            <strong>Phone:</strong> <?php echo htmlspecialchars($message['phone']); ?><br>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Subject:</strong> <?php echo htmlspecialchars($message['subject']); ?><br>
                                                        <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?><br>
                                                        <strong>Status:</strong> 
                                                        <span class="badge bg-<?php 
                                                            echo match($message['status']) {
                                                                'new' => 'danger',
                                                                'read' => 'warning',
                                                                'replied' => 'success',
                                                                'closed' => 'secondary',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($message['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div>
                                                    <strong>Message:</strong><br>
                                                    <div class="mt-2 p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo urlencode($message['subject']); ?>" 
                                                   class="btn btn-primary">
                                                    <i class="fas fa-reply"></i> Reply via Email
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Contact messages pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateStatus(messageId, status) {
    if (confirm('Are you sure you want to update the status of this message?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="message_id" value="${messageId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
