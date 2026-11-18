<?php
/**
 * LABORA - Shared HTML Header & Navigation
 * Include this file AFTER all PHP processing is complete
 * (after session, auth checks, DB queries, and any redirects)
 */

// Resolve active nav
$uri = $_SERVER['REQUEST_URI'] ?? '';

function navActive(string ...$patterns): string {
    global $uri;
    foreach ($patterns as $p) {
        if (str_contains($uri, $p)) return 'active';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= defined('PAGE_TITLE') ? h(PAGE_TITLE) . ' — LABORA' : 'LABORA | منصة التحاليل الطبية' ?></title>
  <meta name="description" content="LABORA - منصة رقمية تربط المرضى بمخابر التحاليل الطبية وتسهّل حجز المواعيد.">

  <!-- Bootstrap 5 RTL -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-free/css/all.min.css">
  <!-- Google Fonts: Tajawal (AR) + Poppins (EN/FR) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- LABORA CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/labora.css">
  <?php if (defined('EXTRA_CSS')) echo EXTRA_CSS; ?>
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────── -->
<nav class="labora-navbar navbar navbar-expand-lg">
  <div class="container">

    <!-- Brand -->
    <?php
    $brandUrl = BASE_URL . 'index.php';
    if (isLoggedIn()) {
        $role = currentUserRole();
        if ($role === 'patient') $brandUrl = BASE_URL . 'patient/dashboard.php';
        elseif ($role === 'lab_manager') $brandUrl = BASE_URL . 'lab/dashboard.php';
        elseif ($role === 'admin') $brandUrl = BASE_URL . 'admin/dashboard.php';
    }
    ?>
    <a class="navbar-brand" href="<?= $brandUrl ?>">
      <span class="brand-icon"><i class="bi bi-clipboard2-pulse"></i></span>
      <span>LABORA</span>
    </a>

    <div class="d-flex align-items-center gap-2">
      <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-sm btn-outline-danger d-lg-none py-1 px-2.5 rounded-pill" style="font-size:0.8rem;">
          <i class="bi bi-box-arrow-right ms-1"></i> خروج
        </a>
      <?php endif; ?>

      <!-- Mobile toggler -->
      <button class="navbar-toggler border-0 shadow-none p-1" type="button"
              data-bs-toggle="collapse" data-bs-target="#navbarMain"
              aria-controls="navbarMain" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navbarMain">

      <!-- Nav links -->
      <ul class="navbar-nav mx-auto gap-1">
        <?php if (isLoggedIn() && currentUserRole() === 'patient'): ?>
          <li class="nav-item">
            <a class="nav-link <?= navActive('patient/dashboard.php') ?>"
               href="<?= BASE_URL ?>patient/dashboard.php">
              <i class="bi bi-grid me-1"></i>لوحة التحكم
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= navActive('patient/book.php') ?>"
               href="<?= BASE_URL ?>patient/book.php">
              <i class="bi bi-plus-circle me-1"></i>حجز موعد
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= navActive('patient/appointments.php') ?>"
               href="<?= BASE_URL ?>patient/appointments.php">
              <i class="bi bi-calendar-check me-1"></i>مواعيدي المحجوزة
            </a>
          </li>
          <li class="nav-item d-lg-none mt-2 pt-2 border-top">
            <a class="nav-link text-danger fw-bold" href="<?= BASE_URL ?>auth/logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>تسجيل الخروج (<?= h(currentUserName()) ?>)
            </a>
          </li>
        <?php elseif (isLoggedIn() && currentUserRole() === 'lab_manager'): ?>
          <li class="nav-item">
            <a class="nav-link active fw-bold text-primary"
               href="<?= BASE_URL ?>lab/dashboard.php">
              <i class="bi bi-hospital me-1"></i>فضاء المخبر
            </a>
          </li>
          <li class="nav-item d-lg-none mt-2 pt-2 border-top">
            <a class="nav-link text-danger fw-bold" href="<?= BASE_URL ?>auth/logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>تسجيل الخروج
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= navActive('index.php') ?>"
               href="<?= BASE_URL ?>index.php">الرئيسية</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= navActive('laboratories', 'laboratory.php') ?>"
               href="<?= BASE_URL ?>laboratories.php">المخابر الطبية</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= navActive('analyses.php') ?>"
               href="<?= BASE_URL ?>analyses.php">التحاليل الطبية</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-primary fw-bold <?= navActive('lab') ?>"
               href="<?= BASE_URL ?>auth/login.php?role=lab">
              <i class="bi bi-hospital me-1"></i>فضاء المخبر
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Auth area (Desktop Only) -->
      <div class="d-none d-lg-flex align-items-center gap-2">
        <?php if (!isLoggedIn()): ?>
          <a href="<?= BASE_URL ?>auth/login.php" class="btn-nav-signin">تسجيل الدخول</a>
          <a href="<?= BASE_URL ?>auth/register.php" class="btn-nav-register">إنشاء حساب</a>

        <?php else: ?>
          <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-2"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i>
              <span><?= h(currentUserName() ?? '') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-start shadow-sm border-0">
              <?php if (currentUserRole() === 'patient'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>patient/dashboard.php">
                    <i class="bi bi-grid me-2 text-primary"></i>لوحة التحكم
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>patient/book.php">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>حجز موعد جديد
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>patient/appointments.php">
                    <i class="bi bi-calendar-check me-2 text-primary"></i>مواعيدي المحجوزة
                  </a>
                </li>
              <?php elseif (currentUserRole() === 'lab_manager'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>lab/dashboard.php">
                    <i class="bi bi-grid me-2 text-primary"></i>لوحة المخبر
                  </a>
                </li>
              <?php elseif (currentUserRole() === 'admin'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php">
                    <i class="bi bi-grid me-2 text-primary"></i>لوحة الإدارة
                  </a>
                </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج
                </a>
              </li>
            </ul>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /.navbar-collapse -->
  </div><!-- /.container -->
</nav>

<?php
// ── Flash message display ──────────────────────────────────
$flash = getFlash();
if ($flash):
    $iconMap = ['success' => 'bi-check-circle-fill', 'error' => 'bi-exclamation-circle-fill', 'info' => 'bi-info-circle-fill', 'warning' => 'bi-exclamation-triangle-fill'];
    $icon    = $iconMap[$flash['type']] ?? 'bi-info-circle-fill';
?>
<div class="container" style="padding-top:1rem;">
  <div class="lb-alert lb-alert-<?= h($flash['type']) ?>">
    <i class="bi <?= $icon ?>"></i>
    <span><?= h($flash['message']) ?></span>
  </div>
</div>
<?php endif; ?>
