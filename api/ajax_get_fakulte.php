<?php
require 'db.php';

// Güvenli şekilde POST değerlerini al
$universite_id = isset($_POST['universite_id']) ? intval($_POST['universite_id']) : 0;
$selected_id   = isset($_POST['selected']) ? intval($_POST['selected']) : null;

// Üniversiteye bağlı fakülteleri getir
$stmt = $db->prepare("
    SELECT fakulte_id, fakulte_adi 
    FROM fakulte_yuksekokul 
    WHERE universite_id = ? 
    ORDER BY fakulte_adi
");
$stmt->execute([$universite_id]);
$fakulteler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown'u oluştur
echo '<option value="">Fakülte Seçiniz</option>';
foreach ($fakulteler as $fak) {
    $sel = ($fak['fakulte_id'] == $selected_id) ? 'selected' : '';
    echo '<option value="'.htmlspecialchars($fak['fakulte_id']).'" '.$sel.'>'
         .htmlspecialchars($fak['fakulte_adi']).'</option>';
}






