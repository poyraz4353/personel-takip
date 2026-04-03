<?php
/**
 * Ayrılma Kaydı Silme API
 */

require_once __DIR__ . '/../config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();

require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
    exit;
}

$ayrilma_id = $_GET['ayrilma_id'] ?? $_POST['ayrilma_id'] ?? null;

if (!$ayrilma_id) {
    echo json_encode(['success' => false, 'error' => 'Kayıt ID gerekli']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Önce silinecek ayrılma kaydının bilgilerini al
    $stmt = $db->prepare("SELECT personel_id, ayrilma_tarihi FROM personel_ayrilma WHERE id = ?");
    $stmt->execute([$ayrilma_id]);
    $silinecek = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($silinecek && !empty($silinecek['ayrilma_tarihi'])) {
        // Ayrılma tarihini temizle (görev kaydındaki bitis_tarihi alanını null yap)
        $gorev_sql = "SELECT id FROM personel_gorev 
                      WHERE personel_id = ? 
                      AND bitis_tarihi = ? 
                      ORDER BY kurum_baslama_tarihi DESC, id DESC 
                      LIMIT 1";
        $stmt2 = $db->prepare($gorev_sql);
        $stmt2->execute([$silinecek['personel_id'], $silinecek['ayrilma_tarihi']]);
        $gorev = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($gorev) {
            $update_sql = "UPDATE personel_gorev SET bitis_tarihi = NULL WHERE id = :id";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute(['id' => $gorev['id']]);
        }
    }
    
    // Ayrılma kaydını sil
    $stmt = $db->prepare("DELETE FROM personel_ayrilma WHERE id = ?");
    $result = $stmt->execute([$ayrilma_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Silme işlemi başarısız']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}