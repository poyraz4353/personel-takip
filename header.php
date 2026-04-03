<?php
/**
 * Personel Takip Sistemi - Header
 *
 * Bu dosya, sadece header HTML yapısını içerir.
 *
 * @version 1.1
 * @author Fatih KARABULUT
 * @license MIT
 */

require_once __DIR__ . '/config/session_manager.php';

// Kullanıcı adını güvenli şekilde tanımla (hata ve uyarı çıkmasın diye)
$username = $_SESSION['kullanici_adi'] ?? 'Kullanıcı';
?>



<body>

<!-- header.php -->
<div class="header">
    <div class="container-fluid d-flex align-items-center justify-content-between h-100">
        <!-- Sol taraf – Sistem adı -->
        <div class="header-title">
            <h1 class="mb-0">
                <i class="bi bi-building me-2"></i> Personel Takip Sistemi
            </h1>
        </div>

        <!-- Sağ taraf – Hoş geldiniz + Çıkış (tam istediğin gibi) -->
        <div class="user-info d-flex align-items-center gap-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle me-2"></i>
                <div>
                    <small class="d-block text-muted">Hoş geldiniz,</small>
                    <strong><?= htmlspecialchars(SessionManager::getUsername()) ?></strong>
                </div>
            </div>

            <!-- Çıkış – ince kırmızı çerçeveli, beyaz arka plan -->
            <a href="logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Çıkış
            </a>
        </div>
    </div>
</div>


