<?php
/**
 * All Customer Entries List & Filter Page
 * Customer Management System
 */

$pageTitle = "Customer Entries";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Filters
$search = trim($_GET['search'] ?? '');
$powerId = !empty($_GET['power_id']) ? (int)$_GET['power_id'] : 0;
$lensTypeId = !empty($_GET['lens_type_id']) ? (int)$_GET['lens_type_id'] : 0;
$frameTypeId = !empty($_GET['frame_type_id']) ? (int)$_GET['frame_type_id'] : 0;
$statusFilter = trim($_GET['status'] ?? 'all');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE condition
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(c.name LIKE ? OR c.mobile LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($powerId > 0) {
    $whereClauses[] = "ce.power_id = ?";
    $params[] = $powerId;
}
if ($lensTypeId > 0) {
    $whereClauses[] = "ce.lens_type_id = ?";
    $params[] = $lensTypeId;
}
if ($frameTypeId > 0) {
    $whereClauses[] = "ce.frame_type_id = ?";
    $params[] = $frameTypeId;
}
if (in_array($statusFilter, ['Paid', 'Pending'])) {
    $whereClauses[] = "ce.status = ?";
    $params[] = $statusFilter;
}
if (!empty($fromDate)) {
    $whereClauses[] = "ce.entry_date >= ?";
    $params[] = $fromDate;
}
if (!empty($toDate)) {
    $whereClauses[] = "ce.entry_date <= ?";
    $params[] = $toDate;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count Total
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM customer_entries ce
    JOIN customers c ON ce.customer_id = c.id
    {$whereSql}
");
$countStmt->execute($params);
$totalEntries = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalEntries / $limit);

// Fetch Data
$dataStmt = $db->prepare("
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
    {$whereSql}
    ORDER BY ce.id DESC
    LIMIT {$limit} OFFSET {$offset}
");
$dataStmt->execute($params);
$entries = $dataStmt->fetchAll();

// Active Dropdown lists for filter dropdowns
$powers = getActivePowers();
$lensTypes = getActiveLensTypes();
$frameTypes = getActiveFrameTypes();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Customer Optical Entries</h3>
        <p class="text-muted m-0">View, search, and filter all customer prescription orders</p>
    </div>
    <a href="add.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-1"></i> Add Entry</a>
</div>

<!-- Filters Form -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="index.php" method="GET" class="row g-2">
            <div class="col-12 col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Customer Name / Mobile..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select" name="power_id">
                    <option value="0">All Powers</option>
                    <?php foreach ($powers as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($powerId == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select" name="lens_type_id">
                    <option value="0">All Lens Types</option>
                    <?php foreach ($lensTypes as $lt): ?>
                        <option value="<?php echo $lt['id']; ?>" <?php echo ($lensTypeId == $lt['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select" name="frame_type_id">
                    <option value="0">All Frame Types</option>
                    <?php foreach ($frameTypes as $ft): ?>
                        <option value="<?php echo $ft['id']; ?>" <?php echo ($frameTypeId == $ft['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ft['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <select class="form-select" name="status">
                    <option value="all">Status</option>
                    <option value="Paid" <?php echo ($statusFilter === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <a href="index.php" class="btn btn-light border text-muted">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Entries Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Entry #</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Power</th>
                    <th>Lens Type</th>
                    <th>Frame Type</th>
                    <th>Date</th>
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
                        <td colspan="12" class="text-center py-5 text-muted">No customer entries found matching filter settings.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $row): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $row['id']; ?></td>
                            <td>
                                <a href="../customers/view.php?id=<?php echo $row['customer_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($row['customer_mobile']); ?></td>
                            <td class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($row['power_name'] ?? 'N/A'); ?></td>
                            <td class="small"><?php echo htmlspecialchars($row['lens_type_name'] ?? 'N/A'); ?></td>
                            <td class="small"><?php echo htmlspecialchars($row['frame_type_name'] ?? 'N/A'); ?></td>
                            <td class="small"><?php echo date('d M Y', strtotime($row['entry_date'])); ?></td>
                            <td class="fw-semibold"><?php echo formatCurrency($row['price']); ?></td>
                            <td class="text-success fw-semibold"><?php echo formatCurrency($row['advanced']); ?></td>
                            <td class="fw-bold <?php echo ($row['balance'] > 0) ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo formatCurrency($row['balance']); ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'Paid'): ?>
                                    <span class="badge badge-status-paid"><i class="bi bi-check-circle me-1"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-status-pending"><i class="bi bi-exclamation-circle me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-light border" title="View Entry">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-light border" title="Edit Entry">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-light border text-danger" onclick="return confirm('Delete this entry?');" title="Delete Entry">
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
            <span class="text-muted small">Showing <?php echo count($entries); ?> of <?php echo $totalEntries; ?> entries</span>
            <nav>
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&power_id=<?php echo $powerId; ?>&lens_type_id=<?php echo $lensTypeId; ?>&frame_type_id=<?php echo $frameTypeId; ?>&status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&power_id=<?php echo $powerId; ?>&lens_type_id=<?php echo $lensTypeId; ?>&frame_type_id=<?php echo $frameTypeId; ?>&status=<?php echo $statusFilter; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?search=<?php echo urlencode($search); ?>&power_id=<?php echo $powerId; ?>&lens_type_id=<?php echo $lensTypeId; ?>&frame_type_id=<?php echo $frameTypeId; ?>&status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
