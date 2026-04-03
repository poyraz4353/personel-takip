<?php
/**
 * Personel Takip Sistemi - Personel Listesi - personel_listesi.php
 *
 * Bu dosya, sadece Personel Listesi yapısını içerir.
 *
 * @version 1.4 (Sıralama düzeltildi)
 * @author Fatih KARABULUT
 * @license MIT
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/database.php';

$personeller = [];
$istatistikler = [];
$total = 0;

// Değişkenleri try dışında tanımlayalım
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// SIRALAMA DEĞİŞKENLERİ - DÜZELTİLDİ
$orderBy = $_GET['orderby'] ?? 'p.id';
$orderDir = strtoupper($_GET['orderdir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// İzin verilen sütunlar - GÜNCELLENDİ
$allowedColumns = [
    'p.id' => 'p.id',
    'p.ad_soyadi' => 'p.ad_soyadi', 
    'p.tc_no' => 'p.tc_no', 
    'p.kayit_tarihi' => 'p.kayit_tarihi'
];

// Güvenlik: Sadece izin verilen sütunlarla sıralama yap
if (!array_key_exists($orderBy, $allowedColumns)) {
    $orderBy = 'p.id';
}

try {
    // Yeni Singleton yapısına geçiş
    $db = Database::getInstance();

    // 1. ARAMA KOŞULU
    $params = [];
    $searchCondition = '';
    if ($search !== '') {
        $searchCondition = " WHERE (p.ad_soyadi LIKE ? OR p.tc_no LIKE ?)";
        $params = ["%$search%", "%$search%"];
    }

	// 2. BASE SQL YAPISI - SADECE PERSONEL TABLOSU (JOIN'LER KALDIRILDI)
	$baseSql = "FROM personel p";

	// Toplam kayıt sayısını al
	$countSql = "SELECT COUNT(DISTINCT p.id) $baseSql $searchCondition";
	$total = (int)$db->fetchColumn($countSql, $params);

	// 3. ANA LİSTE SORGUSU - SADECE PERSONEL TABLOSUNDAN, ALT SORGULAR İLE DİĞER BİLGİLER
	$sql = "
		SELECT 
			p.id, p.ad_soyadi, p.tc_no, p.foto_path, p.kayit_tarihi,
			
			/* personel_kimlik - en son kayıt */
			(SELECT cinsiyeti FROM personel_kimlik WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as cinsiyeti,
			(SELECT dogum_tarihi FROM personel_kimlik WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as dogum_tarihi,
			
			/* personel_iletisim - en son kayıt */
			(SELECT telefon FROM personel_iletisim WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as telefon,
			(SELECT email FROM personel_iletisim WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as email,
			
			/* personel_kadro - en son kayıt */
			(SELECT aylik_derece FROM personel_kadro WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as aylik_derece,
			(SELECT aylik_kademe FROM personel_kadro WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as aylik_kademe,
			(SELECT kadro_derecesi FROM personel_kadro WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as kadro_derecesi,
			
			/* En son görev kaydındaki bilgiler */
			(SELECT istihdam_tipi FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as istihdam_tipi,
			(SELECT kadro_unvani FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as kadro_unvani,
			(SELECT gorev_unvani FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as gorev_unvani,
			(SELECT memuriyete_baslama_tarihi FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as memuriyete_baslama_tarihi,
			(SELECT gorev_okul_adi FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1) as gorev_okul_adi,
			
			/* Durum bilgisi */
			COALESCE(
				(SELECT durum_adi FROM durumu WHERE id = (
					SELECT durum FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1
				)),
				(SELECT durum FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1),
				'Belirtilmemiş'
			) AS durum_adi,
			
			COALESCE(
				(SELECT renk FROM durumu WHERE id = (
					SELECT durum FROM personel_gorev WHERE personel_id = p.id ORDER BY id DESC LIMIT 1
				)),
				'bg-secondary text-white'
			) AS durum_renk
			
		FROM personel p
		$searchCondition
		ORDER BY $orderBy $orderDir
		LIMIT $offset, $perPage
	";

    $personeller = $db->fetchAll($sql, $params);

    // 4. DURUM İSTATİSTİKLERİ SORGUSU
    $statsSql = "
        SELECT 
            COALESCE(d.durum_adi, 'Belirtilmemiş') AS durum_adi,
            COALESCE(d.renk, 'bg-secondary text-white') AS durum_renk,
            COUNT(p.id) as toplam
        FROM personel p
        LEFT JOIN (
            SELECT personel_id, durum FROM personel_gorev pg1
            WHERE id = (SELECT MAX(id) FROM personel_gorev pg2 WHERE pg2.personel_id = pg1.personel_id)
        ) son_gorev ON p.id = son_gorev.personel_id
        LEFT JOIN durumu d ON son_gorev.durum = d.id OR son_gorev.durum = d.durum_adi
        GROUP BY durum_adi, durum_renk
    ";
    $ham_istatistikler = $db->fetchAll($statsSql);

    $istatistikler = [];

    // EN BAŞA TOPLAM PERSONEL KARTINI EKLE
    $istatistikler['toplam_genel'] = [
        'durum_adi' => 'TOPLAM PERSONEL',
        'durum_renk' => 'bg-dark text-white',
        'toplam' => $total
    ];

    foreach ($ham_istatistikler as $stat) {
        $temiz_ad = trim(preg_replace('/\s*\([^)]*\)/', '', $stat['durum_adi']));
        
        if (!isset($istatistikler[$temiz_ad])) {
            $istatistikler[$temiz_ad] = [
                'durum_adi' => $temiz_ad,
                'durum_renk' => $stat['durum_renk'],
                'toplam' => 0
            ];
        }
        $istatistikler[$temiz_ad]['toplam'] += $stat['toplam'];
    }

} catch (Throwable $e) {
    error_log("Personel Listesi Hatası: " . $e->getMessage());
    $_SESSION['error'] = "Veritabanı Hatası: " . $e->getMessage();
}

// YARDIMCI FONKSİYONLAR
function calculateAge($birthDate) {
    if (empty($birthDate) || $birthDate === '0000-00-00') return '?';
    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birth);
        return $age->y;
    } catch (Exception $e) { return '?'; }
}

function formatPhoneNumber($phone) {
    if (empty($phone)) return '-';
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (substr($clean, 0, 1) === '0') { $clean = substr($clean, 1); }
    if (strlen($clean) === 10) {
        return '(' . substr($clean, 0, 3) . ') ' . substr($clean, 3, 3) . ' ' . substr($clean, 6, 2) . ' ' . substr($clean, 8, 2);
    }
    return $phone;
}
?>

<?php include 'head.php'; ?>
<?php include 'header.php'; ?>

<style>
    body { 
        padding-top: 110px !important; 
        font-size: 0.9rem;
    }
    .search-form { 
        display: flex; gap: 0.5rem; 
        flex-grow: 1; 
        max-width: 500px; 
    }
    .search-form .form-control { 
        width: 100%; 
        font-size: 0.9rem;
    }
    .add-personnel-btn { 
        white-space: nowrap; 
        font-size: 0.9rem;
    }

    .col-id {
        width: 40px !important; text-align: center; 
    }
    
    .col-ad-soyad { 
        white-space: nowrap !important;
        min-width: 180px;
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .table {
        border-collapse: separate !important;
        border-spacing: 0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        font-size: 0.82rem;
    }

    .table th,
    .table td {
        border: 1px solid #d0d0d0 !important;
        padding: 6px 4px !important;
        vertical-align: middle;
    }

    .table thead th {
        background-color: #000000 !important;
        color: white !important;
        border-color: #1a252f !important;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .table tbody tr:hover {
        background-color: #e3f2fd !important;
        transition: background-color 0.2s ease;
    }

    .sortable {
        cursor: pointer;
        position: relative;
        user-select: none;
    }
    
    .sortable:hover {
        background-color: #2c3e50 !important;
    }
    
    .sortable::after {
        content: "↕️";
        font-size: 0.7rem;
        margin-left: 5px;
        opacity: 0.6;
    }
    
    .sortable.asc::after {
        content: "↑";
        opacity: 1;
    }
    
    .sortable.desc::after {
        content: "↓";
        opacity: 1;
    }

    .td-long-text {
        max-width: 353px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        font-size: 0.85rem;
    }

    .foto-link {
        display: inline-block;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border-radius: 50%;
    }

    .col-foto {
        width: 50px !important; text-align: center;
    }
        
    .foto-link:hover {
        transform: scale(1.15);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    .foto-circle {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.25);
        transition: all 0.3s ease;
    }

    .no-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0,0,0,0.25);
    }

    .search-header {
        display: flex; align-items: center; 
        justify-content: space-between; 
        flex-wrap: wrap; gap: 1rem; 
        margin-bottom: 1rem;
    }
    .search-title h1 { 
        margin: 0; font-weight: 700; 
        color: #333; 
        font-size: 1.4rem;
    }
    
    .search-form {
        display: flex;
        gap: 0.5rem;
        flex-grow: 1;
        max-width: 520px;
    }
    
    @media (max-width: 768px) {
        .search-header { flex-direction: column; align-items: stretch; }
        .search-form { max-width: none; }
    }
    
    .table th {
        text-align: center;
        vertical-align: middle;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .table td:first-child,
    .table td:nth-child(2) {
        text-align: center;
    }

    .breadcrumb {
        background-color: #ffffff;
        border-radius: 5px;
        padding: 8px 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        height: 40px;
    }

    .breadcrumb-item a {
        color: #0066b3;
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: #004080;
        font-weight: 600;
    }

    .badge-erkek {
        background-color: #1e88e5 !important;
        color: white !important;
    }

    .badge-kadin {
        background-color: #ec407a !important;
        color: white !important;
    }

    .badge-belirsiz {
        background-color: #78909c !important;
        color: white !important;
    }

    /* MODERN İSTATİSTİK KARTLARI */
    .status-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        transition: all 0.3s ease;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        justify-content: center;
        width: 200px;
        min-width: 200px;
        min-height: 100px;
        margin: 10px auto;
        flex: 0 0 auto;
    }

    .status-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .status-card .status-icon {
        position: absolute;
        right: -5px;
        bottom: -5px;
        font-size: 3rem;
        opacity: 0.12;
        transform: rotate(-10deg);
    }

    .status-count {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .status-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.85;
    }
</style>

<div class="container-fluid py-4">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="dashboard_Anasayfa.php?clear_all=1">
                    <i class="bi bi-house-door-fill"></i> Ana Sayfa
                </a>
            </li>
            <li class="breadcrumb-item active">
                <i class="bi bi-person-fill"></i> Personel Listesi
            </li>
        </ol>
    </nav>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- BAŞLIK + ARAMA -->
    <div class="search-header">
        <div class="search-title">
            <h1 class="mb-0">Personel Listesi</h1>
        </div>
        <form method="get" class="search-form">
            <input type="text" name="search" class="form-control" placeholder="Ad Soyad veya TC No ile ara..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Ara</button>
            <?php if ($search): ?>
                <a href="personel_listesi.php" class="btn btn-outline-secondary">Temizle</a>
            <?php endif; ?>
        </form>
        <div>
            <a href="dashboard_Anasayfa.php" class="btn btn-primary">Anasayfa</a>
            <a href="personel_ekle.php" class="btn btn-success">Personel Ekle</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th class="col-foto">Foto</th>
                    <th class="col-id sortable" data-column="p.id">ID</th>
                    <th class="col-ad-soyad sortable" data-column="p.ad_soyadi">Adı Soyadı</th>
                    <th class="sortable" data-column="p.tc_no">T.C. No</th>
                    <th>İstihdam</th>
                    <th>Kadro Ünvanı</th>
                    <th>Görev Ünvanı</th>
                    <th width="40">K.D.</th>
                    <th width="50">D./K.</th>
                    <th>Doğum T.</th>
                    <th>Telefon</th>
                    <th>e-Posta</th>
                    <th>Mem. Baş.</th>
                    <th>Görev Yeri</th>
                    <th>Durumu</th>
                    <th>Cinsiyet</th>
                    <th>Kayıt</th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($personeller)): ?>
                    <tr><td colspan="17" class="text-center py-5">
                        <h4><?= $search ? 'Sonuç bulunamadı' : 'Kayıtlı personel yok' ?></h4>
                        <a href="personel_ekle.php" class="btn btn-primary mt-3">İlk Personeli Ekle</a>
                    </td></tr>
                <?php else: foreach ($personeller as $p): ?>
                    <tr>
                        <td class="text-center">
                            <a href="kimlik_bilgileri.php?tc_search=<?= htmlspecialchars($p['tc_no']) ?>" class="foto-link" title="<?= htmlspecialchars($p['ad_soyadi']) ?> - Detayları görüntüle">
                                <?php if (!empty($p['foto_path']) && file_exists('uploads/personel_fotolar/' . $p['foto_path'])): ?>
                                    <img src="uploads/personel_fotolar/<?= htmlspecialchars($p['foto_path']) ?>" 
                                        class="foto-circle" 
                                        alt="<?= htmlspecialchars($p['ad_soyadi']) ?>"
                                        loading="lazy">
                                <?php else: ?>
                                    <div class="no-photo" title="Fotoğraf bulunamadı">
                                        <?= mb_strtoupper(mb_substr(trim($p['ad_soyadi']), 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-center"><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['ad_soyadi']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['tc_no'] ?? '-') ?></td>
                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($p['istihdam_tipi'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars(trim(explode('(', $p['kadro_unvani'] ?? '-')[0])) ?></td>
                        <td><?= htmlspecialchars(trim(explode('(', $p['gorev_unvani'] ?? '-')[0])) ?></td>
                        <td class="text-center">
                            <?php if (!empty($p['kadro_derecesi'])): ?>
                                <span class="badge bg-info"><?= htmlspecialchars($p['kadro_derecesi']) ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($p['aylik_derece']) && !empty($p['aylik_kademe'])): ?>
                                <span class="badge bg-primary"><?= $p['aylik_derece'] ?> / <?= $p['aylik_kademe'] ?></span>
                            <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($p['dogum_tarihi']) && $p['dogum_tarihi'] !== '0000-00-00'): ?>
                                <small><?= date('d.m.Y', strtotime($p['dogum_tarihi'])) ?></small>
                                <br><small class="text-muted">(<?= calculateAge($p['dogum_tarihi']) ?> yaş)</small>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-center"><?= formatPhoneNumber($p['telefon'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($p['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($p['email']) ?>" class="text-decoration-none"><?= htmlspecialchars($p['email']) ?></a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= (!empty($p['memuriyete_baslama_tarihi']) && $p['memuriyete_baslama_tarihi'] !== '0000-00-00') 
                                ? date('d.m.Y', strtotime($p['memuriyete_baslama_tarihi'])) : '-' ?>
                        </td>
                        <td title="<?= htmlspecialchars($p['gorev_okul_adi']) ?>">
                            <div class="td-long-text">
                                <?= htmlspecialchars(trim(explode('(', explode('--', $p['gorev_okul_adi'] ?? '-')[0])[0])) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $p['durum_renk'] ?>"><?= htmlspecialchars($p['durum_adi']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($p['cinsiyeti'])): 
                                $cinsiyet = strtolower(trim($p['cinsiyeti']));
                                if (strpos($cinsiyet, 'erkek') !== false) {
                                    $badgeClass = 'badge-erkek';
                                } elseif (strpos($cinsiyet, 'kadın') !== false || strpos($cinsiyet, 'kadin') !== false) {
                                    $badgeClass = 'badge-kadin';
                                } else {
                                    $badgeClass = 'badge-belirsiz';
                                }
                            ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($p['cinsiyeti']) ?></span>
                            <?php else: ?><span class="badge badge-belirsiz">-</span><?php endif; ?>
                        </td>
                        <td class="text-center"><?= date('d.m.Y', strtotime($p['kayit_tarihi'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        
        <!-- İSTATİSTİK KARTLARI -->
        <div class="row mb-5 justify-content-center px-3">
            <?php foreach ($istatistikler as $key => $stat): 
                $icon = 'bi-person-badge';
                $label_lower = mb_strtolower($stat['durum_adi'], 'UTF-8');
                
                if($key === 'toplam_genel') $icon = 'bi-people-fill';
                elseif(strpos($label_lower, 'aktif') !== false) $icon = 'bi-check-circle-fill';
                elseif(strpos($label_lower, 'ayrılan') !== false) $icon = 'bi-person-x-fill';
                elseif(strpos($label_lower, 'izin') !== false) $icon = 'bi-pause-circle-fill';
            ?>
            <div class="d-flex justify-content-center col-auto mb-3">
                <div class="card status-card <?= $stat['durum_renk'] ?> shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="status-count"><?= $stat['toplam'] ?></div>
                        <div class="status-label"><?= htmlspecialchars($stat['durum_adi']) ?></div>
                        <i class="bi <?= $icon ?> status-icon"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- SAYFALAMA -->
    <?php if ($total > $perPage):
        $totalPages = ceil($total / $perPage); ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&orderby=<?= urlencode($orderBy) ?>&orderdir=<?= urlencode($orderDir) ?>">
                            <i class="bi bi-chevron-left"></i> Önceki
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&orderby=<?= urlencode($orderBy) ?>&orderdir=<?= urlencode($orderDir) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&orderby=<?= urlencode($orderBy) ?>&orderdir=<?= urlencode($orderDir) ?>">
                            Sonraki <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="text-center text-muted small mt-2">
                <i class="bi bi-info-circle"></i>
                Toplam <?= number_format($total, 0, ',', '.') ?> personel – 
                Sayfa <?= $page ?> / <?= $totalPages ?> – 
                Gösterilen: <?= min($perPage, count($personeller)) ?> kayıt
            </div>
        </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const currentOrderBy = '<?= $orderBy ?>';
    const currentOrderDir = '<?= $orderDir ?>';
    
    document.querySelectorAll('.sortable').forEach(th => {
        const column = th.dataset.column;
        
        // Aktif sıralama sütununa sınıf ekle
        if (column === currentOrderBy) {
            th.classList.add(currentOrderDir === 'ASC' ? 'asc' : 'desc');
        }
        
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const dir = (currentOrderBy === column && currentOrderDir === 'ASC') ? 'DESC' : 'ASC';
            const url = new URL(location);
            url.searchParams.set('orderby', column);
            url.searchParams.set('orderdir', dir);
            location = url;
        });
    });
});
</script>

</body>
</html>