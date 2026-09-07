<?php
/**
 * Admin Profile & Password Management Page
 * Customer Management System
 */

$pageTitle = "Admin Profile";
require_once __DIR__ . '/../includes/header.php';

$adminId = $_SESSION['admin_id'];
$db = Database::getConnection();

// Fetch current admin info
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$infoError = '';
$passError = '';

// Handle Profile Update Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    if (!verifyCsrfToken()) {
        $infoError = "Invalid security token.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $infoError = "Please enter a valid Username and Email address.";
        } else {
            // Check uniqueness
            $checkStmt = $db->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
            $checkStmt->execute([$username, $email, $adminId]);
            if ($checkStmt->fetch()) {
                $infoError = "Username or Email address is already taken by another account.";
            } else {
                $updateStmt = $db->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                $updateStmt->execute([$username, $email, $adminId]);
                
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;

                setFlash('success', 'Profile updated successfully.');
                header("Location: profile.php");
                exit();
            }
        }
    }
}

// Handle Password Change Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_password'])) {
    if (!verifyCsrfToken()) {
        $passError = "Invalid security token.";
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $passError = "Please fill in all password fields.";
        } elseif (!password_verify($currentPassword, $admin['password'])) {
            $passError = "Current password is incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $passError = "New password and confirm password do not match.";
        } elseif (strlen($newPassword) < 6) {
            $passError = "New password must be at least 6 characters long.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updatePassStmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $updatePassStmt->execute([$hashedPassword, $adminId]);

            setFlash('success', 'Password changed successfully!');
            header("Location: profile.php");
            exit();
        }
    }
}
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">Admin Profile Settings</h3>
    <p class="text-muted m-0">Manage account credentials and login security</p>
</div>

<div class="row g-4">
    <!-- Profile Details Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-person-circle me-2 text-primary"></i> Account Details</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($infoError)): ?>
                    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($infoError); ?></div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action_update_profile" value="1">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($admin['username']); ?>">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($admin['email']); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-save me-1"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-shield-lock me-2 text-warning"></i> Change Password</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($passError)): ?>
                    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($passError); ?></div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action_change_password" value="1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Enter current password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="At least 6 characters">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
                    </div>

                    <button type="submit" class="btn btn-warning fw-semibold text-dark">
                        <i class="bi bi-key me-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
