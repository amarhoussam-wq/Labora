<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('lab_manager', 'admin');

$userId = currentUserId();
$userRole = currentUserRole();

// Strict Laboratory Isolation: Each manager ONLY accesses their own laboratory
$stmt = $conn->prepare("SELECT * FROM laboratories WHERE manager_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$lab = $stmt->fetch();

if (!$lab) {
    if ($userRole === 'admin') {
        $lab = $conn->query("SELECT * FROM laboratories ORDER BY id ASC LIMIT 1")->fetch();
    }
}

if (!$lab) {
    setFlash('error', 'لا يوجد مخبر مسجل مرتبط بهذا الحساب.');
    redirect('index.php');
}

$lab_id = (int)$lab['id'];
$_SESSION['lab_id'] = $lab_id;

// Automatically purge expired past slots (prior to today) with no active appointments
try {
    $conn->prepare("DELETE FROM availability_slots WHERE laboratory_id = :lid AND slot_date < CURDATE() AND current_bookings = 0")->execute([':lid' => $lab_id]);
} catch (Exception $e) {}

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
            setFlash('success', 'تمت إضافة الفترة الزمنية بنجاح.');
            redirect('lab/dashboard.php');
        }
    }
}

// Handle Single Slot Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $slotId = (int)($_POST['slot_id'] ?? 0);
        if ($slotId > 0) {
            // First unlink or check appointments
            $checkApt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE slot_id = :sid AND status IN ('pending', 'accepted')");
            $checkApt->execute([':sid' => $slotId]);
            $hasActive = (int)$checkApt->fetchColumn();

            if ($hasActive > 0) {
                setFlash('error', 'لا يمكن حذف هذه الفترة لوجود مواعيد نشطة مرتبطة بها.');
            } else {
                $del = $conn->prepare("DELETE FROM availability_slots WHERE id = :sid AND laboratory_id = :lid");
                $del->execute([':sid' => $slotId, ':lid' => $lab_id]);
                setFlash('success', 'تم حذف الفترة الزمنية بنجاح.');
            }
            redirect('lab/dashboard.php');
        }
    }
}

// Handle Purge All Outdated Slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_outdated_slots'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $del = $conn->prepare("DELETE FROM availability_slots WHERE laboratory_id = :lid AND slot_date < CURDATE()");
        $del->execute([':lid' => $lab_id]);
        setFlash('success', 'تم تنظيف وحذف جميع الفترات القديمة السابقة بنجاح.');
        redirect('lab/dashboard.php');
    }
}

// Fetch Appointments strictly for this lab
$status_filter = trim($_GET['status'] ?? '');
$sql = "
    SELECT a.*, 
           u.first_name, u.last_name, 
           COALESCE(a.contact_phone, u.phone) AS patient_phone,
           u.email AS patient_email
    FROM appointments a
    LEFT JOIN users u ON u.id = a.patient_id
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

// Fetch future active slots for display (starting from today onwards)
$slotsStmt = $conn->prepare("
    SELECT * FROM availability_slots 
    WHERE laboratory_id = :lid AND slot_date >= CURDATE()
    ORDER BY slot_date ASC, slot_time ASC 
    LIMIT 30
");
$slotsStmt->execute([':lid' => $lab_id]);
$slots = $slotsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة إدارة المخبر — LABORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/labora.css">
  <style>
    :root {
      --dash-bg: #F8FAFD;
      --dash-card: #FFFFFF;
      --dash-border: #E8EEF5;
      --dash-text: #0F172A;
      --dash-muted: #64748B;
      --dash-primary: #1E60D5;
      --dash-primary-subtle: #EEF4FF;
      --dash-success: #059669;
      --dash-warning: #D97706;
      --dash-danger: #DC2626;
    }
    body {
      background-color: var(--dash-bg);
      color: var(--dash-text);
      font-family: 'Tajawal', -apple-system, sans-serif;
      min-height: 100vh;
    }
    .dash-navbar {
      background: #0B192C;
      color: #FFFFFF;
      padding: 0.85rem 1.75rem;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      position: sticky;
      top: 0;
      z-index: 1020;
      backdrop-filter: blur(10px);
    }
    .dash-brand-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, #2563EB, #38BDF8);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
    }
    .dash-kpi-card {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 16px;
      padding: 1.25rem 1.4rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 4px 12px rgba(15,23,42,0.03);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    .dash-kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(30,96,213,0.08);
    }
    .dash-kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      left: 0;
      height: 3px;
    }
    .dash-kpi-card.blue::before { background: linear-gradient(90deg, #2563EB, #60A5FA); }
    .dash-kpi-card.amber::before { background: linear-gradient(90deg, #D97706, #FBBF24); }
    .dash-kpi-card.emerald::before { background: linear-gradient(90deg, #059669, #34D399); }
    .dash-kpi-card.rose::before { background: linear-gradient(90deg, #DC2626, #F87171); }

    .dash-kpi-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
    .dash-kpi-icon.blue { background: #EFF6FF; color: #2563EB; }
    .dash-kpi-icon.amber { background: #FFFBEB; color: #D97706; }
    .dash-kpi-icon.emerald { background: #ECFDF5; color: #059669; }
    .dash-kpi-icon.rose { background: #FEF2F2; color: #DC2626; }

    .dash-kpi-val {
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--dash-text);
      line-height: 1.1;
      font-family: 'Plus Jakarta Sans', 'Tajawal', sans-serif;
    }
    .dash-kpi-label {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--dash-muted);
      margin-top: 0.25rem;
    }

    .panel-card {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 18px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 4px 16px rgba(15,23,42,0.03);
      overflow: hidden;
    }
    .panel-card-header {
      padding: 1.15rem 1.5rem;
      border-bottom: 1px solid var(--dash-border);
      background: #FCFDFF;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: gap;
    }

    .filter-pill-nav {
      display: inline-flex;
      background: #F1F5F9;
      padding: 3px;
      border-radius: 12px;
      gap: 3px;
    }
    .filter-pill-link {
      padding: 0.35rem 0.85rem;
      font-size: 0.8rem;
      font-weight: 700;
      border-radius: 9px;
      color: #64748B;
      text-decoration: none;
      transition: all 0.18s ease;
    }
    .filter-pill-link:hover {
      color: #0F172A;
    }
    .filter-pill-link.active {
      background: #FFFFFF;
      color: #1E60D5;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    .modern-table thead th {
      background: #F8FAFD;
      color: #64748B;
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 0.95rem 1.25rem;
      border-bottom: 1px solid var(--dash-border);
      white-space: nowrap;
    }
    .modern-table tbody td {
      padding: 1.05rem 1.25rem;
      border-bottom: 1px solid #F1F5F9;
      vertical-align: middle;
      font-size: 0.875rem;
    }
    .modern-table tbody tr:last-child td {
      border-bottom: none;
    }
    .modern-table tbody tr:hover {
      background: #F8FAFC;
    }

    .patient-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #E0E7FF;
      color: #3730A3;
      font-weight: 800;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .status-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.28rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
    }
    .status-tag.pending {
      background: #FEF3C7;
      color: #92400E;
    }
    .status-tag.accepted {
      background: #D1FAE5;
      color: #065F46;
    }
    .status-tag.rejected {
      background: #FEE2E2;
      color: #991B1B;
    }
    .status-tag.cancelled {
      background: #F1F5F9;
      color: #475569;
    }

    .pulse-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: currentColor;
      animation: pulse-glow 1.8s infinite;
    }
    @keyframes pulse-glow {
      0% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(1.3); }
      100% { opacity: 1; transform: scale(1); }
    }

    .btn-action-accept {
      background: #059669;
      color: #FFFFFF;
      border: none;
      padding: 0.38rem 0.85rem;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: all 0.18s;
      box-shadow: 0 2px 6px rgba(5,150,105,0.2);
    }
    .btn-action-accept:hover {
      background: #047857;
      color: #FFFFFF;
      transform: translateY(-1px);
    }

    .btn-action-reject {
      background: #FFFFFF;
      color: #DC2626;
      border: 1px solid #FECACA;
      padding: 0.38rem 0.85rem;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: all 0.18s;
    }
    .btn-action-reject:hover {
      background: #FEE2E2;
      color: #B91C1C;
    }

    .slot-item {
      padding: 0.65rem 0.85rem;
      background: #F8FAFD;
      border: 1px solid #E2E8F0;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      font-size: 0.82rem;
      transition: all 0.15s ease;
    }
    .slot-item:hover {
      background: #FFFFFF;
      border-color: #CBD5E1;
      box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
  </style>
</head>
<body>

<!-- ── World-Class Top Bar ─────────────────────────────────── -->
<header class="dash-navbar">
  <div class="container-fluid px-lg-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= BASE_URL ?>lab/dashboard.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
        <span class="dash-brand-icon"><i class="bi bi-clipboard2-pulse"></i></span>
        <span class="fw-900 fs-5 tracking-tight">LABORA</span>
      </a>
      <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-10 px-3 py-1.5 rounded-pill fw-600 fs-7 d-none d-sm-inline-flex align-items-center gap-1.5">
        <i class="bi bi-hospital text-info"></i>
        <?= h($lab['name']) ?> — <?= h($lab['city']) ?>
      </span>
    </div>

    <div class="d-flex align-items-center gap-3">
      <div class="text-end d-none d-md-block">
        <div class="fw-bold small text-white"><?= h(currentUserName()) ?></div>
        <div class="text-white-50" style="font-size:0.75rem;">مسؤول مخبر طبي معتمد</div>
      </div>
      <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-sm btn-outline-light px-3 py-1.5 rounded-pill fw-600" style="font-size:0.8rem;">
        <i class="bi bi-box-arrow-right ms-1"></i> خروج
      </a>
    </div>
  </div>
</header>

<!-- ── Main Dashboard Workspace ────────────────────────────── -->
<main class="container-fluid px-lg-5 py-4">

  <!-- Flash Alerts -->
  <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
      <i class="bi <?= $flash['type'] === 'error' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-check-circle-fill text-success' ?> fs-5"></i>
      <span class="fw-bold small"><?= h($flash['message']) ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Welcome Strip -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h4 class="fw-800 text-dark mb-1">لوحة إدارة المواعيد والتحاليل</h4>
      <p class="text-muted small mb-0">متابعة دقيقة وفورية لحجوزات <?= h($lab['name']) ?> وتنظيم الفترات الزمنية للمخبر</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-white text-muted border px-3 py-2 rounded-pill small">
        <i class="bi bi-calendar-event text-primary ms-1"></i>
        <?= date('Y-m-d') ?>
      </span>
    </div>
  </div>

  <!-- ── 4 KPI Stats ────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="dash-kpi-card blue">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="dash-kpi-val"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="dash-kpi-label">إجمالي الطلبات الواردة</div>
          </div>
          <div class="dash-kpi-icon blue">
            <i class="bi bi-inbox-fill"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="dash-kpi-card amber">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="dash-kpi-val text-warning"><?= (int)($stats['pending_cnt'] ?? 0) ?></div>
            <div class="dash-kpi-label">طلبات بانتظار التأكيد</div>
          </div>
          <div class="dash-kpi-icon amber">
            <i class="bi bi-hourglass-split"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="dash-kpi-card emerald">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="dash-kpi-val text-success"><?= (int)($stats['accepted_cnt'] ?? 0) ?></div>
            <div class="dash-kpi-label">مواعيد مقبولة ومؤكدة</div>
          </div>
          <div class="dash-kpi-icon emerald">
            <i class="bi bi-check-circle-fill"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="dash-kpi-card rose">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="dash-kpi-val text-danger"><?= (int)($stats['rejected_cnt'] ?? 0) ?></div>
            <div class="dash-kpi-label">مواعيد مرفوضة</div>
          </div>
          <div class="dash-kpi-icon rose">
            <i class="bi bi-x-circle-fill"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Main Grid ─────────────────────────────────────────── -->
  <div class="row g-4">

    <!-- Appointments Table (Left / Main Col) -->
    <div class="col-xl-8 col-lg-7">
      <div class="panel-card">
        <div class="panel-card-header">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check-fill text-primary fs-5"></i>
            <h5 class="fw-bold mb-0 text-dark">جدول طلبات المواعيد</h5>
          </div>

          <!-- Segmented Filter Control -->
          <div class="filter-pill-nav">
            <a href="?status=" class="filter-pill-link <?= empty($status_filter) ? 'active' : '' ?>">الكل (<?= (int)($stats['total'] ?? 0) ?>)</a>
            <a href="?status=pending" class="filter-pill-link <?= $status_filter === 'pending' ? 'active' : '' ?>">قيد الانتظار (<?= (int)($stats['pending_cnt'] ?? 0) ?>)</a>
            <a href="?status=accepted" class="filter-pill-link <?= $status_filter === 'accepted' ? 'active' : '' ?>">المقبولة (<?= (int)($stats['accepted_cnt'] ?? 0) ?>)</a>
            <a href="?status=rejected" class="filter-pill-link <?= $status_filter === 'rejected' ? 'active' : '' ?>">المرفوضة</a>
          </div>
        </div>

        <div class="p-0">
          <?php if (empty($appointments)): ?>
            <div class="text-center py-5 text-muted">
              <div class="mb-3">
                <i class="bi bi-inbox text-secondary" style="font-size: 3rem; opacity: 0.4;"></i>
              </div>
              <h6 class="fw-bold text-dark">لا توجد طلبات مواعيد ضمن هذه الفئة</h6>
              <p class="small text-muted mb-0">ستظهر طلبات المواعيد الجديدة فور قيام المرضى بالحجز عبر التطبيق.</p>
            </div>
          <?php else: ?>

            <!-- ── Mobile View: Cards (< 768px) ── -->
            <div class="d-md-none">
              <?php foreach ($appointments as $apt): 
                $patientInitials = mb_substr($apt['first_name'] ?? 'م', 0, 1, 'UTF-8');
              ?>
                <div class="p-3 border-bottom bg-white">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="patient-avatar"><?= h($patientInitials) ?></div>
                      <div>
                        <div class="fw-bold text-dark"><?= h(($apt['first_name'] ?? '') . ' ' . ($apt['last_name'] ?? '')) ?></div>
                        <div class="small text-muted" dir="ltr">
                          <?php if (!empty($apt['patient_phone'])): ?>
                            <a href="tel:<?= h($apt['patient_phone']) ?>" class="btn btn-sm btn-light border py-0 px-2 rounded-pill text-primary small">
                              <i class="bi bi-telephone ms-1"></i><?= h($apt['patient_phone']) ?>
                            </a>
                          <?php else: ?>
                            <span class="text-muted small"><i class="bi bi-telephone ms-1"></i>—</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <span class="status-tag <?= h($apt['status']) ?>">
                      <?= match ($apt['status']) {
                        'pending'   => 'قيد الانتظار',
                        'accepted'  => 'مقبول',
                        'rejected'  => 'مرفوض',
                        'cancelled' => 'ملغى',
                        default     => $apt['status']
                      } ?>
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2 text-muted small mb-2 flex-wrap">
                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-calendar3 me-1 text-primary"></i><?= h($apt['appointment_date'] ?? '—') ?></span>
                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-clock me-1 text-primary"></i><?= !empty($apt['appointment_time']) ? substr($apt['appointment_time'], 0, 5) : '—' ?></span>
                    <?php if (!empty($apt['prescription_file'])): ?>
                      <a href="<?= BASE_URL ?>view_prescription.php?file=<?= urlencode($apt['prescription_file']) ?>" target="_blank" class="badge bg-primary text-white text-decoration-none px-2 py-1">
                        <i class="bi bi-paperclip me-1"></i> معاينة الوصفة
                      </a>
                    <?php endif; ?>
                  </div>

                  <div class="p-2.5 bg-light rounded-3 text-dark small mb-2.5" style="font-size:0.82rem; line-height: 1.5; white-space:pre-wrap;">
                    <div class="fw-bold text-secondary mb-1">التحاليل المطلوبة:</div>
                    <?= h($apt['analyses_text'] ?? '') ?>
                    <?php if (!empty($apt['notes'])): ?>
                      <div class="text-muted mt-1 pt-1 border-top"><i class="bi bi-info-circle me-1"></i><?= h($apt['notes']) ?></div>
                    <?php endif; ?>
                  </div>

                  <?php if ($apt['status'] === 'rejected' && !empty($apt['rejection_reason'])): ?>
                    <div class="alert alert-danger py-1.5 px-2.5 small mb-2 rounded-3" style="font-size:0.78rem;">
                      <strong>سبب الرفض:</strong> <?= h($apt['rejection_reason']) ?>
                    </div>
                  <?php endif; ?>

                  <!-- Action Buttons Toolbar for Mobile -->
                  <div class="d-flex align-items-center gap-2 flex-wrap pt-1">
                    <?php if ($apt['status'] === 'pending'): ?>
                      <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                          <i class="bi bi-check-lg ms-1"></i> قبول
                        </button>
                      </form>
                      <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" onclick="openReasonModal(<?= (int)$apt['id'] ?>, 'rejected', 'رفض طلب الموعد')">
                        <i class="bi bi-x-lg ms-1"></i> رفض مع سبب
                      </button>
                    <?php elseif ($apt['status'] === 'accepted'): ?>
                      <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 fw-bold" onclick="openReasonModal(<?= (int)$apt['id'] ?>, 'cancelled', 'إلغاء الموعد')">
                        <i class="bi bi-x-circle ms-1"></i> إلغاء الموعد
                      </button>
                    <?php else: ?>
                      <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold">
                          <i class="bi bi-arrow-repeat ms-1"></i> إعادة قبول
                        </button>
                      </form>
                    <?php endif; ?>

                    <!-- Delete Button -->
                    <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="m-0 me-auto" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الموعد نهائياً؟');">
                      <?= csrfField() ?>
                      <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5" title="حذف الموعد نهائياً">
                        <i class="bi bi-trash3 ms-1"></i> حذف
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- ── Desktop View: Table (>= 768px) ── -->
            <div class="table-responsive d-none d-md-block">
              <table class="modern-table align-middle">
                <thead>
                  <tr>
                    <th>المريض</th>
                    <th>التاريخ والتوقيت</th>
                    <th>التحاليل المطلوبة</th>
                    <th>الوصفة</th>
                    <th>الحالة</th>
                    <th style="min-width: 170px; text-align: left;">الإجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($appointments as $apt): 
                    $patientInitials = mb_substr($apt['first_name'] ?? 'م', 0, 1, 'UTF-8');
                  ?>
                    <tr>
                      <!-- Patient Info -->
                      <td>
                        <div class="d-flex align-items-center gap-2.5">
                          <div class="patient-avatar"><?= h($patientInitials) ?></div>
                          <div>
                            <div class="fw-bold text-dark"><?= h(($apt['first_name'] ?? '') . ' ' . ($apt['last_name'] ?? '')) ?></div>
                            <div class="small text-muted" dir="ltr" style="text-align:right;">
                              <?php if (!empty($apt['patient_phone'])): ?>
                                <a href="tel:<?= h($apt['patient_phone']) ?>" class="text-decoration-none text-muted small">
                                  <i class="bi bi-telephone ms-1 text-primary"></i><?= h($apt['patient_phone']) ?>
                                </a>
                              <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-telephone ms-1 text-secondary"></i>—</span>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </td>

                      <!-- Date & Time -->
                      <td>
                        <div class="fw-bold text-dark" style="font-size:0.875rem;">
                          <?= h($apt['appointment_date'] ?? '—') ?>
                        </div>
                        <span class="badge bg-light text-primary border px-2 py-0.5 rounded-pill small">
                          <i class="bi bi-clock me-1"></i><?= !empty($apt['appointment_time']) ? substr($apt['appointment_time'], 0, 5) : '—' ?>
                        </span>
                      </td>

                      <!-- Analyses -->
                      <td>
                        <div class="p-2 bg-light rounded text-dark small" style="max-width: 240px; white-space: pre-wrap; font-size:0.82rem; line-height:1.5;">
                          <?= h($apt['analyses_text'] ?? '') ?>
                        </div>
                        <?php if (!empty($apt['notes'])): ?>
                          <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                            <i class="bi bi-info-circle me-1"></i><?= h($apt['notes']) ?>
                          </small>
                        <?php endif; ?>
                      </td>

                      <!-- Prescription -->
                      <td>
                        <?php if (!empty($apt['prescription_file'])): ?>
                          <a href="<?= BASE_URL ?>view_prescription.php?file=<?= urlencode($apt['prescription_file']) ?>" 
                             target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill small fw-bold">
                            <i class="bi bi-paperclip me-1"></i> معاينة
                          </a>
                        <?php else: ?>
                          <span class="text-muted small">—</span>
                        <?php endif; ?>
                      </td>

                      <!-- Status -->
                      <td>
                        <span class="status-tag <?= h($apt['status']) ?>">
                          <?php if ($apt['status'] === 'pending'): ?>
                            <span class="pulse-dot"></span>
                          <?php endif; ?>
                          <?= match ($apt['status']) {
                            'pending'   => 'قيد الانتظار',
                            'accepted'  => 'مقبول',
                            'rejected'  => 'مرفوض',
                            'cancelled' => 'ملغى',
                            default     => $apt['status']
                          } ?>
                        </span>
                        <?php if ($apt['status'] === 'rejected' && !empty($apt['rejection_reason'])): ?>
                          <small class="d-block text-danger mt-1" style="font-size:0.72rem;">
                            السبب: <?= h($apt['rejection_reason']) ?>
                          </small>
                        <?php endif; ?>
                      </td>

                      <!-- Actions -->
                      <td style="text-align: left;">
                        <div class="d-flex align-items-center gap-1.5 justify-content-end flex-wrap">
                          <?php if ($apt['status'] === 'pending'): ?>
                            <!-- Accept Form -->
                            <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="d-inline m-0">
                              <?= csrfField() ?>
                              <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                              <input type="hidden" name="status" value="accepted">
                              <button type="submit" class="btn-action-accept" title="قبول وتأكيد الموعد">
                                <i class="bi bi-check-lg"></i> قبول
                              </button>
                            </form>

                            <!-- Reject Button opening Modal -->
                            <button type="button" class="btn-action-reject" 
                                    onclick="openReasonModal(<?= (int)$apt['id'] ?>, 'rejected', 'رفض طلب الموعد')"
                                    title="رفض الموعد مع تدوين السبب">
                              <i class="bi bi-x-lg"></i> رفض
                            </button>

                          <?php elseif ($apt['status'] === 'accepted'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success py-1.5 px-2.5 rounded-pill small">
                              <i class="bi bi-check2"></i> مؤكد
                            </span>
                            <!-- Cancel Button for Accepted Appointments -->
                            <button type="button" class="btn btn-outline-warning btn-sm py-1 px-2 rounded-pill small" 
                                    onclick="openReasonModal(<?= (int)$apt['id'] ?>, 'cancelled', 'إلغاء موعد مقبول')"
                                    title="إلغاء هذا الموعد وتحرير الفترة">
                              <i class="bi bi-x-circle ms-1"></i> إلغاء
                            </button>

                          <?php else: ?>
                            <!-- Allow Re-accept if needed -->
                            <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="d-inline m-0">
                              <?= csrfField() ?>
                              <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                              <input type="hidden" name="status" value="accepted">
                              <button type="submit" class="btn btn-link text-success p-0 text-decoration-none small" title="إعادة قبول الموعد">
                                (إعادة قبول)
                              </button>
                            </form>
                          <?php endif; ?>

                          <!-- Delete Appointment Button -->
                          <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" class="d-inline m-0" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الموعد نهائياً؟');">
                            <?= csrfField() ?>
                            <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 rounded-pill small" title="حذف الموعد نهائياً">
                              <i class="bi bi-trash3"></i>
                            </button>
                          </form>
                        </div>
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

    <!-- Right Column: Slots Management -->
    <div class="col-xl-4 col-lg-5">

      <!-- Add New Slot Card -->
      <div class="panel-card mb-4">
        <div class="panel-card-header">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-plus text-primary fs-5"></i>
            <h6 class="fw-bold mb-0 text-dark">فتح فترة زمنية جديدة</h6>
          </div>
        </div>
        <div class="p-3.5">
          <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="create_slot" value="1">

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">تاريخ الفترة</label>
              <input type="date" class="form-control rounded-3" name="slot_date" 
                     value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">التوقيت (ساعة:دقيقة)</label>
              <input type="time" class="form-control rounded-3" name="slot_time" value="08:30" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">السعة القصوى (المرضى)</label>
              <input type="number" class="form-control rounded-3" name="max_bookings" value="2" min="1" max="10" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm">
              <i class="bi bi-plus-circle ms-1"></i> تثبيت الفترة في الجدول
            </button>
          </form>
        </div>
      </div>

      <!-- Active Slots List -->
      <div class="panel-card">
        <div class="panel-card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary fs-5"></i>
            <h6 class="fw-bold mb-0 text-dark">الفترات القادمة المتاحة</h6>
          </div>
          <form method="POST" action="" onsubmit="return confirm('هل ترغب في تنظيف كافة الفترات السابقة للمخبر؟');" class="m-0">
            <?= csrfField() ?>
            <input type="hidden" name="purge_outdated_slots" value="1">
            <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none small" style="font-size:0.75rem;" title="تنظيف الفترات المنتهية">
              <i class="bi bi-trash ms-1"></i>تنظيف القديم
            </button>
          </form>
        </div>

        <div class="p-3" style="max-height: 420px; overflow-y: auto;">
          <?php if (empty($slots)): ?>
            <div class="text-center py-4 text-muted small">
              <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary opacity-50"></i>
              لا توجد فترات قادمة مسجلة حالياً.<br>
              يمكنك إضافة فترات جديدة أعلاه.
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($slots as $sl): ?>
                <div class="slot-item">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock text-primary"></i>
                    <div>
                      <strong class="text-dark"><?= h($sl['slot_date']) ?></strong>
                      <span class="text-muted ms-1 small">— <?= substr($sl['slot_time'], 0, 5) ?></span>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill <?= (int)$sl['current_bookings'] >= (int)$sl['max_bookings'] ? 'bg-danger' : 'bg-success bg-opacity-10 text-success border border-success' ?>" style="font-size:0.75rem;">
                      <?= (int)$sl['current_bookings'] ?> / <?= (int)$sl['max_bookings'] ?>
                    </span>

                    <!-- Delete button for slot -->
                    <?php if ((int)$sl['current_bookings'] === 0): ?>
                      <form method="POST" action="" onsubmit="return confirm('حذف هذه الفترة الزمنية؟');" class="d-inline m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="delete_slot" value="1">
                        <input type="hidden" name="slot_id" value="<?= (int)$sl['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 border-0 rounded-circle" title="حذف الفترة">
                          <i class="bi bi-trash" style="font-size:0.85rem;"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Modal for Rejection / Cancellation Reason -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-dark" id="modalTitle">
          <i class="bi bi-exclamation-circle text-danger ms-1"></i> تدوين سبب الرفض / الإلغاء
        </h5>
        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>actions/update_status.php" id="modalActionForm">
        <?= csrfField() ?>
        <input type="hidden" name="appointment_id" id="modalAppointmentId" value="0">
        <input type="hidden" name="status" id="modalStatus" value="rejected">
        
        <div class="modal-body p-4">
          <p class="small text-muted mb-3">
            حدد سبب الرفض أو الإلغاء ليتم إرساله للمريض ويظهر بوضوح في حسابه:
          </p>

          <!-- Quick Reason Tags -->
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted d-block">أسباب شائعة جاهزة (اضغط لاختيار أحدها فوراً):</label>
            <div class="d-flex flex-wrap gap-1.5">
              <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="setReason('الفترة الزمنية ممتلئة بالكامل')">الفترة ممتلئة</button>
              <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="setReason('التحاليل المطلوبة غير متوفرة حالياً')">التحاليل غير متوفرة</button>
              <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="setReason('صورة الوصفة الطبية غير واضحة')">الوصفة غير واضحة</button>
              <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="setReason('يرجى اختيار تاريخ وتوقيت آخر')">يرجى اختيار موعد آخر</button>
              <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="setReason('عدم حضور المريض في الموعد المحدد')">عدم حضور المريض</button>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">نص السبب الذي سيصل للمريض:</label>
            <textarea class="form-control rounded-3" name="rejection_reason" id="modalReasonText" rows="3" placeholder="اكتب السبب هنا أو اختر من الأسباب السريعة أعلاه..." required>التحاليل المطلوبة غير متوفرة حالياً</textarea>
          </div>
        </div>

        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">تراجع</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold">
            <i class="bi bi-check-lg ms-1"></i> تأكيد الإجراء وحفظ السبب
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let reasonModalInstance = null;

function openReasonModal(aptId, status, title) {
  document.getElementById('modalAppointmentId').value = aptId;
  document.getElementById('modalStatus').value = status;
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-exclamation-circle text-danger ms-1"></i> ' + title;
  
  const modalEl = document.getElementById('rejectReasonModal');
  try {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      if (!reasonModalInstance) {
        reasonModalInstance = new bootstrap.Modal(modalEl);
      }
      reasonModalInstance.show();
      return;
    }
  } catch (e) {
    console.warn('Bootstrap modal error, using prompt fallback:', e);
  }

  // Graceful fallback if bootstrap modal JS fails on older mobile browsers
  const reason = prompt('يرجى تحديد سبب الرفض أو الإلغاء ليتم إرساله للمريض:', 'التحاليل غير متوفرة حالياً / الفترة ممتلئة');
  if (reason !== null && reason.trim() !== '') {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = '<?= BASE_URL ?>actions/update_status.php';
    f.innerHTML = `
      <?= csrfField() ?>
      <input type="hidden" name="appointment_id" value="${aptId}">
      <input type="hidden" name="status" value="${status}">
      <input type="hidden" name="rejection_reason" value="${reason}">
    `;
    document.body.appendChild(f);
    f.submit();
  }
}

function setReason(text) {
  document.getElementById('modalReasonText').value = text;
}
</script>
</body>
</html>
