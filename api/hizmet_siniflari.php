<?php
require_once __DIR__ . '/../config/db_config.php';
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, sinif_adi FROM hizmet_siniflari WHERE durum = 1 ORDER BY id ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}