<?php
/**
 * Pending Balances Page
 * Customer Management System
 */

$pageTitle = "Pending Balances";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Fetch all customers having total balance > 0
$stmt = $db->query("
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        c.mobile as customer_mobile,
        COUNT(ce.id) as total_entries,
        SUM(ce.price) as total_price,
        SUM(ce.advanced) as total_advanced,
        SUM(ce.balance) as total_pending_balance,
        MAX(ce.entry_date) as last_entry_date
    FROM customers c
    JOIN customer_entries ce ON c.id = ce.customer_id
    GROUP BY c.id, c.name, c.mobile
    HAVING total_pending_balance > 0
    ORDER BY total_pending_balance DESC
");
$pendingCustomers = $stmt->fetchAll();

// Overall Total Pending Amount
$totalPendingSum = array_sum(array_column($pendingCustomers, 'total_pending_balance'));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 text-dark">Pending Customer Balances</h3>
        <p class="text-muted m-0">Overview of customers with unpaid optical balance statements</p>
    </div>
    <div class="header-summary-badge bg-danger text-white fs-6 py-2 px-3 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>Total Outstanding: <strong><?php echo formatCurrency($totalPendingSum); ?></strong></span>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Total Billed</th>
                    <th>Advanced Paid</th>
                    <th>Pending Balance</th>
                    <th>Last Entry Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingCustomers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                            Great news! No customers currently have pending balances.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingCustomers as $index => $row): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $index + 1; ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $row['customer_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($row['customer_mobile']); ?></td>
                            <td><?php echo formatCurrency($row['total_price']); ?></td>
                            <td class="text-success"><?php echo formatCurrency($row['total_advanced']); ?></td>
                            <td class="fw-bold text-danger fs-6">
                                <?php echo formatCurrency($row['total_pending_balance']); ?>
                            </td>
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($row['last_entry_date'])); ?></td>
                            <td class="text-end">
                                <a href="view.php?id=<?php echo $row['customer_id']; ?>" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="bi bi-eye me-1"></i> View Statement
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
