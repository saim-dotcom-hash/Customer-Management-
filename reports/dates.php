<?php
/**
 * Date Range Financial Report Page
 * Customer Management System
 */

$pageTitle = "Date Range Report";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

// Default date range: current month 1st to today
$fromDate = trim($_GET['from_date'] ?? date('Y-m-01'));
$toDate = trim($_GET['to_date'] ?? date('Y-m-d'));

// Query financial aggregate totals for date range
$sumStmt = $db->prepare("
    SELECT 
        COUNT(ce.id) as total_entries,
        COALESCE(SUM(ce.price), 0) as total_price,
        COALESCE(SUM(ce.advanced), 0) as total_advanced,
        COALESCE(SUM(ce.balance), 0) as total_balance
    FROM customer_entries ce
    WHERE ce.entry_date >= ? AND ce.entry_date <= ?
");
$sumStmt->execute([$fromDate, $toDate]);
$totals = $sumStmt->fetch();

// Query detailed entries for date range
$entriesStmt = $db->prepare("
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
    WHERE ce.entry_date >= ? AND ce.entry_date <= ?
    ORDER BY ce.entry_date ASC, ce.id ASC
");
$entriesStmt->execute([$fromDate, $toDate]);
$entries = $entriesStmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Date Range Financial Report</h3>
        <p class="text-muted m-0">Generate financial totals and billing breakdowns for specific date ranges</p>
    </div>
    <button class="btn btn-outline-secondary shadow-sm btn-print-hide" onclick="printWindow()">
        <i class="bi bi-printer me-1"></i> Print Report
    </button>
</div>

<!-- Date Filter Form -->
<div class="card border-0 shadow-sm rounded-3 mb-4 btn-print-hide">
    <div class="card-body p-3">
        <form action="dates.php" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" required value="<?php echo htmlspecialchars($fromDate); ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" required value="<?php echo htmlspecialchars($toDate); ?>">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-filter me-1"></i> Generate Report</button>
                <a href="dates.php" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Date Range Header Summary Badge -->
<div class="alert alert-info d-flex justify-content-between align-items-center mb-4 border-0 shadow-sm">
    <div>
        <i class="bi bi-calendar-check me-2"></i> Report Period: <strong><?php echo date('d M Y', strtotime($fromDate)); ?></strong> to <strong><?php echo date('d M Y', strtotime($toDate)); ?></strong>
    </div>
    <div>
        <span class="badge bg-primary fs-7"><?php echo count($entries); ?> Entries Found</span>
    </div>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Billed in Period</span>
            <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo formatCurrency($totals['total_price']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Advanced Received</span>
            <h3 class="fw-bold text-success mt-1 mb-0"><?php echo formatCurrency($totals['total_advanced']); ?></h3>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3">
            <span class="text-muted small fw-semibold text-uppercase">Pending Balance Outstanding</span>
            <h3 class="fw-bold text-danger mt-1 mb-0"><?php echo formatCurrency($totals['total_balance']); ?></h3>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Entry #</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Specs (Power / Lens / Frame)</th>
                    <th>Price</th>
                    <th>Advanced</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No customer entries recorded within the selected date range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $row): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo $row['id']; ?></td>
                            <td class="small"><?php echo date('d M Y', strtotime($row['entry_date'])); ?></td>
                            <td>
                                <a href="../customers/view.php?id=<?php echo $row['customer_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                </a>
                            </td>
                            <td class="font-monospace small"><?php echo htmlspecialchars($row['customer_mobile']); ?></td>
                            <td class="small text-muted">
                                <?php echo htmlspecialchars($row['power_name'] ?? 'N/A'); ?> / 
                                <?php echo htmlspecialchars($row['lens_type_name'] ?? 'N/A'); ?> / 
                                <?php echo htmlspecialchars($row['frame_type_name'] ?? 'N/A'); ?>
                            </td>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
