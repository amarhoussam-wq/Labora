<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

$userId = currentUserId();

// Fetch active laboratories for dropdown
$labs = $conn->query("SELECT id, name, city, address, phone FROM laboratories WHERE status='active' ORDER BY city, name")->fetchAll();
$selectedLabId = (int)($_GET['lab_id'] ?? ($labs[0]['id'] ?? 0));

define('PAGE_TITLE', 'حجز موعد تحاليل طبية');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 820px;">
  <!-- Header -->
  <div class="text-center mb-4">
    <div class="brand-icon mx-auto mb-2" style="width:52px; height:52px; font-size:1.5rem; background: linear-gradient(135deg, #1565C0, #0288D1);">
      <i class="bi bi-calendar2-check"></i>
    </div>
    <h3 class="fw-800 text-dark">حجز موعد فحص وتحاليل طبية</h3>
    <p class="text-muted small">اختر المخبر، حدد التحاليل المطلوبة أو صوّر وصفتك الطبية مباشرة بكل بساطة</p>
  </div>

  <div class="lb-card p-4 p-md-5 shadow-sm border-0 bg-white rounded-4">
    <div id="bookingAlert" class="alert d-none rounded-3 shadow-sm"></div>

    <form id="patientBookForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="patient_id" value="<?= $userId ?>">
      <!-- Hidden field that collects selected analyses text -->
      <input type="hidden" name="analyses_text" id="finalAnalysesText" value="">

      <!-- ── الخطوة 1: اختيار المخبر ──────────────────────────── -->
      <div class="mb-4">
        <label class="form-label fw-bold text-dark fs-6">
          <span class="badge bg-primary rounded-circle px-2 py-1 me-1">1</span>
          اختر المخبر الطبي:
        </label>
        <select class="form-select form-select-lg rounded-3 shadow-sm" name="laboratory_id" id="labSelect" required style="font-weight:600;">
          <?php foreach ($labs as $lb): ?>
            <option value="<?= (int)$lb['id'] ?>" <?= $selectedLabId === (int)$lb['id'] ? 'selected' : '' ?>>
              🏢 <?= h($lb['name']) ?> — <?= h($lb['city']) ?> (<?= h($lb['address']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text small text-muted mt-1">
          <i class="bi bi-info-circle me-1"></i> تتغير قائمة الفحوصات والأسعار والمواعيد المتاحة تلقائياً حسب المخبر المختار.
        </div>
      </div>

      <!-- ── الخطوة 2: قائمة التحاليل المتوفرة لدى المخبر ─────── -->
      <div class="mb-4 p-3.5 bg-light rounded-4 border">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <label class="form-label fw-bold text-dark fs-6 mb-0">
              <span class="badge bg-primary rounded-circle px-2 py-1 me-1">2</span>
              اختر التحاليل التي ترغب بإجرائها:
            </label>
            <div class="small text-muted">ضع إشارة (✓) أمام التحاليل المطلوبة:</div>
          </div>
          <!-- Live Price Summary Pill -->
          <div id="priceSummaryBadge" class="badge bg-white text-primary border px-3 py-2 rounded-pill shadow-sm fs-7">
            المحدد: <span id="selectedCount" class="fw-bold">0</span> تحليل &nbsp;|&nbsp;
            التكلفة التقديرية: <span id="totalPrice" class="fw-bold text-success">0</span> دج
          </div>
        </div>

        <!-- Quick filter for tests -->
        <div class="mb-2.5">
          <input type="text" id="searchAnalyses" class="form-control form-control-sm rounded-pill" placeholder="🔍 ابحث عن اسم التحليل (مثال: سكر، NFS، كوليسترول، فيتامين...)" style="font-size:0.85rem;">
        </div>

        <!-- Analyses Dynamic Checklist Container -->
        <div id="analysesContainer" class="p-2 bg-white rounded-3 border" style="max-height: 280px; overflow-y: auto;">
          <div class="text-center py-4 text-muted small">
            <span class="spinner-border spinner-border-sm me-2"></span> جارٍ تحميل قائمة تحاليل المخبر...
          </div>
        </div>
      </div>

      <!-- ── الخطوة 3: تصوير أو إرفاق الوصفة الطبية ──────────── -->
      <div class="mb-4 p-3.5 rounded-4" style="background: #F0FDF4; border: 1.5px dashed #86EFAC;">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="rounded-circle bg-success text-white p-2 d-inline-flex align-items-center justify-content-center" style="width:36px; height:36px;">
            <i class="bi bi-camera-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold text-success mb-0">📷 تصوير الوصفة الطبية (خيار مريح وسريع)</h6>
            <small class="text-muted">إذا كنت لا تعرف أسماء التحاليل، يكفيك فقط تصوير وصفتك الطبية وسيقوم المخبر بتجهيزها لك!</small>
          </div>
        </div>

        <input type="file" class="form-control rounded-3 mt-2" name="prescription" id="prescriptionInput" 
               accept="image/jpeg,image/png,image/webp,application/pdf" capture="environment">
        
        <div id="prescriptionPreview" class="mt-2 text-center d-none">
          <img id="previewImg" src="" alt="معاينة الوصفة" class="img-thumbnail rounded-3 shadow-sm" style="max-height: 160px;">
          <div class="small text-success mt-1 fw-bold">✓ تم إرفاق الوصفة بنجاح</div>
        </div>
      </div>

      <!-- ── الخطوة 4: اختيار يوم وتوقيت الموعد ──────────────── -->
      <div class="mb-4">
        <label class="form-label fw-bold text-dark fs-6 mb-2">
          <span class="badge bg-primary rounded-circle px-2 py-1 me-1">3</span>
          اختر موعد وتوقيت الزيارة:
        </label>

        <div class="row g-2">
          <div class="col-sm-6">
            <label class="form-label small fw-bold text-muted">اليوم المتاح:</label>
            <select class="form-select rounded-3 py-2" id="dateSelect" required>
              <option value="">جارٍ تحميل المواعيد...</option>
            </select>
          </div>

          <div class="col-sm-6">
            <label class="form-label small fw-bold text-muted">التوقيت المتاح:</label>
            <select class="form-select rounded-3 py-2" name="slot_id" id="slotSelect" required>
              <option value="">اختر اليوم أولاً</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ── ملاحظات اختيارية ───────────────────────────────── -->
      <div class="mb-4">
        <label class="form-label small fw-bold text-muted">
          <i class="bi bi-chat-left-text me-1"></i> ملاحظات إضافية للمخبر (اختياري):
        </label>
        <input type="text" class="form-control rounded-3" name="notes" placeholder="مثال: مريض سكري، صعوبة في أخذ العينة، إلخ...">
      </div>

      <!-- ── زر التأكيد والإرسال ─────────────────────────────── -->
      <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold fs-5 shadow" id="submitBtn" style="background: linear-gradient(135deg, #1565C0, #0288D1); border:none;">
        <i class="bi bi-check2-circle ms-1"></i> تأكيد وإرسال طلب الحجز للمخبر
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const labSelect = document.getElementById('labSelect');
  const dateSelect = document.getElementById('dateSelect');
  const slotSelect = document.getElementById('slotSelect');
  const analysesContainer = document.getElementById('analysesContainer');
  const searchInput = document.getElementById('searchAnalyses');
  const finalAnalysesText = document.getElementById('finalAnalysesText');
  const selectedCount = document.getElementById('selectedCount');
  const totalPrice = document.getElementById('totalPrice');
  const form = document.getElementById('patientBookForm');
  const submitBtn = document.getElementById('submitBtn');
  const alertBox = document.getElementById('bookingAlert');
  const prescriptionInput = document.getElementById('prescriptionInput');
  const prescriptionPreview = document.getElementById('prescriptionPreview');
  const previewImg = document.getElementById('previewImg');

  let currentAnalysesList = [];

  // 1. Preview prescription image if uploaded
  prescriptionInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        previewImg.src = evt.target.result;
        prescriptionPreview.classList.remove('d-none');
      };
      reader.readAsDataURL(file);
    } else {
      prescriptionPreview.classList.add('d-none');
    }
  });

  // 2. Load analyses available for selected lab
  function loadLabAnalyses() {
    const labId = labSelect.value;
    if (!labId) return;

    analysesContainer.innerHTML = '<div class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span> جارٍ تحميل قائمة تحاليل المخبر...</div>';

    fetch(`<?= BASE_URL ?>api/lab_analyses.php?lab_id=${labId}`)
      .then(res => res.json())
      .then(res => {
        if (!res.success || !res.data || res.data.length === 0) {
          analysesContainer.innerHTML = '<div class="text-center py-3 text-muted small">لا توجد تحاليل مسجلة لهذا المخبر حالياً. يمكنك تصوير وصفتك الطبية بالأسفل وسيتولى المخبر قراءتها.</div>';
          currentAnalysesList = [];
          updateSummary();
          return;
        }

        currentAnalysesList = res.data;
        renderAnalysesList(currentAnalysesList);
        updateSummary();
      })
      .catch(err => {
        analysesContainer.innerHTML = '<div class="text-danger small text-center py-2">تعذر تحميل التحاليل. يمكنك تصوير وصفتك الطبية بدلاً من ذلك.</div>';
      });
  }

  // 3. Render analyses checklist with search capability
  function renderAnalysesList(items) {
    if (items.length === 0) {
      analysesContainer.innerHTML = '<div class="text-muted small text-center py-2">لا توجد نتائج مطابقة لبحثك.</div>';
      return;
    }

    let html = '<div class="d-flex flex-column gap-2">';
    items.forEach(a => {
      const p = parseFloat(a.price) || 0;
      const fastingTag = a.fasting_required == 1 ? '<span class="badge bg-warning text-dark border px-2 py-0.5 rounded-pill ms-1" style="font-size:0.7rem;"><i class="bi bi-exclamation-triangle-fill"></i> يشترط الصيام</span>' : '';

      html += `
        <label class="p-2.5 rounded-3 border d-flex align-items-center justify-content-between gap-2 analysis-check-item" style="cursor:pointer; transition:0.15s; background:#FAFCFF;">
          <div class="d-flex align-items-center gap-2.5">
            <input type="checkbox" class="form-check-input analysis-cb" value="${a.id}" data-name="${a.name_ar} (${a.name_fr})" data-price="${p}" style="width:20px; height:20px; cursor:pointer;">
            <div>
              <div class="fw-bold text-dark" style="font-size:0.9rem;">${a.name_ar}</div>
              <div class="text-muted" style="font-size:0.75rem;">${a.name_fr} ${fastingTag}</div>
            </div>
          </div>
          <div class="fw-bold text-primary text-nowrap" style="font-size:0.88rem;">
            ${p > 0 ? p.toLocaleString() + ' دج' : 'حسب التسعيرة'}
          </div>
        </label>
      `;
    });
    html += '</div>';
    analysesContainer.innerHTML = html;

    // Attach click listeners to checkboxes
    document.querySelectorAll('.analysis-cb').forEach(cb => {
      cb.addEventListener('change', updateSummary);
    });
  }

  // 4. Update Summary & Hidden Text Field
  function updateSummary() {
    const checked = document.querySelectorAll('.analysis-cb:checked');
    let total = 0;
    const names = [];

    checked.forEach(cb => {
      total += parseFloat(cb.getAttribute('data-price')) || 0;
      names.push(cb.getAttribute('data-name'));
    });

    selectedCount.textContent = checked.length;
    totalPrice.textContent = total.toLocaleString();
    finalAnalysesText.value = names.join(' + ');
  }

  // Search filter
  searchInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) {
      renderAnalysesList(currentAnalysesList);
      return;
    }
    const filtered = currentAnalysesList.filter(a => 
      (a.name_ar && a.name_ar.toLowerCase().includes(q)) || 
      (a.name_fr && a.name_fr.toLowerCase().includes(q)) ||
      (a.category && a.category.toLowerCase().includes(q))
    );
    renderAnalysesList(filtered);
  });

  // 5. Load Dates & Slots for Lab
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

        let dateHtml = '';
        data.available_dates.forEach(d => {
          const isSel = (d === data.selected_date) ? 'selected' : '';
          dateHtml += `<option value="${d}" ${isSel}>📅 ${d}</option>`;
        });
        dateSelect.innerHTML = dateHtml;

        if (!data.slots || data.slots.length === 0) {
          slotSelect.innerHTML = '<option value="">اكتملت جميع فترات هذا اليوم</option>';
        } else {
          let slotHtml = '<option value="">اختر توقيت الحجز</option>';
          data.slots.forEach(s => {
            slotHtml += `<option value="${s.id}">⏰ ${s.time_formatted} (متبقي ${s.remaining_slots} أماكن)</option>`;
          });
          slotSelect.innerHTML = slotHtml;
        }
      })
      .catch(err => {
        console.error(err);
      });
  }

  // Lab changes
  labSelect.addEventListener('change', () => {
    loadLabAnalyses();
    loadSlots('');
  });
  dateSelect.addEventListener('change', (e) => loadSlots(e.target.value));

  // Initial calls
  loadLabAnalyses();
  loadSlots('');

  // 6. Submit via AJAX
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Verify at least 1 analysis is checked OR prescription file attached
    const checkedCount = document.querySelectorAll('.analysis-cb:checked').length;
    const hasFile = prescriptionInput.files.length > 0;

    if (checkedCount === 0 && !hasFile) {
      alert("يرجى وضع علامة أمام التحاليل المطلوبة من القائمة، أو تصوير وصفتك الطبية!");
      return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm ms-2"></span> جارٍ إرسال وتأكيد الطلب...';

    const formData = new FormData(form);

    fetch('<?= BASE_URL ?>api/book.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alertBox.className = 'alert alert-success d-block';
        alertBox.innerHTML = '<i class="bi bi-check-circle-fill ms-2"></i> ' + data.message;
        alertBox.classList.remove('d-none');
        setTimeout(() => {
          window.location.href = '<?= BASE_URL ?>patient/appointments.php';
        }, 1200);
      } else {
        alertBox.className = 'alert alert-danger d-block';
        alertBox.innerHTML = '<i class="bi bi-exclamation-circle-fill ms-2"></i> ' + data.message;
        alertBox.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send ms-1"></i> إعادة المحاولة';
      }
    })
    .catch(err => {
      alertBox.className = 'alert alert-danger d-block';
      alertBox.textContent = 'حدث خطأ في الاتصال بالخادم';
      alertBox.classList.remove('d-none');
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-send ms-1"></i> إرسال طلب الحجز';
    });
  });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
