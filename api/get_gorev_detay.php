<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/session_manager.php';

SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

function sendResponse($data = null, $success = true, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => time(),
        'data' => $data
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    if (!isset($_GET['gorev_id']) || !is_numeric($_GET['gorev_id']) || $_GET['gorev_id'] <= 0) {
        sendResponse(null, false, 'Geçersiz görev ID', 400);
    }
    
    $gorev_id = (int)$_GET['gorev_id'];
    $db = Database::getInstance();
    
    // SADECE personel_gorev tablosundan çek, JOIN'leri KALDIR
    $sql = "SELECT * FROM personel_gorev WHERE id = :gorev_id LIMIT 1";
    
    $gorev = $db->fetch($sql, ['gorev_id' => $gorev_id]);
    
    if (!$gorev) {
        sendResponse(null, false, 'Görev bulunamadı', 404);
    }
    
    sendResponse($gorev, true, 'Görev detayları başarıyla getirildi');
    
} catch (Exception $e) {
    error_log("API Hatası: " . $e->getMessage());
    sendResponse(null, false, 'Sunucu hatası: ' . $e->getMessage(), 500);
}
?>