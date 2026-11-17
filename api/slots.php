<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$lab_id = (int)($_GET['lab_id'] ?? 0);
$date   = trim($_GET['date'] ?? '');

if (!$conn || !$lab_id) {
    echo json_encode(['success' => false, 'slots' => [], 'dates' => []]);
    exit;
}

// 1. Fetch available future dates for this lab (dates having at least 1 open slot)
$dStmt = $conn->prepare("
    SELECT DISTINCT slot_date 
    FROM availability_slots 
    WHERE laboratory_id = :lid 
      AND slot_date >= CURDATE() 
      AND status = 'available' 
      AND current_bookings < max_bookings 
    ORDER BY slot_date ASC 
    LIMIT 14
");
$dStmt->execute([':lid' => $lab_id]);
$dates = $dStmt->fetchAll(PDO::FETCH_COLUMN);

// If no date requested, default to first available date
if (!$date && !empty($dates)) {
    $date = $dates[0];
}

$slots = [];
if ($date) {
    // 2. Fetch slots for this lab and date with capacity check
    $sStmt = $conn->prepare("
        SELECT id, slot_time, max_bookings, current_bookings, 
               (max_bookings - current_bookings) AS remaining_slots
        FROM availability_slots 
        WHERE laboratory_id = :lid 
          AND slot_date = :dt 
          AND status = 'available' 
          AND current_bookings < max_bookings 
        ORDER BY slot_time ASC
    ");
    $sStmt->execute([':lid' => $lab_id, ':dt' => $date]);
    $slots = $sStmt->fetchAll();
    
    // Format times for display (HH:MM)
    foreach ($slots as &$s) {
        $s['time_formatted'] = substr($s['slot_time'], 0, 5);
    }
}

echo json_encode([
    'success' => true,
    'selected_date' => $date,
    'available_dates' => $dates,
    'slots' => $slots
], JSON_UNESCAPED_UNICODE);
