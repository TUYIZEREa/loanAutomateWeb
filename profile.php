<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/database.php'; // Ensure database is included for update operations

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// If for some reason user data is not found after login, redirect
if (!$user) {
    header('Location: ' . url('logout.php'));
    exit;
}

$error = '';
$success = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = PROFILE_PICS_DIR;
    // Ensure the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file_extension, $allowed_extensions)) {
        $error = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
    } elseif ($_FILES['profile_picture']['size'] > $max_file_size) {
        $error = 'File size exceeds 5MB limit.';
    } else {
        $new_file_name = $user['user_id'] . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // Update database with new profile picture path
            $query = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ss", $new_file_name, $user['user_id']);
            if ($stmt->execute()) {
                $success = 'Profile picture updated successfully.';
                // Update the session user data immediately
                $_SESSION['user']['profile_picture'] = $new_file_name;
                // Re-fetch user data to ensure latest info is used
                $user = $auth->getCurrentUser();
            } else {
                $error = 'Failed to update database: ' . $db->getConnection()->error;
            }
        } else {
            $error = 'Failed to upload file.';
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <div class="profile-pic-container mb-3">
                            <?php
                            $profile_pic_path = PROFILE_PICS_DIR . htmlspecialchars($user['profile_picture'] ?? 'default_avatar.png');
                            if (!file_exists($profile_pic_path) || is_dir($profile_pic_path)) {
                                $profile_pic_path = PROFILE_PICS_DIR . 'default_avatar.png'; // Fallback to a default avatar
                            }
                            ?>
                            <img src="<?php echo url($profile_pic_path); ?>" alt="Profile Picture"
                                class="img-thumbnail rounded-circle"
                                style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Upload Profile Picture</label>
                                <input class="form-control" type="file" id="profile_picture" name="profile_picture"
                                    accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">Max 5MB. JPG, JPEG, PNG, GIF only.</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Upload Photo</button>
                        </form>
                    </div>

                    <div class="mb-3">
                        <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Account Number:</strong> <?php echo htmlspecialchars($user['account_number']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong> <span
                            class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong> <span
                            class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst(htmlspecialchars($user['status'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                    </div>
                    <div class="mt-4">
                        <a href="change_password.php" class="btn btn-secondary">Change Password</a>
                        <!-- Add an edit profile button later if needed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>