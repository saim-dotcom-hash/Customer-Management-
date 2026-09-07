<?php
/**
 * Single Customer Entry View & Payment Audit Log Page
 * Customer Management System
 */

$pageTitle = "Entry Details";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$entryId = (int)($_GET['id'] ?? 0);

if ($entryId <= 0) {
    setFlash('error', 'Invalid entry ID.');
    header("Location: index.php");
    exit();
}

// Fetch Entry with Customer and Option names
$stmt = $db->prepare("
    SELECT 
        ce.*,
        c.name as customer_name, c.mobile as customer_mobile,
        p.name as power_name,
        lt.name as lens_type_name,
        ft.name as frame_type_name
    FROM customer_entries ce
    JOIN customers c ON ce.customer_id = c.id
    LEFT JOIN powers p ON ce.power_id = p.id
    LEFT JOIN lens_types lt ON ce.lens_type_id = lt.id
    LEFT JOIN frame_types ft ON ce.frame_type_id = ft.id
    WHERE ce.id = ?
");
$stmt->execute([$entryId]);
$entry = $stmt->fetch();

if (!$entry) {
    setFlash('error', 'Customer entry not found.');
    header("Location: index.php");
    exit();
}

// Fetch Payment history for this entry
$payStmt = $db->prepare("SELECT * FROM payments WHERE customer_entry_id = ? ORDER BY id ASC");
$payStmt->execute([$entryId]);
$payments = $payStmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Customer Entry #<?php echo $entry['id']; ?></h3>
        <p class="text-muted m-0">Customer: <strong><a href="../customers/view.php?id=<?php echo $entry['customer_id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($entry['customer_name']); ?></a></strong> (<?php echo htmlspecialchars($entry['customer_mobile']); ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="../customers/view.php?id=<?php echo $entry['customer_id']; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Customer Profile
        </a>
        <a href="edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Entry
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Optical Specifications Card -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-eyeglasses text-primary me-2"></i> Optical Specifications</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless m-0">
                    <tr>
                        <td class="text-muted fw-semibold ps-0">Power Option:</td>
                        <td class="font-monospace fw-bold text-primary fs-6"><?php echo htmlspecialchars($entry['power_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold ps-0">Lens Type:</td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($entry['lens_type_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold ps-0">Frame Type:</td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($entry['frame_type_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold ps-0">Order Date:</td>
                        <td><?php echo date('d M Y', strtotime($entry['entry_date'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold ps-0">Notes / Remarks:</td>
                        <td class="text-muted"><?php echo !empty($entry['notes']) ? nl2br(htmlspecialchars($entry['notes'])) : 'None'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Breakdown Card -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-calculator text-success me-2"></i> Billing & Balance Details</h5>
                <?php if ($entry['status'] === 'Paid'): ?>
                    <span class="badge badge-status-paid"><i class="bi bi-check-circle me-1"></i> Paid</span>
                <?php else: ?>
                    <span class="badge badge-status-pending"><i class="bi bi-exclamation-circle me-1"></i> Pending</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row text-center my-3">
                    <div class="col-4 border-end">
                        <span class="text-muted small fw-semibold text-uppercase d-block">Price</span>
                        <h4 class="fw-bold text-dark mt-1"><?php echo formatCurrency($entry['price']); ?></h4>
                    </div>
                    <div class="col-4 border-end">
                        <span class="text-muted small fw-semibold text-uppercase d-block">Advanced</span>
                        <h4 class="fw-bold text-success mt-1"><?php echo formatCurrency($entry['advanced']); ?></h4>
                    </div>
                    <div class="col-4">
                        <span class="text-muted small fw-semibold text-uppercase d-block">Balance</span>
                        <h4 class="fw-bold <?php echo ($entry['balance'] > 0) ? 'text-danger' : 'text-muted'; ?> mt-1">
                            <?php echo formatCurrency($entry['balance']); ?>
                        </h4>
                    </div>
                </div>

                <?php if ($entry['balance'] > 0): ?>
                    <div class="d-grid mt-4">
                        <button class="btn btn-success fw-bold py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                            <i class="bi bi-cash-coin me-1"></i> Record Additional Payment
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Audit Log -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold m-0 text-dark"><i class="bi bi-receipt text-secondary me-2"></i> Payment History Audit Log</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payment Amount</th>
                    <th>Payment Date</th>
                    <th>Notes / Method</th>
                    <th>Recorded By</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No payments logged for this entry yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $idx => $p): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td class="fw-bold text-success font-monospace fs-6"><?php echo formatCurrency($p['amount']); ?></td>
                            <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($p['notes'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['created_by']); ?></span></td>
                            <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($p['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Payment Modal -->
<?php if ($entry['balance'] > 0): ?>
    <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../payments/add.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $entry['customer_id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Record Payment (Entry #<?php echo $entry['id']; ?>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="<?php echo $entry['balance']; ?>" class="form-control font-monospace fw-bold text-success fs-5" name="amount" required placeholder="0.00" value="<?php echo $entry['balance']; ?>">
                            <small class="text-muted">Max recordable: <?php echo formatCurrency($entry['balance']); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes / Payment Method</label>
                            <input type="text" class="form-control" name="notes" placeholder="e.g. Cash, UPI, GPay, Card">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
