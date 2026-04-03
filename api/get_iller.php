<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    $stmt = $db->prepare("SELECT id, il_adi FROM iller ORDER BY il_adi");
    $stmt->execute();
    $iller = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($iller);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
