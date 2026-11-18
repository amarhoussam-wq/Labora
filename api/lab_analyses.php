<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$lab_id = (int)($_GET['lab_id'] ?? 0);

if (!$conn || !$lab_id) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            a.id, 
            a.name_ar, 
            a.name_fr, 
            a.category, 
            a.fasting_required, 
            la.price
        FROM laboratory_analyses la
        JOIN analyses a ON a.id = la.analysis_id
        WHERE la.laboratory_id = :lid AND la.status = 'available' AND a.status = 'active'
        ORDER BY a.category ASC, a.name_ar ASC
    ");
    $stmt->execute([':lid' => $lab_id]);
    $analyses = $stmt->fetchAll();

    // If this specific lab doesn't have custom links yet, fallback to all active analyses
    if (empty($analyses)) {
        $allStmt = $conn->query("
            SELECT id, name_ar, name_fr, category, fasting_required, 1000.00 AS price 
            FROM analyses 
            WHERE status = 'active' 
            ORDER BY category ASC, name_ar ASC
        ");
        $analyses = $allStmt->fetchAll();
    }

    echo json_encode(['success' => true, 'data' => $analyses], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'data' => []]);
}
