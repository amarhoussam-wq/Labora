<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$stmt = $conn->query("SELECT id, name, city, address, phone FROM laboratories WHERE status='active' ORDER BY city, name");
$labs = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $labs], JSON_UNESCAPED_UNICODE);
