<?php
/**
 * Personel Takip Sistemi - Arama İşlemi
 * 
 * Bu dosya, TC Kimlik No ile personel arama işlemini yönetir.
 * 
 * @version 1.0
 * @author Fatih
 * @license MIT
 */

require_once __DIR__ . '/config/session_manager.php';
require_once __DIR__ . '/config/db_config.php';

SessionManager::requireAuth();

// Arama parametrelerini al
$tc_search = $_GET['tc_search'] ?? '';
$referer = $_GET['referer'] ?? 'dashboard_Anasayfa.php';

// TC Kimlik No validasyonu
if (empty($tc_search) || !preg_match('/^[1-9][0-9]{10}$/', $tc_search)) {
    SessionManager::setMessage('error', 'Geçersiz TC Kimlik No!');
    header("Location: $referer");
    exit;
}

try {
    $db = Database::getInstance();
    
    // Personeli ara
    $stmt = $db->prepare("SELECT * FROM personel WHERE tc_kimlik = ? AND aktif = 1");
    $stmt->execute([$tc_search]);
    $personel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($personel) {
        // Personel bulundu - session'a kaydet
        $_SESSION['aktif_tc'] = $tc_search;
        $_SESSION['aktif_personel'] = $personel;
        SessionManager::setMessage('success', 'Personel bulundu: ' . $personel['ad'] . ' ' . $personel['soyad']);
        
        // Yönlendirme
        if ($referer == 'dashboard_Anasayfa.php') {
            header("Location: kimlik_bilgileri.php");
        } else {
            header("Location: $referer");
        }
    } else {
        // Personel bulunamadı
        SessionManager::setMessage('error', 'TC Kimlik No ile eşleşen personel bulunamadı!');
        header("Location: $referer");
    }
    
} catch (Exception $e) {
    error_log("Arama hatası: " . $e->getMessage());
    SessionManager::setMessage('error', 'Arama işlemi sırasında bir hata oluştu!');
    header("Location: $referer");
}
exit;