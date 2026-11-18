<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$response = ['success' => false, 'message' => ''];

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'قاعدة البيانات غير متصلة']);
    exit;
}

$patient_id    = currentUserId() ?: (int)($_POST['patient_id'] ?? 0);
$laboratory_id = (int)($_POST['laboratory_id'] ?? 0);
$slot_id       = (int)($_POST['slot_id'] ?? 0);
$patient_phone = trim($_POST['patient_phone'] ?? '');
$analyses_text = trim($_POST['analyses_text'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'يرجى تسجيل الدخول أولاً']);
    exit;
}
if (!$patient_phone) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال رقم الهاتف للتواصل']);
    exit;
}
if (!$laboratory_id || !$slot_id) {
    echo json_encode(['success' => false, 'message' => 'يرجى اختيار المخبر والفترة الزمنية']);
    exit;
}
$hasPrescription = (!empty($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK);

if ($analyses_text === '') {
    if ($hasPrescription) {
        $analyses_text = 'فحص وتحاليل حسب الوصفة الطبية المرفقة';
    } else {
        echo json_encode(['success' => false, 'message' => 'يرجى تحديد التحاليل من القائمة أو إرفاق صورة الوصفة الطبية']);
        exit;
    }
}

// Handle Prescription File Upload safely
$uploaded_filename = null;
if (!empty($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['prescription'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes, true)) {
        echo json_encode(['success' => false, 'message' => 'نوع ملف الوصفة غير مسموح. الأنواع المقبولة: JPG, PNG, PDF']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'حجم ملف الوصفة يتجاوز الحد المسموح (5 ميغابايت)']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uploaded_filename = 'rx_' . bin2hex(random_bytes(10)) . '.' . strtolower($ext);
    $upload_dir = ROOT . '/uploads/prescriptions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $uploaded_filename)) {
        echo json_encode(['success' => false, 'message' => 'فشل في حفظ ملف الوصفة']);
        exit;
    }
}

// Database Transaction with Row Lock to strictly prevent double bookings & conflicts
try {
    $conn->beginTransaction();

    // Lock the slot row
    $lockStmt = $conn->prepare("
        SELECT id, slot_date, slot_time, max_bookings, current_bookings, status 
        FROM availability_slots 
        WHERE id = :sid AND laboratory_id = :lid 
        FOR UPDATE
    ");
    $lockStmt->execute([':sid' => $slot_id, ':lid' => $laboratory_id]);
    $slot = $lockStmt->fetch();

    if (!$slot) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'الفترة الزمنية المحددة غير موجودة بالمخبر']);
        exit;
    }

    if ($slot['status'] !== 'available' || (int)$slot['current_bookings'] >= (int)$slot['max_bookings']) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'عذراً، هذه الفترة اكتملت سعتها للتو. يرجى اختيار فترة أخرى']);
        exit;
    }

    // Insert Appointment
    $ins = $conn->prepare("
        INSERT INTO appointments 
        (patient_id, laboratory_id, slot_id, appointment_date, appointment_time, analyses_text, prescription_file, notes, contact_phone, status) 
        VALUES (:pid, :lid, :sid, :dt, :tm, :an, :rx, :nt, :ph, 'pending')
    ");
    $ins->execute([
        ':pid' => $patient_id,
        ':lid' => $laboratory_id,
        ':sid' => $slot_id,
        ':dt'  => $slot['slot_date'],
        ':tm'  => $slot['slot_time'],
        ':an'  => $analyses_text,
        ':rx'  => $uploaded_filename,
        ':nt'  => $notes ?: null,
        ':ph'  => $patient_phone
    ]);
    $appointment_id = (int)$conn->lastInsertId();

    // Increment slot bookings
    $new_bookings = (int)$slot['current_bookings'] + 1;
    $new_status = ($new_bookings >= (int)$slot['max_bookings']) ? 'full' : 'available';

    $updSlot = $conn->prepare("
        UPDATE availability_slots 
        SET current_bookings = :cnt, status = :st 
        WHERE id = :sid
    ");
    $updSlot->execute([':cnt' => $new_bookings, ':st' => $new_status, ':sid' => $slot_id]);

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'تم إرسال طلب الحجز بنجاح وهو بانتظار تأكيد المخبر';
    $response['appointment_id'] = $appointment_id;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response['message'] = 'حدث خطأ تقني أثناء معالجة الحجز: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
