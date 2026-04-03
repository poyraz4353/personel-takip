<?php
/**
 * Dosya Gönderme Kaydı Silme API
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

$dosya_id = $_GET['dosya_id'] ?? $_POST['dosya_id'] ?? null;

if (!$dosya_id) {
    echo json_encode(['success' => false, 'error' => 'Kayıt ID gerekli']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("DELETE FROM personel_dosya_gonderme WHERE id = ?");
    $result = $stmt->execute([$dosya_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Silme işlemi başarısız']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}