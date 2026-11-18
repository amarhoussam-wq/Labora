<?php
/**
 * LABORA - Shared Helper Functions
 */

// ── CSRF ────────────────────────────────────────────────────
function generateCsrfToken(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// ── Flash Messages ───────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $flash;
}

// ── Output / Sanitization ────────────────────────────────────
function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

// ── Date / Time Formatting ───────────────────────────────────
function formatDate(string $date): string {
    if (!$date) return '';
    $d      = new DateTime($date);
    $days   = ['Sunday'=>'الأحد','Monday'=>'الاثنين','Tuesday'=>'الثلاثاء',
               'Wednesday'=>'الأربعاء','Thursday'=>'الخميس','Friday'=>'الجمعة','Saturday'=>'السبت'];
    $months = ['January'=>'يناير','February'=>'فبراير','March'=>'مارس',
               'April'=>'أبريل','May'=>'مايو','June'=>'يونيو',
               'July'=>'يوليو','August'=>'أغسطس','September'=>'سبتمبر',
               'October'=>'أكتوبر','November'=>'نوفمبر','December'=>'ديسمبر'];
    $day   = $days[$d->format('l')]   ?? $d->format('l');
    $month = $months[$d->format('F')] ?? $d->format('F');
    return $day . '، ' . $d->format('d') . ' ' . $month . ' ' . $d->format('Y');
}

function formatTime(string $time): string {
    return substr($time, 0, 5); // HH:MM
}

function formatPrice(float $price): string {
    return number_format($price, 2, '.', ',') . ' دج';
}

// ── Status Helpers ───────────────────────────────────────────
function getStatusInfo(string $status): array {
    return match ($status) {
        'pending'   => ['label' => 'قيد الانتظار', 'class' => 'status-pending',   'icon' => 'bi-clock-history'],
        'confirmed' => ['label' => 'مؤكد',          'class' => 'status-confirmed', 'icon' => 'bi-check-circle-fill'],
        'done'      => ['label' => 'مكتمل',          'class' => 'status-done',      'icon' => 'bi-check2-all'],
        'cancelled' => ['label' => 'ملغى',           'class' => 'status-cancelled', 'icon' => 'bi-x-circle-fill'],
        default     => ['label' => $status,          'class' => 'status-pending',   'icon' => 'bi-question-circle'],
    };
}

// ── File Upload Validation ───────────────────────────────────
function validateUpload(array $file): array {
    $errors = [];

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $errors; // optional upload — no file is OK
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'حدث خطأ أثناء رفع الملف.';
        return $errors;
    }

    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxSize) {
        $errors[] = 'حجم الملف يتجاوز الحد المسموح به (5 ميغابايت).';
    }

    $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        $errors[] = 'نوع الملف غير مقبول. الأنواع المقبولة: PDF، JPG، PNG.';
    }

    return $errors;
}

function safeFilename(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = in_array($ext, ['jpg','jpeg','png','pdf']) ? $ext : 'bin';
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

// ── Pagination Helper ────────────────────────────────────────
function paginate(int $total, int $perPage, int $page): array {
    $totalPages = (int)ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return ['total' => $total, 'pages' => $totalPages, 'page' => $page, 'offset' => $offset, 'per_page' => $perPage];
}
