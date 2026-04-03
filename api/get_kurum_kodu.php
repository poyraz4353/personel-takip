<?php
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['okul_id'])) {
        throw new Exception("Okul ID gereklidir");
    }
    
    $okulId = filter_var($_GET['okul_id'], FILTER_VALIDATE_INT);
    if (!$okulId) {
        throw new Exception("Geçersiz okul ID");
    }
    
    $db = Database::getInstance();
    
    // Okul bilgilerini al
    $sql = "SELECT okul_id, gorev_yeri, okul_tur, kurum_kodu FROM okullar WHERE okul_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$okulId]);
    $okul = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$okul) {
        throw new Exception("Okul bulunamadı");
    }
    
    echo json_encode($okul);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>