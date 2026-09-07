<?php
/**
 * Admin Login Page
 * Customer Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Security token mismatch. Please refresh and try again.";
    } else {
        $usernameOrEmail = trim($_POST['username_email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($usernameOrEmail) || empty($password)) {
            $error = "Please enter both Username/Email and Password.";
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerate session id to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];

                setFlash('success', 'Welcome back, ' . htmlspecialchars($admin['username']) . '!');
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username/email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Customer Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
            <i class="bi bi-eye-fill fs-2"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Admin Panel</h4>
        <p class="text-muted small">Customer & Balance Management System</p>
    </div>

    <?php echo displayFlashMessages(); ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
        <?php echo csrfField(); ?>
        
        <div class="mb-3">
            <label for="username_email" class="form-label">Username or Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                <input type="text" class="form-control" id="username_email" name="username_email" required autofocus placeholder="admin or admin@example.com" value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm">
            <i class="bi bi-box-arrow-in-right me-2"></i> Log In
        </button>
    </form>

    <div class="mt-4 pt-3 border-top text-center text-muted fs-7">
        <small class="d-block text-secondary">Default Credentials:</small>
        <code>Username: <strong>admin</strong> | Password: <strong>admin123</strong></code>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
