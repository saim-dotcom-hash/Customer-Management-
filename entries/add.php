<?php
/**
 * Add Customer Entry Page
 * Customer Management System
 */

$pageTitle = "Add Customer Entry";
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db = Database::getConnection();

// Fetch active dropdowns
$powers = getActivePowers();
$lensTypes = getActiveLensTypes();
$frameTypes = getActiveFrameTypes();

$error = '';
$prefillCustomerId = (int)($_GET['customer_id'] ?? 0);
$prefillName = '';
$prefillMobile = '';

if ($prefillCustomerId > 0) {
    $custStmt = $db->prepare("SELECT name, mobile FROM customers WHERE id = ?");
    $custStmt->execute([$prefillCustomerId]);
    $prefillCust = $custStmt->fetch();
    if ($prefillCust) {
        $prefillName = $prefillCust['name'];
        $prefillMobile = $prefillCust['mobile'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $powerId = !empty($_POST['power_id']) ? (int)$_POST['power_id'] : null;
        $lensTypeId = !empty($_POST['lens_type_id']) ? (int)$_POST['lens_type_id'] : null;
        $frameTypeId = !empty($_POST['frame_type_id']) ? (int)$_POST['frame_type_id'] : null;
        $entryDate = trim($_POST['entry_date'] ?? date('Y-m-d'));
        $price = (float)($_POST['price'] ?? 0);
        $advanced = (float)($_POST['advanced'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if (empty($name)) {
            $error = "Customer Name is required.";
        } elseif (empty($mobile) || !preg_match('/^[0-9+\-\s]{7,15}$/', $mobile)) {
            $error = "Please enter a valid mobile number (7-15 digits).";
        } elseif (empty($entryDate)) {
            $error = "Date is required.";
        } elseif ($price < 0) {
            $error = "Price must be a valid non-negative number.";
        } elseif ($advanced < 0) {
            $error = "Advanced payment must be a valid non-negative number.";
        } elseif ($advanced > $price) {
            $error = "Advanced payment cannot be greater than Price!";
        } else {
            // Server-side Balance Calculation
            $balance = calculateBalance($price, $advanced);
            $status = determineStatus($balance);

            $db->beginTransaction();
            try {
                // Find existing customer or create new one without duplication
                $customerId = findOrCreateCustomer($name, $mobile);

                // Insert Customer Entry
                $stmt = $db->prepare("INSERT INTO customer_entries 
                    (customer_id, power_id, lens_type_id, frame_type_id, entry_date, price, advanced, balance, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$customerId, $powerId, $lensTypeId, $frameTypeId, $entryDate, $price, $advanced, $balance, $status, $notes]);
                $entryId = $db->lastInsertId();

                // If advanced payment > 0, log initial payment entry
                if ($advanced > 0) {
                    $payStmt = $db->prepare("INSERT INTO payments (customer_entry_id, customer_id, amount, payment_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $adminUser = $_SESSION['admin_username'] ?? 'Admin';
                    $payStmt->execute([$entryId, $customerId, $advanced, $entryDate, 'Initial Advance Payment', $adminUser]);
                }

                $db->commit();

                setFlash('success', "Customer Entry #{$entryId} added successfully!");
                header("Location: ../customers/view.php?id=" . $customerId);
                exit();
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Failed to save entry: " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Add Customer Entry</h3>
        <p class="text-muted m-0">Create an optical order entry. Existing customers are automatically detected by mobile.</p>
    </div>
    <a href="../customers/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Customer List
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Dynamic Existing Customer Found Alert Banner -->
<div id="existing-customer-alert" class="alert alert-info d-none shadow-sm mb-4" role="alert">
    <!-- Populated dynamically via JS AJAX -->
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form id="customer-entry-form" action="add.php" method="POST" autocomplete="off">
            <?php echo csrfField(); ?>

            <!-- Section 1: Customer Info -->
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person me-2 text-primary"></i> 1. Customer Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['name'] ?? $prefillName); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="mobile" name="mobile" required placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($_POST['mobile'] ?? $prefillMobile); ?>">
                    <small class="text-muted fs-7">If this mobile already exists, entry will attach to existing profile.</small>
                </div>
            </div>

            <hr class="my-4 text-muted">

            <!-- Section 2: Optical Details -->
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-eyeglasses me-2 text-primary"></i> 2. Optical Prescription & Specifications</h5>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label for="power_id" class="form-label">Power Option <span class="text-danger">*</span></label>
                    <select class="form-select" id="power_id" name="power_id" required>
                        <option value="">-- Select Power --</option>
                        <?php foreach ($powers as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (isset($_POST['power_id']) && $_POST['power_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="lens_type_id" class="form-label">Lens Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="lens_type_id" name="lens_type_id" required>
                        <option value="">-- Select Lens Type --</option>
                        <?php foreach ($lensTypes as $lt): ?>
                            <option value="<?php echo $lt['id']; ?>" <?php echo (isset($_POST['lens_type_id']) && $_POST['lens_type_id'] == $lt['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lt['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="frame_type_id" class="form-label">Frame Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="frame_type_id" name="frame_type_id" required>
                        <option value="">-- Select Frame Type --</option>
                        <?php foreach ($frameTypes as $ft): ?>
                            <option value="<?php echo $ft['id']; ?>" <?php echo (isset($_POST['frame_type_id']) && $_POST['frame_type_id'] == $ft['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ft['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-4 text-muted">

            <!-- Section 3: Financial Details & Date -->
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack me-2 text-primary"></i> 3. Order Date & Pricing</h5>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <label for="entry_date" class="form-label">Entry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="entry_date" name="entry_date" required value="<?php echo htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label for="price" class="form-label">Price (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control font-monospace fw-bold" id="price" name="price" required placeholder="0.00" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label for="advanced" class="form-label">Advanced Payment (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control font-monospace fw-bold text-success" id="advanced" name="advanced" required placeholder="0.00" value="<?php echo htmlspecialchars($_POST['advanced'] ?? '0.00'); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label for="balance" class="form-label">Calculated Balance (₹)</label>
                    <input type="text" class="form-control font-monospace fw-bold text-danger bg-light" id="balance" name="balance" readonly value="0.00">
                    <small id="balance-warning" class="text-danger fw-bold d-none"></small>
                </div>
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label">Notes / Prescription Remarks (Optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Enter special instructions or remarks..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="../customers/index.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Customer Entry
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
