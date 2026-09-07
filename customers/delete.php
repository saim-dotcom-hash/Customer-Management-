<?php
/**
 * Safe Delete Customer Handler
 * Customer Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../admin/login.php");
    exit();
}

$customerId = (int)($_GET['id'] ?? 0);

if (!verifyCsrfToken()) {
    setFlash('error', 'Invalid security token.');
    header("Location: index.php");
    exit();
}

if ($customerId <= 0) {
    setFlash('error', 'Invalid customer ID.');
    header("Location: index.php");
    exit();
}

$db = Database::getConnection();

// Fetch customer name for flash message
$stmt = $db->prepare("SELECT name FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$cust = $stmt->fetch();

if ($cust) {
    $delStmt = $db->prepare("DELETE FROM customers WHERE id = ?");
    $delStmt->execute([$customerId]);
    setFlash('success', "Customer '{$cust['name']}' and all related optical entries deleted successfully.");
} else {
    setFlash('error', 'Customer not found.');
}

header("Location: index.php");
exit();
