<?php
/**
 * PERSONEL ÖĞRENİM BİLGİLERİ - Personel Takip Sistemi - personel_ogrenim.php
 * @version 1.0
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
$ogrenim_listesi = [];
$duzenlenecek_ogrenim = null;
$tc = $_GET['tc_search'] ?? $_SESSION['aktif_tc'] ?? '';
$personel_id = 0;

$pageTitle = 'Öğrenim Bilgileri';
$pageIcon = 'book';
$welcomeMessage = 'Buradan Personelin Öğrenim Bilgilerini yönetebilirsiniz.';

$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceGun = $gunler[date('w')];

$content = 'includes/personel_ogrenim_content.php';

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

// =============================================================================
// LİSTE VERİLERİNİ ÇEK (GÜNCELLENMİŞ)
// =============================================================================
// İller (Okul seçimi için)
$iller = $database->fetchAll("SELECT id, il_adi FROM iller ORDER BY il_adi");

// Öğrenim durumları
try {
    $ogrenim_durumlari = $database->fetchAll("SELECT id, ogrenim_adi FROM ogrenim_durumlari WHERE aktif = 1 ORDER BY ogrenim_adi");
    if (!$ogrenim_durumlari) {
        $ogrenim_durumlari = [];
    }
} catch (Exception $e) {
    $ogrenim_durumlari = [];
    error_log("Öğrenim durumları sorgu hatası: " . $e->getMessage());
}

// Üniversiteler
$universiteler = $database->fetchAll("SELECT universite_id as id, universite_adi FROM universiteler ORDER BY universite_adi");

// Fakülte/Yüksekokul
$fakulteler = $database->fetchAll("SELECT fakulte_id as id, fakulte_adi, universite_id FROM fakulte_yuksekokul ORDER BY fakulte_adi");

// Program
$programlar = $database->fetchAll("SELECT program_id as id, program_adi, fakulte_yuksekokul_id FROM program ORDER BY program_adi");

// Anabilim Dalı
$anabilim_dallari = $database->fetchAll("SELECT anabilim_id as id, anabilim_adi, program_id FROM anabilim_dali ORDER BY anabilim_adi");

// Belge cinsleri
$belge_cinsleri = ['Diploma', 'Çıkış/Geçici Mezuniyet Belgesi', 'Denklik Belgesi', 'Tasdikname'];

// ========== GÜVENLİK KONTROLLERİ ==========
// Veriler boş veya hatalı gelirse boş diziye çevir
if (!$ogrenim_durumlari) $ogrenim_durumlari = [];
if (!$universiteler) $universiteler = [];
if (!$fakulteler) $fakulteler = [];
if (!$programlar) $programlar = [];
if (!$anabilim_dallari) $anabilim_dallari = [];
if (!$iller) $iller = [];

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

// Düzenlenecek kayıt
// Düzenlenecek kayıt
if (isset($_GET['duzenle_id']) && $_GET['duzenle_id'] > 0) {
    $duzenle_id = (int)$_GET['duzenle_id'];
    $duzenlenecek_ogrenim = $database->fetch("SELECT * FROM personel_ogrenim WHERE id = ?", [$duzenle_id]);
    
    if ($duzenlenecek_ogrenim) {
        // Fakülteleri çek (üniversite ID'sine göre)
        if (!empty($duzenlenecek_ogrenim['universite_id'])) {
            $fakulteler = $database->fetchAll(
                "SELECT fakulte_id as id, fakulte_adi, universite_id 
                 FROM fakulte_yuksekokul 
                 WHERE universite_id = ? 
                 ORDER BY fakulte_adi", 
                [$duzenlenecek_ogrenim['universite_id']]
            );
        }
        
        // Programları çek (fakülte ID'sine göre)
        if (!empty($duzenlenecek_ogrenim['fakulte_yuksekokul_id'])) {
            $programlar = $database->fetchAll(
                "SELECT program_id as id, program_adi, fakulte_yuksekokul_id 
                 FROM program 
                 WHERE fakulte_yuksekokul_id = ? 
                 ORDER BY program_adi", 
                [$duzenlenecek_ogrenim['fakulte_yuksekokul_id']]
            );
        }
        
        // Anabilim dallarını çek (program ID'sine göre)
        if (!empty($duzenlenecek_ogrenim['program_id'])) {
            $anabilim_dallari = $database->fetchAll(
                "SELECT anabilim_id as id, anabilim_adi, program_id 
                 FROM anabilim_dali 
                 WHERE program_id = ? 
                 ORDER BY anabilim_adi", 
                [$duzenlenecek_ogrenim['program_id']]
            );
        }
    }
}

// Düzenlenecek kayıt için il/ilçe/okul bilgilerini çek (mezun okul için)
$duzenlenecek_il = null;
$duzenlenecek_ilce = null;
$duzenlenecek_okul_adi = null;

if (isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim['mezun_okul_id']) && is_numeric($duzenlenecek_ogrenim['mezun_okul_id'])) {
    $okul = $database->fetch("SELECT id, gorev_yeri, ilce_id FROM okullar WHERE id = ?", [$duzenlenecek_ogrenim['mezun_okul_id']]);
    if ($okul) {
        $duzenlenecek_okul_adi = $okul['gorev_yeri'];
        $ilce = $database->fetch("SELECT id, ilce_adi, il_id FROM ilceler WHERE id = ?", [$okul['ilce_id']]);
        if ($ilce) {
            $duzenlenecek_ilce = $ilce;
            $il = $database->fetch("SELECT id, il_adi FROM iller WHERE id = ?", [$ilce['il_id']]);
            if ($il) {
                $duzenlenecek_il = $il;
            }
        }
    }
}

// Öğrenim listesini çek (JOIN ile) - Mezuniyet Tarihi'ne göre eskiden yeniye
if ($personel_id > 0) {
    try {
        $ogrenim_sql = "SELECT po.*, 
                        u.universite_adi,
                        f.fakulte_adi,
                        p.program_adi,
                        a.anabilim_adi,
                        o.gorev_yeri as mezun_okul_adi
                        FROM personel_ogrenim po
                        LEFT JOIN universiteler u ON po.universite_id = u.universite_id
                        LEFT JOIN fakulte_yuksekokul f ON po.fakulte_yuksekokul_id = f.fakulte_id
                        LEFT JOIN program p ON po.program_id = p.program_id
                        LEFT JOIN anabilim_dali a ON po.anabilim_dali_id = a.anabilim_id
                        LEFT JOIN okullar o ON po.mezun_okul_id = o.id
                        WHERE po.personel_id = ? 
                        ORDER BY po.mezuniyet_tarihi ASC, po.id ASC";
        $stmt = $db->prepare($ogrenim_sql);
        $stmt->execute([$personel_id]);
        $ogrenim_listesi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı Hatası: " . $e->getMessage());
    }
}

// =============================================================================
// KAYDETME/GÜNCELLEME
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_ogrenim'])) {
    
    try {
        $db->beginTransaction();
        
        // 1. Üniversite
        $universite_adi = trim($_POST['universite_id'] ?? '');
        $universite_id = null;
        
        if (!empty($universite_adi)) {
            $universite = $database->fetch("SELECT universite_id FROM universiteler WHERE universite_adi = ?", [$universite_adi]);
            if ($universite) {
                $universite_id = $universite['universite_id'];
            } else {
                $database->query("INSERT INTO universiteler (universite_adi) VALUES (?)", [$universite_adi]);
                $universite_id = $db->lastInsertId();
            }
        }
        
        // 2. Fakülte
        $fakulte_adi = trim($_POST['fakulte_yuksekokul_id'] ?? '');
        $fakulte_id = null;
        
        if (!empty($fakulte_adi) && $universite_id) {
            $fakulte = $database->fetch("SELECT fakulte_id FROM fakulte_yuksekokul WHERE fakulte_adi = ? AND universite_id = ?", [$fakulte_adi, $universite_id]);
            if ($fakulte) {
                $fakulte_id = $fakulte['fakulte_id'];
            } else {
                $database->query("INSERT INTO fakulte_yuksekokul (fakulte_adi, universite_id, fakulte_tipi) VALUES (?, ?, 'fakulte')", [$fakulte_adi, $universite_id]);
                $fakulte_id = $db->lastInsertId();
            }
        }
        
        // 3. Program
        $program_adi = trim($_POST['program_id'] ?? '');
        $program_id = null;
        
        if (!empty($program_adi) && $fakulte_id) {
            $program = $database->fetch("SELECT program_id FROM program WHERE program_adi = ? AND fakulte_yuksekokul_id = ?", [$program_adi, $fakulte_id]);
            if ($program) {
                $program_id = $program['program_id'];
            } else {
                $database->query("INSERT INTO program (program_adi, fakulte_yuksekokul_id) VALUES (?, ?)", [$program_adi, $fakulte_id]);
                $program_id = $db->lastInsertId();
            }
        }
        
        // 4. Anabilim Dalı
        $anabilim_adi = trim($_POST['anabilim_dali_id'] ?? '');
        $anabilim_id = null;
        
        if (!empty($anabilim_adi) && $program_id) {
            $anabilim = $database->fetch("SELECT anabilim_id FROM anabilim_dali WHERE anabilim_adi = ? AND program_id = ?", [$anabilim_adi, $program_id]);
            if ($anabilim) {
                $anabilim_id = $anabilim['anabilim_id'];
            } else {
                $database->query("INSERT INTO anabilim_dali (anabilim_adi, program_id) VALUES (?, ?)", [$anabilim_adi, $program_id]);
                $anabilim_id = $db->lastInsertId();
            }
        }
        
        // 5. Mezun Okul
        $mezun_okul = !empty($_POST['mezun_okul_id']) ? (int)$_POST['mezun_okul_id'] : null;
        
        // 6. Personel Öğrenim Kaydı
        $existing_id = !empty($_POST['ogrenim_id']) ? (int)$_POST['ogrenim_id'] : null;
        
        if ($existing_id) {
            // GÜNCELLEME
            $sql = "UPDATE personel_ogrenim SET 
                    ogrenim_durumu_id = :ogrenim_durumu_id,
                    mezun_okul_id = :mezun_okul_id,
                    mezuniyet_tarihi = :mezuniyet_tarihi,
                    universite_id = :universite_id,
                    universite_adi = :universite_adi,
                    fakulte_yuksekokul_id = :fakulte_yuksekokul_id,
                    fakulte_adi = :fakulte_adi,
                    program_id = :program_id,
                    program_adi = :program_adi,
                    anabilim_dali_id = :anabilim_dali_id,
                    anabilim_adi = :anabilim_adi,
                    belge_tarihi = :belge_tarihi,
                    belge_no = :belge_no,
                    belge_cinsi = :belge_cinsi,
                    belge_aciklama = :belge_aciklama,
                    guncelleme_tarihi = NOW()
                    WHERE id = :id AND personel_id = :personel_id";
            
            $params = [
                ':ogrenim_durumu_id' => $_POST['ogrenim_durumu_id'] ?? null,
                ':mezun_okul_id' => $mezun_okul,
                ':mezuniyet_tarihi' => !empty($_POST['mezuniyet_tarihi']) ? $_POST['mezuniyet_tarihi'] : null,
                ':universite_id' => $universite_id,
                ':universite_adi' => $universite_adi,
                ':fakulte_yuksekokul_id' => $fakulte_id,
                ':fakulte_adi' => $fakulte_adi,
                ':program_id' => $program_id,
                ':program_adi' => $program_adi,
                ':anabilim_dali_id' => $anabilim_id,
                ':anabilim_adi' => $anabilim_adi,
                ':belge_tarihi' => !empty($_POST['belge_tarihi']) ? $_POST['belge_tarihi'] : null,
                ':belge_no' => $_POST['belge_no'] ?? null,
                ':belge_cinsi' => $_POST['belge_cinsi'] ?? null,
                ':belge_aciklama' => $_POST['belge_aciklama'] ?? null,
                ':id' => $existing_id,
                ':personel_id' => $personel_id
            ];
            
            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Öğrenim bilgisi güncellendi.');
            
        } else {
            // YENİ KAYIT
            $sql = "INSERT INTO personel_ogrenim (
                    personel_id, ogrenim_durumu_id, mezun_okul_id, mezuniyet_tarihi,
                    universite_id, universite_adi, fakulte_yuksekokul_id, fakulte_adi,
                    program_id, program_adi, anabilim_dali_id, anabilim_adi,
                    belge_tarihi, belge_no, belge_cinsi, belge_aciklama,
                    kayit_tarihi, guncelleme_tarihi) 
                    VALUES (
                    :personel_id, :ogrenim_durumu_id, :mezun_okul_id, :mezuniyet_tarihi,
                    :universite_id, :universite_adi, :fakulte_yuksekokul_id, :fakulte_adi,
                    :program_id, :program_adi, :anabilim_dali_id, :anabilim_adi,
                    :belge_tarihi, :belge_no, :belge_cinsi, :belge_aciklama,
                    NOW(), NOW())";
            
            $params = [
                ':personel_id' => $personel_id,
                ':ogrenim_durumu_id' => $_POST['ogrenim_durumu_id'] ?? null,
                ':mezun_okul_id' => $mezun_okul,
                ':mezuniyet_tarihi' => !empty($_POST['mezuniyet_tarihi']) ? $_POST['mezuniyet_tarihi'] : null,
                ':universite_id' => $universite_id,
                ':universite_adi' => $universite_adi,
                ':fakulte_yuksekokul_id' => $fakulte_id,
                ':fakulte_adi' => $fakulte_adi,
                ':program_id' => $program_id,
                ':program_adi' => $program_adi,
                ':anabilim_dali_id' => $anabilim_id,
                ':anabilim_adi' => $anabilim_adi,
                ':belge_tarihi' => !empty($_POST['belge_tarihi']) ? $_POST['belge_tarihi'] : null,
                ':belge_no' => $_POST['belge_no'] ?? null,
                ':belge_cinsi' => $_POST['belge_cinsi'] ?? null,
                ':belge_aciklama' => $_POST['belge_aciklama'] ?? null
            ];
            
            $database->query($sql, $params);
            SessionManager::setMessage('success', 'Öğrenim bilgisi başarıyla eklendi.');
        }
        
        $db->commit();
        // Personel tablosunu güncelle (son güncelleyen kullanıcı)
        $database->query("UPDATE personel SET guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? WHERE id = ?", [$username, $personel_id]);
        // ================================
        
        SessionManager::setMessage('success', 'Öğrenim bilgisi başarıyla eklendi.');
        header("Location: personel_ogrenim.php?tc_search=" . urlencode($tc));
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        SessionManager::setMessage('danger', 'Hata: ' . $e->getMessage());
        header("Location: personel_ogrenim.php?tc_search=" . urlencode($tc));
        exit;
    }
}

// =============================================================================
// SİLME İŞLEMİ
// =============================================================================
if (isset($_POST['sil_ogrenim']) && isset($_POST['sil_id'])) {
    try {
        $sil_id = (int)$_POST['sil_id'];
        $database->query("DELETE FROM personel_ogrenim WHERE id = ? AND personel_id = ?", [$sil_id, $personel_id]);
        
        // Personel tablosunu güncelle (son güncelleyen kullanıcı)
        $database->query("UPDATE personel SET guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? WHERE id = ?", [$username, $personel_id]);
        
        SessionManager::setMessage('success', 'Öğrenim bilgisi silindi.');
    } catch (Exception $e) {
        SessionManager::setMessage('danger', 'Silme hatası: ' . $e->getMessage());
    }
    header("Location: personel_ogrenim.php?tc_search=" . urlencode($tc));
    exit;
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
window.currentPage = "personel_ogrenim.php";

// Düzenleme modunda öğrenim bilgilerini aktar
<?php if (isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim)): ?>
window.duzenlenecekOgrenim = {
    universite_adi: "<?= addslashes($duzenlenecek_ogrenim['universite_adi'] ?? '') ?>",
    fakulte_adi: "<?= addslashes($duzenlenecek_ogrenim['fakulte_adi'] ?? '') ?>",
    program_adi: "<?= addslashes($duzenlenecek_ogrenim['program_adi'] ?? '') ?>",
    anabilim_adi: "<?= addslashes($duzenlenecek_ogrenim['anabilim_adi'] ?? '') ?>",
    mezuniyet_tarihi: "<?= $duzenlenecek_ogrenim['mezuniyet_tarihi'] ?? '' ?>",
    ogrenim_durumu: "<?= addslashes($duzenlenecek_ogrenim['ogrenim_durumu_id'] ?? '') ?>"
};
<?php else: ?>
window.duzenlenecekOgrenim = null;
<?php endif; ?>

// Düzenleme modunda mezun okul bilgileri
<?php if (isset($duzenlenecek_ogrenim) && $duzenlenecek_il && $duzenlenecek_ilce): ?>
window.duzenlenecekOkul = {
    il_id: <?= $duzenlenecek_il['id'] ?? 0 ?>,
    il_adi: "<?= addslashes($duzenlenecek_il['il_adi'] ?? '') ?>",
    ilce_id: <?= $duzenlenecek_ilce['id'] ?? 0 ?>,
    ilce_adi: "<?= addslashes($duzenlenecek_ilce['ilce_adi'] ?? '') ?>",
    okul_adi: "<?= addslashes($duzenlenecek_okul_adi ?? '') ?>"
};
<?php else: ?>
window.duzenlenecekOkul = null;
<?php endif; ?>

</script>

<?php include 'footer.php'; ?>