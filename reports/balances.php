<?php
/**
 * Pending Balance Report Page
 * Customer Management System
 */

$pageTitle = "Pending Balance Report";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Query all pending customer balances
$stmt = $db->query("
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        c.mobile as customer_mobile,
        COUNT(ce.id) as pending_entries_count,
        SUM(ce.price) as total_price,
        SUM(ce.advanced) as total_advanced,
        SUM(ce.balance) as total_balance,
        MAX(ce.entry_date) as last_order_date
    FROM customers c
    JOIN customer_entries ce ON c.id = ce.customer_id
    GROUP BY c.id, c.name, c.mobile
    HAVING total_balance > 0
    ORDER BY total_balance DESC
");
$rows = $stmt->fetchAll();

$grandPending = array_sum(array_column($rows, 'total_balance'));
$grandBilled = array_sum(array_column($rows, 'total_price'));
$grandAdvanced = array_sum(array_column($rows, 'total_advanced'));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Outstanding Pending Balance Report</h3>
        <p class="text-muted m-0">Official audit statement of all customers with uncollected balances</p>
    </div>
    <button class="btn btn-outline-secondary shadow-sm btn-print-hide" onclick="printWindow()">
        <i class="bi bi-printer me-1"></i> Print Balance Report
    </button>
</div>

<!-- Summary Bar -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Customers with Pending Balance</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo count($rows); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Billed to Pending Customers</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo formatCurrency($grandBilled); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 border-danger">
            <span class="text-muted small fw-semibold text-uppercase text-danger">Grand Total Outstanding Balance</span>
            <h3 class="fw-bold text-danger mt-1 mb-0"><?php echo formatCurrency($grandPending); ?></h3>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Pending Orders</th>
                    <th>Total Price</th>
                    <th>Advanced Paid</th>
                    <th>Pending Balance</th>
                    <th>Last Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            All accounts fully cleared! Zero pending balances.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $r): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td>
                                <a href="../customers/view.php?id=<?php echo $r['customer_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($r['customer_name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($r['customer_mobile']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $r['pending_entries_count']; ?> orders</span></td>
                            <td><?php echo formatCurrency($r['total_price']); ?></td>
                            <td class="text-success"><?php echo formatCurrency($r['total_advanced']); ?></td>
                            <td class="fw-bold text-danger fs-6"><?php echo formatCurrency($r['total_balance']); ?></td>
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($r['last_order_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
