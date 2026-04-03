<?php
/**
 * Personel Takip Sistemi - Dashboard - dashboard.php
 *
 * Bu dosya, sadece Dashboard Sayfası yapısını içerir.
 *
 * @version 1.1
 * @author Fatih KARABULUT
 * @license MIT
 */

require_once __DIR__ . '/config/session_manager.php';
SessionManager::requireAuth();

$username = SessionManager::getUsername();

// EDGE tarayıcı kontrolü EKLENDİ
$is_edge = (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Edg') !== false);

// Arama geçmişi temizleme
if (!empty($_GET['clear_all'])) {
    unset($_SESSION['recent_searches']);
    header("Location: dashboard_Anasayfa.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
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
		->query("SELECT COUNT(*) FROM personel WHERE durum = 'aktif'") // 'aktif = 1' yerine 'durum = aktif'
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


 <head>
   <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

	<style>
		/* 1. Genel Sayfa Ayarları */
		body { 
			background-color: #f0f4f8; 
			margin: 0; 
			padding: 0; 
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}
		
		.logo { font-size: 1.5rem; font-weight: bold; }

		/* 2. Navigasyon Şeridi (Dış Kutu) */
		.nav-container {
			background-color: #f8f9fa;
			border-bottom: 1px solid #dee2e6;
			width: 100%;
			margin-top: 67px;    
			height: 42px;        /* Dikey denge için ideal yükseklik */
			position: relative;
			z-index: 100;
			display: block;
		}

		/* 3. Menü Öğeleri (İç Kutu) */
		.menu {
			height: 100%;        
			display: flex;       
			align-items: center; 
			gap: 20px;           
			padding: 0 20px;     
		}

		/* 4. Menü Link Stilleri (Yazı Boyutu Modüllerle Eşitlendi) */
		.menu a {
			font-size: 0.90rem;  /* Sistem Modülleri ile aynı boyut */
			font-weight: 600;
			color: #004080;
			text-decoration: none;
			display: flex;       
			align-items: center; 
			line-height: 1;      
			transition: 0.2s ease;
		}

		.menu a:hover {
			text-decoration: underline;
			color: #007bff;
		}

		.menu a span, .menu a i {
			margin-right: 6px;
			font-size: 1rem;
			display: inline-flex;
			align-items: center;
		}

		/* 5. Sistem Modülleri (Kartlar) */
		.card {
			background: linear-gradient(135deg, #e0f7fa, #ffffff);
			border: none; 
			border-radius: 15px;
			transition: 0.3s;
			box-shadow: 0 4px 10px rgba(0,0,0,0.1);
		}
		
		.card:hover {
			transform: scale(1.05);
			box-shadow: 0 6px 15px rgba(0,0,0,0.2);
		}
		
		.card-icon { font-size: 2.3rem; margin-top: 10px; }

		/* Kart Başlığı (Menü Öğeleri ile Eşitlendi) */
		.card-title { 
			font-size: 0.90rem;  /* Menü ile tam olarak aynı boyut */
			font-weight: 600; 
			color: #003366; 
		}

		/* 6. Bildirimler ve Alertler */
		.auto-hide-alert {
			position: fixed;
			top: 85px; 
			left: 50%;
			transform: translateX(-50%);
			z-index: 9999;
			min-width: 300px;
			border-radius: 12px;
			box-shadow: 0 8px 20px rgba(0,0,0,0.2);
			padding: 20px 25px;
			display: flex;
			flex-direction: column;
			gap: 8px;
			animation: slideDown 0.5s ease-out;
		}
		.auto-hide-alert.success { background-color: #d4edda; color: #155724; border: 2px solid #c3e6cb; }

		/* 7. Animasyonlar */
		@keyframes progressBar { from { width: 100%; } to { width: 0%; } }
		@keyframes slideDown {
			from { opacity: 0; transform: translate(-50%, -20px); }
			to { opacity: 1; transform: translate(-50% , 0); }
		}
	</style>

</head>

<body>

    <?php if ($is_edge): ?>
    <div class="edge-warning text-center">
        <i class="bi bi-info-circle"></i> Edge tarayıcı tespit edildi. Session problemleri yaşarsanız önbelleği temizleyin.
    </div>
    <?php endif; ?>

	<?php if (!empty($_SESSION['login_success'])): ?>
	<?php
		$alertType = 'success';
		$alertMessage = $_SESSION['login_success'];
		$alertIcon = 'fa-check-circle';
		$alertTitle = 'Başarılı!';
	?>
	<div class="alert auto-hide-alert <?= $alertType ?>" id="autoHideAlert" role="alert">
		<div class="alert-icon">
			<i class="fas <?= $alertIcon ?>"></i>
		</div>
		<div class="alert-title"><?= $alertTitle ?></div>
		<div class="alert-message"><?= $alertMessage ?></div>
		<div class="progress-container">
			<div class="progress-bar"></div>
		</div>
	</div>
	<?php unset($_SESSION['login_success']); ?>
	<?php endif; ?>

	<nav class="nav-container">
		<div class="container-fluid px-4 menu">
			<a href="dashboard_Anasayfa.php">🏠 Anasayfa</a>
			<a href="personel_ekle.php">👤 Personel Ekle</a>
			<a href="personel_listesi.php">👥 Personel Listesi</a>
			<a href="dosya-gonder.php">📤 Dosya Gönderme</a>
			<a href="dosya-teslim.php">📥 Dosya Teslim Alma</a>
		</div>
	</nav>

    <main class="container mt-4">
        <h2 class="text-primary mb-4">📊 Sistem Modülleri</h2>

        <div class="row row-cols-2 row-cols-md-4 g-4">

            <?php
            $modules = [
                ["dashboard_Anasayfa.php", "bi-house-door-fill", "text-primary", "Anasayfa"],
                ["personel_listesi.php", "bi-file-earmark-text-fill", "text-success", "Personel Listesi"],
                ["personel_ekle.php", "bi-person-fill", "text-info", "Personel Ekle"],
                ["#", "bi-globe", "text-info", "e-Porta"],
                ["#", "bi-robot", "text-danger", "Halk Robot"],
                ["#", "bi-map", "text-secondary", "MEB-CBS"],
                ["#", "bi-mortarboard-fill", "text-primary", "EBA"],
                ["#", "bi-bar-chart-fill", "text-success", "TBTYS"],
                ["#", "bi-book-fill", "text-warning", "e-Yay"],
                ["#", "bi-person-fill", "text-info", "YETİŞKİN"],
                ["dosya-gonder.php", "bi-folder-fill", "text-danger", "Dosya Gönderme"],
                ["dosya-teslim.php", "bi-folder-fill", "text-danger", "Dosya Teslim Alma"]
            ];

            foreach ($modules as $m):
            ?>
            <div class="col">
                <a href="<?= $m[0] ?>" class="text-decoration-none">
                    <div class="card text-center h-100">
                        <i class="bi <?= $m[1] ?> <?= $m[2] ?> card-icon"></i>
                        <div class="card-body">
                            <div class="card-title"><?= $m[3] ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            console.log("Dashboard hazır - Kullanıcı: <?= $_SESSION['username'] ?>");

            if (navigator.userAgent.includes("Edg")) {
                document.querySelector('.edge-warning').style.display = 'block';
            }

            const alertEl = document.getElementById('autoHideAlert');
            if (alertEl) {
                setTimeout(() => {
                    alertEl.style.transition = "opacity 0.5s ease-out, transform 0.5s ease-out";
                    alertEl.style.opacity = 0;
                    alertEl.style.transform = "translate(-50%, -20px)";
                    setTimeout(() => alertEl.remove(), 500);
                }, 6000); // 6 saniye
            }
        });
    </script>


<?php include 'footer.php'; ?>

</body>


