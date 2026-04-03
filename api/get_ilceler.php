<?php
// API/get_ilceler.php - DÜZELTİLMİŞ YOL
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    if (!isset($_GET['il_id']) || empty($_GET['il_id'])) {
        echo json_encode([]);
        exit;
    }
    
    $il_id = intval($_GET['il_id']);
    
    $stmt = $db->prepare("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi");
    $stmt->execute([$il_id]);
    $ilceler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($ilceler);
    
} catch (PDOException $e) {
    error_log("İlçe getirme hatası: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>