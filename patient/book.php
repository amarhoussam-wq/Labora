<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

$userId = currentUserId();

// Fetch active laboratories for dropdown (Requirement 3: اختيار مخبر أثناء الحجز)
$labs = $conn->query("SELECT id, name, city, address, phone FROM laboratories WHERE status='active' ORDER BY city, name")->fetchAll();

$selectedLabId = (int)($_GET['lab_id'] ?? ($labs[0]['id'] ?? 0));

define('PAGE_TITLE', 'حجز موعد تحاليل طبية');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 680px;">
  <!-- Header -->
  <div class="text-center mb-4">
    <div class="brand-icon mx-auto mb-2" style="width:48px; height:48px; font-size:1.4rem;">
      <i class="bi bi-calendar-plus"></i>
    </div>
    <h3 class="fw-800 text-dark">طلب حجز موعد تحاليل</h3>
    <p class="text-muted small">املأ بيانات موعدك وارفع وصفتك الطبية ليتم تأكيد الموعد من المخبر</p>
  </div>

  <div class="lb-card p-4 shadow-sm border-0 bg-white rounded-4">
    <div id="bookingAlert" class="alert d-none"></div>

    <form id="patientBookForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="patient_id" value="<?= $userId ?>">

      <!-- 1. Select Laboratory -->
      <div class="mb-3">
        <label class="form-label fw-bold text-dark">
          <i class="bi bi-hospital text-primary ms-1"></i> اختر المخبر الطبي *
        </label>
        <select class="form-select form-select-lg" name="laboratory_id" id="labSelect" required>
          <?php foreach ($labs as $lb): ?>
            <option value="<?= (int)$lb['id'] ?>" <?= $selectedLabId === (int)$lb['id'] ? 'selected' : '' ?>>
              <?= h($lb['name']) ?> (<?= h($lb['city']) ?> — <?= h($lb['address']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 2. Manual Analyses Entry -->
      <div class="mb-3">
        <label class="form-label fw-bold text-dark">
          <i class="bi bi-pen text-primary ms-1"></i> التحاليل الطبية المطلوبة (كتابة يدوية) *
        </label>
        <textarea class="form-control" name="analyses_text" rows="3" 
                  placeholder="اكتب هنا التحاليل كما هي مدونة في وصفتك، مثال:&#10;تعداد الدم الكامل NFS، سكر صائم Glycémie، فيتامين D..." required></textarea>
        <div class="form-text small text-muted">اكتب أسماء الفحوصات المذكورة في الوصفة الطبية</div>
      </div>

      <!-- 3. Attach Prescription -->
      <div class="mb-3">
        <label class="form-label fw-bold text-dark">
          <i class="bi bi-file-earmark-medical text-primary ms-1"></i> إرفاق صورة أو PDF للوصفة الطبية (اختياري)
        </label>
        <input type="file" class="form-control" name="prescription" accept="image/jpeg,image/png,application/pdf">
        <div class="form-text small text-muted">الأنواع المسموحة: JPG, PNG, PDF (الحد الأقصى: 5 ميغابايت)</div>
      </div>

      <hr class="my-4">

      <!-- 4. Date & Available Slot -->
      <div class="row g-2 mb-3">
        <div class="col-sm-6">
          <label class="form-label fw-bold text-dark">
            <i class="bi bi-calendar-event text-primary ms-1"></i> يوم الموعد *
          </label>
          <select class="form-select" id="dateSelect" required>
            <option value="">جارٍ تحميل التواريخ...</option>
          </select>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-bold text-dark">
            <i class="bi bi-clock text-primary ms-1"></i> التوقيت المتاح *
          </label>
          <select class="form-select" name="slot_id" id="slotSelect" required>
            <option value="">اختر اليوم أولاً</option>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold text-dark">
          <i class="bi bi-chat-left-text text-primary ms-1"></i> ملاحظات إضافية (اختياري)
        </label>
        <input type="text" class="form-control" name="notes" placeholder="أي ملاحظات تود إبلاغ المخبر بها...">
      </div>

      <button type="submit" class="btn-lb-primary w-100 py-3 justify-content-center fs-6" id="submitBtn">
        <i class="bi bi-send ms-1"></i> إرسال طلب الحجز
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const labSelect = document.getElementById('labSelect');
  const dateSelect = document.getElementById('dateSelect');
  const slotSelect = document.getElementById('slotSelect');
  const form = document.getElementById('patientBookForm');
  const submitBtn = document.getElementById('submitBtn');
  const alertBox = document.getElementById('bookingAlert');

  function loadSlots(selectedDate = '') {
    const labId = labSelect.value;
    if (!labId) return;

    dateSelect.innerHTML = '<option value="">جارٍ التحميل...</option>';
    slotSelect.innerHTML = '<option value="">جارٍ التحميل...</option>';

    fetch(`<?= BASE_URL ?>api/slots.php?lab_id=${labId}&date=${encodeURIComponent(selectedDate)}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !data.available_dates || data.available_dates.length === 0) {
          dateSelect.innerHTML = '<option value="">لا توجد مواعيد مفتوحة حالياً</option>';
          slotSelect.innerHTML = '<option value="">لا توجد فترات شاغرة</option>';
          return;
        }

        // Populate dates
        let dateHtml = '';
        data.available_dates.forEach(d => {
          const isSel = (d === data.selected_date) ? 'selected' : '';
          dateHtml += `<option value="${d}" ${isSel}>${d}</option>`;
        });
        dateSelect.innerHTML = dateHtml;

        // Populate slots
        if (!data.slots || data.slots.length === 0) {
          slotSelect.innerHTML = '<option value="">اكتملت جميع فترات هذا اليوم</option>';
        } else {
          let slotHtml = '<option value="">اختر توقيت الحجز</option>';
          data.slots.forEach(s => {
            slotHtml += `<option value="${s.id}">${s.time_formatted} (متبقي ${s.remaining_slots} أماكن)</option>`;
          });
          slotSelect.innerHTML = slotHtml;
        }
      })
      .catch(err => {
        console.error(err);
      });
  }

  labSelect.addEventListener('change', () => loadSlots(''));
  dateSelect.addEventListener('change', (e) => loadSlots(e.target.value));

  // Initial load
  loadSlots('');

  // Submit via AJAX
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm ms-2"></span> جارٍ إرسال الطلب...';

    const formData = new FormData(form);

    fetch('<?= BASE_URL ?>api/book.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alertBox.className = 'alert alert-success';
        alertBox.innerHTML = '<i class="bi bi-check-circle-fill ms-2"></i> ' + data.message;
        alertBox.classList.remove('d-none');
        setTimeout(() => {
          window.location.href = '<?= BASE_URL ?>patient/appointments.php';
        }, 1500);
      } else {
        alertBox.className = 'alert alert-danger';
        alertBox.innerHTML = '<i class="bi bi-exclamation-circle-fill ms-2"></i> ' + data.message;
        alertBox.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send ms-1"></i> إعادة المحاولة';
      }
    })
    .catch(err => {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = 'حدث خطأ في الاتصال بالخادم';
      alertBox.classList.remove('d-none');
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-send ms-1"></i> إرسال طلب الحجز';
    });
  });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
