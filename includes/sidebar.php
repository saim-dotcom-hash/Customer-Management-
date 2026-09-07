<?php
/**
 * Sidebar Navigation Layout Component
 * Customer Management System
 */

$currentPage = $_SERVER['PHP_SELF'];
$baseUrl = getBaseUrl();

function isActive(string $path, string $currentPage): string {
    return (strpos($currentPage, $path) !== false) ? 'active' : '';
}
?>
<!-- Sidebar -->
<div id="sidebar-wrapper">
    <div class="sidebar-heading">
        <i class="bi bi-wallet2 fs-4 text-primary"></i>
        <span>Customer System</span>
    </div>
    
    <div class="list-group list-group-flush">
        <!-- Main Navigation -->
        <a href="<?php echo $baseUrl; ?>admin/dashboard.php" class="list-group-item list-group-item-action <?php echo isActive('dashboard.php', $currentPage); ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Customer Management Section -->
        <div class="sidebar-category">Customer Management</div>
        <a href="<?php echo $baseUrl; ?>entries/add.php" class="list-group-item list-group-item-action <?php echo isActive('entries/add.php', $currentPage); ?>">
            <i class="bi bi-person-plus-fill"></i> Add Customer Entry
        </a>
        <a href="<?php echo $baseUrl; ?>customers/index.php" class="list-group-item list-group-item-action <?php echo isActive('customers/index.php', $currentPage); ?>">
            <i class="bi bi-people-fill"></i> Customer List
        </a>
        <a href="<?php echo $baseUrl; ?>entries/index.php" class="list-group-item list-group-item-action <?php echo isActive('entries/index.php', $currentPage); ?>">
            <i class="bi bi-journal-text"></i> Customer Entries
        </a>
        <a href="<?php echo $baseUrl; ?>customers/pending.php" class="list-group-item list-group-item-action <?php echo isActive('customers/pending.php', $currentPage); ?>">
            <i class="bi bi-exclamation-circle-fill text-warning"></i> Pending Balances
        </a>

        <!-- Payments Section -->
        <div class="sidebar-category">Financials</div>
        <a href="<?php echo $baseUrl; ?>payments/history.php" class="list-group-item list-group-item-action <?php echo isActive('payments/history.php', $currentPage); ?>">
            <i class="bi bi-receipt"></i> Payment History
        </a>


        <!-- Reports Section -->
        <div class="sidebar-category">Reports</div>
        <a href="<?php echo $baseUrl; ?>reports/customers.php" class="list-group-item list-group-item-action <?php echo isActive('reports/customers.php', $currentPage); ?>">
            <i class="bi bi-bar-chart-fill"></i> Customer Reports
        </a>
        <a href="<?php echo $baseUrl; ?>reports/dates.php" class="list-group-item list-group-item-action <?php echo isActive('reports/dates.php', $currentPage); ?>">
            <i class="bi bi-calendar-range"></i> Date Range Reports
        </a>
        <a href="<?php echo $baseUrl; ?>reports/balances.php" class="list-group-item list-group-item-action <?php echo isActive('reports/balances.php', $currentPage); ?>">
            <i class="bi bi-cash-stack"></i> Pending Balance Report
        </a>

        <!-- System & Account Section -->
        <div class="sidebar-category">Account</div>
        <a href="<?php echo $baseUrl; ?>admin/profile.php" class="list-group-item list-group-item-action <?php echo isActive('profile.php', $currentPage); ?>">
            <i class="bi bi-person-gear"></i> Admin Profile
        </a>
        <a href="<?php echo $baseUrl; ?>admin/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
