<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db_config.php';

try {
    $db = Database::getInstance();

    // Zorunlu alan kontrolü
    $requiredFields = ['ad_soyadi', 'tc_no'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field alanı zorunludur!");
        }
    }

    // Veri temizleme ve doğrulama
    $data = [
        'ad_soyadi' => cleanInput($_POST['ad_soyadi']),
        'tc_no' => validateTCNo($_POST['tc_no']),
        'gorev_yeri' => cleanInput($_POST['gorev_yeri'] ?? null),
        'memuriyet_baslama_tarihi' => validateDate($_POST['memuriyet_baslama_tarihi'] ?? null),
        'kurum_baslama_tarihi' => validateDate($_POST['kurum_baslama_tarihi'] ?? null),
        'istihdam_tipi' => cleanInput($_POST['istihdam_tipi'] ?? null),
        'kadro_unvani' => cleanInput($_POST['kadro_unvani'] ?? null),
        'kariyer_basamagi' => cleanInput($_POST['kariyer_basamagi'] ?? null),
        'gorevi' => cleanInput($_POST['gorevi'] ?? null),
        'alani' => cleanInput($_POST['alani'] ?? null),
        'atama_cesidi' => cleanInput($_POST['atama_cesidi'] ?? null),
        'durumu' => in_array($_POST['durumu'] ?? null, ['Aktif', 'Pasif', 'İzinli']) ? $_POST['durumu'] : 'Aktif',
        'baba_adi' => cleanInput($_POST['baba_adi'] ?? null),
        'dogum_yili' => filter_var($_POST['dogum_yili'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1900, 'max_range' => date('Y')]
        ]),
        'kadro_derecesi' => filter_var($_POST['kadro_derecesi'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 15]
        ]),
        'aylik_derece_kademe' => cleanInput($_POST['aylik_derece_kademe'] ?? null),
        'son_terfi_tarihi' => validateDate($_POST['son_terfi_tarihi'] ?? null),
        'kha_ek_gosterge' => filter_var($_POST['kha_ek_gosterge'] ?? null, FILTER_VALIDATE_INT),
        'emekli_sicil_no' => cleanInput($_POST['emekli_sicil_no'] ?? null),
        'kurum_sicil_no' => cleanInput($_POST['kurum_sicil_no'] ?? null),
        'cinsiyeti' => in_array($_POST['cinsiyeti'] ?? null, ['Erkek', 'Kadın']) ? $_POST['cinsiyeti'] : null,
        'telefon' => validatePhone($_POST['telefon'] ?? null),
        'adres' => cleanInput($_POST['adres'] ?? null),
        'raf_no' => cleanInput($_POST['raf_no'] ?? null),
        'arsiv_no' => cleanInput($_POST['arsiv_no'] ?? null)
    ];

    // SQL sorgusu
    $sql = "INSERT INTO personel SET " . 
        implode(", ", array_map(fn($key) => "$key = :$key", array_keys($data)));

    $stmt = $db->prepare($sql);
    $stmt->execute($data);

    $_SESSION['success'] = "Personel başarıyla eklendi!";
    header("Location: personel_listesi.php");
    exit;

} catch(PDOException $e) {
    error_log("Veritabanı Hatası [".date('Y-m-d H:i:s')."]: " . $e->getMessage());
    $_SESSION['error'] = "Veritabanı hatası oluştu!";
    header("Location: personel_ekle.php");
    exit;
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: personel_ekle.php");
    exit;
}

// Yardımcı fonksiyonlar
function cleanInput($data) {
    return $data !== null ? htmlspecialchars(trim($data)) : null;
}

function validateTCNo($tcno) {
    $tcno = preg_replace('/[^0-9]/', '', $tcno);
    if (strlen($tcno) != 11) {
        throw new Exception("TC Kimlik No 11 haneli olmalıdır!");
    }
    return $tcno;
}

function validateDate($date) {
    if ($date === null || $date === '') return null;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Geçersiz tarih formatı!");
    }
    return $date;
}

function validatePhone($phone) {
    if ($phone === null) return null;
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 ? $phone : null;
}