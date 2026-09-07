<?php
/**
 * Delete Customer Entry Handler
 * Customer Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../admin/login.php");
    exit();
}

$entryId = (int)($_GET['id'] ?? 0);

if (!verifyCsrfToken()) {
    setFlash('error', 'Invalid security token.');
    header("Location: index.php");
    exit();
}

if ($entryId <= 0) {
    setFlash('error', 'Invalid entry ID.');
    header("Location: index.php");
    exit();
}

$db = Database::getConnection();

// Fetch customer ID first
$stmt = $db->prepare("SELECT customer_id FROM customer_entries WHERE id = ?");
$stmt->execute([$entryId]);
$entry = $stmt->fetch();

if ($entry) {
    $customerId = $entry['customer_id'];
    $delStmt = $db->prepare("DELETE FROM customer_entries WHERE id = ?");
    $delStmt->execute([$entryId]);
    setFlash('success', "Customer Entry #{$entryId} deleted successfully.");
    header("Location: ../customers/view.php?id=" . $customerId);
    exit();
} else {
    setFlash('error', 'Entry not found.');
    header("Location: index.php");
    exit();
}
