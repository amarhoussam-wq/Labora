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
$action         = trim($_POST['action'] ?? '');
$new_status     = trim($_POST['status'] ?? '');
$reason         = trim($_POST['rejection_reason'] ?? '');
$lab_id         = currentLabId();

if (!$lab_id) {
    $stmt = $conn->prepare("SELECT id FROM laboratories WHERE manager_id = :uid LIMIT 1");
    $stmt->execute([':uid' => currentUserId()]);
    $labRow = $stmt->fetch();
    $lab_id = $labRow ? (int)$labRow['id'] : 1;
    $_SESSION['lab_id'] = $lab_id;
}

$is_delete = ($action === 'delete' || $new_status === 'delete' || $new_status === 'deleted');

if (!$is_delete && !in_array($new_status, ['accepted', 'rejected', 'cancelled'], true)) {
    setFlash('error', 'إجراء غير صالح');
    redirect('lab/dashboard.php');
}

try {
    $conn->beginTransaction();

    // Verify appointment belongs to this lab manager or user is admin
    if (currentUserRole() === 'admin') {
        $stmt = $conn->prepare("
            SELECT id, slot_id, status 
            FROM appointments 
            WHERE id = :aid 
            FOR UPDATE
        ");
        $stmt->execute([':aid' => $appointment_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT id, slot_id, status 
            FROM appointments 
            WHERE id = :aid AND laboratory_id = :lid 
            FOR UPDATE
        ");
        $stmt->execute([':aid' => $appointment_id, ':lid' => $lab_id]);
    }
    $apt = $stmt->fetch();

    if (!$apt) {
        $conn->rollBack();
        setFlash('error', 'الموعد غير موجود أو لا يتبع لمخبرك');
        redirect('lab/dashboard.php');
    }

    // ── Handle Delete Action ──────────────────────────────────
    if ($is_delete) {
        // Free up slot capacity if slot was reserved
        if (!empty($apt['slot_id']) && in_array($apt['status'], ['pending', 'accepted'], true)) {
            $relSlot = $conn->prepare("
                UPDATE availability_slots 
                SET current_bookings = GREATEST(current_bookings - 1, 0),
                    status = 'available' 
                WHERE id = :sid
            ");
            $relSlot->execute([':sid' => $apt['slot_id']]);
        }

        $del = $conn->prepare("DELETE FROM appointments WHERE id = :aid");
        $del->execute([':aid' => $appointment_id]);

        $conn->commit();
        setFlash('success', 'تم حذف الموعد نهائياً بنجاح.');
        redirect('lab/dashboard.php');
    }

    // ── Handle Status Update ──────────────────────────────────
    $upd = $conn->prepare("
        UPDATE appointments 
        SET status = :st, rejection_reason = :rr 
        WHERE id = :aid
    ");
    $upd->execute([
        ':st' => $new_status,
        ':rr' => in_array($new_status, ['rejected', 'cancelled'], true) ? ($reason ?: 'تم الرفض/الإلغاء من قبل المخبر') : null,
        ':aid' => $appointment_id
    ]);

    // If rejected or cancelled from an active status, free up the slot capacity
    if (in_array($new_status, ['rejected', 'cancelled'], true) && $apt['slot_id']) {
        $relSlot = $conn->prepare("
            UPDATE availability_slots 
            SET current_bookings = GREATEST(current_bookings - 1, 0),
                status = 'available' 
            WHERE id = :sid
        ");
        $relSlot->execute([':sid' => $apt['slot_id']]);
    }

    $conn->commit();
    $statusText = match($new_status) {
        'accepted'  => 'تم قبول الموعد وتأكيده بنجاح.',
        'rejected'  => 'تم رفض الموعد مع حفظ السبب وتحرير الفترة الزمنية.',
        'cancelled' => 'تم إلغاء الموعد المقبول وتحرير الفترة الزمنية.',
        default     => 'تم تحديث حالة الموعد.'
    };
    setFlash('success', $statusText);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    setFlash('error', 'حدث خطأ: ' . $e->getMessage());
}

redirect('lab/dashboard.php');
