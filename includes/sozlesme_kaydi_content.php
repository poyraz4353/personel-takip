<?php
/**
 * SÖZLEŞME KAYDI SİSTEMİ - Personel Takip Sistemi - sozlesme_kaydi_content.php
 * @version 1.2
 * @author Fatih
 */
?>

<style>
/* TEK SCROLLBAR - SADECE DIŞ WRAPPER'DA */
.table-wrapper {
    overflow-x: auto;
    width: 100%;
}

<!-- Sözleşme Kaydı stilleri style.css'den geliyor -->

</style>

<!-- BAŞARI MESAJI TOAST -->
<?php
$successMsg = SessionManager::getMessage('success');
if ($successMsg): ?>
<div class="toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 9999;">
    <div id="successToast" class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #d1e7dd; color: #0f5132;">
        <div class="d-flex flex-column">
            <div class="toast-header border-0" style="background-color: #d1e7dd; color: #0f5132;">
                <i class="bi bi-check-circle-fill me-2 fs-4" style="color: #0f5132;"></i>
                <strong class="me-auto fs-5" style="color: #0f5132;">Başarılı!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.2);"></button>
            </div>
            <div class="toast-body text-center py-3" style="background-color: #d1e7dd; color: #0f5132;">
                <p class="mb-3 fs-5"><?= htmlspecialchars($successMsg) ?></p>
                <div class="progress" style="height: 4px; background-color: #badbcc;">
                    <div id="toastProgressBar" class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #0f5132;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modern Personel Kimlik Kartı -->
<div class="modern-personel-card sozlesme-kaydi">
    <?php if (!empty($search_error)): ?>
        <div class="alert alert-danger modern-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $search_error ?>
        </div>
    <?php elseif ($personel !== null): ?>
    
    <!-- HATA MESAJI -->
    <?php
    $errorMsg = SessionManager::getMessage('danger');
    if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($errorMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Sözleşme Kaydı Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Sözleşme Kaydı</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Sözleşme Kaydı" onclick="yeniSozlesmeKontrol()" id="yeniSozlesmeBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
				<button class="btn-action btn-danger" 
						onclick="standartSil(document.getElementById('sozlesme_id').value, 'sozlesme', 'Sözleşme Kaydı')">
					<i class="bi bi-trash"></i>
				</button>
            </div>
        </div>
    </div>

    <!-- SÖZLEŞME KAYDI FORMU -->
    <div id="sozlesmeKaydiFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_sozlesme) ? 'block' : 'none' ?>;">
        <form method="POST" action="sozlesme_kaydi.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_sozlesme" value="1">
            <input type="hidden" name="sozlesme_id" id="sozlesme_id" value="<?= $duzenlenecek_sozlesme['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_sozlesme) ? 'Sözleşme Düzenle' : 'Yeni Sözleşme Kaydı' ?>
                        </h1>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn-action btn-secondary" title="Kapat" onclick="kapatForm()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="formMessages" class="form-messages-container"></div>
            
            <div class="personel-content">
                <div class="tab-content">
                    <div class="tab-pane fade show active" role="tabpanel">
                        
                        <!-- Kurum/Okul Bilgileri -->
                        <div class="info-card">
                            <div class="card-header">
                                <i class="bi bi-building"></i>
                                <h3>Kurum/Okul Bilgileri</h3>
                            </div>
                            <div class="card-body">
                                <div class="horizontal-form-grid">
                                    <div class="form-group-horizontal">
                                        <label class="form-label">İl</label>
                                        <select name="il_id" id="il_id" class="form-select dynamic-select" required>
                                            <option value="">İl Seçiniz</option>
                                            <?php if (!empty($iller)): ?>
                                                <?php foreach ($iller as $il): ?>
                                                    <option value="<?= $il['id'] ?>" 
                                                        <?= (isset($duzenlenecek_sozlesme) && $duzenlenecek_sozlesme['sozlesme_il_adi'] == $il['il_adi']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($il['il_adi']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group-horizontal">
                                        <label class="form-label">İlçe</label>
                                        <select name="ilce_id" id="ilce_id" class="form-select dynamic-select">
                                            <option value="">İlçe Seçiniz</option>
                                            <?php if (!empty($ilceler)): ?>
                                                <?php foreach ($ilceler as $ilce): ?>
                                                    <option value="<?= $ilce['id'] ?>" 
                                                        <?= (isset($duzenlenecek_sozlesme) && trim($duzenlenecek_sozlesme['sozlesme_ilce_adi']) == trim($ilce['ilce_adi'])) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($ilce['ilce_adi']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group-horizontal">
                                        <label class="form-label">Görev Yeri</label>
                                        <select name="gorev_kurum_kodu" id="gorev_kurum_kodu" class="form-select dynamic-select">
                                            <option value="">Okul Seçiniz</option>
                                            <?php if (!empty($okullar)): ?>
                                                <?php foreach ($okullar as $okul): ?>
                                                    <option value="<?= $okul['kurum_kodu'] ?>" 
                                                        <?= (isset($duzenlenecek_sozlesme) && $duzenlenecek_sozlesme['sozlesme_kurum_kodu'] == $okul['kurum_kodu']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($okul['gorev_yeri']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group-horizontal">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="kapali_kurum" id="kapali_kurum" value="1" class="form-check-input"
                                                <?= (isset($duzenlenecek_sozlesme) && $duzenlenecek_sozlesme['sozlesme_kapali_kurum'] == 1) ? 'checked' : '' ?>>
                                            <label for="kapali_kurum" class="form-check-label">
                                                Kapalı Kurumları Dahil Et
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sözleşme Bilgileri -->
                        <div class="info-card">
                            <div class="card-header">
                                <i class="bi bi-briefcase"></i>
                                <h3>Sözleşme Bilgileri</h3>
                            </div>
                            <div class="card-body">
                                <div class="horizontal-form-grid">
                                    <div class="form-group-horizontal">
                                        <label class="form-label">Sözleşme Türü</label>
                                        <select name="sozlesme_turu" id="sozlesme_turu" class="form-select">
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($sozlesme_turleri as $tur): ?>
                                                <option value="<?= htmlspecialchars($tur) ?>" 
                                                    <?= (isset($duzenlenecek_sozlesme) && $duzenlenecek_sozlesme['sozlesme_turu'] == $tur) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tur) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group-horizontal">
                                        <label class="form-label">Başlama Tarihi</label>
                                        <input type="date" name="baslama_tarihi" id="baslama_tarihi" class="form-control" 
                                            value="<?= $duzenlenecek_sozlesme['sozlesmeli_baslama_tarihi'] ?? '' ?>">
                                    </div>

                                    <div class="form-group-horizontal">
                                        <label class="form-label">Ayrılma Tarihi</label>
                                        <input type="date" name="bitis_tarihi" id="bitis_tarihi" class="form-control" 
                                            value="<?= $duzenlenecek_sozlesme['sozlesmeli_bitis_tarihi'] ?? '' ?>">
                                        <small class="text-muted">Devam ediyorsa boş bırakın</small>
                                    </div>

                                    <!-- AYRILMA NEDENİ (durumu tablosundan) -->
                                    <div class="form-group-horizontal">
                                        <label class="form-label">Ayrılma Nedeni</label>
                                        <select name="ayrilma_nedeni" id="ayrilma_nedeni" class="form-select">
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($durumlar as $d): ?>
                                                <option value="<?= htmlspecialchars($d['durum_adi']) ?>" 
                                                    <?= (isset($duzenlenecek_sozlesme) && $duzenlenecek_sozlesme['ayrilma_nedeni'] == $d['durum_adi']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($d['durum_adi']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Butonları -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" onclick="kapatForm()">
                                <i class="bi bi-x-lg me-2"></i>İptal
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitButton">
                                <i class="bi bi-floppy me-2"></i>Kaydet
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- SÖZLEŞME LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-briefcase"></i>
                    <h3 style="margin: 0;">Personel Sözleşme Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
                        <div class="table-header">
                            <div class="header-row">
                                <div class="header-cell"></div>
                                <div class="header-cell">Başlama Tarihi</div>
                                <div class="header-cell">Ayrılma Tarihi</div>
                                <div class="header-cell">İl</div>
                                <div class="header-cell">İlçe</div>
                                <div class="header-cell">Kurum Kodu</div>
                                <div class="header-cell">Kurum Adı</div>
                                <div class="header-cell">Sözleşme Türü</div>
                                <div class="header-cell">Ayrılma Nedeni</div>
                            </div>
                        </div>

                        <div class="table-content">
                            <?php if (!empty($sozlesmeler)): ?>
                                <?php foreach ($sozlesmeler as $sozlesme): ?>
                                <div class="content-row">
                                    <div class="content-cell">
                                        <i class="bi bi-folder-fill folder-row-icon" 
                                           onclick="sozlesmeDuzenle(<?= $sozlesme['id'] ?? 0 ?>)" 
                                           title="Sözleşmeyi Düzenle"
                                           style="cursor: pointer;">
                                        </i>
                                    </div>

                                    <div class="content-cell">
                                        <?php 
                                        $tarih = $sozlesme['sozlesmeli_baslama_tarihi'] ?? '';
                                        if (empty($tarih) || $tarih === '0000-00-00') {
                                            echo '<span style="color: #ccc;">-</span>'; 
                                        } else {
                                            echo date('d.m.Y', strtotime($tarih));
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="content-cell">
                                        <?php 
                                        $bitis = $sozlesme['sozlesmeli_bitis_tarihi'] ?? '';
                                        if (empty($bitis) || $bitis === '0000-00-00'):
                                            echo '<span class="badge bg-success">Devam Ediyor</span>';
                                        else:
                                            echo date('d.m.Y', strtotime($bitis));
                                        endif;
                                        ?>
                                    </div>

                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['sozlesme_il_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['sozlesme_ilce_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['sozlesme_kurum_kodu'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['sozlesme_okul_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['sozlesme_turu'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($sozlesme['ayrilma_nedeni'] ?? '-') ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state-table">
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <h5>Sözleşme Kaydı Bulunamadı</h5>
                                        <p class="mb-0">Bu personele ait sözleşme kaydı bulunamadı.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alt Bilgi -->
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
                    <i class="bi bi-person-badge"></i>
                    <span>Son Güncelleyen: <?= htmlspecialchars($personel['guncelleyen_kullanici'] ?? 'Bilgi Yok') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="bi bi-search"></i>
        </div>
        <h3>Personel Arama</h3>
        <p>TC Kimlik No ile personel arayabilirsiniz</p>
    </div>
    <?php endif; ?>
</div>

<script>
// =============================================================================
// SÖZLEŞME DÜZENLEME FONKSİYONU
// =============================================================================
function sozlesmeDuzenle(sozlesmeId) {
    if (!sozlesmeId || sozlesmeId === 0) {
        alert('Geçersiz sözleşme ID');
        return;
    }
    window.location.href = 'sozlesme_kaydi.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + sozlesmeId;
}

// =============================================================================
// YENİ SÖZLEŞME KAYDI
// =============================================================================
function yeniSozlesmeKontrol() {
    console.log('➕ Yeni sözleşme kaydı açılıyor...');
    
    const sozlesmeIdInput = document.getElementById('sozlesme_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#sozlesmeKaydiFormu form');
    const formArea = document.getElementById('sozlesmeKaydiFormu');
    
    if (!sozlesmeIdInput || !baslik || !form || !formArea) {
        console.error('❌ Form elementleri bulunamadı!');
        return;
    }
    
    sozlesmeIdInput.value = '';
    baslik.textContent = 'Yeni Sözleşme Kaydı';
    form.reset();
    
    setTimeout(() => {
        const ilSelect = document.getElementById('il_id');
        if (ilSelect) ilSelect.value = '';
        
        const ilceSelect = document.getElementById('ilce_id');
        if (ilceSelect) {
            ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
            ilceSelect.disabled = true;
        }
        
        const okulSelect = document.getElementById('gorev_kurum_kodu');
        if (okulSelect) {
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            okulSelect.disabled = true;
        }
        
        const kapali = document.getElementById('kapali_kurum');
        if (kapali) kapali.checked = false;
        
    }, 100);
    
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
    
    console.log('✅ Yeni sözleşme kaydı formu açıldı');
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    console.log('🔒 Form kapatılıyor...');
    
    const form = document.getElementById('sozlesmeKaydiFormu');
    if (form) {
        form.style.display = 'none';
    }
    
    const sozlesmeIdInput = document.getElementById('sozlesme_id');
    if (sozlesmeIdInput) sozlesmeIdInput.value = '';
    
    const baslik = document.getElementById('formBaslik');
    if (baslik) baslik.textContent = 'Yeni Sözleşme Kaydı';
    
    const formElement = document.querySelector('#sozlesmeKaydiFormu form');
    if (formElement) formElement.reset();
    
    console.log('✅ Form kapatıldı');
}

// =============================================================================
// STANDART SİLME FONKSİYONU - TÜM SAYFALAR İÇİN TEK
// =============================================================================
function standartSil(id, modul, itemName, onSuccess) {
    if (!id || id === 0) {
        showWarningToast('Silinecek kayıt seçilmedi.');
        return;
    }
    
    Swal.fire({
        title: 'Silme Onayı',
        html: `<p style="font-size:1.1rem; margin-bottom:10px;">Bu ${itemName} silmek istediğinize emin misiniz?</p><p style="color: #dc3545; font-weight: bold; margin-top:5px;">⚠️ Bu işlem geri alınamaz!</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i> Evet, Sil',
        cancelButtonText: '<i class="bi bi-x-lg me-1"></i> İptal',
        reverseButtons: true,
        background: '#fff3cd',
        color: '#856404',
        iconColor: '#856404'
    }).then((result) => {
        if (result.isConfirmed) {
            const silBtn = document.querySelector('.btn-action.btn-danger, .btn-sil');
            if (silBtn) {
                silBtn.disabled = true;
                silBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Siliniyor...';
            }
            
            fetch(`api/sil.php?modul=${modul}&id=${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof kapatForm === 'function') {
                        kapatForm();
                    }
                    
                    showSuccessToast(data.message || `${itemName} başarıyla silindi.`);
                    
                    setTimeout(function() {
                        if (onSuccess && typeof onSuccess === 'function') {
                            onSuccess(data);
                        } else {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('duzenle_id');
                            window.location.href = url.toString();
                        }
                    }, 2500);
                } else {
                    throw new Error(data.error || 'Silme işlemi başarısız.');
                }
            })
            .catch(error => {
                showErrorToast(error.message);
                if (silBtn) {
                    silBtn.disabled = false;
                    silBtn.innerHTML = '<i class="bi bi-trash"></i>';
                }
            });
        }
    });
}

// =============================================================================
// YEŞİL TOAST MESAJI (BAŞARI)
// =============================================================================
function showSuccessToast(message) {
    const existingToasts = document.querySelectorAll('.custom-toast-container');
    existingToasts.forEach(toast => toast.remove());
    
    const toastHtml = `
        <div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">
            <div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #d1e7dd; color: #0f5132;">
                <div class="d-flex flex-column">
                    <div class="toast-header border-0" style="background-color: #d1e7dd; color: #0f5132;">
                        <i class="bi bi-check-circle-fill me-2 fs-4" style="color: #0f5132;"></i>
                        <strong class="me-auto fs-5" style="color: #0f5132;">Başarılı!</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.2);"></button>
                    </div>
                    <div class="toast-body text-center py-3" style="background-color: #d1e7dd; color: #0f5132;">
                        <p class="mb-3 fs-5">${message}</p>
                        <div class="progress" style="height: 4px; background-color: #badbcc;">
                            <div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #0f5132;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.querySelector('.custom-toast-container .toast');
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    const progressBar = toastElement.querySelector('.progress-bar');
    setTimeout(() => { if (progressBar) progressBar.style.width = '0%'; }, 50);
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// UYARI TOAST'U (TURUNCU)
// =============================================================================
function showWarningToast(message) {
    const existingToasts = document.querySelectorAll('.custom-toast-container');
    existingToasts.forEach(toast => toast.remove());
    
    const toastHtml = `
        <div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">
            <div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #fff3cd; color: #856404;">
                <div class="d-flex flex-column">
                    <div class="toast-header border-0" style="background-color: #fff3cd; color: #856404;">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-4" style="color: #856404;"></i>
                        <strong class="me-auto fs-5" style="color: #856404;">Uyarı!</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.4);"></button>
                    </div>
                    <div class="toast-body text-center py-3" style="background-color: #fff3cd; color: #856404;">
                        <p class="mb-3 fs-5">${message}</p>
                        <div class="progress" style="height: 4px; background-color: #ffeeba;">
                            <div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #856404;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.querySelector('.custom-toast-container .toast');
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// HATA TOAST'U (KIRMIZI)
// =============================================================================
function showErrorToast(message) {
    const existingToasts = document.querySelectorAll('.custom-toast-container');
    existingToasts.forEach(toast => toast.remove());
    
    const toastHtml = `
        <div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">
            <div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #f8d7da; color: #721c24;">
                <div class="d-flex flex-column">
                    <div class="toast-header border-0" style="background-color: #f8d7da; color: #721c24;">
                        <i class="bi bi-x-circle-fill me-2 fs-4" style="color: #721c24;"></i>
                        <strong class="me-auto fs-5" style="color: #721c24;">Hata!</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.3);"></button>
                    </div>
                    <div class="toast-body text-center py-3" style="background-color: #f8d7da; color: #721c24;">
                        <p class="mb-3 fs-5">${message}</p>
                        <div class="progress" style="height: 4px; background-color: #f5c6cb;">
                            <div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #721c24;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.querySelector('.custom-toast-container .toast');
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// İL-İLÇE-OKUL ZİNCİRİ
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const ilSelect = document.getElementById('il_id');
    const ilceSelect = document.getElementById('ilce_id');
    const okulSelect = document.getElementById('gorev_kurum_kodu');
    const kapaliCheck = document.getElementById('kapali_kurum');
    
    if (!ilSelect || !ilceSelect || !okulSelect) return;
    
    let ilkYukleme = true;
    
    ilSelect.addEventListener('change', function() {
        const ilId = this.value;
        
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        ilceSelect.disabled = true;
        okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
        okulSelect.disabled = true;
        
        if (!ilId) {
            ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
            return;
        }
        
        fetch(`api/get_ilceler.php?il_id=${ilId}`)
            .then(res => res.json())
            .then(data => {
                ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
                if (data.length > 0) {
                    data.forEach(ilce => {
                        const option = document.createElement('option');
                        option.value = ilce.id;
                        option.textContent = ilce.ilce_adi;
                        ilceSelect.appendChild(option);
                    });
                }
                ilceSelect.disabled = false;
            })
            .catch(err => {
                console.error('❌ İlçe yüklenemedi:', err);
                ilceSelect.innerHTML = '<option value="">Hata oluştu</option>';
            });
    });
    
    ilceSelect.addEventListener('change', function() {
        const ilceId = this.value;
        okulSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        okulSelect.disabled = true;
        
        if (!ilceId) {
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            return;
        }
        
        const kapali = kapaliCheck?.checked ? 1 : 0;
        
        fetch(`api/get_okullar.php?ilce_id=${ilceId}&kapali_kurum=${kapali}`)
            .then(res => res.json())
            .then(data => {
                okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
                if (data.length > 0) {
                    data.forEach(okul => {
                        const option = document.createElement('option');
                        option.value = okul.kurum_kodu;
                        option.textContent = okul.gorev_yeri;
                        okulSelect.appendChild(option);
                    });
                }
                okulSelect.disabled = false;
            })
            .catch(err => {
                console.error('❌ Okullar yüklenemedi:', err);
                okulSelect.innerHTML = '<option value="">Hata oluştu</option>';
            });
    });
    
    if (kapaliCheck) {
        kapaliCheck.addEventListener('change', function() {
            if (ilceSelect.value) {
                ilceSelect.dispatchEvent(new Event('change'));
            }
        });
    }
    
    setTimeout(() => {
        ilkYukleme = false;
    }, 1000);

    <?php if (isset($duzenlenecek_sozlesme) && !empty($duzenlenecek_sozlesme['sozlesme_il_adi'])): ?>
    setTimeout(() => {
        for (let i = 0; i < ilSelect.options.length; i++) {
            if (ilSelect.options[i].text === "<?= $duzenlenecek_sozlesme['sozlesme_il_adi'] ?>") {
                ilSelect.selectedIndex = i;
                break;
            }
        }
    }, 100);

    setTimeout(() => {
        for (let i = 0; i < ilceSelect.options.length; i++) {
            if (ilceSelect.options[i].text === "<?= $duzenlenecek_sozlesme['sozlesme_ilce_adi'] ?>") {
                ilceSelect.selectedIndex = i;
                break;
            }
        }
    }, 500);

    setTimeout(() => {
        for (let i = 0; i < okulSelect.options.length; i++) {
            if (okulSelect.options[i].value === "<?= $duzenlenecek_sozlesme['sozlesme_kurum_kodu'] ?>") {
                okulSelect.selectedIndex = i;
                break;
            }
        }
    }, 1200);
    <?php endif; ?>
});

document.addEventListener('DOMContentLoaded', function() {
    const toastElement = document.getElementById('successToast');
    if (toastElement) {
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('toastProgressBar');
        setTimeout(() => { progressBar.style.width = '0%'; }, 50);
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function() { this.closest('.toast-container')?.remove(); });
        const closeBtn = toastElement.querySelector('.btn-close');
        if (closeBtn) closeBtn.addEventListener('click', function() { toast.hide(); });
    }
});
</script>