<?php
require 'db.php';

// Güvenli şekilde POST değerlerini al
$anabilim_id = isset($_POST['anabilim_id']) ? intval($_POST['anabilim_id']) : 0;
$selected_id = isset($_POST['selected']) ? intval($_POST['selected']) : null;

// Anabilime bağlı programları getir
$stmt = $db->prepare("
    SELECT program_id, program_adi 
    FROM program 
    WHERE anabilim_dali_id = ? 
    ORDER BY program_adi
");
$stmt->execute([$anabilim_id]);
$programlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown'u oluştur
echo '<option value="">Program Seçiniz</option>';
foreach ($programlar as $prog) {
    $sel = ($prog['program_id'] == $selected_id) ? 'selected' : '';
    echo '<option value="'.htmlspecialchars($prog['program_id']).'" '.$sel.'>'
         .htmlspecialchars($prog['program_adi']).'</option>';
}





