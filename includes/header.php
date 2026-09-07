<?php
/**
 * Global Admin Header Layout Component
 * Customer Management System
 */

require_once __DIR__ . '/functions.php';
requireLogin();

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$headerStats = getHeaderBalanceSummary();
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Customer Management' : 'Customer & Balance Management System'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo $baseUrl; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div id="wrapper">
    <!-- Include Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light border btn-sm shadow-sm" id="menu-toggle" aria-label="Toggle Navigation Menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h5 class="m-0 fw-bold d-none d-sm-block text-dark">Estimate</h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Header Balance Summary Badge -->
                <div class="header-summary-badge shadow-sm" title="Total Pending Balance across all customers">
                    <i class="bi bi-wallet2 text-warning"></i>
                    <span>Total Balance: <strong><?php echo formatCurrency($headerStats['total_balance'] ?? 0); ?></strong></span>
                </div>

                <!-- Admin Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light border btn-sm dropdown-toggle d-flex align-items-center gap-2 shadow-sm px-3 py-1.5" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.85rem;">
                            <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                        </div>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($adminName); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" aria-labelledby="adminDropdown" style="min-width: 180px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo $baseUrl; ?>admin/profile.php">
                                <i class="bi bi-person-gear me-2 text-primary fs-5"></i>
                                <span>Admin Profile</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center text-danger fw-bold" href="<?php echo $baseUrl; ?>admin/logout.php">
                                <i class="bi bi-box-arrow-right me-2 fs-5"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content Body -->
        <main class="container-fluid p-4">
            <?php echo displayFlashMessages(); ?>
