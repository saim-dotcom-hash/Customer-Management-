<?php
/**
 * Customer List Page
 * Customer Management System
 */

$pageTitle = "Customer List";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Query Filters & Search parameters
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all'); // 'all', 'pending', 'paid'
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build SQL query
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(c.name LIKE ? OR c.mobile LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Having Clause for Balance Filter
$havingSql = "";
if ($filter === 'pending') {
    $havingSql = "HAVING total_balance > 0";
} elseif ($filter === 'paid') {
    $havingSql = "HAVING total_balance <= 0";
}

// Count total matching records for pagination
$countSql = "
    SELECT COUNT(*) FROM (
        SELECT c.id, COALESCE(SUM(ce.balance), 0) as total_balance
        FROM customers c
        LEFT JOIN customer_entries ce ON c.id = ce.customer_id
        {$whereSql}
        GROUP BY c.id, c.name, c.mobile
        {$havingSql}
    ) as subquery
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCustomers = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $limit);

// Fetch paginated customer records with aggregated totals
$dataSql = "
    SELECT 
        c.id, c.name, c.mobile, c.created_at,
        COUNT(ce.id) as total_entries,
        COALESCE(SUM(ce.price), 0) as total_price,
        COALESCE(SUM(ce.advanced), 0) as total_advanced,
        COALESCE(SUM(ce.balance), 0) as total_balance
    FROM customers c
    LEFT JOIN customer_entries ce ON c.id = ce.customer_id
    {$whereSql}
    GROUP BY c.id, c.name, c.mobile, c.created_at
    {$havingSql}
    ORDER BY c.id DESC
    LIMIT {$limit} OFFSET {$offset}
";
$dataStmt = $db->prepare($dataSql);
$dataStmt->execute($params);
$customers = $dataStmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Customer Directory</h3>
        <p class="text-muted m-0">Manage customer accounts and aggregate balance statements</p>
    </div>
    <div>
        <a href="../entries/add.php" class="btn btn-primary shadow-sm"><i class="bi bi-person-plus-fill me-1"></i> Add Customer Entry</a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="index.php" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" name="search" placeholder="Search by customer name or mobile..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select" name="filter" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Balances</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Balances Only</option>
                    <option value="paid" <?php echo $filter === 'paid' ? 'selected' : ''; ?>>Paid / Fully Cleared Only</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <a href="index.php" class="btn btn-light border text-muted">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Customer List Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Entries</th>
                    <th>Total Billed</th>
                    <th>Total Advanced</th>
                    <th>Total Balance</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                            No customers found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $index => $row): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $row['id']; ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $row['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($row['mobile']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['total_entries']; ?> orders</span></td>
                            <td class="fw-semibold"><?php echo formatCurrency($row['total_price']); ?></td>
                            <td class="text-success fw-semibold"><?php echo formatCurrency($row['total_advanced']); ?></td>
                            <td>
                                <?php if ($row['total_balance'] > 0): ?>
                                    <span class="fw-bold text-danger"><?php echo formatCurrency($row['total_balance']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-status-paid"><i class="bi bi-check-circle me-1"></i> Cleared</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-light border" title="View Customer Details & History">
                                        <i class="bi bi-eye text-primary me-1"></i> View
                                    </a>
                                    <a href="../entries/add.php?customer_id=<?php echo $row['id']; ?>" class="btn btn-light border" title="Add New Entry for Customer">
                                        <i class="bi bi-plus-circle text-success me-1"></i> Entry
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-light border" title="Edit Customer Profile">
                                        <i class="bi bi-pencil me-1"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-light border text-danger" onclick="return confirm('Are you sure you want to delete this customer? All associated entries and payment records will be permanently removed!');" title="Delete Customer">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing <?php echo count($customers); ?> of <?php echo $totalCustomers; ?> customers</span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
