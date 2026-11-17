<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

$userId = currentUserId();

$stmt = $conn->prepare("
    SELECT a.*, l.name AS laboratory_name, l.city AS laboratory_city, l.phone AS laboratory_phone, l.address AS laboratory_address
    FROM appointments a
    JOIN laboratories l ON l.id = a.laboratory_id
    WHERE a.patient_id = :pid
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([':pid' => $userId]);
$appointments = $stmt->fetchAll();

define('PAGE_TITLE', 'مواعيدي المحجوزة');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 850px;">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-800 text-dark mb-1"><i class="bi bi-calendar-check text-primary me-2"></i> مواعيدي المحجوزة</h3>
      <p class="text-muted small mb-0">تابع حالة طلبات مواعيدك لدى المخابر الطبية أو قم بإلغائها</p>
    </div>
    <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary py-2 px-3">
      <i class="bi bi-plus-circle ms-1"></i> حجز موعد جديد
    </a>
  </div>

  <div id="cancelAlert" class="alert d-none"></div>

  <?php if (empty($appointments)): ?>
    <div class="lb-card p-5 text-center text-muted bg-white border-0 shadow-sm rounded-4">
      <i class="bi bi-calendar-x fs-1 d-block mb-3 text-muted"></i>
      <h5 class="fw-bold text-dark">ليس لديك أي مواعيد محجوزة حالياً</h5>
      <p class="small text-muted mb-4">يمكنك بسهولة إرسال طلب حجز لأي مخبر في دقائق معدودة.</p>
      <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary">
        حجز موعدك الأول الآن
      </a>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach ($appointments as $apt): ?>
        <div class="lb-card p-4 bg-white border-0 shadow-sm rounded-4 position-relative">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
              <h5 class="fw-bold text-dark mb-1"><?= h($apt['laboratory_name']) ?></h5>
              <div class="small text-muted">
                <i class="bi bi-geo-alt text-primary ms-1"></i><?= h($apt['laboratory_city']) ?> — <?= h($apt['laboratory_address']) ?>
              </div>
            </div>
            <div>
              <span class="status-badge status-<?= h($apt['status']) ?>">
                <i class="bi bi-circle-fill" style="font-size:0.6rem;"></i>
                <?= match ($apt['status']) {
                  'pending'   => 'قيد الانتظار',
                  'accepted'  => 'تم القبول (مؤكد)',
                  'rejected'  => 'تم الرفض',
                  'cancelled' => 'ملغى',
                  default     => $apt['status']
                } ?>
              </span>
            </div>
          </div>

          <div class="row g-2 py-2 border-top border-bottom my-2 bg-light rounded-3 px-2 small">
            <div class="col-sm-4">
              <span class="text-muted d-block">تاريخ الموعد:</span>
              <strong class="text-dark"><i class="bi bi-calendar me-1"></i> <?= h($apt['appointment_date']) ?></strong>
            </div>
            <div class="col-sm-4">
              <span class="text-muted d-block">التوقيت:</span>
              <strong class="text-primary"><i class="bi bi-clock me-1"></i> <?= substr($apt['appointment_time'], 0, 5) ?></strong>
            </div>
            <div class="col-sm-4">
              <span class="text-muted d-block">هاتف المخبر:</span>
              <strong class="text-dark" dir="ltr"><i class="bi bi-telephone me-1"></i> <?= h($apt['laboratory_phone']) ?></strong>
            </div>
          </div>

          <!-- Analyses list -->
          <div class="small mt-2">
            <span class="text-muted fw-bold">التحاليل المطلوبة:</span>
            <div class="p-2 bg-white border rounded mt-1 text-dark">
              <?= nl2br(h($apt['analyses_text'])) ?>
            </div>
          </div>

          <!-- Prescription link -->
          <?php if (!empty($apt['prescription_file'])): ?>
            <div class="small mt-2">
              <a href="<?= BASE_URL ?>view_prescription.php?file=<?= urlencode($apt['prescription_file']) ?>" 
                 target="_blank" class="text-primary fw-bold text-decoration-none">
                <i class="bi bi-paperclip me-1"></i> عرض الوصفة الطبية المرفقة
              </a>
            </div>
          <?php endif; ?>

          <!-- Rejection reason if any -->
          <?php if ($apt['status'] === 'rejected' && !empty($apt['rejection_reason'])): ?>
            <div class="alert alert-danger py-2 px-3 small mt-2 mb-0">
              <i class="bi bi-x-circle-fill ms-1"></i> <strong>سبب الرفض:</strong> <?= h($apt['rejection_reason']) ?>
            </div>
          <?php endif; ?>

          <!-- Cancel button if pending -->
          <?php if ($apt['status'] === 'pending'): ?>
            <div class="text-start mt-3 pt-2 border-top">
              <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelAppointment(<?= (int)$apt['id'] ?>)">
                <i class="bi bi-trash ms-1"></i> إلغاء الحجز
              </button>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function cancelAppointment(aptId) {
  if (!confirm("هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟ سيتم تحرير الفترة الزمنية.")) {
    return;
  }

  const alertBox = document.getElementById('cancelAlert');
  const fd = new FormData();
  fd.append('appointment_id', aptId);
  fd.append('patient_id', '<?= $userId ?>');

  fetch('<?= BASE_URL ?>api/cancel.php', {
    method: 'POST',
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alertBox.className = 'alert alert-success';
      alertBox.textContent = data.message;
      alertBox.classList.remove('d-none');
      setTimeout(() => location.reload(), 1200);
    } else {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = data.message;
      alertBox.classList.remove('d-none');
    }
  })
  .catch(err => {
    alert("حدث خطأ في الاتصال");
  });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
