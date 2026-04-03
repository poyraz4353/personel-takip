<?php
require_once __DIR__ . '/../config/db_config.php';
header('Content-Type: application/json');

$hizmetSinifId = $_GET['hizmet_sinif_id'] ?? 0;
if (!$hizmetSinifId) {
    http_response_code(400);
    echo json_encode(['error' => 'Hizmet sınıfı ID gerekli']);
    exit;
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, unvan_adi FROM kadro_unvanlari WHERE hizmet_sinif_id = ? AND durum = 1 ORDER BY id ASC");
    $stmt->execute([$hizmetSinifId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}