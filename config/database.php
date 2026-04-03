<?php
// config/database.php - SADECE XAMPP (Windows + Mac Uyumlu)

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // XAMPP her iki sistemde de aynı ayarları kullanır
        $host = "127.0.0.1";
        $port = "3306";
        $username = "root";
        $password = "";      // XAMPP'de şifre boş (hem Windows hem Mac)
        $dbname = "personel_takip";
        
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch(PDOException $e) {
            die("Bağlantı Hatası: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) { 
            self::$instance = new Database(); 
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchColumn($sql, $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function getEnvironmentInfo() {
        return [
            'environment' => 'XAMPP',
            'os' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'Windows' : 'macOS'
        ];
    }

    public function checkLogin($username, $password) {
        $user = $this->fetch("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>