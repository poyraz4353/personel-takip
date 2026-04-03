<?php
/**
 * FONKSİYONLAR DOSYASI (functions.php)
 * Personel Takip Sistemi için yardımcı fonksiyonlar
 * Güvenlik, doğrulama, dosya işleme ve veritabanı işlemleri
 * Son Güncelleme: 2024
 */

// Sabitler
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg', 
    'image/png', 
    'image/gif', 
    'image/webp', 
    'image/bmp'
]);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
define('UPLOAD_DIR', 'uploads/');
define('CSRF_TIMEOUT', 3600); // 1 saat

// =============================
// 🛡️ GÜVENLİK FONKSİYONLARI
// =============================

/**
 * CSRF Token Kontrolü
 */
function checkCSRF(string $token, int $timeout = CSRF_TIMEOUT): bool {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    // Zaman aşımı kontrolü
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > $timeout) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

/**
 * CSRF Token Oluşturma
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * XSS Korumalı Input Temizleme
 */
function sanitizeInput(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Array Temizleme
 */
function sanitizeArray(array $data): array {
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $clean[$key] = sanitizeArray($value);
        } else {
            $clean[$key] = sanitizeInput($value);
        }
    }
    return $clean;
}

// =============================
// 🇹🇷 TÜRKİYE ÖZEL DOĞRULAMALAR
// =============================

/**
 * TC Kimlik No Doğrulama
 */
function validateTCNo(string $tcno): bool {
    // Sadece rakamları al
    $tcno = preg_replace('/\D/', '', $tcno);
    
    // Temel kontroller
    if (strlen($tcno) != 11 || $tcno[0] == '0') {
        return false;
    }
    
    // Tüm rakamlar aynı mı kontrol et (11111111111 gibi)
    if (count(array_unique(str_split($tcno))) === 1) {
        return false;
    }
    
    // Rakamları diziye çevir
    $digits = array_map('intval', str_split($tcno));
    
    // TC kimlik algoritması
    $odd = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $even = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    
    // 10. ve 11. rakam kontrolleri
    $check1 = ($digits[9] === (($odd * 7 - $even) % 10));
    $check2 = ($digits[10] === (array_sum(array_slice($digits, 0, 10)) % 10));
    
    return $check1 && $check2;
}

/**
 * Telefon Numarası Doğrulama
 */
function validatePhone(string $phone): bool {
    $cleaned = cleanPhone($phone);
    
    // 10 haneli değilse geçersiz
    if (strlen($cleaned) !== 10) {
        return false;
    }
    
    // Operatör kodu kontrolü
    $operatorCode = substr($cleaned, 0, 3);
    if ($operatorCode[0] !== '5' || !ctype_digit($operatorCode)) {
        return false;
    }
    
    // Güncel operatör kodları
    $validOperatorCodes = [
        '501', '502', '503', '504', '505', '506', '507', '508', '509', // Turkcell
        '510', '511', '512', '513', '514', '515', '516', '517', '518', '519', // Vodafone
        '520', '521', '522', '523', '524', '525', '526', '527', '528', '529', // Türk Telekom
        '530', '531', '532', '533', '534', '535', '536', '537', '538', '539',
        '540', '541', '542', '543', '544', '545', '546', '547', '548', '549',
        '550', '551', '552', '553', '554', '555', '556', '557', '558', '559'
    ];
    
    return in_array($operatorCode, $validOperatorCodes);
}

/**
 * Telefon Numarası Temizleme
 */
function cleanPhone(string $phone): string {
    $cleaned = preg_replace('/\D/', '', $phone);
    
    // Başında 0 varsa kaldır
    if (strlen($cleaned) === 11 && $cleaned[0] === '0') {
        $cleaned = substr($cleaned, 1);
    }
    // +90 ile başlıyorsa kaldır
    elseif (strlen($cleaned) === 12 && substr($cleaned, 0, 2) === '90') {
        $cleaned = substr($cleaned, 2);
    }
    
    return $cleaned;
}

/**
 * Telefon Numarası Formatlama
 */
function formatPhone(string $phone): string {
    $cleaned = cleanPhone($phone);
    
    if (strlen($cleaned) !== 10) {
        return $phone;
    }
    
    return '(' . substr($cleaned, 0, 3) . ') ' . substr($cleaned, 3, 3) . ' ' . 
           substr($cleaned, 6, 2) . ' ' . substr($cleaned, 8, 2);
}

// =============================
// 📧 E-POSTA VE ŞİFRE DOĞRULAMA
// =============================

/**
 * E-posta Doğrulama
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Şifre Güvenlik Kontrolü
 */
function validatePassword(string $password): bool {
    $minLength = 8;
    $hasUpperCase = preg_match('/[A-Z]/', $password);
    $hasLowerCase = preg_match('/[a-z]/', $password);
    $hasNumbers = preg_match('/\d/', $password);
    $hasSpecialChars = preg_match('/[^A-Za-z0-9]/', $password);
    
    return strlen($password) >= $minLength && 
           $hasUpperCase && 
           $hasLowerCase && 
           $hasNumbers && 
           $hasSpecialChars;
}

// =============================
// 📁 DOSYA İŞLEMLERİ
// =============================

/**
 * Dosya Doğrulama
 */
function validateFile(array $file): array {
    $errors = [];
    
    // Temel kontroller
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Dosya yüklenirken hata oluştu";
        return ['valid' => false, 'errors' => $errors];
    }
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = "Güvenlik ihlali: Geçersiz dosya";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Boyut kontrolü
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "Dosya boyutu " . formatFileSize(MAX_FILE_SIZE) . "'tan büyük olamaz";
    }
    
    // MIME türü kontrolü
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_FILE_TYPES)) {
        $errors[] = "Sadece JPG, PNG, GIF, WebP ve BMP dosyaları yüklenebilir";
    }
    
    // Uzantı kontrolü
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = "Geçersiz dosya uzantısı";
    }
    
    // Resim boyutları kontrolü
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = "Geçersiz resim dosyası";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Güvenli Dosya Adı Oluşturma
 */
function generateSafeFileName(array $file, string $tc_no, string $ad_soyadi): string {
    // Türkçe karakterleri dönüştür
    $turkce = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
    $latin  = ['s','S','i','I','g','G','u','U','o','O','c','C'];
    $safeName = str_replace($turkce, $latin, $ad_soyadi);
    
    // Özel karakterleri temizle
    $safeName = preg_replace('/[^A-Za-z0-9]/', '_', $safeName);
    $safeName = preg_replace('/_+/', '_', $safeName);
    $safeName = trim($safeName, '_');
    
    // Uzantıyı güvenli hale getir
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $extension = 'jpg'; // Varsayılan uzantı
    }
    
    // Benzersiz ek ve dosya adı oluştur
    $unique = substr(md5(uniqid('', true)), 0, 8);
    return "{$tc_no}_{$safeName}_{$unique}.{$extension}";
}

/**
 * Resim Boyutlandırma ve Kaydetme
 */
function resizeAndSaveImage(array $file, string $targetPath, int $maxWidth = 800): bool {
    // Resim bilgilerini al
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return false;
    }
    
    [$width, $height, $type] = $imageInfo;
    
    // Resim zaten küçükse direkt kaydet
    if ($width <= $maxWidth) {
        return move_uploaded_file($file['tmp_name'], $targetPath);
    }
    
    // Yeni boyutları hesapla
    $newWidth = $maxWidth;
    $newHeight = (int) round(($height / $width) * $newWidth);
    
    // Resim türüne göre kaynak oluştur
    $src = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG  => imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_GIF  => imagecreatefromgif($file['tmp_name']),
        IMAGETYPE_WEBP => imagecreatefromwebp($file['tmp_name']),
        IMAGETYPE_BMP  => imagecreatefrombmp($file['tmp_name']),
        default        => false
    };
    
    if ($src === false) {
        return false;
    }
    
    // Yeni resim oluştur
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    
    // Şeffaflığı koru (PNG & WebP)
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Boyutlandır
    $success = imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    if (!$success) {
        imagedestroy($src);
        imagedestroy($dst);
        return false;
    }
    
    // Kaydet
    $saved = match ($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $targetPath, 85),
        IMAGETYPE_PNG  => imagepng($dst, $targetPath, 6),
        IMAGETYPE_GIF  => imagegif($dst, $targetPath),
        IMAGETYPE_WEBP => imagewebp($dst, $targetPath, 85),
        IMAGETYPE_BMP  => imagebmp($dst, $targetPath),
        default        => false
    };
    
    // Belleği temizle
    imagedestroy($src);
    imagedestroy($dst);
    
    return $saved;
}

/**
 * Fotoğraf Yükleme İşlemi
 */
function processUploadedPhoto(array $file, string $tc_no, string $ad_soyadi, ?PDO $db = null, ?int $personel_id = null, string $uploadDir = UPLOAD_DIR): ?string {
    // Temel kontroller
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        logError("Dosya yükleme hatası", ['error_code' => $file['error'] ?? 'unknown']);
        return null;
    }
    
    // Güvenlik kontrolü
    if (!is_uploaded_file($file['tmp_name'])) {
        logError("Güvenlik ihlali: Dosya upload edilmemiş", ['file_name' => $file['name']]);
        return null;
    }
    
    // Dosya doğrulama
    $fileValidation = validateFile($file);
    if (!$fileValidation['valid']) {
        logError("Dosya doğrulama hatası", [
            'errors' => $fileValidation['errors'],
            'file_name' => $file['name'],
            'file_size' => $file['size']
        ]);
        return null;
    }
    
    // Upload dizinini hazırla
    if (!prepareUploadDirectory($uploadDir)) {
        return null;
    }
    
    // Güvenli dosya adı oluştur
    $fileName = generateSafeFileName($file, $tc_no, $ad_soyadi);
    $targetPath = rtrim($uploadDir, '/') . '/' . $fileName;
    
    // Resmi işle ve kaydet
    if (!resizeAndSaveImage($file, $targetPath)) {
        logError("Resim işleme hatası", ['target_path' => $targetPath]);
        return null;
    }
    
    // Veritabanına kaydet (isteğe bağlı)
    if ($db && $personel_id) {
        try {
            $stmt = $db->prepare("UPDATE personel SET foto_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$fileName, $personel_id]);
        } catch (Exception $e) {
            // Hata durumunda dosyayı sil
            @unlink($targetPath);
            logError("Veritabanı güncelleme hatası", [
                'error' => $e->getMessage(),
                'personel_id' => $personel_id
            ]);
            return null;
        }
    }
    
    return $fileName;
}

/**
 * Upload Dizini Hazırlama
 */
function prepareUploadDirectory(string $uploadDir): bool {
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            logError("Dizin oluşturulamadı", ['directory' => $uploadDir]);
            return false;
        }
        
        // Güvenlik dosyaları oluştur
        file_put_contents($uploadDir . '/index.html', '');
        file_put_contents($uploadDir . '/.htaccess', "Options -Indexes\nDeny from all");
    }
    
    // Yazma izni kontrolü
    if (!is_writable($uploadDir)) {
        logError("Dizin yazılabilir değil", ['directory' => $uploadDir]);
        return false;
    }
    
    return true;
}

// =============================
// 📊 VERİ FORMATLAMA
// =============================

/**
 * Tarih Formatı Dönüştürme
 */
function convertDateToMySQL(?string $tarih): ?string {
    if (empty($tarih)) {
        return null;
    }
    
    // gg.aa.yyyy → yyyy-mm-dd
    $tarih = str_replace(['.', '/'], '-', $tarih);
    $parcalar = explode('-', $tarih);
    
    if (count($parcalar) === 3) {
        // Gün-ay-yıl formatını kontrol et
        if (strlen($parcalar[0]) === 2 && strlen($parcalar[1]) === 2 && strlen($parcalar[2]) === 4) {
            return $parcalar[2] . '-' . $parcalar[1] . '-' . $parcalar[0];
        }
    }
    
    // Standart dönüşüm
    $timestamp = strtotime($tarih);
    return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
}

/**
 * Dosya Boyutu Formatlama
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * SEO Dostu URL
 */
function seoFriendlyString(string $string): string {
    $turkce = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç',' ','/',':',',','!','?','.'];
    $latin  = ['s','s','i','i','g','g','u','u','o','o','c','c','-','-','-','-','-','-','-'];
    
    $string = str_replace($turkce, $latin, $string);
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    $string = preg_replace('/-+/', '-', $string);
    
    return strtolower(trim($string, '-'));
}

// =============================
// 📝 MESAJ GÖSTERİMİ
// =============================

/**
 * Hata Mesajı Gösterme
 */
function showError(string $msg): void {
    echo '<div class="alert alert-danger d-flex align-items-center gap-2 mt-3" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>' . sanitizeInput($msg) . '</div>
          </div>';
}

/**
 * Başarı Mesajı Gösterme
 */
function showSuccess(string $msg): void {
    echo '<div class="alert alert-success d-flex align-items-center gap-2 mt-3" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>' . sanitizeInput($msg) . '</div>
          </div>';
}

/**
 * Bilgi Mesajı Gösterme
 */
function showInfo(string $msg): void {
    echo '<div class="alert alert-info d-flex align-items-center gap-2 mt-3" role="alert">
            <i class="fas fa-info-circle"></i>
            <div>' . sanitizeInput($msg) . '</div>
          </div>';
}

// =============================
// 🔧 YARDIMCI FONKSİYONLAR
// =============================

/**
 * Form Verisi Çekme
 */
// functions.php dosyasına ekleyin veya güncelleyin
function getFormData($fieldName, $default = '') {
    // Önce POST'tan kontrol et (canlı form gönderimi)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$fieldName])) {
        return sanitizeInput($_POST[$fieldName]);
    }
    
    // Sonra session'dan kontrol et (validasyon hatası sonrası)
    if (isset($_SESSION['form_data'][$fieldName])) {
        $value = $_SESSION['form_data'][$fieldName];
        // Eğer değer string ise, özel karakterleri düzelt
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    return $default;
}

/**
 * Hata Loglama
 */
function logError(string $message, array $context = []): void {
    $logMessage = date('[Y-m-d H:i:s]') . " ERROR: " . $message;
    
    if (!empty($context)) {
        // Hassas verileri maskele
        $safeContext = $context;
        if (isset($safeContext['tc_no'])) {
            $safeContext['tc_no'] = '***' . substr($safeContext['tc_no'], -3);
        }
        if (isset($safeContext['phone'])) {
            $safeContext['phone'] = '***' . substr($safeContext['phone'], -3);
        }
        
        $logMessage .= " | Context: " . json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    error_log($logMessage);
}

/**
 * Debug Loglama
 */
function logDebug(string $message, array $context = []): void {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $logMessage = date('[Y-m-d H:i:s]') . " DEBUG: " . $message;
        
        if (!empty($context)) {
            $logMessage .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        error_log($logMessage);
    }
}

/**
 * Doğrulama Hatalarını Toplama
 */
function getValidationErrors(array $fields): array {
    $errors = [];
    
    foreach ($fields as $field => $value) {
        $fieldErrors = [];
        
        switch($field) {
            case 'tc_no':
                if (!validateTCNo($value)) {
                    $fieldErrors[] = "Geçersiz TC Kimlik Numarası";
                }
                break;
                
            case 'email':
                if (!empty($value) && !validateEmail($value)) {
                    $fieldErrors[] = "Geçersiz e-posta formatı";
                }
                break;
                
            case 'phone':
                if (!empty($value) && !validatePhone($value)) {
                    $fieldErrors[] = "Geçersiz telefon numarası";
                }
                break;
                
            case 'password':
                if (!validatePassword($value)) {
                    $fieldErrors[] = "Şifre en az 8 karakter, büyük/küçük harf, rakam ve özel karakter içermeli";
                }
                break;
        }
        
        if (!empty($fieldErrors)) {
            $errors[$field] = $fieldErrors;
        }
    }
    
    return $errors;
}

/**
 * Veritabanından Hizmet Sınıflarını Getir
 */
function getHizmetSiniflari(PDO $db): array {
    try {
        $stmt = $db->prepare("SELECT id, sinif_adi FROM hizmet_siniflari WHERE durum = 1 ORDER BY sira, sinif_adi ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logError("Hizmet sınıfları getirilemedi", ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Random String Oluşturma
 */
function generateRandomString(int $length = 8): string {
    return bin2hex(random_bytes($length));
}

/**
 * IP Adresi Getirme
 */
function getClientIP(): string {
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

// =============================
// 🎯 OTOMATİK FONKSİYON YÜKLEME
// =============================

/**
 * Otomatik fonksiyon optimizasyonu için preload
 */
if (PHP_VERSION_ID >= 70400 && function_exists('opcache_compile_file')) {
    register_shutdown_function(function() {
        // Önemli fonksiyonları ön belleğe al
        validateTCNo('00000000000');
        sanitizeInput('test');
        validatePhone('5554443322');
    });
}

// =============================
// 📞 DEPRECATED FONKSİYONLAR
// =============================

/**
 * @deprecated advancedValidateTCNo() yerine validateTCNo() kullanın
 */
function advancedValidateTCNo(string $tcno): bool {
    trigger_error('advancedValidateTCNo() fonksiyonu artık kullanılmıyor. validateTCNo() kullanın.', E_USER_DEPRECATED);
    return validateTCNo($tcno);
}




// =============================
// 🔄 METİN DÖNÜŞTÜRME FONKSİYONLARI (YENİ EKLENEN)
// =============================


/**
 * ID'den hizmet sınıfı adını getir
 */
function getHizmetSinifiAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT sinif_adi FROM hizmet_siniflari WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['sinif_adi'] : '';
    } catch (Exception $e) {
        error_log("Hizmet sınıfı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den kadro unvanı adını getir
 */
function getKadroUnvaniAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT unvan_adi FROM kadro_unvanlari WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['unvan_adi'] : '';
    } catch (Exception $e) {
        error_log("Kadro unvanı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den görev unvanı adını getir
 */
function getGorevUnvaniAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT unvan_adi FROM gorev_unvanlari WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['unvan_adi'] : '';
    } catch (Exception $e) {
        error_log("Görev unvanı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den atama alanı adını getir
 */
function getAtamaAlaniAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT alan_adi FROM atama_alani WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['alan_adi'] : '';
    } catch (Exception $e) {
        error_log("Atama alanı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den yer değiştirme çeşidi adını getir
 */
function getYerDegistirmeCesidiAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT yer_degistirme_cesidi FROM yer_degistirme_cesidi WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['yer_degistirme_cesidi'] : '';
    } catch (Exception $e) {
        error_log("Yer değiştirme çeşidi adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den durum adını getir
 */
function getDurumAdi($id, PDO $db = null) {
    if (empty($id)) return 'Görevde';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT durum_adi FROM durumu WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['durum_adi'] : 'Görevde';
    } catch (Exception $e) {
        error_log("Durum adı getirme hatası: " . $e->getMessage());
        return 'Görevde';
    }
}

/**
 * ID'den terfi nedeni adını getir
 */
function getTerfiNedeniAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT terfi_nedeni FROM terfi_nedenleri WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['terfi_nedeni'] : '';
    } catch (Exception $e) {
        error_log("Terfi nedeni adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den öğrenim durumu adını getir
 */
function getOgrenimDurumuAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT ogrenim_adi FROM ogrenim_durumlari WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['ogrenim_adi'] : '';
    } catch (Exception $e) {
        error_log("Öğrenim durumu adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * Değerden belge cinsi adını getir
 */
function getBelgeCinsiAdi($value) {
    $map = [
        'Diploma' => 'Diploma',
        'Çıkış/Geçici Mezuniyet Belgesi' => 'Çıkış/Geçici Mezuniyet Belgesi',
        'Geçici Mezuniyet Belgesi' => 'Çıkış/Geçici Mezuniyet Belgesi',
        'Tasdikname' => 'Tasdikname'
    ];
    
    return $map[$value] ?? $value;
}

/**
 * ID'den üniversite adını getir
 */
function getUniversiteAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT universite_adi FROM universiteler WHERE universite_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['universite_adi'] : '';
    } catch (Exception $e) {
        error_log("Üniversite adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den fakülte adını getir
 */
function getFakulteAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT fakulte_adi FROM fakulte_yuksekokul WHERE fakulte_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['fakulte_adi'] : '';
    } catch (Exception $e) {
        error_log("Fakülte adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den anabilim dalı adını getir
 */
function getAnabilimAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT anabilim_adi FROM anabilim_dali WHERE anabilim_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['anabilim_adi'] : '';
    } catch (Exception $e) {
        error_log("Anabilim dalı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den program adını getir
 */
function getProgramAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT program_adi FROM program WHERE program_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['program_adi'] : '';
    } catch (Exception $e) {
        error_log("Program adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den görev unvanı adını getir (genel - tüm görev unvanları için)
 */
function getOncekiGorevUnvaniAdi($id, PDO $db = null) {
    if (empty($id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT unvan_adi FROM gorev_unvanlari WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['unvan_adi'] : '';
    } catch (Exception $e) {
        error_log("Önceki görev unvanı adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den tüm metin dönüşümlerini yapar
 */
function getIdToText($id, $type, PDO $db = null) {
    switch ($type) {
        case 'hizmet_sinifi':
            return getHizmetSinifiAdi($id, $db);
        case 'kadro_unvani':
            return getKadroUnvaniAdi($id, $db);
        case 'gorev_unvani':
            return getGorevUnvaniAdi($id, $db);
        case 'atama_alani':
            return getAtamaAlaniAdi($id, $db);
        case 'yer_degistirme':
            return getYerDegistirmeCesidiAdi($id, $db);
        case 'durum':
            return getDurumAdi($id, $db);
        case 'terfi_nedeni':
            return getTerfiNedeniAdi($id, $db);
        case 'ogrenim_durumu':
            return getOgrenimDurumuAdi($id, $db);
        case 'universite':
            return getUniversiteAdi($id, $db);
        case 'fakulte':
            return getFakulteAdi($id, $db);
        case 'anabilim':
            return getAnabilimAdi($id, $db);
        case 'program':
            return getProgramAdi($id, $db);
        case 'onceki_gorev':
            return getOncekiGorevUnvaniAdi($id, $db);
        default:
            return $id;
    }
}






// functions.php dosyasının SONUNA ekleyin (getIdToText() fonksiyonundan sonra)

/**
 * ID'den il adını getir
 */
function getIlAdi($il_id, PDO $db = null) {
    if (empty($il_id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT il_adi FROM iller WHERE id = ?");
        $stmt->execute([$il_id]);
        $il = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $il ? $il['il_adi'] : '';
    } catch (Exception $e) {
        error_log("İl adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den ilçe adını getir
 */
function getIlceAdi($ilce_id, PDO $db = null) {
    if (empty($ilce_id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT ilce_adi FROM ilceler WHERE id = ?");
        $stmt->execute([$ilce_id]);
        $ilce = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $ilce ? $ilce['ilce_adi'] : '';
    } catch (Exception $e) {
        error_log("İlçe adı getirme hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * ID'den okul adını getir
 */
function getOkulAdi($okul_id, PDO $db = null) {
    if (empty($okul_id)) return '';
    
    try {
        if (!$db) {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
        }
        
        $stmt = $db->prepare("SELECT gorev_yeri FROM okullar WHERE id = ?");
        $stmt->execute([$okul_id]);
        $okul = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $okul ? $okul['gorev_yeri'] : '';
    } catch (Exception $e) {
        error_log("Okul adı getirme hatası: " . $e->getMessage());
        return '';
    }
}
?>








