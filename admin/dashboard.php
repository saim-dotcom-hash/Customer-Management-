<?php
/**
 * Admin Dashboard Page
 * Customer Management System
 */

$pageTitle = "Dashboard";
require_once __DIR__ . '/../includes/header.php';

$stats = getDashboardStats();
$db = Database::getConnection();

// Fetch Recent 10 Customer Entries
$recentStmt = $db->query("
    SELECT 
        ce.id, ce.entry_date, ce.price, ce.advanced, ce.balance, ce.status,
        c.id as customer_id, c.name as customer_name, c.mobile as customer_mobile,
        lt.name as lens_type_name,
        ft.name as frame_type_name
    FROM customer_entries ce
    JOIN customers c ON ce.customer_id = c.id
    LEFT JOIN lens_types lt ON ce.lens_type_id = lt.id
    LEFT JOIN frame_types ft ON ce.frame_type_id = ft.id
    ORDER BY ce.id DESC
    LIMIT 10
");
$recentEntries = $recentStmt->fetchAll();

// Fetch Top Customers with Pending Balances
$pendingStmt = $db->query("
    SELECT 
        c.id as customer_id, c.name as customer_name, c.mobile as customer_mobile,
        SUM(ce.price) as total_price,
        SUM(ce.advanced) as total_advanced,
        SUM(ce.balance) as pending_balance,
        MAX(ce.entry_date) as last_entry_date
    FROM customers c
    JOIN customer_entries ce ON c.id = ce.customer_id
    GROUP BY c.id, c.name, c.mobile
    HAVING pending_balance > 0
    ORDER BY pending_balance DESC
    LIMIT 10
");
$pendingCustomers = $pendingStmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Dashboard Overview</h3>
        <p class="text-muted m-0">Real-time financial and optical customer metrics</p>
    </div>
    <div>
        <a href="../entries/add.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-1"></i> New Customer Entry</a>
    </div>
</div>

<!-- 1. Summary Cards Grid -->
<div class="row g-4 mb-4">
    <!-- Total Customers -->
    <div class="col-12 col-md-6">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Customers</span>
                    <h2 class="fw-bold text-dark mt-2 mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                </div>
                <div class="icon-box bg-light-primary p-3 fs-2">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Amount -->
    <div class="col-12 col-md-6">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Amount</span>
                    <h2 class="fw-bold text-primary mt-2 mb-0"><?php echo formatCurrency($stats['total_price']); ?></h2>
                    <small class="text-muted">Total Pending Balance: <strong class="text-danger"><?php echo formatCurrency($stats['total_balance']); ?></strong></small>
                </div>
                <div class="icon-box bg-light-success p-3 fs-2">
                    <i class="bi bi-currency-rupee"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 2. Recent Entries Table (Latest 10) -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i> Recent Customer Entries</h5>
                <a href="../entries/index.php" class="btn btn-sm btn-outline-primary fw-semibold">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Lens / Frame</th>
                            <th>Date</th>
                            <th>Price</th>
                            <th>Advanced</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentEntries)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No customer entries found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentEntries as $index => $row): ?>
                                <tr>
                                    <td class="fw-semibold">#<?php echo $row['id']; ?></td>
                                    <td>
                                        <a href="../customers/view.php?id=<?php echo $row['customer_id']; ?>" class="fw-semibold text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($row['customer_name']); ?>
                                        </a>
                                    </td>
                                    <td class="font-monospace small"><?php echo htmlspecialchars($row['customer_mobile']); ?></td>
                                    <td class="small text-muted">
                                        <?php echo htmlspecialchars($row['lens_type_name'] ?? 'N/A'); ?> / 
                                        <?php echo htmlspecialchars($row['frame_type_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="small"><?php echo date('d M Y', strtotime($row['entry_date'])); ?></td>
                                    <td><?php echo formatCurrency($row['price']); ?></td>
                                    <td class="text-success"><?php echo formatCurrency($row['advanced']); ?></td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Pending Customers Widget -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-exclamation-octagon text-danger me-2"></i> Outstanding Balances</h5>
                <a href="../customers/pending.php" class="btn btn-sm btn-outline-danger fw-semibold">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingCustomers)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                        No pending balances found! All accounts cleared.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pendingCustomers as $pc): ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="../customers/view.php?id=<?php echo $pc['customer_id']; ?>" class="fw-bold text-dark text-decoration-none d-block">
                                        <?php echo htmlspecialchars($pc['customer_name']); ?>
                                    </a>
                                    <small class="text-muted font-monospace"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($pc['customer_mobile']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-danger d-block"><?php echo formatCurrency($pc['pending_balance']); ?></span>
                                    <a href="../customers/view.php?id=<?php echo $pc['customer_id']; ?>" class="btn btn-xs btn-light border px-2 py-0 fs-7 mt-1">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
