<?php
// income/delete.php - Delete income record
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = intval($_POST['income_id'] ?? 0);

if (!empty($id)) {
    $delStmt = $pdo->prepare("DELETE FROM income WHERE id = ? AND user_id = ?");
    $delStmt->execute([$id, $userId]);
}

header('Location: index.php');
exit;
?>