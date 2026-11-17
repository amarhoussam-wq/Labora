<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('lab_manager', 'admin');

$userId = currentUserId();
$lab_id = currentLabId();

// If lab manager has no laboratory assigned yet, fetch from DB
if (!$lab_id) {
    $stmt = $conn->prepare("SELECT id FROM laboratories WHERE manager_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $labRow = $stmt->fetch();
    $lab_id = $labRow ? (int)$labRow['id'] : 1; // fallback to lab 1 for demo
    $_SESSION['lab_id'] = $lab_id;
}

// Fetch Laboratory Info
$lStmt = $conn->prepare("SELECT * FROM laboratories WHERE id = :lid LIMIT 1");
$lStmt->execute([':lid' => $lab_id]);
$lab = $lStmt->fetch();

// Handle New Slot Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_slot'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $sDate = trim($_POST['slot_date'] ?? '');
        $sTime = trim($_POST['slot_time'] ?? '');
        $sMax  = max(1, (int)($_POST['max_bookings'] ?? 2));

        if ($sDate && $sTime) {
            $insSlot = $conn->prepare("
                INSERT INTO availability_slots (laboratory_id, slot_date, slot_time, max_bookings, current_bookings, status) 
                VALUES (:lid, :dt, :tm, :mx, 0, 'available')
                ON DUPLICATE KEY UPDATE max_bookings = :mx2
            ");
            $insSlot->execute([':lid' => $lab_id, ':dt' => $sDate, ':tm' => $sTime, ':mx' => $sMax, ':mx2' => $sMax]);
            setFlash('success', 'تمت إضافة الفترة الزمنية بنجاح');
            redirect('lab/dashboard.php');
        }
    }
}

// Fetch Appointments for this lab
$status_filter = trim($_GET['status'] ?? '');
$sql = "
    SELECT a.*, u.first_name, u.last_name, u.phone AS patient_phone, u.email AS patient_email
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.laboratory_id = :lid
";
$params = [':lid' => $lab_id];
if (in_array($status_filter, ['pending', 'accepted', 'rejected', 'cancelled'], true)) {
    $sql .= " AND a.status = :st";
    $params[':st'] = $status_filter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$aStmt = $conn->prepare($sql);
$aStmt->execute($params);
$appointments = $aStmt->fetchAll();

// Counts for statistics
$countStmt = $conn->prepare("
    SELECT 
      COUNT(*) AS total,
      SUM(status = 'pending') AS pending_cnt,
      SUM(status = 'accepted') AS accepted_cnt,
      SUM(status = 'rejected') AS rejected_cnt
    FROM appointments WHERE laboratory_id = :lid
");
$countStmt->execute([':lid' => $lab_id]);
$stats = $countStmt->fetch();

// Fetch future slots for display
$slotsStmt = $conn->prepare("
    SELECT * FROM availability_slots 
    WHERE laboratory_id = :lid AND slot_date >= CURDATE() 
    ORDER BY slot_date ASC, slot_time ASC 
    LIMIT 20
");
$slotsStmt->execute([':lid' => $lab_id]);
$slots = $slotsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة إدارة المخبر — LABORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Tajawal', sans-serif; background-color: #f4f7fa; color: #1e293b; }
    .top-navbar { background: #0d47a1; color: white; padding: 0.85rem 1.5rem; }
    .card-stat { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .status-badge-pending { background: #fff3cd; color: #856404; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
    .status-badge-accepted { background: #d4edda; color: #155724; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
    .status-badge-rejected { background: #f8d7da; color: #721c24; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
    .status-badge-cancelled { background: #e2e3e5; color: #383d41; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
  </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────── -->
<header class="top-navbar d-flex justify-content-between align-items-center">
  <div class="d-flex align-items-center gap-3">
    <span class="fs-4 fw-800"><i class="bi bi-clipboard2-pulse"></i> LABORA</span>
    <span class="badge bg-light text-primary px-3 py-1 fw-bold fs-6">
      <?= h($lab['name'] ?? 'منصة المخبر الطبي') ?>
    </span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span class="small text-white-50 d-none d-md-inline">المسؤول: <?= h(currentUserName()) ?></span>
    <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-box-arrow-right ms-1"></i> خروج
    </a>
  </div>
</header>

<div class="container-fluid py-4 px-lg-5">

  <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
      <i class="bi bi-info-circle-fill ms-2"></i> <?= h($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ── Stat Cards ────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card card-stat p-3 bg-white border-start border-primary border-4">
        <span class="text-muted small fw-bold">إجمالي الطلبات</span>
        <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total'] ?? 0) ?></h3>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card card-stat p-3 bg-white border-start border-warning border-4">
        <span class="text-muted small fw-bold">طلبات بانتظار التأكيد</span>
        <h3 class="fw-bold mb-0 text-warning"><?= (int)($stats['pending_cnt'] ?? 0) ?></h3>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card card-stat p-3 bg-white border-start border-success border-4">
        <span class="text-muted small fw-bold">المواعيد المقبولة</span>
        <h3 class="fw-bold mb-0 text-success"><?= (int)($stats['accepted_cnt'] ?? 0) ?></h3>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card card-stat p-3 bg-white border-start border-danger border-4">
        <span class="text-muted small fw-bold">الطلبات المرفوضة</span>
        <h3 class="fw-bold mb-0 text-danger"><?= (int)($stats['rejected_cnt'] ?? 0) ?></h3>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- ── Left Column: Appointments List ────────────────────── -->
    <div class="col-xl-9 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-inbox-fill text-primary ms-1"></i> طلبات المواعيد الواردة
          </h5>
          <div class="d-flex gap-1">
            <a href="?status=" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-outline-secondary' ?>">الكل</a>
            <a href="?status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">قيد الانتظار</a>
            <a href="?status=accepted" class="btn btn-sm <?= $status_filter === 'accepted' ? 'btn-success' : 'btn-outline-success' ?>">المقبولة</a>
            <a href="?status=rejected" class="btn btn-sm <?= $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">المرفوضة</a>
          </div>
        </div>

        <div class="card-body p-0">
          <?php if (empty($appointments)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
              <p class="mb-0">لا توجد طلبات مواعيد مسجلة ضمن هذه الفئة.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                <thead class="table-light small text-muted">
                  <tr>
                    <th>#</th>
                    <th>المريض</th>
                    <th>التاريخ والتوقيت</th>
                    <th>التحاليل المطلوبة يدوياً</th>
                    <th>الوصفة الطبية</th>
                    <th>الحالة</th>
                    <th style="width: 170px;">الإجراء</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($appointments as $apt): ?>
                    <tr>
                      <td class="text-muted fw-bold">#<?= (int)$apt['id'] ?></td>
                      <td>
                        <strong><?= h($apt['first_name'] . ' ' . $apt['last_name']) ?></strong>
                        <div class="small text-muted" dir="ltr" style="text-align:right;">
                          <i class="bi bi-telephone text-primary ms-1"></i><?= h($apt['patient_phone'] ?: 'بدون هاتف') ?>
                        </div>
                      </td>
                      <td>
                        <div class="fw-bold text-dark"><?= h($apt['appointment_date']) ?></div>
                        <span class="badge bg-light text-primary border"><?= substr($apt['appointment_time'], 0, 5) ?></span>
                      </td>
                      <td>
                        <div class="p-2 bg-light rounded text-dark small" style="max-width: 250px; white-space: pre-wrap;">
                          <?= h($apt['analyses_text']) ?>
                        </div>
                        <?php if (!empty($apt['notes'])): ?>
                          <small class="text-muted d-block mt-1">ملاحظة: <?= h($apt['notes']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($apt['prescription_file'])): ?>
                          <a href="<?= BASE_URL ?>view_prescription.php?file=<?= urlencode($apt['prescription_file']) ?>" 
                             target="_blank" class="btn btn-outline-primary btn-sm py-1 px-2">
                            <i class="bi bi-file-earmark-medical ms-1"></i> معاينة
                          </a>
                        <?php else: ?>
                          <span class="text-muted small">لا توجد وصفة</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="status-badge-<?= h($apt['status']) ?>">
                          <?= match ($apt['status']) {
                            'pending'   => 'قيد الانتظار',
                            'accepted'  => 'مقبول',
                            'rejected'  => 'مرفوض',
                            'cancelled' => 'ملغى',
                            default     => $apt['status']
                          } ?>
                        </span>
                        <?php if ($apt['status'] === 'rejected' && !empty($apt['rejection_reason'])): ?>
                          <small class="d-block text-danger mt-1" style="font-size:0.75rem;">
                            السبب: <?= h($apt['rejection_reason']) ?>
                          </small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($apt['status'] === 'pending'): ?>
                          <div class="d-flex gap-1">
                            <!-- Accept Form -->
                            <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="d-inline">
                              <?= csrfField() ?>
                              <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                              <input type="hidden" name="status" value="accepted">
                              <button type="submit" class="btn btn-success btn-sm px-2" title="قبول وتأكيد الموعد">
                                <i class="bi bi-check-lg"></i> قبول
                              </button>
                            </form>

                            <!-- Reject Form with Reason Prompt -->
                            <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="d-inline" onsubmit="return confirmReject(this);">
                              <?= csrfField() ?>
                              <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                              <input type="hidden" name="status" value="rejected">
                              <input type="hidden" name="rejection_reason" value="">
                              <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="رفض الموعد">
                                <i class="bi bi-x-lg"></i> رفض
                              </button>
                            </form>
                          </div>
                        <?php else: ?>
                          <span class="text-muted small">تمت المعالجة</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ── Right Column: Manage Slots ────────────────────────── -->
    <div class="col-xl-3 col-lg-4">
      <!-- Add Slot Form -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-calendar-plus text-primary ms-1"></i> فتح فترة زمنية جديدة
          </h6>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="create_slot" value="1">

            <div class="mb-3">
              <label class="form-label small fw-bold">التاريخ</label>
              <input type="date" class="form-control form-control-sm" name="slot_date" 
                     value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">التوقيت (ساعة:دقيقة)</label>
              <input type="time" class="form-control form-control-sm" name="slot_time" value="08:30" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">السعة القصوى (عدد المرضى)</label>
              <input type="number" class="form-control form-control-sm" name="max_bookings" value="2" min="1" max="10" required>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-plus-circle ms-1"></i> إضافة الفترة
            </button>
          </form>
        </div>
      </div>

      <!-- Upcoming Slots List -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary ms-1"></i> الفترات القادمة بالمخبر
          </h6>
        </div>
        <div class="card-body p-2" style="max-height: 350px; overflow-y: auto;">
          <?php if (empty($slots)): ?>
            <p class="text-muted small text-center my-3">لا توجد فترات مسجلة.</p>
          <?php else: ?>
            <div class="d-flex flex-column gap-1">
              <?php foreach ($slots as $sl): ?>
                <div class="p-2 rounded bg-light d-flex justify-content-between align-items-center small">
                  <div>
                    <strong><?= h($sl['slot_date']) ?></strong> — <?= substr($sl['slot_time'], 0, 5) ?>
                  </div>
                  <span class="badge <?= (int)$sl['current_bookings'] >= (int)$sl['max_bookings'] ? 'bg-danger' : 'bg-success' ?>">
                    <?= (int)$sl['current_bookings'] ?> / <?= (int)$sl['max_bookings'] ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function confirmReject(form) {
  const reason = prompt("يرجى كتابة سبب رفض هذا الموعد للمريض (اختياري):", "الفترة غير ملائمة / التحاليل غير متوفرة حالياً");
  if (reason === null) {
    return false; // cancelled
  }
  form.querySelector('input[name="rejection_reason"]').value = reason;
  return true;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
