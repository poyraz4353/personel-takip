<?php
// check_users_table.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$host = $isWindows ? "127.0.0.1" : "127.0.0.1";
$port = $isWindows ? "3306" : "8889";
$username = "root";
$password = $isWindows ? "" : "root";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=personel_takip;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Users Tablosu Yapısı</h2>";
    
    // Tablo yapısını göster
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Örnek kullanıcıları göster
    echo "<h2>Örnek Kullanıcılar</h2>";
    $users = $pdo->query("SELECT * FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    if (count($users) > 0) {
        echo "<tr>";
        foreach (array_keys($users[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Hata: " . htmlspecialchars($e->getMessage());
}
?>