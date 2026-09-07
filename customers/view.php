<?php
/**
 * Customer Details & Financial History Page
 * Customer Management System
 */

$pageTitle = "Customer Details";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$customerId = (int)($_GET['id'] ?? 0);

if ($customerId <= 0) {
    setFlash('error', 'Invalid customer ID.');
    header("Location: index.php");
    exit();
}

// Fetch Customer Profile
$custStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$custStmt->execute([$customerId]);
$customer = $custStmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header("Location: index.php");
    exit();
}

// Fetch Customer Financial Summary
$financials = getCustomerFinancialSummary($customerId);

// Fetch All Entries for Customer
$entriesStmt = $db->prepare("
    SELECT 
        ce.*,
        p.name as power_name,
        lt.name as lens_type_name,
        ft.name as frame_type_name
    FROM customer_entries ce
    LEFT JOIN powers p ON ce.power_id = p.id
    LEFT JOIN lens_types lt ON ce.lens_type_id = lt.id
    LEFT JOIN frame_types ft ON ce.frame_type_id = ft.id
    WHERE ce.customer_id = ?
    ORDER BY ce.id DESC
");
$entriesStmt->execute([$customerId]);
$entries = $entriesStmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($customer['name']); ?></h3>
        <p class="text-muted m-0"><i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($customer['mobile']); ?> | Registered on <?php echo date('d M Y', strtotime($customer['created_at'])); ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="../print/statement.php?id=<?php echo $customer['id']; ?>" target="_blank" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Statement
        </a>
        <a href="../entries/add.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> New Entry
        </a>
    </div>
</div>

<!-- Financial Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Billed (All Orders)</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo formatCurrency($financials['total_price']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Advanced Paid</span>
            <h3 class="fw-bold text-success mt-1 mb-0"><?php echo formatCurrency($financials['total_advanced']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Outstanding Pending Balance</span>
            <h3 class="fw-bold <?php echo ($financials['total_balance'] > 0) ? 'text-danger' : 'text-muted'; ?> mt-1 mb-0">
                <?php echo formatCurrency($financials['total_balance']); ?>
            </h3>
        </div>
    </div>
</div>

<!-- Customer Entries Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold m-0 text-dark"><i class="bi bi-journal-text text-primary me-2"></i> Optical Orders & Entries (<?php echo count($entries); ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Power</th>
                    <th>Lens Type</th>
                    <th>Frame Type</th>
                    <th>Price</th>
                    <th>Advanced</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No optical entries found for this customer.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $entry['id']; ?></td>
                            <td class="small"><?php echo date('d M Y', strtotime($entry['entry_date'])); ?></td>
                            <td class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($entry['power_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($entry['lens_type_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($entry['frame_type_name'] ?? 'N/A'); ?></td>
                            <td><?php echo formatCurrency($entry['price']); ?></td>
                            <td class="text-success"><?php echo formatCurrency($entry['advanced']); ?></td>
                            <td class="fw-bold <?php echo ($entry['balance'] > 0) ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo formatCurrency($entry['balance']); ?>
                            </td>
                            <td>
                                <?php if ($entry['status'] === 'Paid'): ?>
                                    <span class="badge badge-status-paid"><i class="bi bi-check-circle me-1"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-status-pending"><i class="bi bi-exclamation-circle me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($entry['balance'] > 0): ?>
                                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#payModal<?php echo $entry['id']; ?>" title="Record Additional Payment">
                                            <i class="bi bi-cash me-1"></i> Pay
                                        </button>
                                    <?php endif; ?>
                                    <a href="../entries/view.php?id=<?php echo $entry['id']; ?>" class="btn btn-light border" title="View Details">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                    <a href="../entries/edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-light border" title="Edit Entry">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="../entries/delete.php?id=<?php echo $entry['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-light border text-danger" onclick="return confirm('Delete this entry?');" title="Delete Entry">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Record Payment Modal for Entry #<?php echo $entry['id']; ?> -->
                        <div class="modal fade" id="payModal<?php echo $entry['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="../payments/add.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Record Payment (Entry #<?php echo $entry['id']; ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <div class="modal-body">
                                            <div class="bg-light p-3 rounded mb-3">
                                                <div class="row text-center">
                                                    <div class="col-4 border-end">
                                                        <small class="text-muted d-block">Price</small>
                                                        <span class="fw-bold"><?php echo formatCurrency($entry['price']); ?></span>
                                                    </div>
                                                    <div class="col-4 border-end">
                                                        <small class="text-muted d-block">Paid So Far</small>
                                                        <span class="fw-bold text-success"><?php echo formatCurrency($entry['advanced']); ?></span>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted d-block">Current Balance</small>
                                                        <span class="fw-bold text-danger"><?php echo formatCurrency($entry['balance']); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Payment Amount (₹) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" max="<?php echo $entry['balance']; ?>" class="form-control font-monospace fw-bold text-success fs-5" name="amount" required placeholder="0.00" value="<?php echo $entry['balance']; ?>">
                                                <small class="text-muted">Maximum recordable amount: <?php echo formatCurrency($entry['balance']); ?></small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Payment Date</label>
                                                <input type="date" class="form-control" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notes / Payment Method (Optional)</label>
                                                <input type="text" class="form-control" name="notes" placeholder="e.g. Cash, UPI, GPay, Card">
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-check-lg me-1"></i> Record Payment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
