<?php
/**
 * GÖREV DÜZENLEME SAYFASI - PHP ile doldurulmuş
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::requireAuth();

$gorev_id = $_GET['id'] ?? 0;
if (!$gorev_id) {
    header('Location: dashboard_Anasayfa.php');
    exit;
}

require_once __DIR__ . '/config/db_config.php';
$db = Database::getInstance();

// Görev bilgilerini al
$gorev = $db->fetch("SELECT * FROM personel_gorev WHERE id = ?", [$gorev_id]);

if (!$gorev) {
    header('Location: dashboard_Anasayfa.php');
    exit;
}

// Personel bilgilerini al
$personel = $db->fetch("SELECT * FROM personel WHERE id = ?", [$gorev['personel_id']]);

// İlleri al
$iller = $db->fetchAll("SELECT id, il_adi FROM iller ORDER BY il_adi");

// İlçeleri al (seçili ile göre)
$ilceler = [];
if ($gorev['gorev_il_adi']) {
    $il = $db->fetch("SELECT id FROM iller WHERE il_adi = ?", [$gorev['gorev_il_adi']]);
    if ($il) {
        $ilceler = $db->fetchAll("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi", [$il['id']]);
    }
}

// Okulları al (seçili ilçeye göre)
$okullar = [];
if ($gorev['gorev_ilce_adi'] && $il) {
    $ilce = $db->fetch("SELECT id FROM ilceler WHERE ilce_adi = ? AND il_id = ?", [$gorev['gorev_ilce_adi'], $il['id']]);
    if ($ilce) {
        $okullar = $db->fetchAll("SELECT id, gorev_yeri, kurum_kodu, okul_tur FROM okullar WHERE ilce_id = ? ORDER BY gorev_yeri", [$ilce['id']]);
    }
}

include 'head.php';
include 'header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Görev Düzenle (ID: <?= $gorev['id'] ?>)</h4>
        </div>
        <div class="card-body">
            <p><strong>Personel:</strong> <?= htmlspecialchars($personel['ad_soyadi']) ?> (<?= $personel['tc_no'] ?>)</p>
            
            <form method="POST" action="gorev_kaydet.php">
                <input type="hidden" name="gorev_id" value="<?= $gorev['id'] ?>">
                <input type="hidden" name="personel_id" value="<?= $gorev['personel_id'] ?>">
                
                <!-- Kurum/Okul Bilgileri -->
                <div class="card mb-3">
                    <div class="card-header">Kurum/Okul Bilgileri</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>İl</label>
                                <select name="il_id" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($iller as $il_sec): ?>
                                        <option value="<?= $il_sec['id'] ?>" 
                                            <?= $il_sec['il_adi'] == $gorev['gorev_il_adi'] ? 'selected' : '' ?>>
                                            <?= $il_sec['il_adi'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>İlçe</label>
                                <select name="ilce_id" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($ilceler as $ilce_sec): ?>
                                        <option value="<?= $ilce_sec['id'] ?>" 
                                            <?= $ilce_sec['ilce_adi'] == $gorev['gorev_ilce_adi'] ? 'selected' : '' ?>>
                                            <?= $ilce_sec['ilce_adi'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Görev Yeri</label>
                                <select name="gorev_kurum_kodu" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($okullar as $okul): ?>
                                        <option value="<?= $okul['kurum_kodu'] ?>" 
                                            <?= $okul['kurum_kodu'] == $gorev['gorev_kurum_kodu'] ? 'selected' : '' ?>>
                                            <?= $okul['gorev_yeri'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Göreve Başlama Bilgileri -->
                <div class="card mb-3">
                    <div class="card-header">Göreve Başlama Bilgileri</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Görev Başlama Tarihi</label>
                                <input type="date" name="kurum_baslama_tarihi" class="form-control" 
                                       value="<?= $gorev['kurum_baslama_tarihi'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Ayrılma Tarihi</label>
                                <input type="date" name="gorev_ayrilma_tarihi" class="form-control" 
                                       value="<?= $gorev['bitis_tarihi'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- İstihdam Bilgileri -->
                <div class="card mb-3">
                    <div class="card-header">İstihdam Bilgileri</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>İstihdam Tipi</label>
                                <input type="text" name="istihdam_tipi" class="form-control" 
                                       value="<?= $gorev['istihdam_tipi'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Hizmet Sınıfı</label>
                                <input type="text" name="hizmet_sinifi" class="form-control" 
                                       value="<?= $gorev['hizmet_sinifi'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Kadro Ünvanı</label>
                                <input type="text" name="kadro_unvani" class="form-control" 
                                       value="<?= $gorev['kadro_unvani'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Görev Ünvanı</label>
                                <input type="text" name="gorev_unvani" class="form-control" 
                                       value="<?= $gorev['gorev_unvani'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Kariyer Basamağı</label>
                                <input type="text" name="kariyer_basamagi" class="form-control" 
                                       value="<?= $gorev['kariyer_basamagi'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Görev Detayları -->
                <div class="card mb-3">
                    <div class="card-header">Görev Detayları</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Atama Çeşidi</label>
                                <input type="text" name="atama_cesidi" class="form-control" 
                                       value="<?= $gorev['atama_cesidi'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Yer Değiştirme Çeşidi</label>
                                <input type="text" name="yer_degistirme_cesidi" class="form-control" 
                                       value="<?= $gorev['yer_degistirme_cesidi'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kapalı Kurum -->
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="gorev_kapali_kurum" class="form-check-input" value="1"
                               <?= $gorev['gorev_kapali_kurum'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Kapalı Kurum</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">Kaydet</button>
                <a href="gorev_kaydi.php?tc_search=<?= urlencode($personel['tc_no']) ?>" class="btn btn-secondary">Geri</a>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>