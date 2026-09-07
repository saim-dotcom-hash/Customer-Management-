<?php
/**
 * Add Payment / Advance Record Handler
 * Customer Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../admin/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken()) {
    setFlash('error', 'Invalid request or security token expired.');
    header("Location: ../customers/index.php");
    exit();
}

$entryId = (int)($_POST['entry_id'] ?? 0);
$customerId = (int)($_POST['customer_id'] ?? 0);
$paymentAmount = (float)($_POST['amount'] ?? 0);
$paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
$notes = trim($_POST['notes'] ?? 'Additional Payment');

if ($entryId <= 0 || $paymentAmount <= 0) {
    setFlash('error', 'Please enter a valid positive payment amount.');
    header("Location: ../entries/view.php?id=" . $entryId);
    exit();
}

$db = Database::getConnection();
$db->beginTransaction();

try {
    // Lock entry row for update
    $stmt = $db->prepare("SELECT price, advanced, customer_id FROM customer_entries WHERE id = ? FOR UPDATE");
    $stmt->execute([$entryId]);
    $entry = $stmt->fetch();

    if (!$entry) {
        $db->rollBack();
        setFlash('error', 'Customer entry not found.');
        header("Location: ../customers/index.php");
        exit();
    }

    $price = (float)$entry['price'];
    $currentAdvanced = (float)$entry['advanced'];
    $newAdvanced = $currentAdvanced + $paymentAmount;

    if ($newAdvanced > $price) {
        $db->rollBack();
        setFlash('error', 'Payment amount cannot cause total advanced to exceed order price. Maximum payable amount is ' . formatCurrency($price - $currentAdvanced));
        header("Location: ../entries/view.php?id=" . $entryId);
        exit();
    }

    $newBalance = calculateBalance($price, $newAdvanced);
    $newStatus = determineStatus($newBalance);

    // Update customer entry
    $updateStmt = $db->prepare("UPDATE customer_entries SET advanced = ?, balance = ?, status = ? WHERE id = ?");
    $updateStmt->execute([$newAdvanced, $newBalance, $newStatus, $entryId]);

    // Insert payment audit log
    $adminUser = $_SESSION['admin_username'] ?? 'Admin';
    $logStmt = $db->prepare("INSERT INTO payments (customer_entry_id, customer_id, amount, payment_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $logStmt->execute([$entryId, $entry['customer_id'], $paymentAmount, $paymentDate, $notes, $adminUser]);

    $db->commit();

    setFlash('success', "Payment of " . formatCurrency($paymentAmount) . " recorded successfully. New balance: " . formatCurrency($newBalance));
    header("Location: ../customers/view.php?id=" . $entry['customer_id']);
    exit();
} catch (Exception $e) {
    $db->rollBack();
    setFlash('error', 'Database error: ' . $e->getMessage());
    header("Location: ../entries/view.php?id=" . $entryId);
    exit();
}
