<?php
// content.php
// TÜRKÇE GÜN ADI - TÜM SAYFALAR İÇİN ORTAK TANIM
if (!isset($turkceGun)) {
    $gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    $turkceGun = $gunler[date('w')];
}
?>

<div class="content_wrapper">

    <!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard_Anasayfa.php' ? 'active' : '' ?>">
            <a href="dashboard_Anasayfa.php?clear_all=1">
                <i class="bi bi-house-door-fill"></i> Anasayfa
            </a>
        </li>
        <?php if(basename($_SERVER['PHP_SELF']) != 'dashboard_Anasayfa.php'): ?>
            <?php if(isset($pageTitle)): ?>
                <li class="breadcrumb-item active">
                    <i class="bi bi-<?= $pageIcon ?? 'person-vcard' ?>"></i> 
                    <?= $pageTitle ?>
                </li>
            <?php endif; ?>
            <?php if(isset($personel) && !empty($personel['ad_soyadi'])): ?>
                <li class="breadcrumb-item active person-name">
                    <?= htmlspecialchars($personel['ad_soyadi']) ?>
                </li>
            <?php endif; ?>
        <?php endif; ?>
    </ol>
</nav>
    
    <!-- Hoş Geldiniz + Tarih -->
    <div class="welcome-section">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $welcomeMessage ?? 'Personel Takip Anasayfa\'sındasınız. Personel Arama yaparak diğer menülere ulaşabilirsiniz.' ?></h5>
            <div class="tarih-kutusu">
                <div class="tarih"><?= date('d.m.Y') ?></div>
                <div class="gun"><?= $turkceGun ?></div>
            </div>
        </div>
    </div>

    <!-- İçerik Alanı -->
    <div class="content">
        <?php 
        // $content değişkeni ile hangi içeriğin yükleneceği belirlenecek
        if (isset($content)) {
            include $content;
        } else {
            // Varsayılan içerik (anasyafa)
            ?>
            <!-- Personel Arama Kartı -->
            <div class="card personel-card mb-4">
                <div class="personel-card-header">
                    <h5 class="mb-0">Personel Kimlik Bilgileri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="no-result-container">
                        <i class="bi bi-search no-result-icon"></i>
                        <h5 class="text-muted">Personel Arama</h5>
                        <p class="text-muted">TC Kimlik No ile arama yapabilirsiniz</p>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>

</div>
<!-- content.php bitti -->