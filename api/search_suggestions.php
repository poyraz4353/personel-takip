<?php
session_start();
require_once "../db.php"; // Veritabanı bağlantısı

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['query']);
$suggestions = [];

// Öneri: TC veya isimle arama
$stmt = $pdo->prepare("SELECT tcno, ad_soyad FROM staff WHERE tcno LIKE :query OR ad_soyad LIKE :query LIMIT 5");
$stmt->execute(['query' => "%$query%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $suggestions[] = [
        'tcno' => substr($row['tcno'], 0, 3) . '******' . substr($row['tcno'], -2), // Maskele
        'name' => $row['ad_soyad']
    ];
}

echo json_encode($suggestions);
