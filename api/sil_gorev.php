<?php
/**
 * Görev Silme API-sil_gorev.php
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/session_manager.php';

// Session başlat ve auth kontrolü
SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

// Sadece DELETE methoduna izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Görev ID'sini al
$gorev_id = isset($_GET['gorev_id']) ? (int)$_GET['gorev_id'] : 0;

if ($gorev_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz görev ID']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Önce görevin var olup olmadığını kontrol et
    $check_stmt = $db->prepare("SELECT * FROM personel_gorev WHERE id = ?");
    $check_stmt->execute([$gorev_id]);
    $gorev = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gorev) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Görev bulunamadı']);
        exit;
    }
    
    // Silme işlemi
    $delete_stmt = $db->prepare("DELETE FROM personel_gorev WHERE id = ?");
    $result = $delete_stmt->execute([$gorev_id]);
    
    if ($result) {
        // Loglama (opsiyonel)
        error_log("Görev silindi - ID: $gorev_id, Kullanıcı: " . SessionManager::getUsername());
        
        echo json_encode([
            'success' => true,
            'message' => 'Görev başarıyla silindi',
            'silinen_id' => $gorev_id
        ]);
    } else {
        throw new Exception('Silme işlemi başarısız');
    }
    
} catch (PDOException $e) {
    error_log("Görev silme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Görev silme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>