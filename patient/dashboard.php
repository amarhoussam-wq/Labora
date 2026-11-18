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

// Fetch latest appointment
$lastAptStmt = $conn->prepare("
    SELECT a.*, l.name AS laboratory_name, l.city AS laboratory_city
    FROM appointments a
    JOIN laboratories l ON l.id = a.laboratory_id
    WHERE a.patient_id = :pid
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 1
");
$lastAptStmt->execute([':pid' => $userId]);
$lastApt = $lastAptStmt->fetch();

define('PAGE_TITLE', 'لوحة تحكم المريض');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">
  <!-- Welcome Banner -->
  <div class="p-4 mb-4 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, #0D47A1 0%, #1565C0 55%, #0288D1 100%);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <div class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 mb-2 small fw-bold">
          <i class="bi bi-shield-check ms-1"></i> حساب مريض موثّق
        </div>
        <h3 class="fw-800 text-white mb-1">أهلاً بك، <?= h(currentUserName()) ?> 👋</h3>
        <p class="text-white-50 small mb-0">لوحتك الرقمية لحجز ومتابعة الفحوصات والتحاليل الطبية بكل سهولة</p>
      </div>
      <a href="<?= BASE_URL ?>patient/book.php" class="btn btn-light fw-bold py-2.5 px-4 rounded-pill shadow-sm text-primary">
        <i class="bi bi-calendar-plus ms-1"></i> حجز موعد جديد
      </a>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
      <div class="stat-widget">
        <div class="wi-icon wi-blue">
          <i class="bi bi-calendar3"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num"><?= (int)($stats['total'] ?? 0) ?></div>
          <div class="wi-lbl">إجمالي المواعيد المطلوبة</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="stat-widget">
        <div class="wi-icon wi-orange">
          <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num text-warning"><?= (int)($stats['pending_cnt'] ?? 0) ?></div>
          <div class="wi-lbl">بانتظار تأكيد المخبر</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-12">
      <div class="stat-widget">
        <div class="wi-icon wi-green">
          <i class="bi bi-check2-circle"></i>
        </div>
        <div class="wi-data">
          <div class="wi-num text-success"><?= (int)($stats['accepted_cnt'] ?? 0) ?></div>
          <div class="wi-lbl">مواعيد مقبولة ومؤكدة</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Latest Appointment Alert if exists -->
  <?php if ($lastApt): ?>
    <div class="p-3 mb-4 rounded-4 bg-white border shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="p-2.5 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <div class="small text-muted">آخر موعد تم تسجيله:</div>
          <div class="fw-bold text-dark">
            <?= h($lastApt['laboratory_name']) ?> — <?= h($lastApt['appointment_date']) ?> (<?= substr($lastApt['appointment_time'], 0, 5) ?>)
          </div>
        </div>
      </div>
      <div>
        <span class="status-badge status-<?= h($lastApt['status']) ?>">
          <?= match ($lastApt['status']) {
            'pending'   => 'قيد الانتظار',
            'accepted'  => 'مقبول ومؤكد',
            'rejected'  => 'مرفوض',
            'cancelled' => 'ملغى',
            default     => $lastApt['status']
          } ?>
        </span>
        <a href="<?= BASE_URL ?>patient/appointments.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-2">
          التفاصيل <i class="bi bi-arrow-left"></i>
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Action Cards -->
  <div class="row g-4">
    <div class="col-md-6">
      <div class="lb-card p-4 h-100 bg-white border-0 shadow-sm rounded-4 text-center d-flex flex-column justify-content-between">
        <div>
          <div class="feature-icon mb-3">
            <i class="bi bi-calendar-check text-primary"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">متابعة مواعيدي المحجوزة</h5>
          <p class="text-muted small mb-4">عرض سجل مواعيدك الكامل، حالة قبول المخابر لطلباتك، والوصفات المرفقة أو إلغاء موعد مسبق.</p>
        </div>
        <a href="<?= BASE_URL ?>patient/appointments.php" class="btn-lb-outline w-100 justify-content-center py-2.5">
          عرض قائمة المواعيد <i class="bi bi-arrow-left ms-1"></i>
        </a>
      </div>
    </div>

    <div class="col-md-6">
      <div class="lb-card p-4 h-100 bg-white border-0 shadow-sm rounded-4 text-center d-flex flex-column justify-content-between">
        <div>
          <div class="feature-icon mb-3" style="background:#ECFDF5; color:#059669;">
            <i class="bi bi-hospital text-success"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">طلب حجز تحليل جديد</h5>
          <p class="text-muted small mb-4">تصفح المخابر المتاحة، اختر الفترة الزمنية المناسبة، دوّن تحاليلك المطلوبة وارفع وصفتك بأمان.</p>
        </div>
        <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary w-100 justify-content-center py-2.5">
          بدء إجراءات الحجز الآن <i class="bi bi-arrow-left ms-1"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
