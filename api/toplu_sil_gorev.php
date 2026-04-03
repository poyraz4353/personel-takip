<?php
/**
 * Toplu Görev Silme API
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/session_manager.php';

// Session başlat ve auth kontrolü
SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

// Sadece POST methoduna izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$gorev_idler = $input['gorev_idler'] ?? [];

if (empty($gorev_idler) || !is_array($gorev_idler)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz görev ID listesi']);
    exit;
}

// ID'leri temizle
$gorev_idler = array_map('intval', $gorev_idler);
$gorev_idler = array_filter($gorev_idler, function($id) {
    return $id > 0;
});

if (empty($gorev_idler)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçerli görev ID bulunamadı']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Placeholder için soru işaretleri oluştur
    $placeholders = str_repeat('?,', count($gorev_idler) - 1) . '?';
    
    // Silme işlemi
    $delete_stmt = $db->prepare("DELETE FROM personel_gorev WHERE id IN ($placeholders)");
    $result = $delete_stmt->execute($gorev_idler);
    
    if ($result) {
        $silinen_sayi = $delete_stmt->rowCount();
        
        // Loglama
        error_log("Toplu görev silindi - Sayı: $silinen_sayi, Kullanıcı: " . SessionManager::getUsername());
        
        echo json_encode([
            'success' => true,
            'message' => "$silinen_sayi adet görev başarıyla silindi",
            'silinen_sayi' => $silinen_sayi
        ]);
    } else {
        throw new Exception('Toplu silme işlemi başarısız');
    }
    
} catch (PDOException $e) {
    error_log("Toplu görev silme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Toplu görev silme hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>