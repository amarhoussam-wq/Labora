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

<div class="container py-4" style="max-width: 900px;">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-800 text-dark mb-1"><i class="bi bi-calendar-check text-primary me-2"></i> مواعيدي المحجوزة</h3>
      <p class="text-muted small mb-0">تابع حالة طلبات مواعيدك لدى المخابر الطبية أو قم بإلغائها بكل مرونة</p>
    </div>
    <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary py-2 px-4 rounded-pill shadow-sm">
      <i class="bi bi-plus-circle ms-1"></i> حجز موعد جديد
    </a>
  </div>

  <div id="cancelAlert" class="alert d-none rounded-3 shadow-sm"></div>

  <?php if (empty($appointments)): ?>
    <div class="lb-card p-5 text-center text-muted bg-white border-0 shadow-sm rounded-4">
      <div class="p-3 bg-light rounded-circle d-inline-flex mb-3">
        <i class="bi bi-calendar-x fs-1 text-primary opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark">ليس لديك أي مواعيد محجوزة حالياً</h5>
      <p class="small text-muted mb-4">يمكنك بسهولة إرسال طلب حجز لأي مخبر معتمد في ثوانٍ معدودة.</p>
      <a href="<?= BASE_URL ?>patient/book.php" class="btn-lb-primary px-4 py-2 rounded-pill">
        حجز موعدك الأول الآن
      </a>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3.5">
      <?php foreach ($appointments as $apt): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white position-relative" style="border: 1px solid #EEF2F6 !important;">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2.5 d-flex align-items-center justify-content-center" style="width:46px; height:46px; font-size:1.3rem;">
                <i class="bi bi-hospital"></i>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0.5"><?= h($apt['laboratory_name']) ?></h5>
                <div class="small text-muted">
                  <i class="bi bi-geo-alt text-primary ms-1"></i><?= h($apt['laboratory_city']) ?> — <?= h($apt['laboratory_address']) ?>
                </div>
              </div>
            </div>

            <div>
              <span class="status-badge status-<?= h($apt['status']) ?>">
                <i class="bi bi-circle-fill" style="font-size:0.55rem;"></i>
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

          <!-- Appointment Meta Strip -->
          <div class="row g-2 py-2.5 px-3 my-2 bg-light rounded-3 small">
            <div class="col-sm-4">
              <span class="text-muted d-block small">تاريخ الموعد:</span>
              <strong class="text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i> <?= h($apt['appointment_date']) ?></strong>
            </div>
            <div class="col-sm-4">
              <span class="text-muted d-block small">التوقيت:</span>
              <strong class="text-primary"><i class="bi bi-clock me-1"></i> <?= substr($apt['appointment_time'], 0, 5) ?></strong>
            </div>
            <div class="col-sm-4">
              <span class="text-muted d-block small">هاتف المخبر:</span>
              <strong class="text-dark" dir="ltr"><i class="bi bi-telephone me-1 text-primary"></i> <?= h($apt['laboratory_phone']) ?></strong>
            </div>
          </div>

          <!-- Analyses list -->
          <div class="small mt-2">
            <span class="text-muted fw-bold">التحاليل والفحوصات المطلوبة:</span>
            <div class="p-2.5 bg-white border rounded-3 mt-1 text-dark" style="background:#FAFCFF !important; line-height:1.6;">
              <?= nl2br(h($apt['analyses_text'])) ?>
            </div>
          </div>

          <!-- Prescription link -->
          <?php if (!empty($apt['prescription_file'])): ?>
            <div class="small mt-2.5">
              <a href="<?= BASE_URL ?>view_prescription.php?file=<?= urlencode($apt['prescription_file']) ?>" 
                 target="_blank" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-bold">
                <i class="bi bi-file-earmark-medical me-1"></i> معاينة الوصفة الطبية المرفقة
              </a>
            </div>
          <?php endif; ?>

          <!-- Rejection reason if any -->
          <?php if ($apt['status'] === 'rejected' && !empty($apt['rejection_reason'])): ?>
            <div class="alert alert-danger py-2 px-3 small mt-3 mb-0 rounded-3 border-0">
              <i class="bi bi-x-circle-fill ms-1"></i> <strong>سبب الرفض من المخبر:</strong> <?= h($apt['rejection_reason']) ?>
            </div>
          <?php endif; ?>

          <!-- Cancel button if pending -->
          <?php if ($apt['status'] === 'pending'): ?>
            <div class="text-start mt-3 pt-2.5 border-top d-flex justify-content-between align-items-center">
              <span class="small text-muted">يمكنك إلغاء طلب الموعد طالما أنه لا يزال قيد الانتظار.</span>
              <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="cancelAppointment(<?= (int)$apt['id'] ?>)">
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
  if (!confirm("هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟ سيتم تحرير الفترة الزمنية للمخبر.")) {
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
      alertBox.className = 'alert alert-success d-block';
      alertBox.textContent = data.message;
      alertBox.classList.remove('d-none');
      setTimeout(() => location.reload(), 1000);
    } else {
      alertBox.className = 'alert alert-danger d-block';
      alertBox.textContent = data.message;
      alertBox.classList.remove('d-none');
    }
  })
  .catch(err => {
    alert("حدث خطأ أثناء معالجة الطلب");
  });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
