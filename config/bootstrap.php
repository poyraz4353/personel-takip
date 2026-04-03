<?php
/**
 * PROJE BOOTSTRAP DOSYASI
 * Bu dosya tüm ortamları otomatik yapılandırır
 */

// Hata Raporlamayı Aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman Dilimi
date_default_timezone_set('Europe/Istanbul');

// YOL TANIMLAMALARI
define('PROJECT_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', PROJECT_ROOT . 'app' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', PROJECT_ROOT . 'public' . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', PROJECT_ROOT . 'config' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', PROJECT_ROOT . 'storage' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('LOGS_PATH', STORAGE_PATH . 'logs' . DIRECTORY_SEPARATOR);

// Ortam Kontrolü ve Veritabanı Ayarları
function getEnvironmentConfig()
{
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    
    $config = [
        'is_local' => true,
        'is_windows' => $isWindows,
        'base_url' => $isWindows ? 'http://localhost/' : 'http://localhost:8888/',
    ];
    
    if ($isWindows) {
        // Laragon (Windows) Ayarları
        $config['db'] = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'username' => 'root',
            'password' => '',
            'database' => 'personel_takip',
            'charset' => 'utf8mb4'
        ];
    } else {
        // MAMP (macOS) Ayarları
        $config['db'] = [
            'host' => '127.0.0.1',
            'port' => '8889', // MAMP default MySQL port
            'username' => 'root',
            'password' => 'root',
            'database' => 'personel_takip',
            'charset' => 'utf8mb4'
        ];
    }
    
    return $config;
}

// Global config değişkeni
$GLOBALS['app_config'] = getEnvironmentConfig();

// Otomatik class yükleme
spl_autoload_register(function ($className) {
    $file = APP_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => false,
        'cookie_httponly' => true
    ]);
}

// Gerekli dosyaların varlığını kontrol et
function requireIfExists($filePath) {
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    error_log("Dosya bulunamadı: " . $filePath);
    return false;
}
?>