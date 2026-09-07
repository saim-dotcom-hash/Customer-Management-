<?php
/**
 * AJAX API Handler Endpoint
 * Customer Management System
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'check_customer':
        $mobile = trim($_GET['mobile'] ?? '');
        if (empty($mobile)) {
            echo json_encode(['exists' => false]);
            exit();
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, mobile FROM customers WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $customer = $stmt->fetch();

        if ($customer) {
            echo json_encode([
                'exists' => true,
                'id' => $customer['id'],
                'name' => $customer['name'],
                'mobile' => $customer['mobile']
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit();

    case 'quick_payment':
        if (!verifyCsrfToken()) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF security token.']);
            exit();
        }

        $entryId = (int)($_POST['entry_id'] ?? 0);
        $paymentAmount = (float)($_POST['amount'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $notes = trim($_POST['notes'] ?? 'Additional Payment');

        if ($entryId <= 0 || $paymentAmount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid positive payment amount.']);
            exit();
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Fetch entry
            $stmt = $db->prepare("SELECT id, customer_id, price, advanced FROM customer_entries WHERE id = ? FOR UPDATE");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch();

            if (!$entry) {
                $db->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Customer entry not found.']);
                exit();
            }

            $price = (float)$entry['price'];
            $currentAdvanced = (float)$entry['advanced'];
            $newAdvanced = $currentAdvanced + $paymentAmount;

            if ($newAdvanced > $price) {
                $db->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Payment amount exceeds remaining balance. Max allowed: ₹' . number_format($price - $currentAdvanced, 2)]);
                exit();
            }

            $newBalance = calculateBalance($price, $newAdvanced);
            $newStatus = determineStatus($newBalance);

            // Update entry
            $updateStmt = $db->prepare("UPDATE customer_entries SET advanced = ?, balance = ?, status = ? WHERE id = ?");
            $updateStmt->execute([$newAdvanced, $newBalance, $newStatus, $entryId]);

            // Insert payment log
            $logStmt = $db->prepare("INSERT INTO payments (customer_entry_id, customer_id, amount, payment_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $adminUser = $_SESSION['admin_username'] ?? 'Admin';
            $logStmt->execute([$entryId, $entry['customer_id'], $paymentAmount, $paymentDate, $notes, $adminUser]);

            $db->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment of ' . formatCurrency($paymentAmount) . ' recorded successfully!',
                'new_advanced' => $newAdvanced,
                'new_balance' => $newBalance,
                'new_status' => $newStatus
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action requested.']);
        exit();
}
