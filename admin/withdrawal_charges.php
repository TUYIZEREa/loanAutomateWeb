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
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_charge') {
            $minAmount = floatval($_POST['min_amount']);
            $maxAmount = floatval($_POST['max_amount']);
            $chargeAmount = floatval($_POST['charge_amount']);
            $chargeType = $_POST['charge_type'];

            if ($minAmount >= $maxAmount) {
                $error = 'Minimum amount must be less than maximum amount';
            } elseif ($chargeAmount <= 0) {
                $error = 'Charge amount must be greater than 0';
            } else {
                $query = "INSERT INTO withdrawal_charges (min_amount, max_amount, charge_amount, charge_type, is_active) 
                         VALUES (?, ?, ?, ?, 1)";
                $stmt = $db->prepare($query);
                $stmt->bind_param("ddds", $minAmount, $maxAmount, $chargeAmount, $chargeType);
                
                if ($stmt->execute()) {
                    $success = 'Withdrawal charge added successfully';
                } else {
                    $error = 'Failed to add withdrawal charge';
                }
            }
        } elseif ($_POST['action'] === 'update_charge') {
            $chargeId = (int)$_POST['charge_id'];
            $minAmount = floatval($_POST['min_amount']);
            $maxAmount = floatval($_POST['max_amount']);
            $chargeAmount = floatval($_POST['charge_amount']);
            $chargeType = $_POST['charge_type'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($minAmount >= $maxAmount) {
                $error = 'Minimum amount must be less than maximum amount';
            } elseif ($chargeAmount <= 0) {
                $error = 'Charge amount must be greater than 0';
            } else {
                $query = "UPDATE withdrawal_charges SET min_amount = ?, max_amount = ?, charge_amount = ?, charge_type = ?, is_active = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("dddsii", $minAmount, $maxAmount, $chargeAmount, $chargeType, $isActive, $chargeId);
                
                if ($stmt->execute()) {
                    $success = 'Withdrawal charge updated successfully';
                } else {
                    $error = 'Failed to update withdrawal charge';
                }
            }
        } elseif ($_POST['action'] === 'delete_charge') {
            $chargeId = (int)$_POST['charge_id'];
            
            $query = "DELETE FROM withdrawal_charges WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $chargeId);
            
            if ($stmt->execute()) {
                $success = 'Withdrawal charge deleted successfully';
            } else {
                $error = 'Failed to delete withdrawal charge';
            }
        }
    }
}

// Get all withdrawal charges
$query = "SELECT * FROM withdrawal_charges ORDER BY min_amount ASC";
$result = $db->query($query);
$charges = [];
while ($row = $result->fetch_assoc()) {
    $charges[] = $row;
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Withdrawal Charges Management</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChargeModal">
            <i class="fas fa-plus"></i> Add New Charge
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Withdrawal Charges Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Withdrawal Charges Configuration</h5>
        </div>
        <div class="card-body">
            <?php if (empty($charges)): ?>
                <p class="text-muted">No withdrawal charges configured</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Min Amount</th>
                                <th>Max Amount</th>
                                <th>Charge Amount</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($charges as $charge): ?>
                                <tr>
                                    <td>FRW <?php echo number_format($charge['min_amount'], 2); ?></td>
                                    <td>FRW <?php echo number_format($charge['max_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($charge['charge_type'] === 'percentage'): ?>
                                            <?php echo $charge['charge_amount']; ?>%
                                        <?php else: ?>
                                            FRW <?php echo number_format($charge['charge_amount'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $charge['charge_type'] === 'percentage' ? 'info' : 'primary'; ?>">
                                            <?php echo ucfirst($charge['charge_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $charge['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $charge['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="editCharge(<?php echo htmlspecialchars(json_encode($charge)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteCharge(<?php echo $charge['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Charge Modal -->
<div class="modal fade" id="addChargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_charge">
                <div class="modal-header">
                    <h5 class="modal-title">Add Withdrawal Charge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_amount" class="form-label">Minimum Amount (FRW)</label>
                                <input type="number" class="form-control" id="min_amount" name="min_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_amount" class="form-label">Maximum Amount (FRW)</label>
                                <input type="number" class="form-control" id="max_amount" name="max_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="charge_amount" class="form-label">Charge Amount</label>
                                <input type="number" class="form-control" id="charge_amount" name="charge_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="charge_type" class="form-label">Charge Type</label>
                                <select class="form-select" id="charge_type" name="charge_type" required>
                                    <option value="fixed">Fixed Amount (FRW)</option>
                                    <option value="percentage">Percentage (%)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Charge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Charge Modal -->
<div class="modal fade" id="editChargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_charge">
                <input type="hidden" name="charge_id" id="edit_charge_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Withdrawal Charge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_min_amount" class="form-label">Minimum Amount (FRW)</label>
                                <input type="number" class="form-control" id="edit_min_amount" name="min_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_max_amount" class="form-label">Maximum Amount (FRW)</label>
                                <input type="number" class="form-control" id="edit_max_amount" name="max_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_charge_amount" class="form-label">Charge Amount</label>
                                <input type="number" class="form-control" id="edit_charge_amount" name="charge_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_charge_type" class="form-label">Charge Type</label>
                                <select class="form-select" id="edit_charge_type" name="charge_type" required>
                                    <option value="fixed">Fixed Amount (FRW)</option>
                                    <option value="percentage">Percentage (%)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Charge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCharge(charge) {
    document.getElementById('edit_charge_id').value = charge.id;
    document.getElementById('edit_min_amount').value = charge.min_amount;
    document.getElementById('edit_max_amount').value = charge.max_amount;
    document.getElementById('edit_charge_amount').value = charge.charge_amount;
    document.getElementById('edit_charge_type').value = charge.charge_type;
    document.getElementById('edit_is_active').checked = charge.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editChargeModal')).show();
}

function deleteCharge(chargeId) {
    if (confirm('Are you sure you want to delete this withdrawal charge?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_charge">
            <input type="hidden" name="charge_id" value="${chargeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
