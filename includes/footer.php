<?php if (!isLoggedIn() || currentUserRole() !== 'patient'): ?>
<!-- ── Footer (Website Only - Hidden in Patient App) ───────── -->
<footer class="lb-footer">
  <div class="container">
    <div class="row g-4">

      <!-- Brand column -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand">
          <span class="brand-icon"><i class="bi bi-clipboard2-pulse"></i></span>
          <span>LABORA</span>
        </div>
        <p style="color:rgba(255,255,255,0.6); font-size:0.875rem; line-height:1.85;">
          منصة رقمية متخصصة تربط المرضى بمخابر التحاليل الطبية وتسهّل عملية حجز المواعيد وإدارتها بأعلى معايير الاحترافية والأمان.
        </p>
      </div>

      <!-- Quick links -->
      <div class="col-lg-2 col-md-3 col-6">
        <h6>روابط سريعة</h6>
        <a href="<?= BASE_URL ?>index.php">الرئيسية</a>
        <a href="<?= BASE_URL ?>laboratories.php">المخابر الطبية</a>
        <a href="<?= BASE_URL ?>analyses.php">التحاليل الطبية</a>
      </div>

      <!-- Account links -->
      <div class="col-lg-2 col-md-3 col-6">
        <h6>الحساب</h6>
        <?php if (!isLoggedIn()): ?>
          <a href="<?= BASE_URL ?>auth/login.php">تسجيل الدخول</a>
          <a href="<?= BASE_URL ?>auth/register.php">إنشاء حساب</a>
        <?php elseif (currentUserRole() === 'lab_manager'): ?>
          <a href="<?= BASE_URL ?>lab/dashboard.php">لوحة المخبر</a>
          <a href="<?= BASE_URL ?>auth/logout.php">تسجيل الخروج</a>
        <?php elseif (currentUserRole() === 'admin'): ?>
          <a href="<?= BASE_URL ?>admin/dashboard.php">لوحة الإدارة</a>
          <a href="<?= BASE_URL ?>auth/logout.php">تسجيل الخروج</a>
        <?php endif; ?>
      </div>

      <!-- Contact -->
      <div class="col-lg-4 col-md-6">
        <h6>تواصل معنا</h6>
        <p style="font-size:0.875rem; color:rgba(255,255,255,0.6); line-height:2; margin:0;">
          <i class="bi bi-envelope me-2"></i>contact@labora.dz<br>
          <i class="bi bi-telephone me-2"></i>+213 xx xxx xxxx<br>
          <i class="bi bi-geo-alt me-2"></i>الجزائر
        </p>
      </div>

    </div><!-- /.row -->

    <div class="footer-sep">
      <p class="footer-copy mb-0">
        &copy; <?= date('Y') ?> LABORA. جميع الحقوق محفوظة. &nbsp;|&nbsp;
        منصة حجز التحاليل الطبية
      </p>
    </div>
  </div><!-- /.container -->
</footer>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (defined('EXTRA_JS')) echo EXTRA_JS; ?>
</body>
</html>
