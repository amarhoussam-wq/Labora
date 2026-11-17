<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$patient_id     = currentUserId() ?: (int)($_POST['patient_id'] ?? 0);
$appointment_id = (int)($_POST['appointment_id'] ?? 0);

if (!$conn || !$patient_id || !$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        SELECT id, slot_id, status 
        FROM appointments 
        WHERE id = :aid AND patient_id = :pid 
        FOR UPDATE
    ");
    $stmt->execute([':aid' => $appointment_id, ':pid' => $patient_id]);
    $apt = $stmt->fetch();

    if (!$apt) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'الموعد غير موجود أو غير تابع لك']);
        exit;
    }

    if ($apt['status'] !== 'pending') {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'لا يمكن إلغاء الموعد إلا إذا كان قيد الانتظار']);
        exit;
    }

    // Set appointment status to cancelled
    $updApt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :aid");
    $updApt->execute([':aid' => $appointment_id]);

    // Release capacity on the slot
    if ($apt['slot_id']) {
        $updSlot = $conn->prepare("
            UPDATE availability_slots 
            SET current_bookings = GREATEST(current_bookings - 1, 0),
                status = 'available' 
            WHERE id = :sid
        ");
        $updSlot->execute([':sid' => $apt['slot_id']]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'تم إلغاء الموعد بنجاح وتحرير الفترة الزمنية']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'فشل الإلغاء: ' . $e->getMessage()]);
}
