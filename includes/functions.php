<?php
/**
 * System Functions & Helpers
 * Customer Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Format numeric value into Indian Rupee Currency string
 * Example: 5000 -> ₹5,000.00
 */
function formatCurrency($amount): string {
    $val = (float)($amount ?? 0);
    return '₹' . number_format($val, 2);
}

/**
 * Sanitize text input string
 */
function sanitizeInput(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message to session
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Render Flash Messages HTML
 */
function displayFlashMessages(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $html = '';
    if (isset($_SESSION['flash_success'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['flash_success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['flash_error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['flash_error']);
    }
    return $html;
}

/**
 * Calculate Balance: Price - Advanced
 */
function calculateBalance(float $price, float $advanced): float {
    $balance = $price - $advanced;
    return max(0.00, round($balance, 2));
}

/**
 * Determine Status based on Balance
 */
function determineStatus(float $balance): string {
    return ($balance <= 0.0001) ? 'Paid' : 'Pending';
}

/**
 * Get active powers dropdown options
 */
function getActivePowers(): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM powers WHERE status = 'active' ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get active lens types dropdown options
 */
function getActiveLensTypes(): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM lens_types WHERE status = 'active' ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get active frame types dropdown options
 */
function getActiveFrameTypes(): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM frame_types WHERE status = 'active' ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get dynamic global system header financial summary
 */
function getHeaderBalanceSummary(): array {
    $db = Database::getConnection();
    $query = "SELECT 
                COUNT(DISTINCT c.id) as total_customers,
                COALESCE(SUM(ce.price), 0) as total_price,
                COALESCE(SUM(ce.advanced), 0) as total_advanced,
                COALESCE(SUM(ce.balance), 0) as total_balance,
                SUM(CASE WHEN ce.balance > 0 THEN 1 ELSE 0 END) as total_pending_entries
              FROM customers c
              LEFT JOIN customer_entries ce ON c.id = ce.customer_id";
    return $db->query($query)->fetch();
}

/**
 * Get Dashboard Summary Statistics
 */
function getDashboardStats(): array {
    $db = Database::getConnection();
    
    // Customers count
    $stmtCust = $db->query("SELECT COUNT(*) FROM customers");
    $totalCustomers = (int)$stmtCust->fetchColumn();

    // Entries count & financial totals
    $stmtEntries = $db->query("SELECT 
        COUNT(*) as total_entries,
        COALESCE(SUM(price), 0) as total_price,
        COALESCE(SUM(advanced), 0) as total_advanced,
        COALESCE(SUM(balance), 0) as total_balance
    FROM customer_entries");
    $financials = $stmtEntries->fetch();

    return [
        'total_customers' => $totalCustomers,
        'total_entries' => (int)($financials['total_entries'] ?? 0),
        'total_price' => (float)($financials['total_price'] ?? 0),
        'total_advanced' => (float)($financials['total_advanced'] ?? 0),
        'total_balance' => (float)($financials['total_balance'] ?? 0)
    ];
}

/**
 * Get aggregated financials for a specific customer
 */
function getCustomerFinancialSummary(int $customerId): array {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(price), 0) as total_price,
        COALESCE(SUM(advanced), 0) as total_advanced,
        COALESCE(SUM(balance), 0) as total_balance,
        COUNT(id) as total_entries
    FROM customer_entries 
    WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    return $stmt->fetch();
}

/**
 * Find or create customer by mobile
 */
function findOrCreateCustomer(string $name, string $mobile): int {
    $db = Database::getConnection();
    $mobile = trim($mobile);
    $name = trim($name);

    // Search by mobile
    $stmt = $db->prepare("SELECT id, name FROM customers WHERE mobile = ? LIMIT 1");
    $stmt->execute([$mobile]);
    $customer = $stmt->fetch();

    if ($customer) {
        // Optionally update customer name if provided
        if (!empty($name) && $customer['name'] !== $name) {
            $updateStmt = $db->prepare("UPDATE customers SET name = ? WHERE id = ?");
            $updateStmt->execute([$name, $customer['id']]);
        }
        return (int)$customer['id'];
    }

    // Insert new customer
    $insertStmt = $db->prepare("INSERT INTO customers (name, mobile) VALUES (?, ?)");
    $insertStmt->execute([$name, $mobile]);
    return (int)$db->lastInsertId();
}
