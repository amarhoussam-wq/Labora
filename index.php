<?php
require_once 'config.php';

// Redirect logged-in users away from public homepage
if (isLoggedIn()) {
    $role = currentUserRole();
    if ($role === 'lab_manager') redirect('lab/dashboard.php');
    elseif ($role === 'admin') redirect('admin/dashboard.php');
    else redirect('patient/dashboard.php');
}

// If accessed from mobile app, avoid showing public landing page
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'dz.labora.app') || str_contains($userAgent, '; wv')) {
    redirect('auth/login.php');
}

// Fetch dynamic statistics safely
$labCount = 0;
$analysisCount = 0;
$aptCount = 0;
$featuredLabs = [];

if ($conn) {
    try {
        $labCount = (int)$conn->query("SELECT COUNT(*) FROM laboratories WHERE status='active'")->fetchColumn();
        $analysisCount = (int)$conn->query("SELECT COUNT(*) FROM analyses WHERE status='active'")->fetchColumn();
        $aptCount = (int)$conn->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

        // Fetch top 3 featured labs
        $stmt = $conn->query("
            SELECT l.*, COUNT(la.id) AS analysis_count
            FROM laboratories l
            LEFT JOIN laboratory_analyses la ON la.laboratory_id = l.id AND la.status = 'available'
            WHERE l.status = 'active'
            GROUP BY l.id
            ORDER BY l.id ASC
            LIMIT 3
        ");
        $featuredLabs = $stmt->fetchAll();
    } catch (Exception $e) {
        // Fallback gracefully if tables are being set up
    }
}

define('PAGE_TITLE', 'الرئيسية — منصة المخابر والتحاليل الطبية');
require_once 'includes/header.php';
?>

<!-- ── Hero Section ────────────────────────────────────────── -->
<section class="lb-hero">
  <div class="container hero-content">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
          <i class="bi bi-shield-check ms-1"></i> المنصة المعتمدة لحجز التحاليل الطبية
        </span>
        <h1 class="fw-800 display-5 mb-3">
          احجز موعد تحليلك الطبي بكل سهولة وسرعة مع <span style="text-decoration: underline; text-decoration-color: #64B5F6;">LABORA</span>
        </h1>
        <p class="lead mb-4">
          نربطك مباشرةً بمخابر التحاليل الطبية المعتمدة. استعرض قائمة التحاليل والأسعار، اختر الموعد المناسب بدون انتظار، وارفع وصفتك الطبية بأمان تام.
        </p>
        <div class="hero-actions">
          <a href="<?= BASE_URL ?>laboratories.php" class="btn-hero-white">
            <i class="bi bi-geo-alt-fill text-primary"></i> استعرض المخابر المتاحة
          </a>
          <?php if (!isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>auth/register.php" class="btn-hero-outline">
              <i class="bi bi-person-plus"></i> إنشاء حساب مريض
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>patient/appointments.php" class="btn-hero-outline">
              <i class="bi bi-calendar-check"></i> مواعيدي المحجوزة
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <div class="p-4 bg-white rounded-4 shadow-lg text-dark text-start" style="border: 2px solid rgba(255,255,255,0.8);">
          <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
            <div class="brand-icon" style="width:48px; height:48px; border-radius:12px; font-size:1.4rem;">
              <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold">حجز فوري ومنظم</h6>
              <small class="text-muted">مواعيد دقيقة دون طوابير انتظار</small>
            </div>
          </div>
          <div class="d-flex flex-column gap-2 small">
            <div class="p-2 bg-light rounded d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>فترات زمنية دقيقة ومحدثة آنياً</span>
            </div>
            <div class="p-2 bg-light rounded d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>إرفاق آمن للوصفات الطبية (PDF/صور)</span>
            </div>
            <div class="p-2 bg-light rounded d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>شفافية كاملة في الأسعار والتحضيرات المطلوبة</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Live Statistics ─────────────────────────────────────── -->
<section class="lb-stats">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <div class="col-md-4 col-sm-6">
        <div class="stat-block">
          <div class="stat-num"><?= max($labCount, 3) ?>+</div>
          <div class="stat-lbl">مخابر طبية معتمدة ومجهزة</div>
        </div>
      </div>
      <div class="col-md-4 col-sm-6">
        <div class="stat-block">
          <div class="stat-num"><?= max($analysisCount, 18) ?>+</div>
          <div class="stat-lbl">نوع تحليل طبي متاح بالدليل</div>
        </div>
      </div>
      <div class="col-md-4 col-sm-6">
        <div class="stat-block">
          <div class="stat-num"><?= max($aptCount, 25) ?>+</div>
          <div class="stat-lbl">موعد منظم بنجاح عبر المنصة</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Features Section ────────────────────────────────────── -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center mb-5">
      <div class="section-label">لماذا LABORA؟</div>
      <h2 class="section-title">تجربة طبية حديثة وموثوقة</h2>
      <p class="section-sub mx-auto" style="max-width:600px;">
        صُممت منصة LABORA لتبسيط مسار المريض وحل مشكلة الاكتظاظ وضياع الوقت في المخابر.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon">
            <i class="bi bi-hospital"></i>
          </div>
          <h5 class="fw-bold mb-2">مخابر معتمدة وموثوقة</h5>
          <p class="text-muted small">
            دليل شامل يضم نخبة من مخابر التحاليل الطبية المتخصصة، مع عناوينها وساعات عملها والتحاليل المتوفرة لديها.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon">
            <i class="bi bi-calendar2-range"></i>
          </div>
          <h5 class="fw-bold mb-2">تنظيم دقيق للفترات الزمنية</h5>
          <p class="text-muted small">
            اختر اليوم والتوقيت الذي يلائمك من بين الفترات المفتوحة مباشرة من قبل مسؤولي المخبر لمنع التضارب.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon">
            <i class="bi bi-file-earmark-medical"></i>
          </div>
          <h5 class="fw-bold mb-2">إرفاق الوصفة الطبية بأمان</h5>
          <p class="text-muted small">
            إمكانية رفع وصفتك الطبية بسهولة وسرية، حتى يتسنى للمخبر الاستعداد المسبق لزيارتك وتجهيز المستلزمات.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── How It Works ────────────────────────────────────────── -->
<section class="py-5" style="background-color: var(--lb-gray-50);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <div class="section-label">خطوات بسيطة</div>
      <h2 class="section-title">كيف تحجز موعدك في 3 خطوات؟</h2>
    </div>

    <div class="row g-4 text-center">
      <div class="col-md-4">
        <div class="p-4 bg-white rounded-3 shadow-sm h-100">
          <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:48px; height:48px; font-weight:800;">
            1
          </div>
          <h5 class="fw-bold">اختر المخبر والتحاليل</h5>
          <p class="text-muted small mb-0">
            تصفح دليل المخابر، اختر المخبر القريب منك وحدد الفحوصات والتحاليل الطبية المطلوبة مع معرفة تكلفتها.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white rounded-3 shadow-sm h-100">
          <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:48px; height:48px; font-weight:800;">
            2
          </div>
          <h5 class="fw-bold">حدد الفترة الزمنية المناسبة</h5>
          <p class="text-muted small mb-0">
            اختر التاريخ والوقت المتاحين بنقرة واحدة من جدول المواعيد الفعلي للمخبر لتفادي أي انتظار.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white rounded-3 shadow-sm h-100">
          <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:48px; height:48px; font-weight:800;">
            3
          </div>
          <h5 class="fw-bold">أكد حجزك وارفع وصفتك</h5>
          <p class="text-muted small mb-0">
            راجع تفاصيل موعدك، ارفع الوصفة الطبية اختيارياً، واحصل على تأكيد حجز رسمي منظم في حسابك.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Featured Laboratories ───────────────────────────────── -->
<?php if (!empty($featuredLabs)): ?>
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <div class="section-label">شبكة المخابر</div>
        <h2 class="section-title mb-0">مخابر طبية متميزة</h2>
      </div>
      <a href="<?= BASE_URL ?>laboratories.php" class="btn-lb-outline">
        عرض جميع المخابر <i class="bi bi-arrow-left"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredLabs as $lab): ?>
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
                <span><?= h($lab['phone']) ?></span>
              </div>
              <div class="lab-info-row">
                <i class="bi bi-clock text-primary"></i>
                <span><?= h($lab['working_hours'] ?: '08:00 - 17:00') ?></span>
              </div>
              <div class="lab-info-row mt-2">
                <span class="cat-badge">
                  <i class="bi bi-droplet-half me-1"></i> <?= (int)$lab['analysis_count'] ?> تحليل طبي متوفر
                </span>
              </div>
            </div>
            <div class="lab-card-footer">
              <a href="<?= BASE_URL ?>laboratory.php?id=<?= (int)$lab['id'] ?>" class="btn-lb-primary w-100 justify-content-center">
                عرض التفاصيل وحجز موعد <i class="bi bi-chevron-left"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Call to Action ──────────────────────────────────────── -->
<section class="py-5" style="background: linear-gradient(135deg, var(--lb-primary-dark), var(--lb-accent)); color: white;">
  <div class="container text-center py-4">
    <h2 class="fw-800 text-white mb-3">هل أنت جاهز لتجربة حجز تحاليلك الطبية بسلاسة؟</h2>
    <p class="lead text-white-50 mx-auto mb-4" style="max-width: 650px;">
      انضم إلى آلاف المرضى الذين يوفرون وقتهم وينظمون فحوصاتهم الطبية عبر LABORA.
    </p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="<?= BASE_URL ?>laboratories.php" class="btn-hero-white">
        <i class="bi bi-search"></i> ابدأ بالبحث عن مخبر الآن
      </a>
      <?php if (!isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>auth/register.php" class="btn-hero-outline">
          <i class="bi bi-person-plus"></i> إنشاء حساب جديد
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
