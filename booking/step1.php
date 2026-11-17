<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

$lab_id = (int)($_GET['lab_id'] ?? $_SESSION['booking']['lab_id'] ?? 0);
if (!$lab_id || !$conn) {
    setFlash('error', 'يرجى اختيار المخبر أولاً للبدء بالحجز.');
    redirect('laboratories.php');
}

// Load lab details
$stmt = $conn->prepare("SELECT * FROM laboratories WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $lab_id]);
$lab = $stmt->fetch();
if (!$lab) {
    setFlash('error', 'المخبر المطلوب غير متاح.');
    redirect('laboratories.php');
}

// Load analyses available at this lab
$stmt = $conn->prepare("
    SELECT la.id AS la_id, la.price, a.name_ar, a.name_fr, a.category, a.preparation_notes
    FROM laboratory_analyses la
    JOIN analyses a ON a.id = la.analysis_id
    WHERE la.laboratory_id = :lid AND la.status = 'available' AND a.status = 'active'
    ORDER BY a.category ASC, a.name_ar ASC
");
$stmt->execute([':lid' => $lab_id]);
$analysesRaw = $stmt->fetchAll();

$groupedAnalyses = [];
foreach ($analysesRaw as $an) {
    $groupedAnalyses[$an['category']][] = $an;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'طلب غير صالح أو انتهت صلاحية الجلسة.';
    } else {
        $selectedLaIds = array_map('intval', $_POST['analyses'] ?? []);
        if (empty($selectedLaIds)) {
            $error = 'يرجى اختيار تحليل طبي واحد على الأقل للمتابعة.';
        } else {
            // Validate that selected IDs belong to this lab
            $placeholders = implode(',', array_fill(0, count($selectedLaIds), '?'));
            $valStmt = $conn->prepare("
                SELECT la.id AS la_id, la.price, a.name_ar, a.name_fr
                FROM laboratory_analyses la
                JOIN analyses a ON a.id = la.analysis_id
                WHERE la.id IN ($placeholders) AND la.laboratory_id = ? AND la.status = 'available'
            ");
            $params = array_merge($selectedLaIds, [$lab_id]);
            $valStmt->execute($params);
            $validAnalyses = $valStmt->fetchAll();

            if (empty($validAnalyses)) {
                $error = 'التحاليل المحددة غير صالحة أو غير متوفرة.';
            } else {
                $totalPrice = 0;
                foreach ($validAnalyses as $item) {
                    $totalPrice += (float)$item['price'];
                }

                $_SESSION['booking'] = [
                    'lab_id'   => $lab_id,
                    'lab_name' => $lab['name'],
                    'analyses' => $validAnalyses,
                    'total'    => $totalPrice,
                ];
                redirect('booking/step2.php');
            }
        }
    }
}

// Pre-selected IDs from previous step if any
$preselected = [];
if (!empty($_SESSION['booking']['analyses']) && ($_SESSION['booking']['lab_id'] ?? 0) === $lab_id) {
    $preselected = array_column($_SESSION['booking']['analyses'], 'la_id');
}

define('PAGE_TITLE', 'الخطوة 1: اختيار التحاليل — ' . $lab['name']);
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="lb-page-header">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="mb-1"><i class="bi bi-calendar-plus me-2"></i> حجز موعد تحاليل طبية</h1>
        <p class="mb-0 text-white-50">المخبر: <strong><?= h($lab['name']) ?></strong> (<?= h($lab['city']) ?>)</p>
      </div>
      <a href="<?= BASE_URL ?>laboratory.php?id=<?= $lab['id'] ?>" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-right"></i> العودة للمخبر
      </a>
    </div>
  </div>
</div>

<div class="container pb-5">
  <!-- ── Steps Bar ─────────────────────────────────────────── -->
  <div class="steps-bar">
    <div class="step active">
      <div class="step-num">1</div>
      <div class="step-label">اختيار التحاليل</div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-label">تحديد الموعد</div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-label">التأكيد والوصفة</div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="lb-alert lb-alert-error mb-4">
      <i class="bi bi-exclamation-circle-fill"></i>
      <span><?= h($error) ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" action="" id="analysesForm">
    <?= csrfField() ?>

    <div class="row g-4">
      <!-- ── Analyses Selection Area ────────────────────────── -->
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-800 text-dark mb-0">
            <i class="bi bi-check2-square text-primary me-1"></i> حدد التحاليل المطلوبة لهذا الموعد:
          </h5>
          <small class="text-muted">يمكنك اختيار أكثر من تحليل في نفس الموعد</small>
        </div>

        <?php if (empty($groupedAnalyses)): ?>
          <div class="lb-card p-5 text-center text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <h5 class="mt-2">لا توجد تحاليل متاحة حالياً للحجز في هذا المخبر</h5>
          </div>
        <?php else: ?>
          <?php foreach ($groupedAnalyses as $catName => $items): ?>
            <div class="lb-card mb-4">
              <div class="lb-card-header bg-light">
                <h6 class="fw-bold mb-0 text-primary">
                  <i class="bi bi-folder2-open me-1"></i> <?= h($catName) ?>
                </h6>
              </div>
              <div class="lb-card-body p-3">
                <div class="d-flex flex-column gap-2">
                  <?php foreach ($items as $item): 
                    $isChecked = in_array((int)$item['la_id'], $preselected, true);
                  ?>
                    <label class="analysis-item d-flex align-items-center justify-content-between gap-3 p-3 <?= $isChecked ? 'selected' : '' ?>" style="cursor:pointer;">
                      <div class="d-flex align-items-center gap-3">
                        <input type="checkbox" name="analyses[]" value="<?= (int)$item['la_id'] ?>"
                               data-price="<?= (float)$item['price'] ?>"
                               data-name="<?= h($item['name_ar']) ?>"
                               <?= $isChecked ? 'checked' : '' ?>
                               class="analysis-checkbox">
                        <div>
                          <div class="analysis-name"><?= h($item['name_ar']) ?></div>
                          <div class="analysis-name-fr" dir="ltr" style="text-align:right;"><?= h($item['name_fr']) ?></div>
                          <?php if (!empty($item['preparation_notes'])): ?>
                            <small class="text-warning d-block" style="font-size:0.75rem;">
                              <i class="bi bi-info-circle ms-1"></i><?= h($item['preparation_notes']) ?>
                            </small>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="analysis-price">
                        <?= formatPrice((float)$item['price']) ?>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- ── Sidebar Summary ────────────────────────────────── -->
      <div class="col-lg-4">
        <div class="summary-box sticky-top" style="top:90px;">
          <h5 class="fw-800 text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-receipt text-primary me-1"></i> ملخص التحاليل المختارة
          </h5>

          <div id="selectedList" class="d-flex flex-column gap-2 mb-3" style="min-height: 80px;">
            <p class="text-muted small text-center my-3" id="noSelectionMsg">لم يتم تحديد أي تحليل بعد</p>
          </div>

          <div class="summary-row border-top pt-2 mt-2">
            <span class="s-label">العدد الإجمالي:</span>
            <span class="s-value" id="selectedCount">0 تحليل</span>
          </div>

          <div class="summary-row">
            <span class="s-label fw-bold">المجموع المقدر:</span>
            <span class="summary-total" id="totalPriceDisplay">0.00 دج</span>
          </div>

          <button type="submit" class="btn-lb-primary w-100 justify-content-center py-2 mt-4" id="continueBtn" disabled>
            متابعة لاختيار التاريخ والتوقيت <i class="bi bi-arrow-left ms-1"></i>
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkboxes = document.querySelectorAll('.analysis-checkbox');
  const selectedList = document.getElementById('selectedList');
  const noSelectionMsg = document.getElementById('noSelectionMsg');
  const selectedCount = document.getElementById('selectedCount');
  const totalPriceDisplay = document.getElementById('totalPriceDisplay');
  const continueBtn = document.getElementById('continueBtn');

  function updateSummary() {
    let count = 0;
    let total = 0;
    let html = '';

    checkboxes.forEach(cb => {
      const parentLabel = cb.closest('.analysis-item');
      if (cb.checked) {
        count++;
        const price = parseFloat(cb.dataset.price || 0);
        total += price;
        parentLabel.classList.add('selected');

        html += `
          <div class="d-flex justify-content-between small text-dark border-bottom pb-1">
            <span class="text-truncate" style="max-width: 60%;">${cb.dataset.name}</span>
            <span class="fw-bold text-primary">${price.toFixed(2)} دج</span>
          </div>
        `;
      } else {
        parentLabel.classList.remove('selected');
      }
    });

    if (count > 0) {
      noSelectionMsg.style.display = 'none';
      selectedList.innerHTML = html;
      continueBtn.disabled = false;
    } else {
      noSelectionMsg.style.display = 'block';
      selectedList.innerHTML = '';
      selectedList.appendChild(noSelectionMsg);
      continueBtn.disabled = true;
    }

    selectedCount.textContent = count + ' تحليل';
    totalPriceDisplay.textContent = total.toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' دج';
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', updateSummary);
  });

  // Initial update
  updateSummary();
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
