<?php
/**
 * Personel Takip Sistemi - Anasayfa
 *
 * Bu dosya, sadece Anasayfa yapısını içerir.
 *
 * @version 1.1
 * @author Fatih KARABULUT
 * @license MIT
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::requireAuth();

$username = SessionManager::getUsername();

// Arama geçmişi temizleme
if (!empty($_GET['clear_all'])) {
    unset($_SESSION['recent_searches']);
    header("Location: dashboard_Anasayfa.php");
    exit;
}

require_once __DIR__ . '/config/db_config.php';
$db = Database::getInstance();

// Gün bilgisi
$gunler = [
    'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe', 'Friday' => 'Cuma',
    'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
];

$turkceGun = $gunler[date('l')] ?? date('l');

// İstatistikleri hesapla (footer için gerekli!)
try {

    // Toplam aktif personel sayısı
    $stats['total_personel'] = (int)$db
        ->query("SELECT COUNT(*) FROM personel WHERE aktif = 1")
        ->fetchColumn();

    // Bugün eklenen personeller
    $stats['today_added'] = (int)$db
        ->query("SELECT COUNT(*) FROM personel WHERE DATE(eklenme_tarihi) = CURDATE()")
        ->fetchColumn();

} catch (Exception $e) {
    error_log("Dashboard istatistik hatası: " . $e->getMessage());
    $stats = ['total_personel' => 0, 'today_added' => 0];
}

// Footer'a aktarılacak
$footer_stats = $stats;
?>



<?php include 'head.php'; ?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<?php include 'content.php'; ?> <!-- Sadece içerik çağrılıyor -->

<?php include 'footer.php'; ?>