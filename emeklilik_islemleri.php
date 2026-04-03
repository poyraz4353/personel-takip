<?php
/**
 * EMEKLİLİK İŞLEMLERİ - Personel Takip Sistemi - emeklilik_islemleri.php
 * @version 2.0
 * @author Fatih
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();
$user_id = SessionManager::getUserId();

require_once __DIR__ . '/config/db_config.php';
$database = Database::getInstance();
$db = $database->getConnection();

// CSRF KORUMASI
function generateSimpleToken() {
    if (!isset($_SESSION['simple_token'])) {
        $_SESSION['simple_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['simple_token'];
}

$simpleToken = generateSimpleToken();

$personel = null;
$emekli_listesi = [];
$duzenlenecek_emekli = null;
$son_gorev_bilgisi = null;
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

$pageTitle = 'Emeklilik İşlemleri';
$pageIcon = 'bank';
$welcomeMessage = 'Buradan Personelin Emeklilik İşlemlerini yönetebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/emeklilik_islemleri_content.php';

// TC kontrolü
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

// Personel bul
function findPersonel($db, $search_by, $value) {
    try {
        $sql = $search_by === 'tc' ? "SELECT * FROM personel WHERE tc_no = ?" : "SELECT * FROM personel WHERE id = ?";
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
    
	// =========================================================================
	// SON GÖREV BİLGİSİNİ ÇEK (Devam eden görev)
	// =========================================================================
	$son_gorev_sql = "SELECT pg.* 
					  FROM personel_gorev pg
					  WHERE pg.personel_id = ? 
					  AND (pg.bitis_tarihi IS NULL OR pg.bitis_tarihi = '0000-00-00' OR pg.bitis_tarihi = '')
					  ORDER BY pg.kurum_baslama_tarihi DESC LIMIT 1";
	$stmt = $db->prepare($son_gorev_sql);
	$stmt->execute([$personel_id]);
	$son_gorev_bilgisi = $stmt->fetch(PDO::FETCH_ASSOC);

	// Devam eden görev yoksa, en son görevi al
	if (!$son_gorev_bilgisi) {
		$son_gorev_sql = "SELECT pg.* 
						  FROM personel_gorev pg
						  WHERE pg.personel_id = ? 
						  ORDER BY pg.kurum_baslama_tarihi DESC LIMIT 1";
		$stmt = $db->prepare($son_gorev_sql);
		$stmt->execute([$personel_id]);
		$son_gorev_bilgisi = $stmt->fetch(PDO::FETCH_ASSOC);
	}
		
} else {
    SessionManager::setMessage('error', 'TC No ile eşleşen personel bulunamadı!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}

// Emeklilik türleri (MEBİS uyumlu)
// Emeklilik türleri (MEBİS uyumlu)
$emeklilik_turleri = [
    'Kendi İsteğiyle',
    'Malulen',
    'Yaş Haddinden',
    'Re\'sen',  // Kesme işareti için kaçış karakteri
    'İstekle 61 Yaş Haddi',
    '5434 SK 39/j Sakatlık',
    '5510 SK Görev Sakatlık',
    '5510 SK Geçici 4.Md. Göre Sakatlık',
    '5510 SK Geçici 4.Md.',
    'Emeklilik (Cumhurbaşkanlığı 24 Numaralı Kararnamesi Gereği Göreve Devam Eden)'
];
// Düzenlenecek kayıt
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_emekli = $database->fetch("SELECT * FROM personel_emekli WHERE id = ?", [$duzenle_id]);
}

// Emeklilik listesini çek
if ($personel_id > 0) {
    try {
        $emekli_sql = "SELECT * FROM personel_emekli WHERE personel_id = ? ORDER BY emeklilik_tarihi ASC, id ASC";
        $stmt = $db->prepare($emekli_sql);
        $stmt->execute([$personel_id]);
        $emekli_listesi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_emekli'])) {
    
    // Zorunlu alan kontrolleri
    $errors = [];
    
    if (empty($_POST['emeklilik_basvuru_tarihi'])) {
        $errors[] = 'Emeklilik başvuru tarihi zorunludur.';
    }
    if (empty($_POST['emeklilik_tarihi'])) {
        $errors[] = 'Emeklilik tarihi zorunludur.';
    }
    if (empty($_POST['emeklilik_tipi'])) {
        $errors[] = 'Emeklilik türü zorunludur.';
    }
    if (empty($_POST['onay_tarihi'])) {
        $errors[] = 'Onay tarihi zorunludur.';
    }
    if (empty($_POST['onay_sayisi'])) {
        $errors[] = 'Onay sayısı zorunludur.';
    }
    
    if (!empty($errors)) {
        $error_msg = implode('<br>', $errors);
        SessionManager::setMessage('danger', $error_msg);
        header("Location: emeklilik_islemleri.php?tc_search=" . urlencode($tc));
        exit;
    }
        
    try {
        $db->beginTransaction();
        
        $data = [
            'personel_id' => $personel_id,
            'son_gorev_yeri' => $_POST['son_gorev_yeri'] ?? null,
            'gorev_unvani' => $_POST['gorev_unvani'] ?? null,
            'gorev_baslama_tarihi' => !empty($_POST['gorev_baslama_tarihi']) ? $_POST['gorev_baslama_tarihi'] : null,
            'emeklilik_basvuru_tarihi' => !empty($_POST['emeklilik_basvuru_tarihi']) ? $_POST['emeklilik_basvuru_tarihi'] : null,
            'emeklilik_tarihi' => !empty($_POST['emeklilik_tarihi']) ? $_POST['emeklilik_tarihi'] : null,
            'emeklilik_tipi' => $_POST['emeklilik_tipi'] ?? null,
            'aciklama' => $_POST['aciklama'] ?? null,
            'ev_adresi' => $_POST['ev_adresi'] ?? null,
            'onay_tarihi' => !empty($_POST['onay_tarihi']) ? $_POST['onay_tarihi'] : null,
            'onay_sayisi' => $_POST['onay_sayisi'] ?? null,
            'emekli_maas' => !empty($_POST['emekli_maas']) ? (float)$_POST['emekli_maas'] : null,
            'maas_baglanma_tarihi' => !empty($_POST['maas_baglanma_tarihi']) ? $_POST['maas_baglanma_tarihi'] : null,
            'ikramiye_bilgisi' => $_POST['ikramiye_bilgisi'] ?? null
        ];

        $existing_id = !empty($_POST['emekli_id']) ? (int)$_POST['emekli_id'] : null;

        // =========================================================================
        // EMEKLİLİK TARİHİNİ GÖREV KAYDINA İŞLE
        // =========================================================================
        $emeklilik_tarihi = $data['emeklilik_tarihi'];
        if ($emeklilik_tarihi && $personel_id > 0) {
            try {
                // Devam eden görevi bul (bitis_tarihi boş olan)
                $devam_eden_gorev_sql = "SELECT id FROM personel_gorev 
                                         WHERE personel_id = ? 
                                         AND (bitis_tarihi IS NULL OR bitis_tarihi = '0000-00-00' OR bitis_tarihi = '')
                                         ORDER BY kurum_baslama_tarihi DESC, id DESC 
                                         LIMIT 1";
                $stmt = $db->prepare($devam_eden_gorev_sql);
                $stmt->execute([$personel_id]);
                $devam_eden_gorev = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($devam_eden_gorev) {
                    // Devam eden görevin bitiş tarihini emeklilik tarihi olarak güncelle
                    $update_sql = "UPDATE personel_gorev SET bitis_tarihi = :bitis_tarihi WHERE id = :id";
                    $update_stmt = $db->prepare($update_sql);
                    $update_stmt->execute([
                        'bitis_tarihi' => $emeklilik_tarihi,
                        'id' => $devam_eden_gorev['id']
                    ]);
                } else {
                    // Devam eden görev yoksa, en son görevin bitiş tarihini güncelle
                    $son_gorev_sql = "SELECT id FROM personel_gorev 
                                      WHERE personel_id = ? 
                                      ORDER BY kurum_baslama_tarihi DESC, id DESC 
                                      LIMIT 1";
                    $stmt = $db->prepare($son_gorev_sql);
                    $stmt->execute([$personel_id]);
                    $son_gorev = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($son_gorev && empty($son_gorev['bitis_tarihi'])) {
                        $update_sql = "UPDATE personel_gorev SET bitis_tarihi = :bitis_tarihi WHERE id = :id";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([
                            'bitis_tarihi' => $emeklilik_tarihi,
                            'id' => $son_gorev['id']
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("Görev bitiş tarihi güncelleme hatası: " . $e->getMessage());
            }
        }

        if ($existing_id) {
            // GÜNCELLEME
            $sql = "UPDATE personel_emekli SET 
                    son_gorev_yeri = :son_gorev_yeri,
                    gorev_unvani = :gorev_unvani,
                    gorev_baslama_tarihi = :gorev_baslama_tarihi,
                    emeklilik_basvuru_tarihi = :emeklilik_basvuru_tarihi,
                    emeklilik_tarihi = :emeklilik_tarihi,
                    emeklilik_tipi = :emeklilik_tipi,
                    aciklama = :aciklama,
                    ev_adresi = :ev_adresi,
                    onay_tarihi = :onay_tarihi,
                    onay_sayisi = :onay_sayisi,
                    emekli_maas = :emekli_maas,
                    maas_baglanma_tarihi = :maas_baglanma_tarihi,
                    ikramiye_bilgisi = :ikramiye_bilgisi,
                    guncelleme_tarihi = NOW(),
                    guncelleyen_kullanici_id = :guncelleyen_kullanici_id
                    WHERE id = :id AND personel_id = :personel_id";
            
            $params = [
                ':son_gorev_yeri' => $data['son_gorev_yeri'],
                ':gorev_unvani' => $data['gorev_unvani'],
                ':gorev_baslama_tarihi' => $data['gorev_baslama_tarihi'],
                ':emeklilik_basvuru_tarihi' => $data['emeklilik_basvuru_tarihi'],
                ':emeklilik_tarihi' => $data['emeklilik_tarihi'],
                ':emeklilik_tipi' => $data['emeklilik_tipi'],
                ':aciklama' => $data['aciklama'],
                ':ev_adresi' => $data['ev_adresi'],
                ':onay_tarihi' => $data['onay_tarihi'],
                ':onay_sayisi' => $data['onay_sayisi'],
                ':emekli_maas' => $data['emekli_maas'],
                ':maas_baglanma_tarihi' => $data['maas_baglanma_tarihi'],
                ':ikramiye_bilgisi' => $data['ikramiye_bilgisi'],
                ':guncelleyen_kullanici_id' => $user_id,
                ':id' => $existing_id,
                ':personel_id' => $personel_id
            ];
            
            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Emeklilik kaydı güncellendi.');
            
        } else {
            // YENİ KAYIT
            $sql = "INSERT INTO personel_emekli (
                    personel_id, son_gorev_yeri, gorev_unvani, gorev_baslama_tarihi,
                    emeklilik_basvuru_tarihi, emeklilik_tarihi, emeklilik_tipi,
                    aciklama, ev_adresi, onay_tarihi, onay_sayisi, 
                    emekli_maas, maas_baglanma_tarihi, ikramiye_bilgisi,
                    kayit_tarihi, guncelleme_tarihi, ekleyen_kullanici_id, guncelleyen_kullanici_id) 
                    VALUES (
                    :personel_id, :son_gorev_yeri, :gorev_unvani, :gorev_baslama_tarihi,
                    :emeklilik_basvuru_tarihi, :emeklilik_tarihi, :emeklilik_tipi,
                    :aciklama, :ev_adresi, :onay_tarihi, :onay_sayisi,
                    :emekli_maas, :maas_baglanma_tarihi, :ikramiye_bilgisi,
                    NOW(), NOW(), :ekleyen_kullanici_id, :guncelleyen_kullanici_id)";

            $params = [
                ':personel_id' => $personel_id,
                ':son_gorev_yeri' => $data['son_gorev_yeri'],
                ':gorev_unvani' => $data['gorev_unvani'],
                ':gorev_baslama_tarihi' => $data['gorev_baslama_tarihi'],
                ':emeklilik_basvuru_tarihi' => $data['emeklilik_basvuru_tarihi'],
                ':emeklilik_tarihi' => $data['emeklilik_tarihi'],
                ':emeklilik_tipi' => $data['emeklilik_tipi'],
                ':aciklama' => $data['aciklama'],
                ':ev_adresi' => $data['ev_adresi'],
                ':onay_tarihi' => $data['onay_tarihi'],
                ':onay_sayisi' => $data['onay_sayisi'],
                ':emekli_maas' => $data['emekli_maas'],
                ':maas_baglanma_tarihi' => $data['maas_baglanma_tarihi'],
                ':ikramiye_bilgisi' => $data['ikramiye_bilgisi'],
                ':ekleyen_kullanici_id' => $user_id,
                ':guncelleyen_kullanici_id' => $user_id
            ];

            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Emeklilik kaydı başarıyla eklendi.');
        }
        
        // Personel tablosunu güncelle
        $database->query("UPDATE personel SET guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? WHERE id = ?", [$username, $personel_id]);
        
        $db->commit();
        header("Location: emeklilik_islemleri.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: emeklilik_islemleri.php?tc_search=" . urlencode($tc));
        exit;
    }
}

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
window.currentPage = "emeklilik_islemleri.php";

// Son görev bilgilerini JavaScript'e aktar
<?php if ($son_gorev_bilgisi): ?>
window.sonGorevYeri = "<?= addslashes($son_gorev_bilgisi['gorev_yeri_adi'] ?? $son_gorev_bilgisi['gorev_okul_adi'] ?? '') ?>";
window.sonGorevBaslamaTarihi = "<?= $son_gorev_bilgisi['kurum_baslama_tarihi'] ?? '' ?>";
window.sonGorevUnvani = "<?= addslashes($son_gorev_bilgisi['gorev_unvani'] ?? '') ?>";
<?php else: ?>
window.sonGorevYeri = "";
window.sonGorevBaslamaTarihi = "";
window.sonGorevUnvani = "";
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>