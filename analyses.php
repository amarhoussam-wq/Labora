<?php
require_once 'config.php';

$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$categories = [];
$analyses   = [];

if ($conn) {
    try {
        $categories = $conn->query("SELECT DISTINCT category FROM analyses WHERE status='active' AND category IS NOT NULL ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

        $sql = "SELECT a.*, COUNT(la.id) AS labs_count 
                FROM analyses a
                LEFT JOIN laboratory_analyses la ON la.analysis_id = a.id AND la.status = 'available'
                WHERE a.status = 'active'";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (a.name_ar LIKE :s OR a.name_fr LIKE :s OR a.description LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        if ($category !== '') {
            $sql .= " AND a.category = :c";
            $params[':c'] = $category;
        }

        $sql .= " GROUP BY a.id ORDER BY a.category ASC, a.name_ar ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $analyses = $stmt->fetchAll();
    } catch (Exception $e) {
        $analyses = [];
    }
}

define('PAGE_TITLE', 'دليل التحاليل الطبية');
require_once 'includes/header.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="lb-page-header">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1><i class="bi bi-droplet-half me-2"></i> دليل الفحوصات والتحاليل الطبية</h1>
        <p>تصفح قائمة التحاليل الطبية الشاملة المتاحة على المنصة، وتعرف على شروط التحضير والمخابر التي توفرها.</p>
      </div>
      <div class="col-md-4 text-md-start mt-3 mt-md-0">
        <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold">
          <i class="bi bi-card-checklist"></i> <?= count($analyses) ?> تحليل مسجل
        </span>
      </div>
    </div>
  </div>
</div>

<div class="container pb-5">
  <!-- ── Search & Filter ───────────────────────────────────── -->
  <form method="GET" action="" class="lb-search-bar">
    <div class="flex-grow-1">
      <label class="lb-label" for="search"><i class="bi bi-search me-1"></i> البحث بالاسم (عربي / فرنسي)</label>
      <input type="text" class="lb-input" id="search" name="search"
             value="<?= h($search) ?>" placeholder="مثال: NFS, Glycémie, تعداد الدم, فيتامين D...">
    </div>

    <div style="min-width: 240px;">
      <label class="lb-label" for="category"><i class="bi bi-tag me-1"></i> الفئة / التخصص</label>
      <select class="lb-input" id="category" name="category">
        <option value="">جميع الفئات</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <button type="submit" class="btn-lb-primary py-2 px-4">
        <i class="bi bi-filter"></i> تطبيق الفلتر
      </button>
      <?php if ($search || $category): ?>
        <a href="<?= BASE_URL ?>analyses.php" class="btn-lb-outline py-2 px-3 ms-2">
          إلغاء
        </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ── Analyses List ─────────────────────────────────────── -->
  <?php if (empty($analyses)): ?>
    <div class="empty-state bg-white rounded-3 p-5 border">
      <i class="bi bi-search text-muted" style="font-size: 3.5rem;"></i>
      <h5 class="fw-bold mt-3">لم نجد أي تحليل يطابق نتائج البحث</h5>
      <p class="text-muted small">يرجى التحقق من الكلمات المدخلة أو تجربة فئة أخرى.</p>
      <a href="<?= BASE_URL ?>analyses.php" class="btn-lb-outline mt-2">عرض جميع التحاليل</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($analyses as $an): ?>
        <div class="col-lg-6">
          <div class="lb-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <h5 class="fw-bold text-dark mb-1"><?= h($an['name_ar']) ?></h5>
                  <div class="text-muted small fw-medium" dir="ltr" style="text-align:right;"><?= h($an['name_fr']) ?></div>
                </div>
                <span class="cat-badge"><?= h($an['category']) ?></span>
              </div>

              <?php if (!empty($an['description'])): ?>
                <p class="text-muted small mb-3" style="line-height:1.6;">
                  <?= h($an['description']) ?>
                </p>
              <?php endif; ?>

              <div class="p-3 bg-light rounded-3 small mb-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="bi bi-clock-history text-primary"></i>
                  <span>مدة تسليم النتيجة التقريبية: <strong><?= (int)$an['preparation_time_days'] ?> يوم</strong></span>
                </div>
                <?php if (!empty($an['preparation_notes'])): ?>
                  <div class="d-flex align-items-start gap-2 text-dark">
                    <i class="bi bi-info-circle text-warning mt-1"></i>
                    <span>تعليمات التحضير: <em><?= h($an['preparation_notes']) ?></em></span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="small text-muted">
                <i class="bi bi-hospital text-primary me-1"></i> متوفر في <strong><?= (int)$an['labs_count'] ?></strong> مخبر
              </span>
              <a href="<?= BASE_URL ?>laboratories.php" class="btn-lb-light py-1 px-3 small">
                ابحث عن مخبر يوفر التحليل <i class="bi bi-arrow-left ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
