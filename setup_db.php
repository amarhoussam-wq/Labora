<?php
session_start();

$localConfigFile = __DIR__ . '/db_config.php';
$savedConfig = file_exists($localConfigFile) ? (require $localConfigFile) : [];

$host = $_POST['db_host'] ?? ($savedConfig['host'] ?? (getenv('DB_HOST') ?: 'mysql'));
$port = (int)($_POST['db_port'] ?? ($savedConfig['port'] ?? (getenv('DB_PORT') ?: 3306)));
$db   = $_POST['db_name'] ?? ($savedConfig['name'] ?? (getenv('DB_NAME') ?: 'labora'));
$user = $_POST['db_user'] ?? ($savedConfig['user'] ?? (getenv('DB_USER') ?: 'root'));
$pass = $_POST['db_pass'] ?? ($savedConfig['pass'] ?? (getenv('DB_PASS') ?: ''));

$msg = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to server
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        // Create Database if needed
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");

        // Run labora.sql
        $sqlPath = __DIR__ . '/labora.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }

        // Save working config to db_config.php
        $exportCode = "<?php\nreturn " . var_export([
            'host' => $host,
            'port' => $port,
            'name' => $db,
            'user' => $user,
            'pass' => $pass
        ], true) . ";\n";
        @file_put_contents($localConfigFile, $exportCode);

        $msg = "✅ تم الاتصال بنجاح بالخادم ($host) وتوليد قاعدة البيانات $db والجداول والبيانات التجريبية، وحُفظ الإعداد فوراً!";
        $status = "success";
    } catch (Exception $e) {
        $msg = "❌ فشل الاتصال: " . $e->getMessage();
        $status = "danger";
    }
} else {
    // Check if current config works
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $msg = "الاتصال بقاعدة البيانات يعمل بنجاح! ($host:$port / $db)";
        $status = "success";
    } catch (Exception $e) {
        $msg = "لم يتم الاتصال بقاعدة البيانات بعد: " . $e->getMessage();
        $status = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تهيئة الاتصال بقاعدة بيانات LABORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Tajawal', sans-serif; background: #eef2f6; padding: 2rem 1rem; }
    .card { border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    .host-badge { cursor: pointer; transition: 0.2s; }
    .host-badge:hover { transform: scale(1.05); }
  </style>
</head>
<body>
<div class="container" style="max-width: 680px;">
  <div class="card p-4 bg-white border-0">
    <div class="text-center mb-3">
      <h3 class="fw-bold text-primary mb-1">تهيئة الاتصال بـ MySQL على Coolify</h3>
      <p class="text-muted small">حدد بيانات خادم MySQL ليتم الربط وتثبيت الجداول بنقرة واحدة</p>
    </div>

    <div class="alert alert-<?= $status ?> small">
      <?= htmlspecialchars($msg) ?>
    </div>

    <!-- Quick Host Suggestions -->
    <div class="mb-3 p-3 bg-light rounded-3 small">
      <strong class="d-block mb-2 text-dark">خيارات المضيف الشائعة في Coolify (اضغط لتجربة أي منها):</strong>
      <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-secondary host-badge p-2" onclick="setHost('host.docker.internal', 3306)">host.docker.internal</span>
        <span class="badge bg-secondary host-badge p-2" onclick="setHost('172.17.0.1', 3306)">172.17.0.1 (Docker Gateway)</span>
        <span class="badge bg-secondary host-badge p-2" onclick="setHost('194.34.232.105', 3306)">194.34.232.105 (IP السيرفر)</span>
      </div>
      <small class="text-muted d-block mt-2">أو افتح صفحة MySQL في Coolify وانسخ <strong>Internal Hostname / Container Name</strong>.</small>
    </div>

    <form method="POST" action="">
      <div class="row g-2 mb-3">
        <div class="col-8">
          <label class="form-label small fw-bold">مضيف قاعدة البيانات (Host / IP) *</label>
          <input type="text" class="form-control" name="db_host" id="db_host" value="<?= htmlspecialchars($host) ?>" required>
        </div>
        <div class="col-4">
          <label class="form-label small fw-bold">المنفذ (Port)</label>
          <input type="number" class="form-control" name="db_port" id="db_port" value="<?= htmlspecialchars($port) ?>" required>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-bold">اسم المستخدم (User) *</label>
          <input type="text" class="form-control" name="db_user" value="<?= htmlspecialchars($user) ?>" required>
        </div>
        <div class="col-6">
          <label class="form-label small fw-bold">كلمة المرور (Password)</label>
          <input type="password" class="form-control" name="db_pass" value="<?= htmlspecialchars($pass) ?>" placeholder="كلمة مرور MySQL">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small fw-bold">اسم قاعدة البيانات</label>
        <input type="text" class="form-control" name="db_name" value="<?= htmlspecialchars($db) ?>" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-6">
        🚀 حفظ والاتصال وإنشاء الجداول الآن
      </button>
    </form>

    <?php if ($status === 'success'): ?>
      <div class="mt-4 pt-3 border-top text-center">
        <a href="auth/login.php" class="btn btn-success px-4 py-2 fw-bold">
          الانتقال لتسجيل الدخول (حساب المخبر) <i class="bi bi-arrow-left"></i>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function setHost(h, p) {
  document.getElementById('db_host').value = h;
  if (p) document.getElementById('db_port').value = p;
}
</script>
</body>
</html>
