<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات']);
    exit;
}

if ($action === 'register') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$first_name || !$last_name || !$email || !$password) {
        $response['message'] = 'جميع الحقول الأساسية مطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'صيغة البريد الإلكتروني غير صالحة';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'كلمة المرور يجب أن لا تقل عن 6 أحرف';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = :e");
        $check->execute([':e' => $email]);
        if ($check->fetch()) {
            $response['message'] = 'البريد الإلكتروني مسجل مسبقاً';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (:fn, :ln, :em, :ph, :pw, 'patient')");
            $stmt->execute([':fn' => $first_name, ':ln' => $last_name, ':em' => $email, ':ph' => $phone, ':pw' => $hash]);
            
            $userId = (int)$conn->lastInsertId();
            loginUser(['id' => $userId, 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'role' => 'patient']);
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء الحساب بنجاح';
            $response['user'] = ['id' => $userId, 'name' => "$first_name $last_name", 'email' => $email, 'role' => 'patient'];
        }
    }
} elseif ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $response['message'] = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $labId = null;
            if ($user['role'] === 'lab_manager') {
                $ls = $conn->prepare("SELECT id FROM laboratories WHERE manager_id = :uid LIMIT 1");
                $ls->execute([':uid' => $user['id']]);
                $lab = $ls->fetch();
                $labId = $lab ? (int)$lab['id'] : null;
            }
            loginUser($user, $labId);

            $response['success'] = true;
            $response['message'] = 'تم تسجيل الدخول بنجاح';
            $response['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'lab_id' => $labId
            ];
        } else {
            $response['message'] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
} else {
    $response['message'] = 'إجراء غير معروف';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
