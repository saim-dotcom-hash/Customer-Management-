<?php
/**
 * Authentication & Security Helper Functions
 * Customer Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the admin is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require login for protected pages. Redirects to login page if unauthenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please login to access the admin panel.";
        $baseUrl = getBaseUrl();
        header("Location: " . $baseUrl . "admin/login.php");
        exit();
    }
}

/**
 * Get Base URL dynamically
 */
function getBaseUrl(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Find customer-management directory depth or default root relative
    $dirs = explode('/', trim($scriptName, '/'));
    // If running under xampp subfolder vs root
    $projectIndex = array_search('customer-management', $dirs);
    if ($projectIndex !== false) {
        $path = implode('/', array_slice($dirs, 0, $projectIndex + 1));
        return '/' . $path . '/';
    }
    
    // Relative back calculation based on file location
    $depth = count(array_filter(explode('/', dirname($_SERVER['SCRIPT_NAME']))));
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || 
        strpos($_SERVER['SCRIPT_NAME'], '/customers/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/entries/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/payments/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/dropdowns/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/print/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
        return '../';
    }
    return './';
}

/**
 * Generate CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output HTML hidden CSRF input field
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF Token from POST request
 */
function verifyCsrfToken(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
    }
    return true;
}
