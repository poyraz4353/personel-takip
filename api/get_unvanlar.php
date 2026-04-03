<?php
require_once 'config/database.php';

$tip = $_GET['tip'] ?? '';
$hizmet_sinifi = $_GET['hizmet_sinifi'] ?? '';

if ($tip === 'kadro') {
    $tablo = 'kadro_unvanlari';
} else {
    $tablo = 'gorev_unvanlari';
}

$unvanlar = $db->query("SELECT * FROM $tablo WHERE hizmet_sinifi = '$hizmet_sinifi' AND durum = 1 ORDER BY unvan_adi")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($unvanlar);
?>