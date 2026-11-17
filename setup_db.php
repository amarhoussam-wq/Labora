<?php
session_start();

$host = getenv('DB_HOST') ?: ($_POST['db_host'] ?? 'localhost');
$db   = getenv('DB_NAME') ?: ($_POST['db_name'] ?? 'labora');
$user = getenv('DB_USER') ?: ($_POST['db_user'] ?? 'root');
$pass = getenv('DB_PASS') ?: ($_POST['db_pass'] ?? '');

$msg = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['init_db'])) {
    try {
        // Connect to server (without specific db first to create it if needed)
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create DB
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");

        // Run labora.sql
        $sqlPath = __DIR__ . '/labora.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
            $msg = "تم الاتصال بنجاح وإنشاء قاعدة البيانات والجداول والبيانات التجريبية!";
            $status = "success";
        } else {
            $msg = "تم إنشاء قاعدة البيانات ولكن ملف labora.sql غير موجود.";
            $status = "warning";
        }
    } catch (Exception $e) {
        $msg = "فشل الاتصال بقاعدة البيانات: " . $e->getMessage();
        $status = "danger";
    }
} else {
    // Test current connection
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $msg = "الاتصال بقاعدة البيانات يعمل بنجاح! (" . $host . " / " . $db . ")";
        $status = "success";
    } catch (Exception $e) {
        $msg = "حالة الاتصال الحالية: " . $e->getMessage();
        $status = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تهيئة قاعدة بيانات LABORA على Coolify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Tajawal', sans-serif; background: #f0f4f8; padding: 2rem 1rem; }
    .card { border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
  </style>
</head>
<body>
<div class="container" style="max-width: 650px;">
  <div class="card p-4 bg-white border-0">
    <div class="text-center mb-4">
      <h3 class="fw-bold text-primary">تهيئة قاعدة بيانات LABORA</h3>
      <p class="text-muted small">هذه الصفحة تفحص وتنشئ قاعدة البيانات تلقائياً على خادم Coolify</p>
    </div>

    <div class="alert alert-<?= $status ?>">
      <?= htmlspecialchars($msg) ?>
    </div>

    <div class="p-3 bg-light rounded-3 mb-4 small">
      <strong>المتغيرات المقروءة حالياً من السيرفر (Environment Variables):</strong>
      <ul class="mb-0 mt-2">
        <li><code>DB_HOST:</code> <?= htmlspecialchars($host) ?></li>
        <li><code>DB_NAME:</code> <?= htmlspecialchars($db) ?></li>
        <li><code>DB_USER:</code> <?= htmlspecialchars($user) ?></li>
        <li><code>DB_PASS:</code> <?= $pass ? '****** (محددة)' : 'فارغة' ?></li>
      </ul>
    </div>

    <form method="POST" action="">
      <h6 class="fw-bold mb-3">تثبيت وتوليد الجداول تلقائياً من <code>labora.sql</code>:</h6>
      <button type="submit" name="init_db" value="1" class="btn btn-primary w-100 py-2 fw-bold">
        🔄 تشغيل وتوليد قاعدة البيانات الآن
      </button>
    </form>

    <div class="mt-4 pt-3 border-top text-center">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">العودة للرئيسية</a>
      <a href="auth/login.php" class="btn btn-outline-primary btn-sm ms-2">صفحة تسجيل الدخول</a>
    </div>
  </div>
</div>
</body>
</html>
