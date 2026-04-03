<?php
/**
 * KİMLİK BİLGİLERİ SİSTEMİ - Personel Takip Sistemi - kimlik_bilgileri_content.php
 * * @version 2.7
 * @author Fatih
 */
 
 // DEBUG: POST verilerini logla
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST DEBUG ===");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("TC No: " . ($tc ?? 'YOK'));
    error_log("Personel ID: " . ($personel_id ?? 'YOK'));
}

// 1. ADIM: POST İŞLEMİNİ YAKALA VE HTML ÇIKTISINI ENGELLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_kimlik'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (ob_get_length()) ob_clean();

    try {
        // 1. Token Kontrolü (Güvenlik)
        if (!isset($_POST['simple_token']) || $_POST['simple_token'] !== $simpleToken) {
            throw new Exception("Güvenlik doğrulaması başarısız.");
        }

        // 2. Verileri Al
        $p_id = $_POST['personel_id'] ?? 0;
        $cinsiyet = $_POST['cinsiyeti'] ?? '';
        $dogum_tarihi = $_POST['dogum_tarihi'] ?? null;
        $dogum_yeri = $_POST['dogum_yeri'] ?? '';
        $baba_adi = $_POST['baba_adi'] ?? '';
        $medeni_durum = $_POST['medeni_durum'] ?? '';
        $kan_grubu = $_POST['kan_grubu'] ?? '';

        // 3. Veritabanı Güncelleme (Örnektir, kendi DB yapınıza göre düzenleyin)
        // Örn: $sorgu = $db->prepare("UPDATE personel SET cinsiyeti = ?, dogum_tarihi = ?, ... WHERE id = ?");
        // $sonuc = $sorgu->execute([$cinsiyet, $dogum_tarihi, ... , $p_id]);

        // Şimdilik başarılı simülasyonu yapıyoruz
        echo json_encode([
            'status' => 'success',
            'message' => 'Kimlik bilgileri başarıyla güncellendi.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400); // Hata durumunda tarayıcıya bildir
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit; 
}
?>

    <!-- Modern Personel Kimlik Kartı -->
    <div class="modern-personel-card">
        <?php if (!empty($search_error)): ?>
            <div class="alert alert-danger modern-alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $search_error ?>
            </div>
        <?php elseif ($personel !== null): ?>
            <!-- Kimlik Bilgileri Header -->
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-avatar">
                        <?= getPersonelPhoto($personel['foto'] ?? '') ?>
                    </div>
                    <div class="personel-basic-info">
                        <h1 class="personel-name">Kimlik Bilgileri</h1>
                        <p class="personel-title"><?= htmlspecialchars($personel['ad_soyadi'] ?? '') ?></p>
                        <div class="personel-meta">
                            <span class="status-badge badge-<?= ($personel['gorev_durum'] ?? '') === 'Aktif' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($personel['gorev_durum'] ?? 'Belirsiz') ?>
                            </span>
                            <span class="meta-item">
                                <i class="bi bi-person-badge"></i>
                                TC: <?= htmlspecialchars($personel['tc_no'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn-action btn-success" title="Düzenle" onclick="acDuzenleModali()">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-action btn-danger" title="Yazdır" onclick="yazdirKimlik()">
                            <i class="bi bi-printer"></i>
                        </button>
                        <button class="btn-action btn-info" title="Fotoğraf Yükle" onclick="acFotoYukleModali()">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- KİMLİK BİLGİLERİ FORMU - Gizli/Görünür durumda -->
            <div id="kimlikBilgileriFormu" class="modern-personel-card mt-4" style="display: none;">
                <form id="kimlikForm" onsubmit="return kaydetKimlik();">
                    <!-- CSRF Token -->
                    <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
                    <input type="hidden" name="kaydet_kimlik" value="1">
                    <input type="hidden" name="personel_id" value="<?= $personel_id ?>">

                    <div class="personel-header">
                        <div class="header-background"></div>
                        <div class="header-content">
                            <div class="personel-basic-info">
                                <h1 class="personel-name">Kimlik Bilgilerini Düzenle</h1>
                            </div>
                            <div class="header-actions">
                                <button type="button" class="btn-action btn-secondary" title="Kapat" onclick="kapatForm()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form Mesajları Container -->
                    <div id="formMessages" class="form-messages-container"></div>
                    
                    <div class="personel-content">
                        <div class="content-grid">
                            <!-- Temel Kimlik Bilgileri -->
                            <div class="info-card">
                                <div class="card-header">
                                    <i class="bi bi-person-vcard"></i>
                                    <h3>Temel Kimlik Bilgileri</h3>
                                </div>
								<div class="card-body">
									<div class="horizontal-form-grid">
										<div class="form-group-horizontal">
											<label class="form-label">T.C. Kimlik No</label>
											<input type="text" class="form-control" 
												   value="<?= htmlspecialchars($personel['tc_no'] ?? '') ?>" 
												   readonly
												   name="tc_no">  <!-- BU SATIRI EKLEYİN -->
										</div>

										<div class="form-group-horizontal">
											<label class="form-label">Ad Soyad</label>
											<input type="text" class="form-control" 
												   value="<?= htmlspecialchars($personel['ad_soyadi'] ?? '') ?>" 
												   readonly
												   name="ad_soyadi">  <!-- BU SATIRI EKLEYİN -->
										</div>
		
                                        <div class="form-group-horizontal">
                                            <label class="form-label">Cinsiyet</label>
                                            <select name="cinsiyeti" class="form-select">
                                                <option value="">Seçiniz</option>
                                                <option value="Erkek" <?= ($personel['cinsiyeti'] ?? '') === 'Erkek' ? 'selected' : '' ?>>Erkek</option>
                                                <option value="Kadın" <?= ($personel['cinsiyeti'] ?? '') === 'Kadın' ? 'selected' : '' ?>>Kadın</option>
                                            </select>
                                        </div>

                                        <div class="form-group-horizontal">
                                            <label class="form-label">Doğum Tarihi</label>
                                            <input type="date" name="dogum_tarihi" class="form-control" 
                                                   value="<?= !empty($personel['dogum_tarihi']) ? date('Y-m-d', strtotime($personel['dogum_tarihi'])) : '' ?>">
                                        </div>

                                        <div class="form-group-horizontal">
                                            <label class="form-label">Doğum Yeri</label>
                                            <input type="text" name="dogum_yeri" class="form-control" 
                                                   value="<?= htmlspecialchars($personel['dogum_yeri'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aile Bilgileri -->
                            <div class="info-card">
                                <div class="card-header">
                                    <i class="bi bi-people"></i>
                                    <h3>Aile Bilgileri</h3>
                                </div>
                                <div class="card-body">
                                    <div class="horizontal-form-grid">
                                        <div class="form-group-horizontal">
                                            <label class="form-label">Baba Adı</label>
                                            <input type="text" name="baba_adi" class="form-control" 
                                                   value="<?= htmlspecialchars($personel['baba_adi'] ?? '') ?>">
                                        </div>
                                        <div class="form-group-horizontal">
                                            <label class="form-label">Medeni Hal</label>
                                            <select name="medeni_durum" class="form-select">
                                                <option value="">Seçiniz</option>
                                                <option value="Evli" <?= ($personel['medeni_durum'] ?? '') === 'Evli' ? 'selected' : '' ?>>Evli</option>
                                                <option value="Bekar" <?= ($personel['medeni_durum'] ?? '') === 'Bekar' ? 'selected' : '' ?>>Bekar</option>
                                                <option value="Dul" <?= ($personel['medeni_durum'] ?? '') === 'Dul' ? 'selected' : '' ?>>Dul</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Diğer Bilgiler -->
                            <div class="info-card">
                                <div class="card-header">
                                    <i class="bi bi-info-circle"></i>
                                    <h3>Diğer Bilgiler</h3>
                                </div>
                                <div class="card-body">
                                    <div class="horizontal-form-grid">
                                        <div class="form-group-horizontal">
                                            <label class="form-label">Kan Grubu</label>
                                            <select name="kan_grubu" class="form-select">
                                                <option value="">Seçiniz</option>
                                                <option value="A Rh+" <?= ($personel['kan_grubu'] ?? '') === 'A Rh+' ? 'selected' : '' ?>>A Rh+</option>
                                                <option value="A Rh-" <?= ($personel['kan_grubu'] ?? '') === 'A Rh-' ? 'selected' : '' ?>>A Rh-</option>
                                                <option value="B Rh+" <?= ($personel['kan_grubu'] ?? '') === 'B Rh+' ? 'selected' : '' ?>>B Rh+</option>
                                                <option value="B Rh-" <?= ($personel['kan_grubu'] ?? '') === 'B Rh-' ? 'selected' : '' ?>>B Rh-</option>
                                                <option value="AB Rh+" <?= ($personel['kan_grubu'] ?? '') === 'AB Rh+' ? 'selected' : '' ?>>AB Rh+</option>
                                                <option value="AB Rh-" <?= ($personel['kan_grubu'] ?? '') === 'AB Rh-' ? 'selected' : '' ?>>AB Rh-</option>
                                                <option value="0 Rh+" <?= ($personel['kan_grubu'] ?? '') === '0 Rh+' ? 'selected' : '' ?>>0 Rh+</option>
                                                <option value="0 Rh-" <?= ($personel['kan_grubu'] ?? '') === '0 Rh-' ? 'selected' : '' ?>>0 Rh-</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Butonları -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" onclick="kapatForm()">
                                <i class="bi bi-x-lg me-2"></i>İptal
                            </button>
                            <button type="button" class="btn btn-primary" id="submitButton" onclick="kaydetKimlik()">
                                <i class="bi bi-floppy me-2"></i>Kaydet
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- KİMLİK BİLGİLERİ GÖSTERİM ALANI -->
            <div class="personel-content">
                <div class="content-grid">
                    <!-- Kimlik Bilgileri -->
                    <div class="info-card">
                        <div class="card-header">
                            <i class="bi bi-person-vcard"></i>
                            <h3>Kimlik Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-pairs">
                                <div class="info-pair">
                                    <span class="info-label">T.C. Kimlik No</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['tc_no'] ?? '') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Cinsiyet</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['cinsiyeti'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Doğum Tarihi</span>
                                    <span class="info-value">
                                        <?= !empty($personel['dogum_tarihi']) ? date('d.m.Y', strtotime($personel['dogum_tarihi'])) : 'Bilgi Yok' ?>
                                    </span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Doğum Yeri</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['dogum_yeri'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Medeni Hal</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['medeni_durum'] ?? 'Bilgi Yok') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aile Bilgileri -->
                    <div class="info-card">
                        <div class="card-header">
                            <i class="bi bi-people"></i>
                            <h3>Aile Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-pairs">
                                <div class="info-pair">
                                    <span class="info-label">Baba Adı</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['baba_adi'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Kan Grubu</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['kan_grubu'] ?? 'Bilgi Yok') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İletişim Bilgileri -->
                    <div class="info-card">
                        <div class="card-header">
                            <i class="bi bi-telephone"></i>
                            <h3>İletişim Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-pairs">
                                <div class="info-pair">
                                    <span class="info-label">Telefon</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['telefon'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">E-posta</span>
                                    <span class="info-value">
                                        <?php if (!empty($personel['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($personel['email']) ?>" class="email-link">
                                                <?= htmlspecialchars($personel['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            Bilgi Yok
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Ev Adresi</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['ev_adresi'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">İkametgah</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['ikametgah_adresi'] ?? 'Bilgi Yok') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Görev Bilgileri -->
                    <div class="info-card">
                        <div class="card-header">
                            <i class="bi bi-briefcase"></i>
                            <h3>Görev Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-pairs">
                                <div class="info-pair">
                                    <span class="info-label">Görev Yeri</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['gorev_yeri'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Kurum Kodu</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['kurum_kodu'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">İl / İlçe</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($personel['gorev_il_adi'] ?? 'Bilgi Yok') ?> / 
                                        <?= htmlspecialchars($personel['gorev_ilce_adi'] ?? 'Bilgi Yok') ?>
                                    </span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Görev Başlama</span>
                                    <span class="info-value">
                                        <?= !empty($personel['baslama_tarihi']) ? date('d.m.Y', strtotime($personel['baslama_tarihi'])) : 'Bilgi Yok' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eğitim Bilgileri -->
                    <div class="info-card">
                        <div class="card-header">
                            <i class="bi bi-mortarboard"></i>
                            <h3>Eğitim Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-pairs">
                                <div class="info-pair">
                                    <span class="info-label">Eğitim Durumu</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['egitim_durumu'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Üniversite</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['universite'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Fakülte</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['fakulte'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Bölüm</span>
                                    <span class="info-value"><?= htmlspecialchars($personel['bolum'] ?? 'Bilgi Yok') ?></span>
                                </div>
                                <div class="info-pair">
                                    <span class="info-label">Mezuniyet Tarihi</span>
                                    <span class="info-value">
                                        <?= !empty($personel['mezuniyet_tarihi']) ? date('d.m.Y', strtotime($personel['mezuniyet_tarihi'])) : 'Bilgi Yok' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ALT BİLGİ BÖLÜMÜ -->
            <div class="personel-footer">
                <div class="footer-content">
                    <div class="system-info">
                        <div class="system-item">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Kayıt Tarihi: <?= !empty($personel['kayit_tarihi']) ? date('d.m.Y H:i', strtotime($personel['kayit_tarihi'])) : 'Bilgi Yok' ?></span>
                        </div>
                        <div class="system-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>Son Güncelleme: <?= !empty($personel['guncelleme_tarihi']) ? date('d.m.Y H:i', strtotime($personel['guncelleme_tarihi'])) : 'Bilgi Yok' ?></span>
                        </div>
                        <div class="system-item">
                            <i class="bi bi-archive"></i>
                            <span>Arşiv: <?= htmlspecialchars($personel['raf_no'] ?? '') ?>-<?= htmlspecialchars($personel['arsiv_no'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Arama Boş State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3>Personel Arama</h3>
                <p>TC Kimlik No ile personel arayabilirsiniz</p>
            </div>
        <?php endif; ?>
    </div>


<!-- FOTOĞRAF YÜKLEME MODALI - BASİT VERSİYON -->
<div class="modal fade" id="fotoYukleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera me-2"></i>Fotoğraf Yükle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Mesajlar -->
                <div id="fotoUploadMessages" class="mb-3"></div>
                
                <!-- Personel Bilgisi -->
                <div class="personel-info-card mb-4 p-3 rounded" style="background: #f8f9fa;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div id="currentPhotoPreview" class="photo-preview-current" style="width: 60px; height: 60px;">
                                <?= getPersonelPhoto($personel['foto'] ?? '') ?>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($personel['ad_soyadi'] ?? '') ?></h6>
                            <p class="text-muted mb-0">TC: <?= htmlspecialchars($personel['tc_no'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Yükleme Formu -->
                <form id="fotoYukleForm" enctype="multipart/form-data">
                    <input type="hidden" name="personel_id" id="modalPersonelId" value="<?= $personel_id ?>">
                    
                    <!-- Dosya Seçme -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fotoğraf Seçin</label>
                        <input type="file" class="form-control" id="fotoInput" name="foto" 
                               accept=".jpg,.jpeg,.png,.gif,.webp" required>
                        <div class="form-text">
                            JPG, PNG, GIF, WebP (Maksimum 5MB)
                        </div>
                    </div>
                    
                    <!-- Önizleme -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Önizleme</label>
                        <div id="fotoPreview" class="border rounded p-3 text-center" style="min-height: 150px; background: #f8f9fa;">
                            <div class="photo-preview-empty">
                                <i class="bi bi-image fs-1 text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">Fotoğraf seçiniz</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Butonlar -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>İptal
                        </button>
                        <button type="submit" class="btn btn-primary" id="fotoUploadBtn">
                            <i class="bi bi-upload me-2"></i>Yükle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- JavaScript -->
<script>
function yazdirKimlik() {
    window.print();
}

function kaydetKimlik() {
    const form = document.getElementById('kimlikForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitButton');
    const msgDiv = document.getElementById('formMessages');

    // Butonu kilitle
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Kaydediliyor...';

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            msgDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                location.reload(); // Veriler güncellendiği için sayfayı yenile
            }, 1500);
        } else {
            msgDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-floppy me-2"></i>Kaydet';
        }
    })
    .catch(error => {
        console.error('Hata:', error);
        msgDiv.innerHTML = `<div class="alert alert-danger">Sistem hatası oluştu!</div>`;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-floppy me-2"></i>Kaydet';
    });

    return false; // Formun normal submit olmasını engelle
}

// Formu açıp kapatan yardımcı fonksiyonlar
function acDuzenleModali() {
    document.getElementById('kimlikBilgileriFormu').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function kapatForm() {
    document.getElementById('kimlikBilgileriFormu').style.display = 'none';
}
</script>
