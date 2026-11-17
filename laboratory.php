<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !$conn) {
    setFlash('error', 'المخبر المطلوب غير موجود.');
    redirect('laboratories.php');
}

// Fetch lab data
$stmt = $conn->prepare("SELECT * FROM laboratories WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $id]);
$lab = $stmt->fetch();

if (!$lab) {
    setFlash('error', 'المخبر غير متوفر أو غير نشط.');
    redirect('laboratories.php');
}

// Fetch analyses offered by this lab grouped by category
$stmt = $conn->prepare("
    SELECT a.*, la.id AS la_id, la.price
    FROM laboratory_analyses la
    JOIN analyses a ON a.id = la.analysis_id
    WHERE la.laboratory_id = :lid AND la.status = 'available' AND a.status = 'active'
    ORDER BY a.category ASC, a.name_ar ASC
");
$stmt->execute([':lid' => $id]);
$analysesList = $stmt->fetchAll();

$groupedAnalyses = [];
foreach ($analysesList as $a) {
    $groupedAnalyses[$a['category']][] = $a;
}

define('PAGE_TITLE', $lab['name'] . ' — تفاصيل المخبر والتحاليل');
require_once 'includes/header.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="lb-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php" class="text-white-50">الرئيسية</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>laboratories.php" class="text-white-50">المخابر الطبية</a></li>
        <li class="breadcrumb-item active text-white" aria-current="page"><?= h($lab['name']) ?></li>
      </ol>
    </nav>
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-3 bg-white p-3 text-primary d-flex align-items-center justify-content-center shadow" style="width:64px; height:64px; font-size:1.8rem;">
        <i class="bi bi-hospital"></i>
      </div>
      <div>
        <h1 class="mb-1"><?= h($lab['name']) ?></h1>
        <p class="mb-0 text-white-50"><i class="bi bi-geo-alt"></i> <?= h($lab['city']) ?> — <?= h($lab['address']) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="container pb-5">
  <div class="row g-4">
    <!-- ── Left Column: Lab Info Card ────────────────────────── -->
    <div class="col-lg-4">
      <div class="lb-card p-4 mb-4">
        <h5 class="fw-800 text-dark border-bottom pb-3 mb-3">
          <i class="bi bi-info-circle text-primary me-1"></i> معلومات المخبر
        </h5>

        <div class="d-flex flex-column gap-3 small">
          <div>
            <span class="text-muted d-block mb-1">المدينة / الولاية:</span>
            <strong class="text-dark"><i class="bi bi-geo-alt text-primary ms-1"></i> <?= h($lab['city']) ?></strong>
          </div>

          <div>
            <span class="text-muted d-block mb-1">العنوان الكامل:</span>
            <strong class="text-dark"><i class="bi bi-pin-map text-primary ms-1"></i> <?= h($lab['address']) ?></strong>
          </div>

          <div>
            <span class="text-muted d-block mb-1">رقم الهاتف:</span>
            <strong class="text-dark" dir="ltr"><i class="bi bi-telephone text-primary ms-1"></i> <?= h($lab['phone']) ?></strong>
          </div>

          <?php if (!empty($lab['email'])): ?>
            <div>
              <span class="text-muted d-block mb-1">البريد الإلكتروني:</span>
              <strong class="text-dark"><i class="bi bi-envelope text-primary ms-1"></i> <?= h($lab['email']) ?></strong>
            </div>
          <?php endif; ?>

          <div>
            <span class="text-muted d-block mb-1">مواعيد وساعات العمل:</span>
            <strong class="text-dark"><i class="bi bi-clock text-primary ms-1"></i> <?= h($lab['working_hours'] ?: '08:00 - 17:00') ?></strong>
          </div>

          <?php if (!empty($lab['description'])): ?>
            <div class="pt-2 border-top">
              <span class="text-muted d-block mb-1">نبذة عن المخبر:</span>
              <p class="mb-0 text-muted" style="line-height:1.6;"><?= nl2br(h($lab['description'])) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 pt-3 border-top">
          <?php if (!isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>auth/login.php?redirect=<?= urlencode(BASE_URL . 'booking/step1.php?lab_id=' . $lab['id']) ?>" class="btn-lb-primary w-100 justify-content-center py-2 mb-2">
              <i class="bi bi-calendar-plus ms-1"></i> تسجيل الدخول لحجز موعد
            </a>
            <small class="text-muted d-block text-center">يرجى تسجيل الدخول كـ مريض لإتمام الحجز</small>
          <?php elseif (currentUserRole() === 'patient'): ?>
            <a href="<?= BASE_URL ?>booking/step1.php?lab_id=<?= (int)$lab['id'] ?>" class="btn-lb-primary w-100 justify-content-center py-2">
              <i class="bi bi-calendar-plus ms-1"></i> ابدأ حجز موعد في هذا المخبر
            </a>
          <?php else: ?>
            <div class="alert alert-secondary py-2 small mb-0 text-center">
              حسابك الحالي (<?= h(currentUserRole()) ?>) غير مخصص لإجراء حجوزات كـ مريض.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ── Right Column: Available Analyses ──────────────────── -->
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-800 text-dark mb-0">
          <i class="bi bi-droplet-half text-primary me-1"></i> التحاليل المتوفرة في المخبر
        </h4>
        <span class="badge bg-primary text-white rounded-pill px-3 py-2">
          <?= count($analysesList) ?> تحليل متوفر
        </span>
      </div>

      <?php if (empty($groupedAnalyses)): ?>
        <div class="lb-card p-5 text-center text-muted">
          <i class="bi bi-inbox" style="font-size:3rem;"></i>
          <h5 class="mt-3">لا توجد تحاليل مدخلة حالياً لهذا المخبر</h5>
        </div>
      <?php else: ?>
        <?php foreach ($groupedAnalyses as $categoryName => $analyses): ?>
          <div class="lb-card mb-4 overflow-hidden">
            <div class="lb-card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-tag-fill me-1"></i> <?= h($categoryName) ?>
              </h5>
              <span class="small text-muted"><?= count($analyses) ?> فحص</span>
            </div>
            <div class="p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                  <thead class="table-light small">
                    <tr>
                      <th style="width: 45%;">اسم التحليل</th>
                      <th style="width: 25%;">التسمية بالفرنسية</th>
                      <th style="width: 15%;">مدة الإنجاز</th>
                      <th style="width: 15%; text-align:left;">السعر</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($analyses as $an): ?>
                      <tr>
                        <td>
                          <strong><?= h($an['name_ar']) ?></strong>
                          <?php if (!empty($an['preparation_notes'])): ?>
                            <small class="d-block text-muted" style="font-size:0.78rem;">
                              <i class="bi bi-exclamation-circle text-warning ms-1"></i><?= h($an['preparation_notes']) ?>
                            </small>
                          <?php endif; ?>
                        </td>
                        <td><span class="text-muted" dir="ltr"><?= h($an['name_fr']) ?></span></td>
                        <td>
                          <span class="badge bg-light text-secondary border">
                            <?= (int)$an['preparation_time_days'] ?> يوم
                          </span>
                        </td>
                        <td style="text-align:left;">
                          <strong class="text-primary"><?= formatPrice((float)$an['price']) ?></strong>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (isLoggedIn() && currentUserRole() === 'patient'): ?>
          <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>booking/step1.php?lab_id=<?= (int)$lab['id'] ?>" class="btn-lb-primary px-5 py-2">
              <i class="bi bi-calendar-check ms-1"></i> متابعة لاختيار التحاليل وحجز الموعد
            </a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
