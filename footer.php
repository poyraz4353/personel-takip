<?php
/**
 * Personel Takip Sistemi - Footer
 * 
 * @version 1.2
 * @author Fatih
 * @license MIT
 */
?>

<!-- JavaScript'ler BODY SONUNDA -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Genel JavaScript dosyaları (TÜM SAYFALARDA) -->
<script src="assets/js/general.js?v=<?= time() ?>"></script>
<script src="assets/js/sidebar.js?v=<?= time() ?>"></script>

<!-- Sayfaya özel JavaScript -->
<?php 
// Mevcut sayfayı al
$currentPage = basename($_SERVER['PHP_SELF']);

// Sayfaya özel JS dosyaları
$pageSpecificJS = [
    'kimlik_bilgileri.php' => 'kimlik_bilgileri.js',
    'gorev_kaydi.php' => 'gorev_kaydi.js',
    // Diğer sayfalarınızı buraya ekleyin
];

if (isset($pageSpecificJS[$currentPage])) {
    echo '<script src="assets/js/' . $pageSpecificJS[$currentPage] . '?v=' . time() . '"></script>';
}
?>

</body>
</html>