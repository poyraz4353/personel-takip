<?php
/**
 * Öğrenim Bilgisi Silme API'si
 */

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['ogrenim_id']) || empty($_GET['ogrenim_id'])) {
        throw new Exception('Öğrenim ID gereklidir');
    }
    
    $ogrenim_id = (int)$_GET['ogrenim_id'];
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Önce kaydın var olduğunu kontrol et
    $stmt = $db->prepare("SELECT id FROM personel_ogrenim WHERE id = ?");
    $stmt->execute([$ogrenim_id]);
    $kayit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kayit) {
        throw new Exception('Kayıt bulunamadı');
    }
    
    // Silme işlemi
    $stmt = $db->prepare("DELETE FROM personel_ogrenim WHERE id = ?");
    $stmt->execute([$ogrenim_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Öğrenim silme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>