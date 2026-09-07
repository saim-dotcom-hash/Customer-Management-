<?php
/**
 * Customer Report Page
 * Customer Management System
 */

$pageTitle = "Customer Financial Report";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Overall Stats
$overallStmt = $db->query("
    SELECT 
        COUNT(DISTINCT c.id) as total_customers,
        COUNT(ce.id) as total_entries,
        COALESCE(SUM(ce.price), 0) as total_price,
        COALESCE(SUM(ce.advanced), 0) as total_advanced,
        COALESCE(SUM(ce.balance), 0) as total_balance
    FROM customers c
    LEFT JOIN customer_entries ce ON c.id = ce.customer_id
");
$overall = $overallStmt->fetch();

// Customer List Report
$listStmt = $db->query("
    SELECT 
        c.id, c.name, c.mobile,
        COUNT(ce.id) as entry_count,
        COALESCE(SUM(ce.price), 0) as total_price,
        COALESCE(SUM(ce.advanced), 0) as total_advanced,
        COALESCE(SUM(ce.balance), 0) as total_balance
    FROM customers c
    LEFT JOIN customer_entries ce ON c.id = ce.customer_id
    GROUP BY c.id, c.name, c.mobile
    ORDER BY total_price DESC
");
$reportRows = $listStmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Customer Summary Report</h3>
        <p class="text-muted m-0">Comprehensive financial audit report grouped by customer profile</p>
    </div>
    <button class="btn btn-outline-secondary shadow-sm btn-print-hide" onclick="printWindow()">
        <i class="bi bi-printer me-1"></i> Print Report
    </button>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Customers</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo number_format($overall['total_customers']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Billed</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo formatCurrency($overall['total_price']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Advanced Received</span>
            <h3 class="fw-bold text-success mt-1 mb-0"><?php echo formatCurrency($overall['total_advanced']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Pending Balance</span>
            <h3 class="fw-bold text-danger mt-1 mb-0"><?php echo formatCurrency($overall['total_balance']); ?></h3>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Total Orders</th>
                    <th>Total Billed Amount</th>
                    <th>Total Advanced Paid</th>
                    <th>Outstanding Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportRows)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No data available for report.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportRows as $idx => $r): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($r['name']); ?></td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($r['mobile']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $r['entry_count']; ?> entries</span></td>
                            <td class="fw-semibold"><?php echo formatCurrency($r['total_price']); ?></td>
                            <td class="text-success fw-semibold"><?php echo formatCurrency($r['total_advanced']); ?></td>
                            <td class="fw-bold <?php echo ($r['total_balance'] > 0) ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo formatCurrency($r['total_balance']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
