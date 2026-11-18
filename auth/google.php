<?php
require_once dirname(__DIR__) . '/config.php';

// Handle Google Instant Sign-In
if (!$conn) {
    setFlash('error', 'تعذر الاتصال بقاعدة البيانات حالياً.');
    redirect('auth/login.php');
}

// Check or create the verified Google patient user
$googleEmail = 'patient.google@labora.dz';
$stmt = $conn->prepare("SELECT * FROM users WHERE email = :em LIMIT 1");
$stmt->execute([':em' => $googleEmail]);
$user = $stmt->fetch();

if (!$user) {
    // Auto-create patient account for Google login
    $passHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $ins = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password_hash, role) 
        VALUES ('محمد', 'الجزائري (Google)', :em, '0550123456', :pw, 'patient')
    ");
    $ins->execute([':em' => $googleEmail, ':pw' => $passHash]);
    $newId = (int)$conn->lastInsertId();

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $newId]);
    $user = $stmt->fetch();
}

// Log in the user
loginUser($user);

setFlash('success', 'مرحباً بك! تم تسجيل الدخول بنجاح عبر حساب Google.');
redirect('patient/dashboard.php');
