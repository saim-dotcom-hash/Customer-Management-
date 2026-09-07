<?php
/**
 * Payment History Audit Log Page
 * Customer Management System
 */

$pageTitle = "Payment History";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$search = trim($_GET['search'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Where conditions
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(c.name LIKE ? OR c.mobile LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (!empty($fromDate)) {
    $whereClauses[] = "p.payment_date >= ?";
    $params[] = $fromDate;
}
if (!empty($toDate)) {
    $whereClauses[] = "p.payment_date <= ?";
    $params[] = $toDate;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count Total
$countStmt = $db->prepare("
    SELECT COUNT(*), COALESCE(SUM(p.amount), 0) as total_received
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    {$whereSql}
");
$countStmt->execute($params);
$totals = $countStmt->fetch();
$totalRecords = (int)($totals['COUNT(*)'] ?? 0);
$totalReceivedSum = (float)($totals['total_received'] ?? 0);
$totalPages = ceil($totalRecords / $limit);

// Fetch Payments
$stmt = $db->prepare("
    SELECT 
        p.*,
        c.name as customer_name, c.mobile as customer_mobile
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    {$whereSql}
    ORDER BY p.id DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Payment History Log</h3>
        <p class="text-muted m-0">Audit log of all payments and advances collected from customers</p>
    </div>
    <div class="header-summary-badge bg-success text-white py-2 px-3 shadow-sm fs-6">
        <i class="bi bi-cash-coin me-1"></i> Total Collected: <strong><?php echo formatCurrency($totalReceivedSum); ?></strong>
    </div>
</div>

<!-- Search & Date Filter Form -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="history.php" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Customer Name or Mobile..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" placeholder="From Date">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" placeholder="To Date">
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <a href="history.php" class="btn btn-light border text-muted">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Payment History Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Entry #</th>
                    <th>Payment Amount</th>
                    <th>Payment Date</th>
                    <th>Notes / Method</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No payment records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $p['id']; ?></td>
                            <td>
                                <a href="../customers/view.php?id=<?php echo $p['customer_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($p['customer_name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($p['customer_mobile']); ?></td>
                            <td>
                                <a href="../entries/view.php?id=<?php echo $p['customer_entry_id']; ?>" class="badge bg-light text-dark border">
                                    Entry #<?php echo $p['customer_entry_id']; ?>
                                </a>
                            </td>
                            <td class="fw-bold text-success font-monospace fs-6"><?php echo formatCurrency($p['amount']); ?></td>
                            <td class="small"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                            <td class="small text-muted"><?php echo htmlspecialchars($p['notes'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($p['created_by']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing <?php echo count($payments); ?> of <?php echo $totalRecords; ?> payment records</span>
            <nav>
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="history.php?search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                        <li class="page-item <?php echo ($pg === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="history.php?search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>&page=<?php echo $pg; ?>"><?php echo $pg; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="history.php?search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>&page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
