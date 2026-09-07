<?php
/**
 * Printer-Friendly Customer Statement Page
 * Customer Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../admin/login.php");
    exit();
}

$db = Database::getConnection();
$customerId = (int)($_GET['id'] ?? 0);

if ($customerId <= 0) {
    die("Invalid Customer ID");
}

// Fetch Customer
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found");
}

// Fetch Financial Summary
$financials = getCustomerFinancialSummary($customerId);

// Fetch Entries
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
    ORDER BY ce.id ASC
");
$entriesStmt->execute([$customerId]);
$entries = $entriesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - <?php echo htmlspecialchars($customer['name']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff;
            color: #1e293b;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 2rem;
        }
        .statement-box {
            max-width: 850px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            padding: 2.5rem;
            border-radius: 0.5rem;
        }
        @media print {
            .btn-print-hide { display: none !important; }
            body { padding: 0; }
            .statement-box { border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="statement-box">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 btn-print-hide pb-3 border-bottom">
        <a href="../customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Profile
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3 fw-bold">
            Print Statement
        </button>
    </div>

    <!-- Statement Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Customer Statement</h2>
            <p class="text-muted mb-0">Customer Optical Prescription & Financial Statement</p>
        </div>
        <div class="text-end">
            <h6 class="fw-bold m-0 text-secondary">STATEMENT DATE</h6>
            <p class="fw-bold text-dark mb-0"><?php echo date('d M Y'); ?></p>
        </div>
    </div>

    <!-- Customer Profile Block -->
    <div class="row mb-4">
        <div class="col-6">
            <h6 class="text-uppercase text-muted fw-bold small">Customer Information</h6>
            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($customer['name']); ?></h4>
            <p class="mb-0 text-secondary">Mobile: <strong><?php echo htmlspecialchars($customer['mobile']); ?></strong></p>
            <p class="mb-0 text-secondary small">Customer ID: #<?php echo $customer['id']; ?></p>
        </div>
        <div class="col-6 text-end bg-light p-3 rounded">
            <h6 class="text-uppercase text-muted fw-bold small">Financial Summary</h6>
            <div class="d-flex justify-content-between my-1">
                <span>Total Billed:</span>
                <strong><?php echo formatCurrency($financials['total_price']); ?></strong>
            </div>
            <div class="d-flex justify-content-between my-1 text-success">
                <span>Total Advanced Paid:</span>
                <strong><?php echo formatCurrency($financials['total_advanced']); ?></strong>
            </div>
            <hr class="my-1">
            <div class="d-flex justify-content-between fw-bold fs-5 text-danger">
                <span>Total Balance Due:</span>
                <span><?php echo formatCurrency($financials['total_balance']); ?></span>
            </div>
        </div>
    </div>

    <!-- Detailed Entry Table -->
    <h5 class="fw-bold mb-3">Optical Orders & Entries</h5>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Power</th>
                <th>Lens Type</th>
                <th>Frame Type</th>
                <th>Price</th>
                <th>Advanced</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">No orders found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td>#<?php echo $e['id']; ?></td>
                        <td class="small"><?php echo date('d M Y', strtotime($e['entry_date'])); ?></td>
                        <td class="fw-bold font-monospace"><?php echo htmlspecialchars($e['power_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($e['lens_type_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($e['frame_type_name'] ?? 'N/A'); ?></td>
                        <td><?php echo formatCurrency($e['price']); ?></td>
                        <td class="text-success"><?php echo formatCurrency($e['advanced']); ?></td>
                        <td class="fw-bold <?php echo ($e['balance'] > 0) ? 'text-danger' : ''; ?>">
                            <?php echo formatCurrency($e['balance']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="5" class="text-end">Grand Totals:</td>
                <td><?php echo formatCurrency($financials['total_price']); ?></td>
                <td class="text-success"><?php echo formatCurrency($financials['total_advanced']); ?></td>
                <td class="text-danger"><?php echo formatCurrency($financials['total_balance']); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer Terms & Signatures -->
    <div class="row mt-5 pt-4 border-top">
        <div class="col-8">
            <small class="text-muted">
                * This is a computer-generated statement issued by Customer Management System.<br>
                Please preserve this statement for warranty and future prescription reference.
            </small>
        </div>
        <div class="col-4 text-end">
            <div style="border-bottom: 1px solid #000; height: 40px; width: 180px; margin-left: auto;"></div>
            <small class="fw-bold d-block mt-1">Authorized Signature</small>
        </div>
    </div>
</div>

<script>
    // Auto-trigger print prompt on load if print param set
    if (window.location.search.indexOf('auto=1') !== -1) {
        window.print();
    }
</script>
</body>
</html>
