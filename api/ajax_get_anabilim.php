<?php
require 'db.php';

// Güvenli şekilde POST değerlerini al
$fakulte_id  = isset($_POST['fakulte_id']) ? intval($_POST['fakulte_id']) : 0;
$selected_id = isset($_POST['selected']) ? intval($_POST['selected']) : null;

// Fakülteye bağlı anabilim dallarını getir
$stmt = $db->prepare("
    SELECT anabilim_id, anabilim_adi 
    FROM anabilim_dali 
    WHERE fakulte_yuksekokul_id = ? 
    ORDER BY anabilim_adi
");
$stmt->execute([$fakulte_id]);
$anabilimler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown'u oluştur
echo '<option value="">Anabilim Dalı Seçiniz</option>';
foreach ($anabilimler as $ana) {
    $sel = ($ana['anabilim_id'] == $selected_id) ? 'selected' : '';
    echo '<option value="'.htmlspecialchars($ana['anabilim_id']).'" '.$sel.'>'
         .htmlspecialchars($ana['anabilim_adi']).'</option>';
}






