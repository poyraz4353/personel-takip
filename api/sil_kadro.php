<?php
/**
 * Kadro Kaydı Silme API'si
 */

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['kadro_id']) || empty($_GET['kadro_id'])) {
        throw new Exception('Kadro ID gereklidir');
    }
    
    $kadro_id = (int)$_GET['kadro_id'];
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM personel_kadro WHERE id = ?");
    $stmt->execute([$kadro_id]);
    $kayit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kayit) {
        throw new Exception('Kayıt bulunamadı');
    }
    
    $stmt = $db->prepare("DELETE FROM personel_kadro WHERE id = ?");
    $stmt->execute([$kadro_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Kadro silme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>