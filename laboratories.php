<?php
require_once 'config.php';

$search = trim($_GET['search'] ?? '');
$city   = trim($_GET['city'] ?? '');

$cities = [];
$labs = [];

if ($conn) {
    try {
        // Unique cities list for filter
        $cities = $conn->query("SELECT DISTINCT city FROM laboratories WHERE status='active' AND city IS NOT NULL ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);

        // Query labs
        $sql = "
            SELECT l.*, COUNT(la.id) AS analysis_count
            FROM laboratories l
            LEFT JOIN laboratory_analyses la ON la.laboratory_id = l.id AND la.status = 'available'
            WHERE l.status = 'active'
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (l.name LIKE :s OR l.description LIKE :s OR l.address LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        if ($city !== '') {
            $sql .= " AND l.city = :c";
            $params[':c'] = $city;
        }

        $sql .= " GROUP BY l.id ORDER BY l.name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $labs = $stmt->fetchAll();
    } catch (Exception $e) {
        $labs = [];
    }
}

define('PAGE_TITLE', 'دليل المخابر الطبية');
require_once 'includes/header.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="lb-page-header">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1><i class="bi bi-hospital me-2"></i> دليل المخابر الطبية المعتمدة</h1>
        <p>استكشف المخابر الطبية الشريكة، قارن التحاليل المتوفرة، واحجز موعدك فوراً في التوقيت الذي يناسبك.</p>
      </div>
      <div class="col-md-4 text-md-start mt-3 mt-md-0">
        <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold">
          <i class="bi bi-check2-circle"></i> <?= count($labs) ?> مخبر متاح
        </span>
      </div>
    </div>
  </div>
</div>

<div class="container pb-5">
  <!-- ── Search & Filter Bar ───────────────────────────────── -->
  <form method="GET" action="" class="lb-search-bar">
    <div class="flex-grow-1">
      <label class="lb-label" for="search"><i class="bi bi-search me-1"></i> البحث بالاسم أو العنوان</label>
      <input type="text" class="lb-input" id="search" name="search"
             value="<?= h($search) ?>" placeholder="مثال: مخبر ابن سينا، الحي الطبي...">
    </div>

    <div style="min-width: 220px;">
      <label class="lb-label" for="city"><i class="bi bi-geo-alt me-1"></i> المدينة / الولاية</label>
      <select class="lb-input" id="city" name="city">
        <option value="">جميع المدن</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= h($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <button type="submit" class="btn-lb-primary py-2 px-4">
        <i class="bi bi-filter"></i> تطبيق الفلتر
      </button>
      <?php if ($search || $city): ?>
        <a href="<?= BASE_URL ?>laboratories.php" class="btn-lb-outline py-2 px-3 ms-2">
          إلغاء
        </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ── Labs Grid ─────────────────────────────────────────── -->
  <?php if (empty($labs)): ?>
    <div class="empty-state bg-white rounded-3 p-5 border">
      <i class="bi bi-hospital text-muted" style="font-size: 3.5rem;"></i>
      <h5 class="fw-bold mt-3">لم يتم العثور على أي مخبر طبي مطابقة لمعايير البحث</h5>
      <p class="text-muted small">يرجى تجربة البحث بكلمات مختلفة أو إزالة الفلتر لإظهار جميع المخابر.</p>
      <a href="<?= BASE_URL ?>laboratories.php" class="btn-lb-outline mt-2">
        عرض جميع المخابر
      </a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($labs as $lab): ?>
        <div class="col-lg-4 col-md-6">
          <div class="lab-card">
            <div class="lab-card-header">
              <div class="lab-icon-wrap">
                <i class="bi bi-hospital"></i>
              </div>
              <h5><?= h($lab['name']) ?></h5>
              <div class="lab-city-tag"><i class="bi bi-geo-alt"></i> <?= h($lab['city']) ?></div>
            </div>
            <div class="lab-card-body">
              <div class="lab-info-row">
                <i class="bi bi-pin-map text-primary"></i>
                <span><?= h($lab['address']) ?></span>
              </div>
              <div class="lab-info-row">
                <i class="bi bi-telephone text-primary"></i>
                <span dir="ltr"><?= h($lab['phone']) ?></span>
              </div>
              <div class="lab-info-row">
                <i class="bi bi-clock text-primary"></i>
                <span><?= h($lab['working_hours'] ?: '08:00 - 17:00') ?></span>
              </div>
              <?php if (!empty($lab['description'])): ?>
                <p class="text-muted small mt-2 mb-0" style="line-height:1.5;">
                  <?= h(mb_strimwidth($lab['description'], 0, 95, '...')) ?>
                </p>
              <?php endif; ?>
              <div class="lab-info-row mt-2">
                <span class="cat-badge">
                  <i class="bi bi-droplet-half me-1"></i> <?= (int)$lab['analysis_count'] ?> تحليل متوفر
                </span>
              </div>
            </div>
            <div class="lab-card-footer">
              <a href="<?= BASE_URL ?>laboratory.php?id=<?= (int)$lab['id'] ?>" class="btn-lb-primary w-100 justify-content-center">
                عرض التفاصيل وقائمة التحاليل <i class="bi bi-chevron-left"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
