<?php
/**
 * KİMLİK BİLGİLERİ SİSTEMİ - Personel Takip Sistemi - kimlik_bilgileri.php
 * @version 2.8 (Database uyumlu - getConnection() düzeltildi)
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
// VERİTABANI BAĞLANTISI - DÜZELTİLDİ
// =============================================================================
require_once __DIR__ . '/config/db_config.php';
$database = Database::getInstance();
$db = $database->getConnection(); // Artık çalışır! (getConnection eklendi)

// =============================================================================
// CSRF KORUMASI
// =============================================================================
function generateSimpleToken() {
    if (!isset($_SESSION['simple_token'])) {
        $_SESSION['simple_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['simple_token'];
}

function validateSimpleToken($token) {
    if (isset($_SESSION['simple_token']) && $_SESSION['simple_token'] === $token) {
        unset($_SESSION['simple_token']);
        return true;
    }
    return false;
}

$simpleToken = generateSimpleToken();

// =============================================================================
// DEĞİŞKEN TANIMLAMALARI
// =============================================================================
$personel = null;
$search_error = '';
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

// =============================================================================
// SAYFA AYARLARI
// =============================================================================
$pageTitle = 'Kimlik Bilgileri';
$pageIcon = 'person-vcard';
$welcomeMessage = 'Buradan Personel Kimlik Bilgilerini yönetebilir, düzenleyebilirsiniz.';
$content = 'includes/kimlik_bilgileri_content.php';

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
// PERSONEL BULMA İŞLEMLERİ
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
    $personel_id = $personel['id'];
    SessionManager::setAktifPersonelID($personel_id);
    SessionManager::addToSearchHistory($tc, $personel['ad_soyadi']);
} else {
    SessionManager::setMessage('error', 'TC No ile eşleşen personel bulunamadı!');
    header("Location: dashboard_Anasayfa.php");
    exit;
}

// =============================================================================
// DETAYLI PERSONEL BİLGİSİ ÇEKME
// =============================================================================
if ($personel) {
    try {
        // Kimlik bilgileri - $database->fetch() kullan (daha güvenli)
        $kimlik_sql = "SELECT * FROM personel_kimlik WHERE personel_id = ?";
        $kimlik_bilgisi = $database->fetch($kimlik_sql, [$personel['id']]);
        if ($kimlik_bilgisi) $personel = array_merge($personel, $kimlik_bilgisi);

        // İletişim bilgileri
        $iletisim_sql = "SELECT * FROM personel_iletisim WHERE personel_id = ?";
        $iletisim_bilgisi = $database->fetch($iletisim_sql, [$personel['id']]);
        if ($iletisim_bilgisi) $personel = array_merge($personel, $iletisim_bilgisi);

        // Aile bilgileri
        $aile_sql = "SELECT * FROM personel_aile WHERE personel_id = ?";
        $aile_bilgisi = $database->fetch($aile_sql, [$personel['id']]);
        if ($aile_bilgisi) $personel = array_merge($personel, $aile_bilgisi);

        // Son görev bilgisi
        $gorev_sql = "SELECT * FROM personel_gorev WHERE personel_id = ? ORDER BY kayit_tarihi DESC LIMIT 1";
        $gorev_bilgisi = $database->fetch($gorev_sql, [$personel['id']]);
        if ($gorev_bilgisi) $personel = array_merge($personel, $gorev_bilgisi);

        // Son eğitim bilgisi
        $egitim_sql = "SELECT * FROM personel_ogrenim WHERE personel_id = ? ORDER BY mezuniyet_tarihi DESC LIMIT 1";
        $egitim_bilgisi = $database->fetch($egitim_sql, [$personel['id']]);
        if ($egitim_bilgisi) $personel = array_merge($personel, $egitim_bilgisi);

    } catch (Exception $e) {
        error_log("Detaylı bilgiler alınırken hata: " . $e->getMessage());
    }
}

// =============================================================================
// KİMLİK BİLGİLERİ KAYDETME/GÜNCELLEME - DÜZELTİLDİ
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_kimlik'])) {
    
    if (!validateSimpleToken($_POST['simple_token'] ?? '')) {
        SessionManager::setMessage('danger', 'Geçersiz güvenlik tokenı!');
        header("Location: kimlik_bilgileri.php?tc_search=" . urlencode($tc));
        exit;
    }
    
    try {
        $personel_id = $personel['id'] ?? null;
        
        if (!$personel_id) {
            throw new Exception("Personel ID bulunamadı");
        }

        // Mevcut kayıt kontrolü
        $existing_kimlik = $database->fetch(
            "SELECT id FROM personel_kimlik WHERE personel_id = ?", 
            [$personel_id]
        );

        if ($existing_kimlik) {
            // GÜNCELLEME - $database->query() ile
            $update_sql = "UPDATE personel_kimlik SET
                    baba_adi = ?, 
                    dogum_yeri = ?, 
                    dogum_tarihi = ?, 
                    medeni_durum = ?, 
                    kan_grubu = ?, 
                    cinsiyeti = ?,
                    guncelleme_tarihi = NOW()
                    WHERE personel_id = ?";
            
            $result = $database->query($update_sql, [
                !empty($_POST['baba_adi']) ? trim($_POST['baba_adi']) : null,
                !empty($_POST['dogum_yeri']) ? trim($_POST['dogum_yeri']) : null,
                !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null,
                !empty($_POST['medeni_durum']) ? trim($_POST['medeni_durum']) : null,
                !empty($_POST['kan_grubu']) ? trim($_POST['kan_grubu']) : null,
                !empty($_POST['cinsiyeti']) ? trim($_POST['cinsiyeti']) : null,
                $personel_id
            ]);
            
            if ($result && $result->rowCount() > 0) {
                SessionManager::setMessage('success', 'Kimlik bilgileri başarıyla güncellendi!');
            } else {
                SessionManager::setMessage('info', 'Veriler zaten güncel (değişiklik yapılmadı).');
            }
            
        } else {
            // YENİ KAYIT
            $insert_sql = "INSERT INTO personel_kimlik (
                personel_id, baba_adi, dogum_yeri, dogum_tarihi,
                medeni_durum, kan_grubu, cinsiyeti,
                kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = $database->query($insert_sql, [
                $personel_id,
                !empty($_POST['baba_adi']) ? trim($_POST['baba_adi']) : null,
                !empty($_POST['dogum_yeri']) ? trim($_POST['dogum_yeri']) : null,
                !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null,
                !empty($_POST['medeni_durum']) ? trim($_POST['medeni_durum']) : null,
                !empty($_POST['kan_grubu']) ? trim($_POST['kan_grubu']) : null,
                !empty($_POST['cinsiyeti']) ? trim($_POST['cinsiyeti']) : null
            ]);
            
            if ($result) {
                SessionManager::setMessage('success', 'Kimlik bilgileri başarıyla kaydedildi!');
            }
        }

        // Personel verilerini yeniden yükle
        $personel = findPersonel($db, 'tc', $tc);
        if ($personel) {
            $kimlik_bilgisi = $database->fetch(
                "SELECT * FROM personel_kimlik WHERE personel_id = ?", 
                [$personel_id]
            );
            if ($kimlik_bilgisi) {
                $personel = array_merge($personel, $kimlik_bilgisi);
            }
        }

        header("Location: kimlik_bilgileri.php?tc_search=" . urlencode($tc) . "&t=" . time());
        exit;

    } catch (Exception $e) {
        error_log("Kimlik kaydetme hatası: " . $e->getMessage());
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: kimlik_bilgileri.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// YARDIMCI FONKSİYONLAR
// =============================================================================
function getPersonelPhoto($photoPath) {
    if (empty($photoPath)) {
        return '<div class="no-photo-container">
            <i class="bi bi-person-circle no-photo-icon"></i>
            <p class="text-muted mt-2">Fotoğraf Yok</p>
        </div>';
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        return '<div class="alert alert-warning">Geçersiz fotoğraf formatı</div>';
    }

    $fullPath = 'uploads/personel_fotolar/' . basename($photoPath);

    if (!file_exists($fullPath)) {
        return '<div class="no-photo-container">
            <i class="bi bi-person-circle no-photo-icon"></i>
            <p class="text-muted mt-2">Fotoğraf Bulunamadı</p>
        </div>';
    }

    return '<img src="' . htmlspecialchars($fullPath) . '" class="personel-photo mb-3" alt="Personel Fotoğrafı">';
}

// Gün bilgisi
$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

// =============================================================================
// SAYFA YÜKLEME
// =============================================================================
include 'head.php';
include 'header.php';
include 'sidebar.php';
include 'content.php';
?>

<!-- FEEDBACK MESAJLARI -->
<div class="feedback-container">
    <?php
    $alertTypes = [
        'success' => ['icon' => 'check-circle-fill', 'class' => 'success', 'duration' => 6000],
        'danger' => ['icon' => 'exclamation-triangle-fill', 'class' => 'danger', 'duration' => 0],
        'warning' => ['icon' => 'exclamation-circle-fill', 'class' => 'warning', 'duration' => 0],
        'info' => ['icon' => 'info-circle-fill', 'class' => 'info', 'duration' => 0]
    ];
    
    foreach ($alertTypes as $type => $config) {
        $message = SessionManager::getMessage($type);
        
        if (!empty($message)) {
            $autoClose = $config['duration'] > 0 ? 'data-auto-close="' . $config['duration'] . '"' : '';
            echo '<div class="modern-alert modern-alert-' . $config['class'] . '" ' . $autoClose . '>';
            echo '<div class="alert-content">';
            echo '<i class="bi bi-' . $config['icon'] . ' alert-icon"></i>';
            echo '<div class="flex-grow-1">' . htmlspecialchars($message) . '</div>';
            echo '<button type="button" class="btn-close alert-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            echo '</div>';
        }
    }
    ?>
</div>

<!-- Global değişkenleri JavaScript'e aktar -->
<script>
window.aktifTc = "<?= htmlspecialchars($tc ?? '') ?>";
window.currentPage = "<?= basename($_SERVER['PHP_SELF']) ?>";
</script>

<?php include 'footer.php'; ?>