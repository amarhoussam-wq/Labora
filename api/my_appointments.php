<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$patient_id = currentUserId() ?: (int)($_GET['patient_id'] ?? 0);

if (!$conn || !$patient_id) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.appointment_time, a.analyses_text, 
           a.prescription_file, a.status, a.rejection_reason, a.created_at,
           l.name AS laboratory_name, l.city AS laboratory_city, l.phone AS laboratory_phone, l.address AS laboratory_address
    FROM appointments a
    JOIN laboratories l ON l.id = a.laboratory_id
    WHERE a.patient_id = :pid
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([':pid' => $patient_id]);
$appointments = $stmt->fetchAll();

foreach ($appointments as &$apt) {
    $apt['time_formatted'] = substr($apt['appointment_time'], 0, 5);
    $apt['status_ar'] = match ($apt['status']) {
        'pending'   => 'قيد الانتظار',
        'accepted'  => 'مقبول',
        'rejected'  => 'مرفوض',
        'cancelled' => 'ملغى',
        default     => $apt['status']
    };
    $apt['prescription_url'] = $apt['prescription_file'] 
        ? BASE_URL . 'view_prescription.php?file=' . urlencode($apt['prescription_file'])
        : null;
}

echo json_encode(['success' => true, 'data' => $appointments], JSON_UNESCAPED_UNICODE);
