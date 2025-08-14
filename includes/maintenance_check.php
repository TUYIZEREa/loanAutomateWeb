<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utilities.php';

function checkMaintenanceMode() {
    $db = Database::getInstance();
    $auth = new Auth();
    
    // Get maintenance mode status
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'";
    $result = $db->query($query);
    $maintenance_mode = $result->fetch_assoc()['setting_value'] ?? '0';

    // If maintenance mode is enabled and user is not an admin, show maintenance page
    if ($maintenance_mode === '1' && (!$auth->isLoggedIn() || !$auth->hasRole('admin'))) {
        require_once __DIR__ . '/../templates/header.php';
        ?>
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-tools fa-4x text-warning mb-3"></i>
                            <h2>System Maintenance</h2>
                            <p class="lead">We are currently performing system maintenance.</p>
                            <p>Please check back later. We apologize for any inconvenience.</p>
                            <?php if ($auth->isLoggedIn()): ?>
                                <a href="<?php echo url('logout.php'); ?>" class="btn btn-primary">Logout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require_once __DIR__ . '/../templates/footer.php';
        exit;
    }
}

// Call the function when this file is included
checkMaintenanceMode(); 