<?php
/**
 * MEB'DEN AYRILMA İŞLEMİ - Personel Takip Sistemi - meb_ayrilma.php
 * @version 2.4
 * @author Fatih
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();
SessionManager::requireAuth();
$username = SessionManager::getUsername();

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
$ayrilma_listesi = [];
$duzenlenecek_ayrilma = null;
$son_gorev_bilgisi = null;
$ayrilma_nedenleri = [];
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

$pageTitle = 'MEB\'den Ayrılma İşlemi';
$pageIcon = 'person-arms-up';
$welcomeMessage = 'Buradan Personelin MEB\'den Ayrılma İşlemlerini yönetebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/meb_ayrilma_content.php';

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
} else {
    SessionManager::setMessage('error', 'TC No ile eşleşen personel bulunamadı!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}

// AYRILMA NEDENLERİNİ ÇEK
$ayrilma_nedenleri = $database->fetchAll("SELECT id, neden_adi FROM ayrilma_nedeni WHERE aktif = 1 ORDER BY sira ASC, neden_adi ASC");

// SON GÖREV BİLGİSİNİ ÇEK (personel_gorev tablosundan - DOĞRU ALAN ADLARI)
if ($personel_id > 0) {
    try {
        // Önce bitiş tarihi boş olan (devam eden) görevi dene
        $son_gorev_sql = "SELECT * FROM personel_gorev 
                          WHERE personel_id = ? 
                          AND (bitis_tarihi IS NULL OR bitis_tarihi = '0000-00-00' OR bitis_tarihi = '')
                          ORDER BY kurum_baslama_tarihi DESC, id DESC 
                          LIMIT 1";
        $stmt = $db->prepare($son_gorev_sql);
        $stmt->execute([$personel_id]);
        $son_gorev_bilgisi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Devam eden görev yoksa, en son başlama tarihli görevi al
        if (!$son_gorev_bilgisi) {
            $son_gorev_sql = "SELECT * FROM personel_gorev 
                              WHERE personel_id = ? 
                              ORDER BY kurum_baslama_tarihi DESC, id DESC 
                              LIMIT 1";
            $stmt = $db->prepare($son_gorev_sql);
            $stmt->execute([$personel_id]);
            $son_gorev_bilgisi = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($son_gorev_bilgisi) {
            error_log("Son görev bulundu: ID=" . ($son_gorev_bilgisi['id'] ?? '') . 
                      ", Görev Yeri=" . ($son_gorev_bilgisi['gorev_okul_adi'] ?? '') . 
                      ", Başlama=" . ($son_gorev_bilgisi['kurum_baslama_tarihi'] ?? ''));
        } else {
            error_log("Son görev bulunamadı - personel_id: " . $personel_id);
        }
        
    } catch (PDOException $e) {
        error_log("Son görev bilgisi hatası: " . $e->getMessage());
    }
}

// Düzenlenecek kayıt
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_ayrilma = $database->fetch("SELECT * FROM personel_ayrilma WHERE id = ?", [$duzenle_id]);
}

// Ayrılma listesini çek (Ayrılma nedeni adı ile birlikte)
if ($personel_id > 0) {
    try {
        $ayrilma_sql = "SELECT a.*, n.neden_adi 
                        FROM personel_ayrilma a
                        LEFT JOIN ayrilma_nedeni n ON a.ayrilma_nedeni_id = n.id
                        WHERE a.personel_id = ? 
                        ORDER BY a.id DESC";
        $stmt = $db->prepare($ayrilma_sql);
        $stmt->execute([$personel_id]);
        $ayrilma_listesi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_ayrilma'])) {
    try {
        $ayrilma_tarihi = !empty($_POST['ayrilma_tarihi']) ? $_POST['ayrilma_tarihi'] : null;
        
        $data = [
            'personel_id' => $personel_id,
            'son_gorev_yeri' => $_POST['son_gorev_yeri'] ?? null,
            'baslama_tarihi' => !empty($_POST['baslama_tarihi']) ? $_POST['baslama_tarihi'] : null,
            'gorev_unvani' => $_POST['gorev_unvani'] ?? null,
            'ayrilma_tarihi' => $ayrilma_tarihi,
            'ayrilma_nedeni_id' => !empty($_POST['ayrilma_nedeni_id']) ? (int)$_POST['ayrilma_nedeni_id'] : null,
            'ayrilma_aciklama' => $_POST['ayrilma_aciklama'] ?? null,
            'onay_tarihi' => !empty($_POST['onay_tarihi']) ? $_POST['onay_tarihi'] : null,
            'onay_sayisi' => $_POST['onay_sayisi'] ?? null
        ];

        $existing_id = !empty($_POST['ayrilma_id']) ? (int)$_POST['ayrilma_id'] : null;

        // AYRILMA TARİHİNİ GÖREV KAYDINA GÜNCELLE (personel_gorev tablosundaki bitis_tarihi alanı)
        if ($ayrilma_tarihi && $personel_id > 0) {
            try {
                // Önce devam eden görevi bul (bitis_tarihi boş olan)
                $devam_eden_gorev_sql = "SELECT id FROM personel_gorev 
                                         WHERE personel_id = ? 
                                         AND (bitis_tarihi IS NULL OR bitis_tarihi = '0000-00-00' OR bitis_tarihi = '')
                                         ORDER BY kurum_baslama_tarihi DESC, id DESC 
                                         LIMIT 1";
                $stmt = $db->prepare($devam_eden_gorev_sql);
                $stmt->execute([$personel_id]);
                $devam_eden_gorev = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($devam_eden_gorev) {
                    // Devam eden görevin bitiş tarihini güncelle
                    $update_sql = "UPDATE personel_gorev SET bitis_tarihi = :bitis_tarihi WHERE id = :id";
                    $update_stmt = $db->prepare($update_sql);
                    $update_stmt->execute([
                        'bitis_tarihi' => $ayrilma_tarihi,
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
                    
                    if ($son_gorev) {
                        $update_sql = "UPDATE personel_gorev SET bitis_tarihi = :bitis_tarihi WHERE id = :id";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([
                            'bitis_tarihi' => $ayrilma_tarihi,
                            'id' => $son_gorev['id']
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("Görev bitiş tarihi güncelleme hatası: " . $e->getMessage());
            }
        }

        if ($existing_id) {
            $sql = "UPDATE personel_ayrilma SET 
                    son_gorev_yeri = :son_gorev_yeri,
                    baslama_tarihi = :baslama_tarihi,
                    gorev_unvani = :gorev_unvani,
                    ayrilma_tarihi = :ayrilma_tarihi,
                    ayrilma_nedeni_id = :ayrilma_nedeni_id,
                    ayrilma_aciklama = :ayrilma_aciklama,
                    onay_tarihi = :onay_tarihi,
                    onay_sayisi = :onay_sayisi,
                    guncelleme_tarihi = NOW()
                    WHERE id = :id AND personel_id = :personel_id";
            $data['id'] = $existing_id;
            $database->query($sql, $data);
            SessionManager::setMessage('success', 'Ayrılma kaydı güncellendi.');
        } else {
            $sql = "INSERT INTO personel_ayrilma (
                    personel_id, son_gorev_yeri, baslama_tarihi, gorev_unvani,
                    ayrilma_tarihi, ayrilma_nedeni_id, ayrilma_aciklama, onay_tarihi, onay_sayisi,
                    kayit_tarihi, guncelleme_tarihi) 
                    VALUES (
                    :personel_id, :son_gorev_yeri, :baslama_tarihi, :gorev_unvani,
                    :ayrilma_tarihi, :ayrilma_nedeni_id, :ayrilma_aciklama, :onay_tarihi, :onay_sayisi,
                    NOW(), NOW())";
            $database->query($sql, $data);
            SessionManager::setMessage('success', 'Ayrılma kaydı başarıyla eklendi.');
        }
        
        header("Location: meb_ayrilma.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: meb_ayrilma.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// SİLME İŞLEMİ
// =============================================================================
if (isset($_POST['sil_ayrilma']) && isset($_POST['sil_id'])) {
    try {
        $sil_id = (int)$_POST['sil_id'];
        
        // Önce silinecek ayrılma kaydının bilgilerini al (personel_id ve ayrilma_tarihi için)
        $silinecek_kayit = $database->fetch("SELECT * FROM personel_ayrilma WHERE id = ? AND personel_id = ?", [$sil_id, $personel_id]);
        
        if ($silinecek_kayit && !empty($silinecek_kayit['ayrilma_tarihi'])) {
            // Ayrılma tarihini temizle (görev kaydındaki bitis_tarihi alanını null yap)
            try {
                // Önce bu ayrılma tarihine sahip görevi bul
                $gorev_sql = "SELECT id FROM personel_gorev 
                              WHERE personel_id = ? 
                              AND bitis_tarihi = ? 
                              ORDER BY kurum_baslama_tarihi DESC, id DESC 
                              LIMIT 1";
                $stmt = $db->prepare($gorev_sql);
                $stmt->execute([$personel_id, $silinecek_kayit['ayrilma_tarihi']]);
                $gorev = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($gorev) {
                    // Görevin bitiş tarihini temizle
                    $update_sql = "UPDATE personel_gorev SET bitis_tarihi = NULL WHERE id = :id";
                    $update_stmt = $db->prepare($update_sql);
                    $update_stmt->execute(['id' => $gorev['id']]);
                    error_log("Görev bitiş tarihi temizlendi: ID=" . $gorev['id']);
                }
            } catch (Exception $e) {
                error_log("Görev bitiş tarihi temizleme hatası: " . $e->getMessage());
            }
        }
        
        // Ayrılma kaydını sil
        $database->query("DELETE FROM personel_ayrilma WHERE id = ? AND personel_id = ?", [$sil_id, $personel_id]);
        SessionManager::setMessage('success', 'Ayrılma kaydı silindi.');
        
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Silme hatası: ' . $e->getMessage());
    }
    header("Location: meb_ayrilma.php?tc_search=" . urlencode($tc));
    exit;
}

include 'head.php';
include 'header.php';
include 'sidebar.php';
include 'content.php';
?>


<script>
window.aktifTc = "<?= htmlspecialchars($tc) ?>";
window.personelId = <?= $personel_id ?>;
window.simpleToken = "<?= htmlspecialchars($simpleToken ?? '', ENT_QUOTES) ?>";
window.currentPage = "meb_ayrilma.php";

// Son görev bilgilerini JavaScript'e aktar (DOĞRU ALAN ADLARI)
<?php if ($son_gorev_bilgisi): ?>
window.sonGorevYeri = "<?= addslashes($son_gorev_bilgisi['gorev_okul_adi'] ?? '') ?>";
window.sonGorevBaslamaTarihi = "<?= $son_gorev_bilgisi['kurum_baslama_tarihi'] ?? '' ?>";
window.sonGorevUnvani = "<?= addslashes($son_gorev_bilgisi['gorev_unvani'] ?? '') ?>";
window.sonGorevKurumKodu = "<?= addslashes($son_gorev_bilgisi['gorev_kurum_kodu'] ?? '') ?>";
window.sonGorevIlAdi = "<?= addslashes($son_gorev_bilgisi['gorev_il_adi'] ?? '') ?>";
window.sonGorevIlceAdi = "<?= addslashes($son_gorev_bilgisi['gorev_ilce_adi'] ?? '') ?>";
<?php else: ?>
window.sonGorevYeri = "";
window.sonGorevBaslamaTarihi = "";
window.sonGorevUnvani = "";
window.sonGorevKurumKodu = "";
window.sonGorevIlAdi = "";
window.sonGorevIlceAdi = "";
<?php endif; ?>

// Debug için console log
console.log("=== Son görev bilgileri ===");
console.log("Yer: " + window.sonGorevYeri);
console.log("Başlama: " + window.sonGorevBaslamaTarihi);
console.log("Ünvan: " + window.sonGorevUnvani);
</script>

<?php include 'footer.php'; ?>