require 'excel_okuyucu.php';
require 'veri_isleyici.php';
require 'veritabani.php';

$pdo = baglantiKur();
$satirlar = excelVerisiniOku('egitimler.xlsx');

foreach ($satirlar as $satir) {
    foreach ([[$satir[0], $satir[1]], [$satir[2], $satir[3]], [$satir[4], $satir[5]]] as [$ogrenim_durumu, $okul_bilgisi]) {
        if (empty($okul_bilgisi)) continue;

        $egitim = egitimParcala($okul_bilgisi);
        $uni_id = getOrInsert($pdo, 'universiteler', ['ad' => $egitim['universite']]);

        if ($egitim['fakulte']) {
            $fakulte_id = getOrInsert($pdo, 'fakulteler', ['ad' => $egitim['fakulte'], 'universite_id' => $uni_id]);
            $bolum_id = getOrInsert($pdo, 'bolumler', ['ad' => $egitim['bolum'], 'fakulte_id' => $fakulte_id]);
            $yuksek_okul_id = null;
        } else {
            $yuksek_okul_id = getOrInsert($pdo, 'yuksek_okullar', ['ad' => $egitim['yuksek_okul'], 'universite_id' => $uni_id]);
            $bolum_id = getOrInsert($pdo, 'bolumler', ['ad' => $egitim['bolum'], 'yuksek_okul_id' => $yuksek_okul_id]);
            $fakulte_id = null;
        }

        $insert = $pdo->prepare("INSERT INTO personel_egitim (personel_id, universite_id, fakulte_id, yuksek_okul_id, bolum_id, ogrenim_durumu) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$personel_id, $uni_id, $fakulte_id, $yuksek_okul_id, $bolum_id, $ogrenim_durumu]);
    }
}
