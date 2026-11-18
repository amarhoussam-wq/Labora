<?php
require_once dirname(__DIR__) . '/config.php';

if (isLoggedIn()) {
    $role = currentUserRole();
    if ($role === 'admin') redirect('admin/dashboard.php');
    elseif ($role === 'lab_manager') redirect('lab/dashboard.php');
    else redirect('patient/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'صيغة البريد الإلكتروني غير صحيحة.';
    } else {
            if (!$conn) {
                global $db_connection_error;
                $error = 'تعذر الاتصال بقاعدة البيانات: ' . ($db_connection_error ?? 'تحقق من إعدادات DB_HOST') . ' — <a href="' . BASE_URL . 'setup_db.php" class="text-primary fw-bold">اضغط هنا لتهيئة قاعدة البيانات</a>';
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($pass, $user['password_hash'])) {
                    $labId = null;
                    if ($user['role'] === 'lab_manager') {
                        $ls = $conn->prepare("SELECT id FROM laboratories WHERE manager_id = :uid LIMIT 1");
                        $ls->execute([':uid' => $user['id']]);
                        $labRow = $ls->fetch();
                        $labId = $labRow ? (int)$labRow['id'] : null;
                    }
                    loginUser($user, $labId);

                    $redirectUrl = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '');
                    if ($redirectUrl && str_starts_with($redirectUrl, '/') && !in_array($redirectUrl, ['/', '/index.php', '/auth/login.php', '/auth/register.php'], true)) {
                        header('Location: ' . $redirectUrl);
                        exit;
                    }

                    if ($user['role'] === 'admin') redirect('admin/dashboard.php');
                    elseif ($user['role'] === 'lab_manager') redirect('lab/dashboard.php');
                    else redirect('patient/dashboard.php');
                } else {
                    $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
                }
            }
        }
    }

$redirectVal = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول — LABORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/labora.css">
</head>
<body class="lb-auth-bg">

<div class="lb-auth-card">
  <div class="auth-brand">
    <span class="brand-icon"><i class="bi bi-clipboard2-pulse"></i></span>
    <span class="brand-text">LABORA</span>
  </div>

  <h4 class="text-center fw-800 mb-1" style="color:var(--lb-text-dark);">تسجيل الدخول</h4>
  <p class="text-center text-lb-muted small mb-4">أهلاً بك مجدداً في منصة LABORA للتحاليل الطبية</p>

  <?php if ($error): ?>
    <div class="lb-alert lb-alert-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <span><?= $error ?></span>
    </div>
  <?php endif; ?>

  <?php $flash = getFlash(); if ($flash): ?>
    <div class="lb-alert lb-alert-<?= h($flash['type']) ?>">
      <i class="bi bi-info-circle-fill"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
  <?php endif; ?>



  <form method="POST" action="">
    <?= csrfField() ?>
    <input type="hidden" name="redirect" value="<?= h($redirectVal) ?>">

    <div class="lb-form-group">
      <label class="lb-label" for="email">البريد الإلكتروني</label>
      <input type="email" class="lb-input" id="email" name="email"
             value="<?= h($_POST['email'] ?? '') ?>"
             placeholder="example@labora.dz" required autofocus>
    </div>

    <div class="lb-form-group mb-4">
      <label class="lb-label" for="password">كلمة المرور</label>
      <input type="password" class="lb-input" id="password" name="password"
             placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn-lb-primary w-100 justify-content-center py-2 mb-3">
      <i class="bi bi-box-arrow-in-left ms-1"></i> تسجيل الدخول
    </button>
  </form>

  <div class="text-center mt-3 pt-3 border-top">
    <p class="small text-lb-muted mb-2">ليس لديك حساب بعد؟</p>
    <a href="<?= BASE_URL ?>auth/register.php" class="btn-lb-outline w-100 justify-content-center py-2">
      إنشاء حساب مريض جديد
    </a>
  </div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
