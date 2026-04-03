<?php
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

try {
    // İlçe ID kontrolü
    if (!isset($_GET['ilce_id'])) {
        throw new Exception("İlçe ID gereklidir");
    }

    $ilceId = filter_var($_GET['ilce_id'], FILTER_VALIDATE_INT);
    if (!$ilceId) {
        throw new Exception("Geçersiz ilçe ID");
    }

    $db = Database::getInstance();

    // Kapalı kurum filtresi
    $kapaliKurum = isset($_GET['kapali_kurum']) && $_GET['kapali_kurum'] == '1';

    // ✅ DÜZELTİLDİ: okul_id yerine id, il ve ilce yerine il_id ve ilce_id
    $sql = "SELECT id, gorev_yeri, okul_tur, kurum_kodu FROM okullar WHERE ilce_id = ?";
    $params = [$ilceId];

    if (!$kapaliKurum) {
        $sql .= " AND kapali = 0";
    }

    $sql .= " ORDER BY gorev_yeri";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $okullar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($okullar, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>