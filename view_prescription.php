<?php
require_once 'config.php';
requireLogin();

$filename = basename($_GET['file'] ?? '');
if (!$filename) {
    http_response_code(400);
    die('اسم الملف مفقود');
}

$filepath = ROOT . '/uploads/prescriptions/' . $filename;
if (!file_exists($filepath)) {
    http_response_code(404);
    die('الملف غير موجود');
}

// Security Check: Ensure current user is the patient or the lab manager
$userId = currentUserId();
$userRole = currentUserRole();

$stmt = $conn->prepare("
    SELECT a.id, a.patient_id, l.manager_id 
    FROM appointments a
    JOIN laboratories l ON l.id = a.laboratory_id
    WHERE a.prescription_file = :fn
    LIMIT 1
");
$stmt->execute([':fn' => $filename]);
$apt = $stmt->fetch();

if (!$apt && $userRole !== 'admin') {
    http_response_code(403);
    die('غير مصرح لك بالوصول لهذا المستند');
}

if ($userRole !== 'admin') {
    $isOwnerPatient = ((int)$apt['patient_id'] === $userId);
    $isLabManager   = ((int)$apt['manager_id'] === $userId);
    if (!$isOwnerPatient && !$isLabManager) {
        http_response_code(403);
        die('غير مصرح لك بمشاهدة هذه الوصفة الطبية');
    }
}

// Serve the file safely
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filepath);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="' . $filename . '"');
readfile($filepath);
exit;
