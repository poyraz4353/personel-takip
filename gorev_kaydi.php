<?php
/**
 * GÖREV KAYDI SİSTEMİ - Personel Takip Sistemi - gorev_kaydi.php
 * @version 3.0
 * @author Fatih
 */

// =============================================================================
// SESSION YÖNETİMİ
// =============================================================================
require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();

// =============================================================================
// VERİTABANI BAĞLANTISI
// =============================================================================
require_once __DIR__ . '/config/db_config.php';
$database = Database::getInstance();
$db = $database->getConnection();

// =============================================================================
// CSRF KORUMASI
// =============================================================================
function generateSimpleToken() {
    if (!isset($_SESSION['simple_token'])) {
        $_SESSION['simple_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['simple_token'];
}

$simpleToken = generateSimpleToken();

// =============================================================================
// DEĞİŞKEN TANIMLAMALARI
// =============================================================================
$personel = null;
$gorevler = [];
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;
$duzenlenecek_gorev = null;

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$pageTitle = 'Görev Kaydı';
$pageIcon = 'briefcase';
$welcomeMessage = 'Buradan Personel Görev Kayıtlarını yönetebilir, düzenleyebilirsiniz.';
$content = 'includes/gorev_kaydi_content.php';

// =============================================================================
// ERİŞİM KONTROLÜ
// =============================================================================
if (empty($tc)) {
    SessionManager::setMessage('error', 'Lütfen geçerli bir TC Kimlik No ile arama yapınız!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}
if (strlen($tc) !== 11 || !ctype_digit($tc)) {
    SessionManager::setMessage('error', 'Geçerli bir TC Kimlik No giriniz (11 haneli rakam)');
    header("Location: dashboard_Anasayfa.php");
    exit;
}
SessionManager::setAktifTC($tc);

// =============================================================================
// PERSONEL BULMA
// =============================================================================
function findPersonel($db, $search_by, $value) {
    try {
        $sql = $search_by === 'tc' 
            ? "SELECT * FROM personel WHERE tc_no = ?"
            : "SELECT * FROM personel WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Personel arama hatası: " . $e->getMessage());
        return null;
    }
}

$personel = findPersonel($db, 'tc', $tc);
if ($personel) {
    $personel_id = (int)$personel['id'];
    SessionManager::setAktifPersonelID($personel_id);
    SessionManager::addToSearchHistory($tc, $personel['ad_soyadi']);
} else {
    SessionManager::setMessage('error', 'TC No ile eşleşen personel bulunamadı!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}

// =============================================================================
// DÜZENLENECEK KAYDI ÇEK
// =============================================================================
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_gorev = $database->fetch("SELECT * FROM personel_gorev WHERE id = ?", [$duzenle_id]);
    
    // HİZMET SINIFI ID'sini EKLE (bu satır EKSİK)
    if (!empty($duzenlenecek_gorev['hizmet_sinifi'])) {
        $hizmetSinif = $database->fetch("SELECT id FROM hizmet_siniflari WHERE sinif_adi = ?", [$duzenlenecek_gorev['hizmet_sinifi']]);
        if ($hizmetSinif) {
            $duzenlenecek_gorev['hizmet_sinifi_id'] = $hizmetSinif['id'];
        } else {
            $duzenlenecek_gorev['hizmet_sinifi_id'] = '';
        }
    } else {
        $duzenlenecek_gorev['hizmet_sinifi_id'] = '';
    }
    
    // KADRO ÜNVANI ID'sini METNE ÇEVİR
    if (!empty($duzenlenecek_gorev['kadro_unvani']) && is_numeric($duzenlenecek_gorev['kadro_unvani'])) {
        $kadro = $database->fetch("SELECT unvan_adi FROM kadro_unvanlari WHERE id = ?", [$duzenlenecek_gorev['kadro_unvani']]);
        if ($kadro) {
            $duzenlenecek_gorev['kadro_unvani'] = $kadro['unvan_adi'];
        }
    }
    
    // GÖREV ÜNVANI ID'sini METNE ÇEVİR
    if (!empty($duzenlenecek_gorev['gorev_unvani']) && is_numeric($duzenlenecek_gorev['gorev_unvani'])) {
        $gorev = $database->fetch("SELECT unvan_adi FROM gorev_unvanlari WHERE id = ?", [$duzenlenecek_gorev['gorev_unvani']]);
        if ($gorev) {
            $duzenlenecek_gorev['gorev_unvani'] = $gorev['unvan_adi'];
        }
    }
}

// =============================================================================
// İLÇELERİ VE OKULLARI ÇEK (Düzenleme için)
// =============================================================================
$ilceler = [];
$okullar = [];

if (isset($duzenlenecek_gorev) && !empty($duzenlenecek_gorev['gorev_il_adi'])) {
    $il = $database->fetch("SELECT id FROM iller WHERE il_adi = ?", [$duzenlenecek_gorev['gorev_il_adi']]);
    if ($il) {
        $ilceler = $database->fetchAll("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi", [$il['id']]);
    }
}

if (isset($duzenlenecek_gorev) && !empty($duzenlenecek_gorev['gorev_ilce_adi']) && !empty($duzenlenecek_gorev['gorev_il_adi'])) {
    $il = $database->fetch("SELECT id FROM iller WHERE il_adi = ?", [$duzenlenecek_gorev['gorev_il_adi']]);
    if ($il) {
        $ilce = $database->fetch("SELECT id FROM ilceler WHERE ilce_adi = ? AND il_id = ?", [
            $duzenlenecek_gorev['gorev_ilce_adi'], 
            $il['id']
        ]);
        if ($ilce) {
            $okullar = $database->fetchAll("SELECT kurum_kodu, gorev_yeri FROM okullar WHERE ilce_id = ? ORDER BY gorev_yeri", [$ilce['id']]);
        }
    }
}

// =============================================================================
// GÖREV BİLGİLERİNİ ÇEKME (JOIN ile ünvan adlarını al)
// =============================================================================
if ($personel_id > 0) {
    try {
        $gorev_sql = "SELECT 
                        g.*,
                        ku.unvan_adi as kadro_unvani_adi,
                        gu.unvan_adi as gorev_unvani_adi,
                        hs.sinif_adi as hizmet_sinifi_adi
                      FROM personel_gorev g
                      LEFT JOIN kadro_unvanlari ku ON g.kadro_unvani = ku.id
                      LEFT JOIN gorev_unvanlari gu ON g.gorev_unvani = gu.id
                      LEFT JOIN hizmet_siniflari hs ON g.hizmet_sinifi = hs.sinif_adi
                      WHERE g.personel_id = ? 
                      ORDER BY g.id ASC";
        $stmt = $db->prepare($gorev_sql);
        $stmt->execute([$personel_id]);
        $gorevler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// GÖREV KAYDI KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_gorev'])) {
    
    try {
        // İl ve İlçe adlarını ID'den al
        $il_adi = null;
        $ilce_adi = null;
        
        if (!empty($_POST['il_id'])) {
            $il = $database->fetch("SELECT il_adi FROM iller WHERE id = ?", [$_POST['il_id']]);
            $il_adi = $il['il_adi'] ?? null;
        }
        
        if (!empty($_POST['ilce_id'])) {
            $ilce = $database->fetch("SELECT ilce_adi FROM ilceler WHERE id = ?", [$_POST['ilce_id']]);
            $ilce_adi = $ilce['ilce_adi'] ?? null;
        }
        
        $kurum_kodu = $_POST['gorev_kurum_kodu'] ?? null;
        $gorev_okul_adi = null;
        $gorev_okul_tur = null;

        if ($kurum_kodu) {
            $okul_res = $database->fetch("SELECT gorev_yeri, okul_tur FROM okullar WHERE kurum_kodu = ?", [$kurum_kodu]);
            $gorev_okul_adi = $okul_res['gorev_yeri'] ?? null;
            $gorev_okul_tur = $okul_res['okul_tur'] ?? null;
        }

        // Hizmet sınıfı ID'sinden adını al
        $hizmet_sinifi_adi = null;
        if (!empty($_POST['hizmet_sinifi'])) {
            $hs = $database->fetch("SELECT sinif_adi FROM hizmet_siniflari WHERE id = ?", [$_POST['hizmet_sinifi']]);
            $hizmet_sinifi_adi = $hs['sinif_adi'] ?? null;
        }

        $gorev_data = [
            'personel_id'           => $personel_id,
            'gorev_il_adi'          => $il_adi,
            'gorev_ilce_adi'        => $ilce_adi,
            'gorev_okul_adi'        => $gorev_okul_adi,
            'gorev_okul_tur'        => $gorev_okul_tur,
            'gorev_kurum_kodu'      => $kurum_kodu,
            'kurum_baslama_tarihi'  => !empty($_POST['kurum_baslama_tarihi']) ? $_POST['kurum_baslama_tarihi'] : null,
            'bitis_tarihi'          => !empty($_POST['gorev_ayrilma_tarihi']) ? $_POST['gorev_ayrilma_tarihi'] : null,
            'hizmet_sinifi'         => $hizmet_sinifi_adi,
            'istihdam_tipi'         => $_POST['istihdam_tipi'] ?? null,
            'kadro_unvani'          => $_POST['kadro_unvani'] ?? '',
            'gorev_unvani'          => $_POST['gorev_unvani'] ?? '',
            'kariyer_basamagi'      => $_POST['kariyer_basamagi'] ?? null,
            'atama_alani'           => $_POST['atama_alani'] ?? null,
            'atama_cesidi'          => $_POST['atama_cesidi'] ?? null,
            'durum'                 => $_POST['durum'] ?? null,
            'yer_degistirme_cesidi' => $_POST['yer_degistirme_cesidi'] ?? null,
            'gorev_kapali_kurum'    => isset($_POST['kapali_kurum']) ? 1 : 0
        ];

        $existing_id = !empty($_POST['gorev_id']) ? (int)$_POST['gorev_id'] : null;

        if ($existing_id) {
            $gorev_data['id'] = $existing_id;
            $sql = "UPDATE personel_gorev SET 
                    gorev_il_adi = :gorev_il_adi, 
                    gorev_ilce_adi = :gorev_ilce_adi, 
                    gorev_okul_adi = :gorev_okul_adi,
                    gorev_okul_tur = :gorev_okul_tur,
                    gorev_kurum_kodu = :gorev_kurum_kodu, 
                    kurum_baslama_tarihi = :kurum_baslama_tarihi,
                    bitis_tarihi = :bitis_tarihi, 
                    hizmet_sinifi = :hizmet_sinifi, 
                    istihdam_tipi = :istihdam_tipi,
                    kadro_unvani = :kadro_unvani, 
                    gorev_unvani = :gorev_unvani, 
                    kariyer_basamagi = :kariyer_basamagi,
                    atama_alani = :atama_alani,
                    atama_cesidi = :atama_cesidi,
                    durum = :durum,
                    yer_degistirme_cesidi = :yer_degistirme_cesidi,
                    gorev_kapali_kurum = :gorev_kapali_kurum, 
                    guncelleme_tarihi = NOW()
                    WHERE id = :id AND personel_id = :personel_id";
            $database->query($sql, $gorev_data);
            SessionManager::setMessage('success', 'Görev kaydı güncellendi.');
        } else {
            $sql = "INSERT INTO personel_gorev (
                    personel_id, gorev_il_adi, gorev_ilce_adi, gorev_okul_adi, gorev_okul_tur,
                    gorev_kurum_kodu, kurum_baslama_tarihi, bitis_tarihi, hizmet_sinifi,
                    istihdam_tipi, kadro_unvani, gorev_unvani, kariyer_basamagi,
                    atama_alani, atama_cesidi, durum, yer_degistirme_cesidi,
                    gorev_kapali_kurum, kayit_tarihi, guncelleme_tarihi) 
                    VALUES (
                    :personel_id, :gorev_il_adi, :gorev_ilce_adi, :gorev_okul_adi, :gorev_okul_tur,
                    :gorev_kurum_kodu, :kurum_baslama_tarihi, :bitis_tarihi, :hizmet_sinifi,
                    :istihdam_tipi, :kadro_unvani, :gorev_unvani, :kariyer_basamagi,
                    :atama_alani, :atama_cesidi, :durum, :yer_degistirme_cesidi,
                    :gorev_kapali_kurum, NOW(), NOW())";
            $database->query($sql, $gorev_data);
            SessionManager::setMessage('success', 'Yeni görev kaydı başarıyla eklendi.');
        }
        
        header("Location: gorev_kaydi.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: gorev_kaydi.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// TÜM LİSTE VERİLERİNİ ÇEK
// =============================================================================
$iller = $database->fetchAll("SELECT id, il_adi FROM iller ORDER BY il_adi");
$hizmet_siniflari = $database->fetchAll("SELECT id, sinif_adi FROM hizmet_siniflari WHERE durum = 1 ORDER BY sinif_adi");
$kadro_unvanlari = $database->fetchAll("SELECT id, unvan_adi FROM kadro_unvanlari WHERE durum = 1 ORDER BY unvan_adi");
$gorev_unvanlari = $database->fetchAll("SELECT id, unvan_adi FROM gorev_unvanlari WHERE durum = 1 ORDER BY unvan_adi");
$atama_cesitleri = $database->fetchAll("SELECT id, atama_cesidi FROM atama_cesidi WHERE durum = 1 ORDER BY atama_cesidi");
$yer_degistirme_cesitleri = $database->fetchAll("SELECT id, yer_degistirme_cesidi FROM yer_degistirme_cesidi WHERE durum = 1 ORDER BY yer_degistirme_cesidi");
$durumlar = $database->fetchAll("SELECT id, durum_adi FROM durumu WHERE aktif = 1 ORDER BY durum_adi");

$istihdam_tipleri = [
    'Kadrolu',
    'Sözleşmeli Personel',
    'Geçici Personel',
    'İşçi',
    'Kurumlararası Görevlendirme(Bakanlık)',
    'Kurumlararası Görevlendirme(Valilik)',
    'Görevlendirme',
    'İşçi (696 K.H.K.)'
];

// =============================================================================
// SAYFA YÜKLEME
// =============================================================================
include 'head.php';
include 'header.php';
include 'sidebar.php';
include 'content.php';
?>

<div class="feedback-container">
    <?php
    $alertConfigs = ['success' => 'check-circle', 'danger' => 'exclamation-triangle', 'warning' => 'exclamation-circle', 'info' => 'info-circle'];
    foreach ($alertConfigs as $type => $icon) {
        $msg = SessionManager::getMessage($type);
        if ($msg) {
            echo '<div class="modern-alert modern-alert-'.$type.'">
                    <div class="alert-content">
                        <i class="bi bi-'.$icon.' alert-icon"></i>
                        <div class="flex-grow-1">'.htmlspecialchars($msg).'</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  </div>';
            SessionManager::clearMessage($type);
        }
    }
    ?>
</div>

<script>
window.aktifTc = "<?= htmlspecialchars($tc) ?>";
window.personelId = <?= $personel_id ?>;
window.simpleToken = "<?= htmlspecialchars($simpleToken ?? '', ENT_QUOTES) ?>";
window.currentPage = "gorev_kaydi.php";
</script>

<?php include 'footer.php'; ?>