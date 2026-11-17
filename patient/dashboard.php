<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

$userId = currentUserId();

// Count patient's appointments
$stmt = $conn->prepare("
    SELECT 
      COUNT(*) AS total,
      SUM(status = 'pending') AS pending_cnt,
      SUM(status = 'accepted') AS accepted_cnt
    FROM appointments 
    WHERE patient_id = :pid
");
$stmt->execute([':pid' => $userId]);
$stats = $stmt->fetch();

define('PAGE_TITLE', 'لوحة تحكم المريض');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 800px;">
  <!-- Welcome -->
  <div class="lb-card p-4 mb-4 bg-white border-0 shadow-sm rounded-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h4 class="fw-800 text-dark mb-1">مرحباً بك، <?= h(currentUserName()) ?> 👋</h4>
        <p class="text-muted small mb-0">لوحة المريض الخاصة بك على منصة LABORA</p>
      </div>
      <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary py-2 px-3">
        <i class="bi bi-calendar-plus ms-1"></i> طلب حجز جديد
      </a>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="stat-widget">
        <div class="wi-icon wi-blue">
          <i class="bi bi-calendar3"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num"><?= (int)($stats['total'] ?? 0) ?></div>
          <div class="wi-lbl">إجمالي الحجوزات</div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="stat-widget">
        <div class="wi-icon wi-orange">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num"><?= (int)($stats['pending_cnt'] ?? 0) ?></div>
          <div class="wi-lbl">قيد الانتظار</div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="stat-widget">
        <div class="wi-icon wi-green">
          <i class="bi bi-check-circle"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num"><?= (int)($stats['accepted_cnt'] ?? 0) ?></div>
          <div class="wi-lbl">حجوزات مقبولة</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="row g-3">
    <div class="col-sm-6">
      <div class="lb-card p-4 h-100 text-center bg-white border-0 shadow-sm rounded-4">
        <i class="bi bi-calendar-check text-primary fs-1 mb-2"></i>
        <h5 class="fw-bold text-dark">متابعة مواعيدي</h5>
        <p class="text-muted small">عرض تفاصيل الحجوزات، حالة القبول والرفض من المخبر، أو إلغاء موعد.</p>
        <a href="<?= BASE_URL ?>patient/appointments.php" class="btn-lb-outline w-100 py-2">
          عرض المواعيد <i class="bi bi-arrow-left ms-1"></i>
        </a>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="lb-card p-4 h-100 text-center bg-white border-0 shadow-sm rounded-4">
        <i class="bi bi-hospital text-success fs-1 mb-2"></i>
        <h5 class="fw-bold text-dark">حجز تحليل جديد</h5>
        <p class="text-muted small">اختر مخبراً، دوّن التحاليل المطلوبة يدوياً، وارفع وصفتك الطبية في خطوات سريعة.</p>
        <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary w-100 py-2">
          ابدأ الحجز الآن <i class="bi bi-arrow-left ms-1"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
