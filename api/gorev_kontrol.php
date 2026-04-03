<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/session_manager.php';

SessionManager::start();
header('Content-Type: application/json');

if (!isset($_GET['gorev_id']) || !is_numeric($_GET['gorev_id'])) {
    echo json_encode(['devam_ediyor' => false]);
    exit;
}

$gorev_id = (int)$_GET['gorev_id'];
$db = Database::getInstance();

$gorev = $db->fetch("SELECT bitis_tarihi FROM personel_gorev WHERE id = ?", [$gorev_id]);

if (!$gorev) {
    echo json_encode(['devam_ediyor' => false]);
    exit;
}

// bitis_tarihi boş veya 0000-00-00 ise devam ediyor
$devam_ediyor = empty($gorev['bitis_tarihi']) || $gorev['bitis_tarihi'] === '0000-00-00';

echo json_encode(['devam_ediyor' => $devam_ediyor]);
?>