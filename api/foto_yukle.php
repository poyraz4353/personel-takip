<?php
/**
 * FOTOĞRAF YÜKLEME API
 * @version 2.9
 * @author Fatih
 */

// ============================================
// GÜVENLİK ÖNLEMLERİ - EN ÜST
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Output buffer başlat (çift çıktıyı önle)
if (ob_get_level()) ob_end_clean();
ob_start();

// SADECE JSON
header('Content-Type: application/json; charset=utf-8');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS isteği için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit;
}

// HATA FONKSİYONU
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ============================================
// BAĞIMLILIKLAR
// ============================================
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

// ============================================
// THUMBNAIL OLUŞTURMA FONKSİYONU
// ============================================
function createThumbnail($sourcePath, $destPath, $maxWidth = 200, $maxHeight = 200) {
    try {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        list($width, $height, $type) = getimagesize($sourcePath);
        
        if (!$width || !$height) {
            return false;
        }
        
        $ratio = $width / $height;
        if ($maxWidth / $maxHeight > $ratio) {
            $newWidth = $maxHeight * $ratio;
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        }
        
        $newWidth = round($newWidth);
        $newHeight = round($newHeight);
        
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                $transparentIndex = imagecolortransparent($source);
                if ($transparentIndex >= 0) {
                    $transparentColor = imagecolorsforindex($source, $transparentIndex);
                    $transparentIndex = imagecolorallocate($thumb, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                    imagefill($thumb, 0, 0, $transparentIndex);
                    imagecolortransparent($thumb, $transparentIndex);
                }
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            imagedestroy($thumb);
            return false;
        }
        
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $destPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $destPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $destPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb, $destPath, 85);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumb);
        chmod($destPath, 0644);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Thumbnail oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

// ============================================
// EXIF DATA TEMİZLEME FONKSİYONU
// ============================================
function cleanExifData($filePath) {
    try {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $image = imagecreatefromjpeg($filePath);
            if ($image) {
                imagejpeg($image, $filePath, 90);
                imagedestroy($image);
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("EXIF temizleme hatası: " . $e->getMessage());
        return false;
    }
}

// ============================================
// ANA KOD
// ============================================
SessionManager::start();

// DEBUG MOD
define('DEBUG_MODE', true);

// TEST MODU
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Fotoğraf API çalışıyor',
        'version' => '2.9',
        'timestamp' => date('Y-m-d H:i:s'),
        'session' => [
            'user_id' => $_SESSION['user_id'] ?? 'null',
            'session_id' => session_id()
        ]
    ]);
    exit;
}

// METHOD KONTROLÜ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Sadece POST methodu kabul edilir.', 405);
}

// SESSION KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    api_error('Oturum açmanız gerekiyor.', 401);
}

try {
    // VERİTABANI BAĞLANTISI
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // PERSONEL ID KONTROLÜ
    $personel_id = $_POST['personel_id'] ?? 0;
    if (empty($personel_id) || !is_numeric($personel_id)) {
        api_error('Geçerli bir Personel ID gerekiyor.');
    }
    
    // PERSONEL VAR MI KONTROL
    $checkPersonel = $db->prepare("SELECT id, ad_soyadi FROM personel WHERE id = ?");
    $checkPersonel->execute([$personel_id]);
    $personelData = $checkPersonel->fetch(PDO::FETCH_ASSOC);
    
    if (!$personelData) {
        api_error('Personel bulunamadı.');
    }
    
    // DOSYA KONTROLÜ - DÜZELTİLMİŞ KISIM
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Dosya PHP ayarlarında belirlenen maksimum boyutu aşıyor (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE => 'Dosya formda belirlenen maksimum boyutu aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya sadece kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Hiç dosya yüklenmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi dosya yüklemeyi durdurdu.'
        ];
        
        // DÜZELTİLDİ: foto_path yerine foto
        $errorCode = $_FILES['foto']['error'] ?? -1;
        $errorMsg = $errorMessages[$errorCode] ?? 'Dosya yüklenirken bilinmeyen bir hata oluştu (Kod: ' . $errorCode . ').';
        
        api_error($errorMsg);
    }
    
    $uploadedFile = $_FILES['foto'];
    
    // DOSYA BİLGİLERİ
    $fileName = htmlspecialchars(basename($uploadedFile['name']), ENT_QUOTES, 'UTF-8');
    $fileTmp = $uploadedFile['tmp_name'];
    $fileSize = $uploadedFile['size'];
    
    // UZANTI KONTROLÜ
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExt, $allowedExt)) {
        api_error('Geçersiz dosya formatı. İzin verilen formatlar: ' . implode(', ', $allowedExt));
    }
    
    // MIME TYPE KONTROLÜ
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($mime, $allowedMimes)) {
        api_error('Geçersiz dosya türü. Gerçek bir resim dosyası yükleyin.');
    }
    
    // RESİM BOYUT KONTROLÜ
    list($width, $height) = getimagesize($fileTmp);
    if (!$width || !$height) {
        api_error('Geçerli bir resim dosyası değil.');
    }
    
    // DOSYA BOYUT KONTROLÜ (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        api_error('Dosya çok büyük. Maksimum: 5MB');
    }
    
    // RESİM BOYUTLARI KONTROLÜ
    $maxWidth = 4000;
    $maxHeight = 4000;
    if ($width > $maxWidth || $height > $maxHeight) {
        api_error("Resim boyutları çok büyük. Maksimum: {$maxWidth}x{$maxHeight}");
    }
    
    $minWidth = 100;
    $minHeight = 100;
    if ($width < $minWidth || $height < $minHeight) {
        api_error("Resim boyutları çok küçük. Minimum: {$minWidth}x{$minHeight}");
    }
    
    // ESKİ FOTOĞRAFI SİL
    $oldPhoto = $db->prepare("SELECT foto_path FROM personel WHERE id = ?");
    $oldPhoto->execute([$personel_id]);
    $oldPhotoData = $oldPhoto->fetch(PDO::FETCH_ASSOC);
    
    if ($oldPhotoData && !empty($oldPhotoData['foto_path'])) {
        $oldFilePath = __DIR__ . '/../uploads/personel_fotolar/' . $oldPhotoData['foto_path'];
        $oldThumbPath = __DIR__ . '/../uploads/personel_fotolar/thumbs/thumb_' . $oldPhotoData['foto_path'];
        
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            unlink($oldFilePath);
        }
        
        if (file_exists($oldThumbPath) && is_file($oldThumbPath)) {
            unlink($oldThumbPath);
        }
    }
    
    // YENİ DOSYA ADI
    $timestamp = date('Ymd_His');
    $uniqueId = substr(md5(uniqid() . mt_rand()), 0, 8);
    $newFileName = 'personel_' . $personel_id . '_' . $timestamp . '_' . $uniqueId . '.' . $fileExt;
    $uploadPath = __DIR__ . '/../uploads/personel_fotolar/' . $newFileName;
    
    // KLASÖR KONTROLÜ
    $uploadDir = __DIR__ . '/../uploads/personel_fotolar/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            api_error('Upload klasörü oluşturulamadı.');
        }
        file_put_contents($uploadDir . 'index.html', '<!-- Directory listing disabled -->');
    }
    
    // GÜVENLİK KONTROLÜ
    if (!is_uploaded_file($fileTmp)) {
        api_error('Güvenlik hatası: Geçerli bir upload dosyası değil.');
    }
    
    // DOSYAYI YÜKLE
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        chmod($uploadPath, 0644);
        
        // EXIF TEMİZLEME
        if ($fileExt === 'jpg' || $fileExt === 'jpeg') {
            cleanExifData($uploadPath);
        }
        
        // THUMBNAIL OLUŞTUR
        $thumbDir = __DIR__ . '/../uploads/personel_fotolar/thumbs/';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
            file_put_contents($thumbDir . 'index.html', '<!-- Directory listing disabled -->');
        }
        
        $thumbFileName = 'thumb_' . $newFileName;
        $thumbPath = $thumbDir . $thumbFileName;
        createThumbnail($uploadPath, $thumbPath, 200, 200);
        
        // VERİTABANI GÜNCELLE
        $updateSql = "UPDATE personel SET foto_path = ?, guncelleme_tarihi = NOW() WHERE id = ?";
        $stmt = $db->prepare($updateSql);
        $result = $stmt->execute([$newFileName, $personel_id]);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => '✅ Fotoğraf başarıyla yüklendi ve güncellendi!',
                'personel' => [
                    'id' => $personel_id,
                    'ad_soyadi' => $personelData['ad_soyadi']
                ],
                'files' => [
                    'original' => [
                        'name' => $newFileName,
                        'url' => '/personel-takip/uploads/personel_fotolar/' . $newFileName,
                        'size' => $fileSize,
                        'size_kb' => round($fileSize / 1024, 2),
                        'size_mb' => round($fileSize / 1024 / 1024, 2),
                        'dimensions' => ['width' => $width, 'height' => $height],
                        'mime' => $mime
                    ],
                    'thumbnail' => [
                        'name' => $thumbFileName,
                        'url' => '/personel-takip/uploads/personel_fotolar/thumbs/' . $thumbFileName,
                        'exists' => file_exists($thumbPath)
                    ]
                ]
            ];
            
            ob_end_clean();
            echo json_encode($response);
            
        } else {
            unlink($uploadPath);
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
            api_error('Veritabanı güncellenemedi.');
        }
        
    } else {
        api_error('Dosya kaydedilemedi. Klasör yazma izinlerini kontrol edin.');
    }
    
} catch (PDOException $e) {
    api_error('Veritabanı hatası: ' . $e->getMessage());
} catch (Exception $e) {
    api_error('Sistem hatası: ' . $e->getMessage());
}

// DEBUG LOG
if (DEBUG_MODE) {
    error_log("=== FOTOĞRAF YÜKLEME TAMAMLANDI ===");
}
?>