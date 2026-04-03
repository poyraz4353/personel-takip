<?php
// config/db_config.php - ÇOKLU ORTAM UYUMLU (XAMPP, LARAGON, MAMP)
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // İşletim sistemi tespiti
        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        
        // Varsayılan değerler
        $host = "127.0.0.1";
        $dbname = "personel_takip";
        $username = "root";
        
        if ($isWindows) {
            // XAMPP (Windows)
            $port = "3306";
            $password = ""; 
        } else {
            // XAMPP (macOS) - MAMP değil, XAMPP kullanıyorsan
            $port = "3306";
            $password = "";  // veya "root" dene
        }
                
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $username, 
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch(PDOException $e) {
            $env = $isWindows ? "XAMPP / Laragon (Windows)" : "MAMP (macOS)";
            $errorDetails = "<div style='font-family: Arial; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
            $errorDetails .= "<h3 style='color: #721c24;'>Veritabanı Bağlantı Hatası</h3>";
            $errorDetails .= "<p><strong>Tespit Edilen Ortam:</strong> $env</p>";
            $errorDetails .= "<p><strong>Hata Mesajı:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            $errorDetails .= "</div>";
            
            die($errorDetails);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * PDO bağlantısını döndürür
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Kullanıcı Giriş Kontrolü
     */
    public function checkLogin($username, $password) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                // 1. Modern Hash doğrulama
                if (password_verify($password, $user['password'])) {
                    return $user;
                }
                // 2. Geçici Düz metin doğrulama (Geliştirme aşaması için)
                if (trim($password) === trim($user['password'])) {
                    return $user;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login Sorgu Hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sorgu çalıştırır
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Sorgu Hatası: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Tek kayıt döndürür
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Tüm kayıtları döndürür
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Tek sütun değeri döndürür
     */
    public function fetchColumn($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetchColumn();
            return ($result !== false) ? $result : 0;
        } catch (PDOException $e) {
            error_log("fetchColumn Hatası: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Prepared statement hazırlar
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    /**
     * Son eklenen ID'yi döndürür
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Transaction başlatır
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Transaction'ı onaylar
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Transaction'ı geri alır
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
}

// --- HATA AYIKLAMA (DEBUG) KULLANIMI ---
/* // Verilerin neden gelmediğini anlamak için sayfanın en başında şu testi yapabilirsin:

$db = Database::getInstance();
$id = 1; // Test etmek istediğin personel ID'si
$veri = $db->fetch("SELECT * FROM personel WHERE id = :id", ['id' => $id]);

if($veri) {
    echo "<h3>Veritabanından Gelen Ham Veri:</h3>";
    echo "<pre>"; print_r($veri); echo "</pre>"; 
} else {
    echo "Belirtilen ID ile personel bulunamadı.";
}
*/
?>