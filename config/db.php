<?php
// config/db.php - SADECE XAMPP (Windows + Mac Uyumlu)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// XAMPP her iki sistemde de aynı ayarları kullanır
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');      // XAMPP'de şifre boş (hem Windows hem Mac)
define('DB_NAME', 'personel_takip');
define('DB_CHARSET', 'utf8mb4');
define('DEBUG_MODE', true);

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Bağlantı Hatası: " . $e->getMessage());
            } else {
                die("Veritabanı bağlantı hatası.");
            }
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}
?>