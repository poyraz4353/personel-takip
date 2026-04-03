<?php
// config/app.php
// Laragon ve MAMP otomatik yapılandırması

class AppConfig {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        $this->detectEnvironment();
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function detectEnvironment() {
        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        
        $this->config['environment'] = [
            'is_windows' => $isWindows,
            'os' => $isWindows ? 'Windows' : 'macOS',
            'server' => $isWindows ? 'Laragon' : 'MAMP',
            'root_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR
        ];
    }
    
    private function loadConfig() {
        $isWindows = $this->config['environment']['is_windows'];
        
        // Veritabanı yapılandırması
        $this->config['database'] = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => $isWindows ? '3306' : '8889',
            'database' => 'personel_takip',
            'username' => 'root',
            'password' => $isWindows ? '' : 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ];
        
        // Yol yapılandırması
        $this->config['paths'] = [
            'root' => $this->config['environment']['root_path'],
            'config' => $this->config['environment']['root_path'] . 'config' . DIRECTORY_SEPARATOR,
            'uploads' => $this->config['environment']['root_path'] . 'uploads' . DIRECTORY_SEPARATOR,
            'assets' => $this->config['environment']['root_path'] . 'assets' . DIRECTORY_SEPARATOR,
            'cache' => $this->config['environment']['root_path'] . 'cache' . DIRECTORY_SEPARATOR,
            'classes' => $this->config['environment']['root_path'] . 'classes' . DIRECTORY_SEPARATOR,
            'includes' => $this->config['environment']['root_path'] . 'includes' . DIRECTORY_SEPARATOR,
            'api' => $this->config['environment']['root_path'] . 'api' . DIRECTORY_SEPARATOR
        ];
        
        // Uygulama ayarları
        $this->config['app'] = [
            'name' => 'Personel Takip Sistemi',
            'version' => '1.0',
            'timezone' => 'Europe/Istanbul',
            'debug' => true,
            'session_lifetime' => 7200 // 2 saat
        ];
    }
    
    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    public function getAll() {
        return $this->config;
    }
    
    // Debug için
    public function debugInfo() {
        return [
            'environment' => $this->config['environment'],
            'database_host' => $this->config['database']['host'],
            'database_port' => $this->config['database']['port'],
            'database_user' => $this->config['database']['username'],
            'paths_root' => $this->config['paths']['root']
        ];
    }
}

// Helper fonksiyonlar
function config($key, $default = null) {
    return AppConfig::getInstance()->get($key, $default);
}

function app_path($relativePath = '') {
    $base = config('paths.root');
    return $base . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
}

function is_windows_env() {
    return config('environment.is_windows');
}
?>