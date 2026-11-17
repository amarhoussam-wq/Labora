<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('patient');

if (empty($_SESSION['booking']['lab_id']) || empty($_SESSION['booking']['analyses']) || !$conn) {
    setFlash('error', 'يرجى اختيار التحاليل أولاً.');
    redirect('laboratories.php');
}

$booking = &$_SESSION['booking'];
$lab_id  = (int)$booking['lab_id'];

// Get available dates from database (slots with available capacity)
$stmt = $conn->prepare("
    SELECT DISTINCT slot_date 
    FROM availability_slots 
    WHERE laboratory_id = :lid 
      AND slot_date >= CURDATE() 
      AND status = 'available' 
      AND current_bookings < max_bookings
    ORDER BY slot_date ASC 
    LIMIT 30
");
$stmt->execute([':lid' => $lab_id]);
$availableDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Default selected date
$selectedDate = trim($_GET['date'] ?? '');
if ($selectedDate === '' || !in_array($selectedDate, $availableDates, true)) {
    $selectedDate = !empty($availableDates) ? $availableDates[0] : date('Y-m-d');
}

// Fetch slots for this specific date
$stmt = $conn->prepare("
    SELECT * 
    FROM availability_slots 
    WHERE laboratory_id = :lid AND slot_date = :dt 
    ORDER BY slot_time ASC
");
$stmt->execute([':lid' => $lab_id, ':dt' => $selectedDate]);
$slotsForDate = $stmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'طلب غير صالح أو انتهت صلاحية الجلسة.';
    } else {
        $slot_id = (int)($_POST['slot_id'] ?? 0);
        if (!$slot_id) {
            $error = 'يرجى النقر على إحدى الفترات الزمنية المتاحة لتحديد موعدك.';
        } else {
            // Verify slot availability in DB
            $slotCheck = $conn->prepare("
                SELECT * FROM availability_slots 
                WHERE id = :id AND laboratory_id = :lid AND status = 'available' AND current_bookings < max_bookings
                LIMIT 1
            ");
            $slotCheck->execute([':id' => $slot_id, ':lid' => $lab_id]);
            $validSlot = $slotCheck->fetch();

            if (!$validSlot) {
                $error = 'عذراً، الفترة الزمنية التي اخترتها تم حجزها بالكامل أو لم تعد متاحة. يرجى اختيار فترة أخرى.';
            } else {
                $booking['slot_id']  = $validSlot['id'];
                $booking['apt_date'] = $validSlot['slot_date'];
                $booking['apt_time'] = $validSlot['slot_time'];
                redirect('booking/step3.php');
            }
        }
    }
}

define('PAGE_TITLE', 'الخطوة 2: اختيار الموعد — ' . $booking['lab_name']);
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="lb-page-header">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="mb-1"><i class="bi bi-clock-history me-2"></i> تحديد التاريخ والتوقيت</h1>
        <p class="mb-0 text-white-50">المخبر: <strong><?= h($booking['lab_name']) ?></strong> | عدد التحاليل: <strong><?= count($booking['analyses']) ?></strong></p>
      </div>
      <a href="<?= BASE_URL ?>booking/step1.php?lab_id=<?= $lab_id ?>" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-right"></i> تعديل التحاليل
      </a>
    </div>
  </div>
</div>

<div class="container pb-5">
  <!-- ── Steps Bar ─────────────────────────────────────────── -->
  <div class="steps-bar">
    <div class="step done">
      <div class="step-num"><i class="bi bi-check-lg"></i></div>
      <div class="step-label">اختيار التحاليل</div>
    </div>
    <div class="step active">
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

  <div class="row g-4">
    <!-- ── Main Slots Area ───────────────────────────────────── -->
    <div class="col-lg-8">
      <!-- Date Selection Tabs -->
      <div class="lb-card p-4 mb-4">
        <h5 class="fw-800 text-dark mb-3">
          <i class="bi bi-calendar-event text-primary me-1"></i> اختر يوم الموعد:
        </h5>

        <?php if (empty($availableDates)): ?>
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle-fill ms-1"></i>
            لا تتوفر فترات حجز مفتوحة حالياً لهذا المخبر في الأيام القادمة. يرجى مراجعة المخبر لاحقاً أو اختيار مخبر آخر.
          </div>
        <?php else: ?>
          <div class="d-flex gap-2 overflow-auto pb-2" style="scrollbar-width: thin;">
            <?php foreach ($availableDates as $dt): 
              $isCurrentDate = ($dt === $selectedDate);
              $dObj = new DateTime($dt);
            ?>
              <a href="?date=<?= $dt ?>" class="btn <?= $isCurrentDate ? 'btn-primary' : 'btn-outline-primary' ?> text-center px-3 py-2 flex-shrink-0" style="min-width: 100px; border-radius: 10px;">
                <div class="small fw-normal" style="font-size:0.75rem;"><?= formatDate($dt) ?></div>
                <div class="fw-bold fs-5 mt-1"><?= $dObj->format('d/m') ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Time Slots Grid -->
      <?php if (!empty($availableDates)): ?>
        <div class="lb-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-800 text-dark mb-0">
              <i class="bi bi-alarm text-primary me-1"></i> الفترات المتاحة ليوم: <?= formatDate($selectedDate) ?>
            </h5>
            <small class="text-muted"><i class="bi bi-info-circle me-1"></i> انقر على التوقيت المناسب</small>
          </div>

          <?php if (empty($slotsForDate)): ?>
            <p class="text-muted text-center py-4 mb-0">لا توجد فترات مسجلة لهذا التاريخ بالتحديد.</p>
          <?php else: ?>
            <form method="POST" action="" id="slotForm">
              <?= csrfField() ?>
              <input type="hidden" name="slot_id" id="selectedSlotInput" value="<?= h($booking['slot_id'] ?? '') ?>">

              <div class="row g-3">
                <?php foreach ($slotsForDate as $slot): 
                  $isFull = ($slot['status'] !== 'available' || (int)$slot['current_bookings'] >= (int)$slot['max_bookings']);
                  $isSelected = ((int)($booking['slot_id'] ?? 0) === (int)$slot['id']);
                ?>
                  <div class="col-6 col-sm-4 col-md-3">
                    <button type="button"
                            class="slot-btn w-100 justify-content-center py-3 <?= $isFull ? 'full' : '' ?> <?= $isSelected ? 'active' : '' ?>"
                            <?= $isFull ? 'disabled' : '' ?>
                            data-slot-id="<?= (int)$slot['id'] ?>"
                            data-slot-time="<?= formatTime($slot['slot_time']) ?>"
                            onclick="selectSlot(this, <?= (int)$slot['id'] ?>, '<?= formatTime($slot['slot_time']) ?>')">
                      <i class="bi bi-clock ms-1"></i>
                      <span><?= formatTime($slot['slot_time']) ?></span>
                      <?php if ($isFull): ?>
                        <small class="d-block w-100 text-danger" style="font-size:0.65rem;">مكتمل</small>
                      <?php endif; ?>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="<?= BASE_URL ?>booking/step1.php?lab_id=<?= $lab_id ?>" class="btn-lb-outline">
                  <i class="bi bi-arrow-right"></i> رجوع للتحاليل
                </a>
                <button type="submit" class="btn-lb-primary py-2 px-4" id="submitSlotBtn" <?= empty($booking['slot_id']) ? 'disabled' : '' ?>>
                  متابعة للتأكيد والوصفة <i class="bi bi-arrow-left ms-1"></i>
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Right Column Summary ──────────────────────────────── -->
    <div class="col-lg-4">
      <div class="summary-box sticky-top" style="top:90px;">
        <h5 class="fw-800 text-dark border-bottom pb-2 mb-3">
          <i class="bi bi-card-checklist text-primary me-1"></i> تفاصيل الحجز حتى الآن
        </h5>

        <div class="summary-row">
          <span class="s-label">المخبر:</span>
          <span class="s-value"><?= h($booking['lab_name']) ?></span>
        </div>

        <div class="summary-row">
          <span class="s-label">التحاليل (<?= count($booking['analyses']) ?>):</span>
          <span class="s-value text-start">
            <?php foreach ($booking['analyses'] as $a): ?>
              <div class="small"><?= h($a['name_ar']) ?></div>
            <?php endforeach; ?>
          </span>
        </div>

        <div class="summary-row">
          <span class="s-label">المبلغ الإجمالي:</span>
          <span class="summary-total"><?= formatPrice((float)$booking['total']) ?></span>
        </div>

        <div class="summary-row border-top pt-2 mt-2">
          <span class="s-label">التاريخ المحدد:</span>
          <span class="s-value text-primary fw-bold" id="selectedDateDisplay"><?= formatDate($selectedDate) ?></span>
        </div>

        <div class="summary-row">
          <span class="s-label">التوقيت المحدد:</span>
          <span class="s-value text-success fw-bold" id="selectedTimeDisplay">
            <?= !empty($booking['apt_time']) ? formatTime($booking['apt_time']) : 'لم يحدد بعد' ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function selectSlot(button, slotId, timeStr) {
  document.querySelectorAll('.slot-btn').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');

  document.getElementById('selectedSlotInput').value = slotId;
  document.getElementById('selectedTimeDisplay').textContent = timeStr;
  document.getElementById('submitSlotBtn').disabled = false;
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
