<?php
require_once dirname(__DIR__) . '/config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$formData = [
    'first_name' => '',
    'last_name'  => '',
    'email'      => '',
    'phone'      => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'طلب غير صالح أو انتهت صلاحية الجلسة.';
    } else {
        $fn   = trim($_POST['first_name'] ?? '');
        $ln   = trim($_POST['last_name'] ?? '');
        $em   = trim($_POST['email'] ?? '');
        $ph   = trim($_POST['phone'] ?? '');
        $pw   = $_POST['password'] ?? '';
        $pw2  = $_POST['confirm_password'] ?? '';

        $formData = [
            'first_name' => $fn,
            'last_name'  => $ln,
            'email'      => $em,
            'phone'      => $ph
        ];

        if (!$fn) $errors[] = 'الاسم الأول مطلوب.';
        if (!$ln) $errors[] = 'اللقب مطلوب.';
        if (!$em || !filter_var($em, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صالح.';
        if (strlen($pw) < 6) $errors[] = 'كلمة المرور يجب أن تتكون من 6 أحرف على الأقل.';
        if ($pw !== $pw2) $errors[] = 'كلمتا المرور غير متطابقتين.';

        if (empty($errors)) {
            if (!$conn) {
                $errors[] = 'لا يمكن الاتصال بقاعدة البيانات حالياً.';
            } else {
                $check = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $check->execute([':email' => $em]);
                if ($check->fetch()) {
                    $errors[] = 'هذا البريد الإلكتروني مسجل بالفعل. يرجى تسجيل الدخول.';
                } else {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $ins = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (:fn, :ln, :em, :ph, :pw, 'patient')");
                    $ins->execute([
                        ':fn' => $fn,
                        ':ln' => $ln,
                        ':em' => $em,
                        ':ph' => $ph ?: null,
                        ':pw' => $hash
                    ]);
                    setFlash('success', 'تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.');
                    redirect('auth/login.php');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إنشاء حساب جديد — LABORA</title>
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

  <h4 class="text-center fw-800 mb-1" style="color:var(--lb-text-dark);">إنشاء حساب مريض</h4>
  <p class="text-center text-lb-muted small mb-4">انضم إلى LABORA لحجز مواعيد التحاليل الطبية وإدارتها بسهولة</p>

  <?php if (!empty($errors)): ?>
    <div class="lb-alert lb-alert-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <div>
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <?= csrfField() ?>

    <div class="row g-2 mb-2">
      <div class="col-6">
        <label class="lb-label" for="first_name">الاسم الأول *</label>
        <input type="text" class="lb-input" id="first_name" name="first_name"
               value="<?= h($formData['first_name']) ?>" required>
      </div>
      <div class="col-6">
        <label class="lb-label" for="last_name">اللقب *</label>
        <input type="text" class="lb-input" id="last_name" name="last_name"
               value="<?= h($formData['last_name']) ?>" required>
      </div>
    </div>

    <div class="lb-form-group">
      <label class="lb-label" for="email">البريد الإلكتروني *</label>
      <input type="email" class="lb-input" id="email" name="email"
             value="<?= h($formData['email']) ?>" placeholder="name@example.com" required>
    </div>

    <div class="lb-form-group">
      <label class="lb-label" for="phone">رقم الهاتف (اختياري)</label>
      <input type="tel" class="lb-input" id="phone" name="phone"
             value="<?= h($formData['phone']) ?>" placeholder="06XXXXXXXX">
    </div>

    <div class="row g-2 mb-4">
      <div class="col-6">
        <label class="lb-label" for="password">كلمة المرور *</label>
        <input type="password" class="lb-input" id="password" name="password"
               placeholder="6 أحرف على الأقل" required>
      </div>
      <div class="col-6">
        <label class="lb-label" for="confirm_password">تأكيد كلمة المرور *</label>
        <input type="password" class="lb-input" id="confirm_password" name="confirm_password"
               placeholder="إعادة كلمة المرور" required>
      </div>
    </div>

    <button type="submit" class="btn-lb-primary w-100 justify-content-center py-2 mb-3">
      <i class="bi bi-person-check ms-1"></i> تسجيل الحساب
    </button>
  </form>

  <div class="text-center mt-3 pt-3 border-top">
    <p class="small text-lb-muted mb-2">لديك حساب بالفعل؟</p>
    <a href="<?= BASE_URL ?>auth/login.php" class="btn-lb-outline w-100 justify-content-center py-2">
      تسجيل الدخول
    </a>
  </div>

  <div class="text-center mt-3">
    <a href="<?= BASE_URL ?>index.php" class="small text-lb-muted">
      <i class="bi bi-arrow-right"></i> العودة للصفحة الرئيسية
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
