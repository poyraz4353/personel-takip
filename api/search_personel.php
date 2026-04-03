<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/session_manager.php';

SessionManager::start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT tc_no, ad_soyadi FROM personel WHERE tc_no LIKE ? OR ad_soyadi LIKE ? LIMIT 5");
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch(PDOException $e) {
    error_log("Arama öneri hatası: " . $e->getMessage());
    echo json_encode([]);
}
?>