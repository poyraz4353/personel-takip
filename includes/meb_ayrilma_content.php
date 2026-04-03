<?php
/**
 * MEB'DEN AYRILMA İŞLEMİ - Personel Takip Sistemi - meb_ayrilma_content.php
 * @version 2.5
 * @author Fatih
 */
?>

<style>
/* MEB AYRILMA SAYFASI ÖZEL STİLLERİ */
.meb-ayrilma .table-wrapper {
    overflow-x: auto;
    width: 100%;
    border-radius: 10px;
}

.meb-ayrilma .table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

.meb-ayrilma .header-row,
.meb-ayrilma .content-row {
    display: grid !important;
    grid-template-columns: 
        53px   /* 1. klasör/ikon */
        99px  /* 2. ayrılma tarihi */
        286px  /* 3. ayrılma nedeni */
        286px  /* 4. son görev yeri */
        153px  /* 5. görev ünvanı */
        99px  /* 6. başlama tarihi */
        99px  /* 7. onay sayısı */
        99px  /* 8. onay tarihi */
    !important;
    gap: 0;
    align-items: center;
    min-width: 1174px !important;
}

.meb-ayrilma .header-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

.meb-ayrilma .content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
    min-height: 43px;
}

.meb-ayrilma .header-cell {
    color: var(--beyaz);
    font-weight: 600;
    font-size: 0.72rem;
    text-align: center;
    padding: 10px 4px;
    text-transform: uppercase;
    line-height: 1.3;
    word-break: break-word;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid rgba(255, 255, 255, 0.3);
    min-height: 34px;
    box-sizing: border-box;
}

.meb-ayrilma .content-cell {
    padding: 5px 4px !important;
    text-align: center;
    border-right: 1px solid var(--border-renk);
    font-size: 0.85rem;
    color: var(--text-koyu);
    background: var(--beyaz);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 43px !important;
    line-height: 1.3;
    word-break: break-word;
    box-sizing: border-box;
}

.meb-ayrilma .header-cell:last-child,
.meb-ayrilma .content-cell:last-child {
    border-right: none;
}

/* özel hücre hizalamaları */
.meb-ayrilma .content-cell:nth-child(3),
.meb-ayrilma .content-cell:nth-child(4) {
    text-align: left;
    justify-content: flex-start;
    padding: 5px 8px !important;
}

/* klasör ikonu */
.meb-ayrilma .folder-row-icon {
    font-size: 22px;
    color: #dc3545;
    cursor: pointer;
    transition: all 0.3s ease;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.meb-ayrilma .folder-row-icon:hover {
    color: #c82333;
    transform: scale(1.1);
}

/* ===== AYRILMA KAYDI FORMU - ORTADA VE DAR ===== */
#ayrilmaKaydiFormu {
    max-width: 800px;
    margin: 20px auto;
    width: 90%;
}

#ayrilmaKaydiFormu .personel-content {
    padding: 20px;
}

#ayrilmaKaydiFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
}

#ayrilmaKaydiFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
}

#ayrilmaKaydiFormu .card-header h3 {
    font-size: 1.2rem;
}

#ayrilmaKaydiFormu .card-header i {
    font-size: 1.3rem;
}

#ayrilmaKaydiFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

#ayrilmaKaydiFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 1rem;
    align-items: center;
    margin-bottom: 0.1rem;
}

#ayrilmaKaydiFormu .form-group-horizontal .form-label {
    font-weight: 600;
    color: var(--text-koyu);
    font-size: 0.9rem;
    margin-bottom: 0;
    text-align: left;
    line-height: 1.4;
}

#ayrilmaKaydiFormu .form-group-horizontal .form-control,
#ayrilmaKaydiFormu .form-group-horizontal .form-select,
#ayrilmaKaydiFormu .form-group-horizontal textarea {
    width: 100%;
    margin: 0;
    background-color: var(--beyaz);
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
    border: 1px solid var(--border-renk);
    border-radius: 0.375rem;
    min-height: 38px;
    max-width: 100%;
}

#ayrilmaKaydiFormu .form-group-horizontal textarea {
    min-height: 70px;
    resize: vertical;
}

#ayrilmaKaydiFormu .text-muted {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.2rem;
    display: block;
    text-align: left;
}

/* responsive */
@media (max-width: 768px) {
    #ayrilmaKaydiFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }
    
    #ayrilmaKaydiFormu .form-group-horizontal .form-label {
        margin-bottom: 0.2rem;
    }
}
</style>

<!-- BAŞARI MESAJI TOAST - ORTADA (GÖREV KAYDI İLE AYNI) -->
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
<div class="modern-personel-card meb-ayrilma">
    <?php if ($personel !== null): ?>
    
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

    <!-- MEB'den Ayrılma İşlemi Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">MEB'den Ayrılma İşlemi</h1>
            </div>
			<div class="header-actions">
				<button class="btn-action btn-success" title="Yeni Ayrılma Kaydı" onclick="yeniAyrilmaKontrol()" id="yeniAyrilmaBtn">
					<i class="bi bi-plus-circle"></i>
				</button>
				<button class="btn-action btn-danger" title="Sil" 
						onclick="standartSil(document.getElementById('ayrilma_id').value, 'ayrilma', 'Ayrılma Kaydı')">
					<i class="bi bi-trash"></i>
				</button>
			</div>
        </div>
    </div>

    <!-- AYRILMA KAYDI FORMU -->
    <div id="ayrilmaKaydiFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_ayrilma) ? 'block' : 'none' ?>;">
        <form method="POST" action="meb_ayrilma.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_ayrilma" value="1">
            <input type="hidden" name="ayrilma_id" id="ayrilma_id" value="<?= $duzenlenecek_ayrilma['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_ayrilma) ? 'Ayrılma Kaydı Düzenle' : 'Yeni Ayrılma Kaydı' ?>
                        </h1>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn-action btn-secondary" title="Kapat" onclick="kapatForm()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="personel-content">
                <!-- Son Görev Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-briefcase"></i>
                        <h3>Son Görev Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Son Görev Yeri</label>
                                <input type="text" name="son_gorev_yeri" id="son_gorev_yeri" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_ayrilma['son_gorev_yeri'] ?? '') ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Başlama Tarihi</label>
                                <input type="date" name="baslama_tarihi" id="baslama_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_ayrilma['baslama_tarihi'] ?? '' ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Görev Ünvanı</label>
                                <input type="text" name="gorev_unvani" id="gorev_unvani" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_ayrilma['gorev_unvani'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ayrılma Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-calendar-x"></i>
                        <h3>Ayrılma Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Ayrılma Tarihi</label>
                                <input type="date" name="ayrilma_tarihi" id="ayrilma_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_ayrilma['ayrilma_tarihi'] ?? '' ?>">
                            </div>

                            <!-- AYRILMA NEDENİ DROPDOWN -->
                            <div class="form-group-horizontal">
                                <label class="form-label">Ayrılma Nedeni</label>
                                <select name="ayrilma_nedeni_id" id="ayrilma_nedeni_id" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <?php if (!empty($ayrilma_nedenleri)): ?>
                                        <?php foreach ($ayrilma_nedenleri as $neden): ?>
                                            <option value="<?= $neden['id'] ?>" 
                                                <?= (isset($duzenlenecek_ayrilma) && $duzenlenecek_ayrilma['ayrilma_nedeni_id'] == $neden['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($neden['neden_adi']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Açıklama</label>
                                <textarea name="ayrilma_aciklama" id="ayrilma_aciklama" class="form-control" rows="3"><?= htmlspecialchars($duzenlenecek_ayrilma['ayrilma_aciklama'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onay Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-file-check"></i>
                        <h3>Bakanlıktan / İlden Alınan ONAY Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Onay Tarihi</label>
                                <input type="date" name="onay_tarihi" id="onay_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_ayrilma['onay_tarihi'] ?? '' ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Onay Sayısı</label>
                                <input type="text" name="onay_sayisi" id="onay_sayisi" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_ayrilma['onay_sayisi'] ?? '') ?>">
                            </div>
                        </div>
                        <small class="text-muted">Tarih gg/aa/yyyy formatında girilecektir.</small>
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
        </form>
    </div>
    
    <!-- AYRILMA LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-box-arrow-right"></i>
                    <h3 style="margin: 0;">Personel Ayrılma Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
                        <div class="table-header">
                            <div class="header-row">
                                <div class="header-cell"></div>
                                <div class="header-cell">Ayrılma Tarihi</div>
                                <div class="header-cell">Ayrılma Nedeni</div>
                                <div class="header-cell">Son Görev Yeri</div>
                                <div class="header-cell">Görev Ünvanı</div>
                                <div class="header-cell">Başlama Tarihi</div>
                                <div class="header-cell">Onay Sayısı</div>
                                <div class="header-cell">Onay Tarihi</div>
                            </div>
                        </div>

                        <div class="table-content">
                            <?php if (!empty($ayrilma_listesi)): ?>
                                <?php foreach ($ayrilma_listesi as $kayit): ?>
                                <div class="content-row" data-id="<?= $kayit['id'] ?>">
                                    <div class="content-cell">
                                        <i class="bi bi-folder-fill folder-row-icon" 
                                           onclick="ayrilmaDuzenle(<?= $kayit['id'] ?? 0 ?>)" 
                                           title="Kaydı Düzenle"
                                           style="cursor: pointer;">
                                        </i>
                                    </div>

                                    <div class="content-cell">
                                        <?= !empty($kayit['ayrilma_tarihi']) ? date('d.m.Y', strtotime($kayit['ayrilma_tarihi'])) : '-' ?>
                                    </div>

                                    <div class="content-cell"><?= htmlspecialchars($kayit['neden_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($kayit['son_gorev_yeri'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($kayit['gorev_unvani'] ?? '-') ?></div>
                                    <div class="content-cell"><?= !empty($kayit['baslama_tarihi']) ? date('d.m.Y', strtotime($kayit['baslama_tarihi'])) : '-' ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($kayit['onay_sayisi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= !empty($kayit['onay_tarihi']) ? date('d.m.Y', strtotime($kayit['onay_tarihi'])) : '-' ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state-table">
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <h5>Ayrılma Kaydı Bulunamadı</h5>
                                        <p class="mb-0">Bu personele ait ayrılma kaydı bulunamadı.</p>
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
// AYRILMA DÜZENLEME FONKSİYONU
// =============================================================================
function ayrilmaDuzenle(ayrilmaId) {
    if (!ayrilmaId || ayrilmaId === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Geçersiz kayıt ID',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    window.location.href = 'meb_ayrilma.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + ayrilmaId;
}

// =============================================================================
// YENİ AYRILMA KAYDI - SON GÖREV BİLGİLERİNİ OTOMATİK DOLDUR
// =============================================================================
function yeniAyrilmaKontrol() {
    console.log('➕ Yeni ayrılma kaydı açılıyor...');
    console.log('Son görev yeri (JS): ' + window.sonGorevYeri);
    console.log('Son görev başlama tarihi (JS): ' + window.sonGorevBaslamaTarihi);
    console.log('Son görev ünvanı (JS): ' + window.sonGorevUnvani);
    
    const ayrilmaIdInput = document.getElementById('ayrilma_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#ayrilmaKaydiFormu form');
    const formArea = document.getElementById('ayrilmaKaydiFormu');
    const sonGorevYeri = document.getElementById('son_gorev_yeri');
    const baslamaTarihi = document.getElementById('baslama_tarihi');
    const gorevUnvani = document.getElementById('gorev_unvani');
    
    if (!ayrilmaIdInput || !baslik || !form || !formArea) {
        console.error('❌ Form elementleri bulunamadı!');
        return;
    }
    
    ayrilmaIdInput.value = '';
    baslik.textContent = 'Yeni Ayrılma Kaydı';
    form.reset();
    
    // SON GÖREV BİLGİLERİNİ OTOMATİK DOLDUR (eğer varsa)
    if (window.sonGorevYeri && window.sonGorevYeri !== '') {
        if (sonGorevYeri) {
            sonGorevYeri.value = window.sonGorevYeri;
            console.log('✅ Son görev yeri dolduruldu: ' + window.sonGorevYeri);
        }
    } else {
        console.log('⚠️ Son görev yeri bilgisi yok');
    }
    
    if (window.sonGorevBaslamaTarihi && window.sonGorevBaslamaTarihi !== '') {
        if (baslamaTarihi) {
            baslamaTarihi.value = window.sonGorevBaslamaTarihi;
            console.log('✅ Başlama tarihi dolduruldu: ' + window.sonGorevBaslamaTarihi);
        }
    } else {
        console.log('⚠️ Başlama tarihi bilgisi yok');
    }
    
    if (window.sonGorevUnvani && window.sonGorevUnvani !== '') {
        if (gorevUnvani) {
            gorevUnvani.value = window.sonGorevUnvani;
            console.log('✅ Görev ünvanı dolduruldu: ' + window.sonGorevUnvani);
        }
    } else {
        console.log('⚠️ Görev ünvanı bilgisi yok');
    }
    
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
    
    console.log('✅ Yeni ayrılma kaydı formu açıldı');
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    console.log('🔒 Form kapatılıyor...');
    
    const form = document.getElementById('ayrilmaKaydiFormu');
    if (form) {
        form.style.display = 'none';
    }
    
    const ayrilmaIdInput = document.getElementById('ayrilma_id');
    if (ayrilmaIdInput) ayrilmaIdInput.value = '';
    
    const baslik = document.getElementById('formBaslik');
    if (baslik) baslik.textContent = 'Yeni Ayrılma Kaydı';
    
    const formElement = document.querySelector('#ayrilmaKaydiFormu form');
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
// BAŞARI TOAST'U (YEŞİL) - GÖREV KAYDI İLE AYNI
// =============================================================================
function showSuccessToast(message) {
    // Mevcut toast'ları kaldır
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
    
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 50);
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// UYARI TOAST'U (TURUNCU) - BAŞARI TOAST'I İLE AYNI FORM, TURUNCU RENK
// =============================================================================
function showWarningToast(message) {
    // Mevcut toast'ları kaldır
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
    const progressBar = toastElement.querySelector('.progress-bar');
    
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 50);
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// HATA TOAST'U (KIRMIZI)
// =============================================================================
function showErrorToast(message) {
    // Mevcut toast'ları kaldır
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
    const progressBar = toastElement.querySelector('.progress-bar');
    
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 50);
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.closest('.custom-toast-container')?.remove();
    });
}

// =============================================================================
// SAYFA YÜKLENDİĞİNDE TOAST YÖNETİMİ
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const toastElement = document.getElementById('successToast');
    if (toastElement) {
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('toastProgressBar');
        setTimeout(() => {
            progressBar.style.width = '0%';
        }, 50);
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.closest('.toast-container')?.remove();
        });
        const closeBtn = toastElement.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                toast.hide();
            });
        }
    }
});
</script>