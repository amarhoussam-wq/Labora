<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// LABORA - Core Configuration
// ============================================================
define('ROOT', __DIR__);

// Auto-detect BASE_URL based on server environment
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $projRoot = str_replace('\\', '/', realpath(__DIR__));
    if ($docRoot && str_starts_with($projRoot, $docRoot)) {
        $sub = trim(substr($projRoot, strlen($docRoot)), '/');
        $base = $sub === '' ? '/' : '/' . $sub . '/';
    } else {
        $base = '/labora/';
    }
    define('BASE_URL', $base);
}

// Database connection
$tmpConfig = '/tmp/labora_db_config.php';
$localConfigFile = ROOT . '/db_config.php';

if (file_exists($tmpConfig)) {
    $savedConfig = require $tmpConfig;
} elseif (file_exists($localConfigFile)) {
    $savedConfig = require $localConfigFile;
} else {
    $savedConfig = [];
}

$defaultHost = (PHP_OS_FAMILY === 'Windows') ? 'localhost' : 'e880ooggksgs0004sosggows';

$host = $savedConfig['host'] ?? (getenv('DB_HOST') ?: $defaultHost);
// If running in Docker on Coolify and host is set to "mysql", automatically use the real container host
if (PHP_OS_FAMILY !== 'Windows' && ($host === 'mysql' || $host === 'localhost' || empty($host))) {
    $host = 'e880ooggksgs0004sosggows';
}
$port = (int)($savedConfig['port'] ?? (getenv('DB_PORT') ?: 3306));
$db   = $savedConfig['name'] ?? (getenv('DB_NAME') ?: 'labora');
$user = $savedConfig['user'] ?? (getenv('DB_USER') ?: 'root');
$pass = $savedConfig['pass'] ?? (getenv('DB_PASS') ?: '');

try {
    $conn = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Ensure analyses & laboratory_analyses tables exist
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `analyses` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `name_ar` VARCHAR(150) NOT NULL,
              `name_fr` VARCHAR(150) NOT NULL,
              `category` VARCHAR(100) NOT NULL,
              `description` TEXT NULL,
              `sample_type` VARCHAR(100) DEFAULT 'دم وريدي',
              `fasting_required` TINYINT(1) DEFAULT 0,
              `status` ENUM('active', 'inactive') DEFAULT 'active'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `laboratory_analyses` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `laboratory_id` INT NOT NULL,
              `analysis_id` INT NOT NULL,
              `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `turnaround_time` VARCHAR(100) DEFAULT 'خلال 24 ساعة',
              `status` ENUM('available', 'unavailable') DEFAULT 'available',
              UNIQUE KEY `uniq_lab_analysis` (`laboratory_id`, `analysis_id`),
              FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $cnt = (int)$conn->query("SELECT COUNT(*) FROM analyses")->fetchColumn();
        if ($cnt === 0) {
            $conn->exec("
                INSERT INTO `analyses` (`id`, `name_ar`, `name_fr`, `category`, `fasting_required`) VALUES
                (1, 'تعداد الدم الكامل', 'NFS - Numération Formule Sanguine', 'أمراض الدم (Hématologie)', 0),
                (2, 'سرعة الترسيب', 'VS - Vitesse de Sédimentation', 'أمراض الدم (Hématologie)', 0),
                (3, 'فصيلة الدم والريزوس', 'Groupage Sanguin + Rhésus', 'أمراض الدم (Hématologie)', 0),
                (4, 'زمن التخثر', 'TP / INR', 'أمراض الدم (Hématologie)', 0),
                (5, 'سكر الدم على الريق', 'Glycémie à jeun', 'الكيمياء الحيوية (Biochimie)', 1),
                (6, 'السكر التراكمي', 'HbA1c - Hémoglobine Glyquée', 'الكيمياء الحيوية (Biochimie)', 0),
                (7, 'الكوليسترول الكلي والدهون', 'Bilan Lipidique (Cholestérol, Triglycérides)', 'الكيمياء الحيوية (Biochimie)', 1),
                (8, 'وظائف الكلى (البولة والكراتينين)', 'Urée & Créatinine', 'الكيمياء الحيوية (Biochimie)', 0),
                (9, 'وظائف الكبد', 'Bilan Hépatique (ASAT, ALAT, Bilirubine)', 'الكيمياء الحيوية (Biochimie)', 0),
                (10, 'حمض البوليك (النقرس)', 'Acide Urique', 'الكيمياء الحيوية (Biochimie)', 0),
                (11, 'فحص الأملاح والمعادن', 'Ionogramme Sanguin (Na+, K+, Cl-)', 'الكيمياء الحيوية (Biochimie)', 0),
                (12, 'هرمون الغدة الدرقية', 'TSH - Thyréostimuline', 'الهرمونات (Hormonologie)', 0),
                (13, 'فيتامين د', 'Vitamine D (25-OH)', 'الهرمونات والفيتامينات', 0),
                (14, 'مخزون الحديد', 'Ferritine', 'الهرمونات والفيتامينات', 0),
                (15, 'بروتين الالتهاب', 'CRP - Protéine C-Réactive', 'الأمصال والمناعة', 0),
                (16, 'فحص وزرع البول', 'ECBU - Examen Cytobactériologique des Urines', 'الميكروبيولوجيا', 0);

                INSERT IGNORE INTO `laboratory_analyses` (`laboratory_id`, `analysis_id`, `price`) VALUES
                (1, 1, 800), (1, 2, 400), (1, 3, 600), (1, 4, 800),
                (1, 5, 400), (1, 6, 1500), (1, 7, 1400), (1, 8, 1000),
                (1, 9, 1600), (1, 10, 600), (1, 11, 1200), (1, 12, 1800),
                (1, 13, 2800), (1, 14, 2000), (1, 15, 800), (1, 16, 1200),
                (2, 1, 750), (2, 2, 400), (2, 3, 500), (2, 5, 350),
                (2, 6, 1400), (2, 7, 1300), (2, 8, 950), (2, 9, 1500),
                (2, 12, 1700), (2, 13, 2600), (2, 15, 750), (2, 16, 1100),
                (3, 1, 800), (3, 5, 400), (3, 6, 1500), (3, 7, 1400),
                (3, 8, 1000), (3, 12, 1800), (3, 13, 2800), (3, 15, 800);
            ");
        }
        // Add contact_phone column to appointments if not exists (safe cross-version MySQL/MariaDB migration)
        try {
            $hasCol = $conn->query("SHOW COLUMNS FROM `appointments` LIKE 'contact_phone'")->fetch();
            if (!$hasCol) {
                $conn->exec("ALTER TABLE `appointments` ADD `contact_phone` VARCHAR(20) DEFAULT NULL AFTER `notes`");
            }
        } catch (Exception $e) {}
    } catch (Exception $e) {}
} catch (Exception $e) {
    // If 'labora' database does not exist yet, we catch gracefully
    $conn = null;
    $db_connection_error = $e->getMessage();
}

// Load shared modules
require_once ROOT . '/includes/functions.php';
require_once ROOT . '/includes/auth.php';
