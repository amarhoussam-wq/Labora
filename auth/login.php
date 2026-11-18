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
$roleParam   = $_GET['role'] ?? $_POST['role'] ?? '';

// Google login is shown ONLY for patient login & mobile app
$isMobileApp = isset($_SERVER['HTTP_USER_AGENT']) && (
    str_contains($_SERVER['HTTP_USER_AGENT'], 'LaboraApp') ||
    str_contains($_SERVER['HTTP_USER_AGENT'], 'Android') ||
    str_contains($_SERVER['HTTP_USER_AGENT'], 'Mobile')
);

$showGoogleLogin = ($roleParam === 'patient') 
                || (str_contains($redirectVal, 'patient')) 
                || ($isMobileApp && $roleParam !== 'lab' && !str_contains($redirectVal, 'lab'));
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

  <h4 class="text-center fw-800 mb-1" style="color:var(--lb-text-dark);">
    <?= ($roleParam === 'lab' || str_contains($redirectVal, 'lab')) ? 'دخول فضاء المخبر' : 'تسجيل الدخول' ?>
  </h4>
  <p class="text-center text-lb-muted small mb-4">
    <?= ($roleParam === 'lab' || str_contains($redirectVal, 'lab')) ? 'تسجيل دخول مخصص لمدراء ومسؤولي المخابر الطبية' : 'أهلاً بك مجدداً في منصة LABORA للتحاليل الطبية' ?>
  </p>

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

  <?php if ($showGoogleLogin): ?>
    <!-- Google One-Tap / Instant Sign-In للمريض والتطبيق فقط -->
    <a href="<?= BASE_URL ?>auth/google.php" class="btn btn-outline-secondary w-100 py-2.5 mb-3 d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm bg-white text-dark fw-bold border" style="font-size:0.95rem;">
      <svg width="20" height="20" viewBox="0 0 48 48">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      <span>المتابعة السريعة باستخدام Google</span>
    </a>

    <div class="d-flex align-items-center my-3 text-muted">
      <hr class="flex-grow-1 m-0">
      <span class="px-2 small text-muted">أو عبر البريد الإلكتروني</span>
      <hr class="flex-grow-1 m-0">
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <?= csrfField() ?>
    <input type="hidden" name="redirect" value="<?= h($redirectVal) ?>">
    <input type="hidden" name="role" value="<?= h($roleParam) ?>">

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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
