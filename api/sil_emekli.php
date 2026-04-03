<?php
/**
 * Emeklilik Kaydı Silme API'si
 */

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['emekli_id']) || empty($_GET['emekli_id'])) {
        throw new Exception('Emeklilik ID gereklidir');
    }
    
    $emekli_id = (int)$_GET['emekli_id'];
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Önce silinecek kaydın bilgilerini al
    $kayit = $database->fetch("SELECT * FROM personel_emekli WHERE id = ?", [$emekli_id]);
    
    if (!$kayit) {
        throw new Exception('Kayıt bulunamadı');
    }
    
    $personel_id = $kayit['personel_id'];
    $emeklilik_tarihi = $kayit['emeklilik_tarihi'];
    
    // Silme işlemi
    $stmt = $db->prepare("DELETE FROM personel_emekli WHERE id = ?");
    $stmt->execute([$emekli_id]);
    
    // Emeklilik tarihine sahip görevin bitiş tarihini temizle
    if ($emeklilik_tarihi && $personel_id) {
        $gorev_sql = "SELECT id FROM personel_gorev 
                      WHERE personel_id = ? 
                      AND bitis_tarihi = ? 
                      LIMIT 1";
        $stmt = $db->prepare($gorev_sql);
        $stmt->execute([$personel_id, $emeklilik_tarihi]);
        $gorev = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($gorev) {
            $update_sql = "UPDATE personel_gorev SET bitis_tarihi = NULL WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$gorev['id']]);
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Emeklilik silme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>