<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('lab_manager', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('lab/dashboard.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح أو انتهت الجلسة');
    redirect('lab/dashboard.php');
}

$appointment_id = (int)($_POST['appointment_id'] ?? 0);
$new_status     = trim($_POST['status'] ?? '');
$reason         = trim($_POST['rejection_reason'] ?? '');
$lab_id         = currentLabId();

if (!in_array($new_status, ['accepted', 'rejected'], true)) {
    setFlash('error', 'إجراء غير صالح');
    redirect('lab/dashboard.php');
}

try {
    $conn->beginTransaction();

    // Verify appointment belongs to this lab manager
    $stmt = $conn->prepare("
        SELECT id, slot_id, status 
        FROM appointments 
        WHERE id = :aid AND laboratory_id = :lid 
        FOR UPDATE
    ");
    $stmt->execute([':aid' => $appointment_id, ':lid' => $lab_id]);
    $apt = $stmt->fetch();

    if (!$apt) {
        $conn->rollBack();
        setFlash('error', 'الموعد غير موجود أو لا يتبع لمخبرك');
        redirect('lab/dashboard.php');
    }

    // Update appointment status
    $upd = $conn->prepare("
        UPDATE appointments 
        SET status = :st, rejection_reason = :rr 
        WHERE id = :aid
    ");
    $upd->execute([
        ':st' => $new_status,
        ':rr' => ($new_status === 'rejected') ? ($reason ?: 'تم الرفض من قبل المخبر') : null,
        ':aid' => $appointment_id
    ]);

    // If rejected, free up the slot capacity so another patient can book
    if ($new_status === 'rejected' && $apt['slot_id']) {
        $relSlot = $conn->prepare("
            UPDATE availability_slots 
            SET current_bookings = GREATEST(current_bookings - 1, 0),
                status = 'available' 
            WHERE id = :sid
        ");
        $relSlot->execute([':sid' => $apt['slot_id']]);
    }

    $conn->commit();
    setFlash('success', $new_status === 'accepted' ? 'تم قبول الموعد وتأكيده بنجاح' : 'تم رفض طلب الموعد وتحرير الفترة الزمنية');

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    setFlash('error', 'حدث خطأ: ' . $e->getMessage());
}

redirect('lab/dashboard.php');
