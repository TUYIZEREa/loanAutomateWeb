<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ' . url('login.php'));
    exit;
}

$db = Database::getInstance();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_address' => trim($_POST['company_address'] ?? ''),
        'company_phone' => trim($_POST['company_phone'] ?? ''),
        'company_email' => trim($_POST['company_email'] ?? ''),
        'default_interest_rate' => floatval($_POST['default_interest_rate'] ?? 10),
        'max_loan_amount' => floatval($_POST['max_loan_amount'] ?? 10000),
        'min_loan_amount' => floatval($_POST['min_loan_amount'] ?? 100),
        'max_loan_duration' => intval($_POST['max_loan_duration'] ?? 24),
        'min_loan_duration' => intval($_POST['min_loan_duration'] ?? 1),
        'risk_assessment_threshold' => intval($_POST['risk_assessment_threshold'] ?? 700),
        'enable_auto_approval' => isset($_POST['enable_auto_approval']) ? 1 : 0,
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
        'smtp_port' => trim($_POST['smtp_port'] ?? ''),
        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
        'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls')
    ];

    // Validate settings
    if (empty($settings['company_name'])) {
        $error = 'Company name is required';
    } elseif (!filter_var($settings['company_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid company email format';
    } elseif ($settings['min_loan_amount'] >= $settings['max_loan_amount']) {
        $error = 'Minimum loan amount must be less than maximum loan amount';
    } elseif ($settings['min_loan_duration'] >= $settings['max_loan_duration']) {
        $error = 'Minimum loan duration must be less than maximum loan duration';
    } else {
        // Update settings
        foreach ($settings as $key => $value) {
            $query = "INSERT INTO settings (setting_key, setting_value) 
                      VALUES (?, ?) 
                      ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('sss', $key, $value, $value);
            $stmt->execute();
        }

        // Update SMTP password if provided
        if (!empty($_POST['smtp_password'])) {
            $smtp_password = password_hash($_POST['smtp_password'], PASSWORD_DEFAULT);
            $query = "INSERT INTO settings (setting_key, setting_value) 
                      VALUES ('smtp_password', ?) 
                      ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ss', $smtp_password, $smtp_password);
            $stmt->execute();
        }

        $success = 'Settings updated successfully';
    }
}

// Get current settings
$query = "SELECT * FROM settings";
$result = $db->query($query);
$current_settings = [];
while ($row = $result->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Settings Menu</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#company" class="list-group-item list-group-item-action" data-bs-toggle="pill">Company Information</a>
                    <a href="#loan" class="list-group-item list-group-item-action" data-bs-toggle="pill">Loan Settings</a>
                    <a href="#email" class="list-group-item list-group-item-action" data-bs-toggle="pill">Email Settings</a>
                    <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="pill">System Settings</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Settings</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="tab-content">
                            <!-- Company Information -->
                            <div class="tab-pane fade show active" id="company">
                                <h5 class="mb-4">Company Information</h5>
                                
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo htmlspecialchars($current_settings['company_name'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="company_address" class="form-label">Company Address</label>
                                    <textarea class="form-control" id="company_address" name="company_address" rows="3"
                                            ><?php echo htmlspecialchars($current_settings['company_address'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="company_phone" class="form-label">Company Phone</label>
                                    <input type="tel" class="form-control" id="company_phone" name="company_phone"
                                           value="<?php echo htmlspecialchars($current_settings['company_phone'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="company_email" class="form-label">Company Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email"
                                           value="<?php echo htmlspecialchars($current_settings['company_email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- Loan Settings -->
                            <div class="tab-pane fade" id="loan">
                                <h5 class="mb-4">Loan Settings</h5>

                                <div class="alert alert-info">
                                    <strong>Note:</strong> The interest rate below will be automatically applied to all new loan requests. 
                                    Interest is calculated on a simple interest basis and added to the principal amount when loans are approved.
                                </div>

                                <div class="mb-3">
                                    <label for="default_interest_rate" class="form-label">Default Interest Rate (%)</label>
                                    <input type="number" class="form-control" id="default_interest_rate" name="default_interest_rate"
                                           value="<?php echo htmlspecialchars($current_settings['default_interest_rate'] ?? '10'); ?>"
                                           step="0.01" min="0" max="100" required>
                                    <div class="form-text">This rate will be applied to all new loan requests</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="min_loan_amount" class="form-label">Minimum Loan Amount</label>
                                            <input type="number" class="form-control" id="min_loan_amount" name="min_loan_amount"
                                                   value="<?php echo htmlspecialchars($current_settings['min_loan_amount'] ?? '100'); ?>"
                                                   step="1" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_loan_amount" class="form-label">Maximum Loan Amount</label>
                                            <input type="number" class="form-control" id="max_loan_amount" name="max_loan_amount"
                                                   value="<?php echo htmlspecialchars($current_settings['max_loan_amount'] ?? '10000'); ?>"
                                                   step="1" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="min_loan_duration" class="form-label">Minimum Loan Duration (months)</label>
                                            <input type="number" class="form-control" id="min_loan_duration" name="min_loan_duration"
                                                   value="<?php echo htmlspecialchars($current_settings['min_loan_duration'] ?? '1'); ?>"
                                                   step="1" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_loan_duration" class="form-label">Maximum Loan Duration (months)</label>
                                            <input type="number" class="form-control" id="max_loan_duration" name="max_loan_duration"
                                                   value="<?php echo htmlspecialchars($current_settings['max_loan_duration'] ?? '24'); ?>"
                                                   step="1" min="1" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="risk_assessment_threshold" class="form-label">Risk Assessment Threshold</label>
                                    <input type="number" class="form-control" id="risk_assessment_threshold" name="risk_assessment_threshold"
                                           value="<?php echo htmlspecialchars($current_settings['risk_assessment_threshold'] ?? '700'); ?>"
                                           step="1" min="0" max="1000" required>
                                    <div class="form-text">Minimum credit score required for automatic loan approval</div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="enable_auto_approval" name="enable_auto_approval"
                                               <?php echo ($current_settings['enable_auto_approval'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_auto_approval">Enable Automatic Loan Approval</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Settings -->
                            <div class="tab-pane fade" id="email">
                                <h5 class="mb-4">Email Settings</h5>

                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                           value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="text" class="form-control" id="smtp_port" name="smtp_port"
                                           value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                           value="<?php echo htmlspecialchars($current_settings['smtp_username'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                           placeholder="Leave blank to keep current password">
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls" <?php echo ($current_settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($current_settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo ($current_settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>

                            <!-- System Settings -->
                            <div class="tab-pane fade" id="system">
                                <h5 class="mb-4">System Settings</h5>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode"
                                               <?php echo ($current_settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                    </div>
                                    <div class="form-text">When enabled, only administrators can access the system</div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap pills
    var triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="pill"]'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });

    // Show first tab by default
    var firstTab = document.querySelector('[data-bs-toggle="pill"]');
    if (firstTab) {
        var tab = new bootstrap.Tab(firstTab);
        tab.show();
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?> 