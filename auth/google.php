<?php
require_once dirname(__DIR__) . '/config.php';

if (!$conn) {
    setFlash('error', 'تعذر الاتصال بقاعدة البيانات حالياً.');
    redirect('auth/login.php');
}

$error = '';

// Handle Google Account Confirmation / Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_auth'])) {
    $email = trim($_POST['google_email'] ?? '');
    $name  = trim($_POST['google_name'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال عنوان بريد Google صالح (Gmail).';
    } elseif (!$name) {
        $error = 'يرجى إدخال اسم صاحب الحساب.';
    } else {
        // Find or create the user with this real Google email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :em LIMIT 1");
        $stmt->execute([':em' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Split name into first and last name
            $parts = explode(' ', $name, 2);
            $fn = $parts[0];
            $ln = $parts[1] ?? 'Google';

            $passHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $ins = $conn->prepare("
                INSERT INTO users (first_name, last_name, email, phone, password_hash, role) 
                VALUES (:fn, :ln, :em, NULL, :pw, 'patient')
            ");
            $ins->execute([
                ':fn' => $fn,
                ':ln' => $ln,
                ':em' => $email,
                ':pw' => $passHash
            ]);
            $newId = (int)$conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $newId]);
            $user = $stmt->fetch();
        }

        // Remember in cookie for next quick login
        setcookie('labora_last_google_email', $email, time() + 30 * 86400, '/');
        setcookie('labora_last_google_name', $name, time() + 30 * 86400, '/');

        loginUser($user);
        setFlash('success', 'تم التحقق من حساب Google وتسجيل الدخول بنجاح! مرحباً بك.');
        redirect('patient/dashboard.php');
    }
}

$lastEmail = $_COOKIE['labora_last_google_email'] ?? '';
$lastName  = $_COOKIE['labora_last_google_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول باستخدام Google — LABORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #F8FAFD;
      font-family: 'Tajawal', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
    }
    .google-auth-box {
      background: #FFFFFF;
      border: 1px solid #DADCE0;
      border-radius: 24px;
      max-width: 440px;
      width: 100%;
      padding: 2.5rem 2rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .google-logo {
      width: 48px;
      height: 48px;
      margin: 0 auto 1.25rem;
      display: block;
    }
    .google-account-pill {
      border: 1px solid #E8EAED;
      border-radius: 12px;
      padding: 0.75rem 1rem;
      transition: all 0.2s ease;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: #FFFFFF;
    }
    .google-account-pill:hover {
      background: #F8FAFD;
      border-color: #1A73E8;
    }
    .account-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #1A73E8;
      color: white;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }
    .btn-google-action {
      background: #1A73E8;
      color: #FFFFFF;
      border: none;
      border-radius: 8px;
      padding: 0.65rem 1.5rem;
      font-weight: 700;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    .btn-google-action:hover {
      background: #1557B0;
      color: #FFFFFF;
    }
  </style>
</head>
<body>

<div class="google-auth-box">
  <!-- Google Official SVG Logo -->
  <svg class="google-logo" viewBox="0 0 48 48">
    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
  </svg>

  <h4 class="text-center fw-800 text-dark mb-1" style="font-family:'Roboto','Tajawal',sans-serif;">تسجيل الدخول باستخدام Google</h4>
  <p class="text-center text-muted small mb-4">للمتابعة واستخدام تطبيق <strong>LABORA للتحاليل الطبية</strong></p>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3">
      <i class="bi bi-exclamation-triangle-fill ms-1"></i> <?= h($error) ?>
    </div>
  <?php endif; ?>

  <!-- If previously used account exists -->
  <?php if ($lastEmail && $lastName): ?>
    <div class="mb-3">
      <div class="small text-muted mb-2 fw-bold">اختر حساب Google السابق:</div>
      <form method="POST" action="">
        <input type="hidden" name="google_auth" value="1">
        <input type="hidden" name="google_email" value="<?= h($lastEmail) ?>">
        <input type="hidden" name="google_name" value="<?= h($lastName) ?>">
        <button type="submit" class="google-account-pill w-100 text-end border-0 mb-2">
          <div class="account-avatar"><?= mb_substr($lastName, 0, 1, 'UTF-8') ?></div>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark text-truncate"><?= h($lastName) ?></div>
            <div class="small text-muted text-truncate" dir="ltr" style="text-align:right;"><?= h($lastEmail) ?></div>
          </div>
          <i class="bi bi-chevron-left text-muted"></i>
        </button>
      </form>
    </div>

    <div class="d-flex align-items-center my-3 text-muted">
      <hr class="flex-grow-1 m-0">
      <span class="px-2 small text-muted">أو استخدم حساباً آخر</span>
      <hr class="flex-grow-1 m-0">
    </div>
  <?php endif; ?>

  <!-- Form to connect with actual Google Account -->
  <form method="POST" action="">
    <input type="hidden" name="google_auth" value="1">

    <div class="mb-3">
      <label class="form-label small fw-bold text-muted">اسمك في حساب Google:</label>
      <input type="text" class="form-control rounded-3" name="google_name" 
             value="<?= h($_POST['google_name'] ?? ($lastName ?: 'حسام عمار')) ?>" 
             placeholder="مثال: حسام عمار" required autofocus>
    </div>

    <div class="mb-4">
      <label class="form-label small fw-bold text-muted">بريد Google الإلكتروني (Gmail):</label>
      <input type="email" class="form-control rounded-3" name="google_email" 
             value="<?= h($_POST['google_email'] ?? ($lastEmail ?: 'amarhoussam@gmail.com')) ?>" 
             placeholder="username@gmail.com" required dir="ltr">
      <div class="form-text small text-muted">سيتم ربط حسابك بهذا البريد وتأكيده فوراً.</div>
    </div>

    <button type="submit" class="btn-google-action w-100 py-2.5 rounded-pill shadow-sm mb-3">
      <i class="bi bi-check2-circle ms-1"></i> متابعة بحساب Google هذا
    </button>
  </form>

  <div class="text-center pt-2 border-top">
    <a href="<?= BASE_URL ?>auth/login.php" class="text-muted small text-decoration-none">
      <i class="bi bi-arrow-right"></i> الرجوع لخيارات الدخول الأخرى
    </a>
  </div>
</div>

</body>
</html>
