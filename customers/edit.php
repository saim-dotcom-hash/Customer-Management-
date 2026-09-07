<?php
/**
 * Edit Customer Info Page
 * Customer Management System
 */

$pageTitle = "Edit Customer Profile";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$customerId = (int)($_GET['id'] ?? 0);

if ($customerId <= 0) {
    setFlash('error', 'Invalid customer ID.');
    header("Location: index.php");
    exit();
}

// Fetch Customer
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');

        if (empty($name)) {
            $error = "Customer Name is required.";
        } elseif (empty($mobile) || !preg_match('/^[0-9+\-\s]{7,15}$/', $mobile)) {
            $error = "Please enter a valid mobile number.";
        } else {
            // Check if mobile taken by another customer
            $checkStmt = $db->prepare("SELECT id FROM customers WHERE mobile = ? AND id != ?");
            $checkStmt->execute([$mobile, $customerId]);
            if ($checkStmt->fetch()) {
                $error = "Mobile number '{$mobile}' is already registered for another customer profile.";
            } else {
                $updateStmt = $db->prepare("UPDATE customers SET name = ?, mobile = ? WHERE id = ?");
                $updateStmt->execute([$name, $mobile, $customerId]);

                setFlash('success', 'Customer profile updated successfully.');
                header("Location: view.php?id=" . $customerId);
                exit();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Edit Customer Profile</h3>
        <p class="text-muted m-0">Update name or mobile number for Customer #<?php echo $customer['id']; ?></p>
    </div>
    <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Customer Profile
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4" style="max-width: 600px;">
        <form action="edit.php?id=<?php echo $customer['id']; ?>" method="POST">
            <?php echo csrfField(); ?>

            <div class="mb-3">
                <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? $customer['name']); ?>">
            </div>

            <div class="mb-4">
                <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="mobile" name="mobile" required value="<?php echo htmlspecialchars($_POST['mobile'] ?? $customer['mobile']); ?>">
            </div>

            <div class="d-flex gap-2">
                <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check-circle me-1"></i> Update Customer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
