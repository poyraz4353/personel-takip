<?php
/**
 * KADRO KAYDI - İçerik Dosyası
 * @version 1.0
 * @author Fatih
 */
?>

<style>
/* Kadro Kaydı Sayfası Özel Stilleri */
.kadro-kaydi .table-wrapper {
    overflow-x: auto;
    width: 100%;
    border-radius: 10px;
}

/* Başlık ve içerik satırları AYNI grid yapısını kullanmalı */
.kadro-kaydi .header-row,
.kadro-kaydi .content-row {
    display: grid !important;
    grid-template-columns: 
        50px   /* 1. ikon */
        100px  /* 2. terfi tarihi */
        486px  /* 3. terfi nedeni */
        63px  /* 4. kadro derecesi */
        86px  /* 5. aylık derece */
        86px  /* 6. aylık kademe */
        99px  /* 7. KHA ek gösterge */
        86px  /* 8. emekli derece */
        86px  /* 9. emekli kadro */
        99px  /* 10. emekli ek gösterge */
        100px  /* 11. kararname tarihi */
        120px  /* 12. kararname no */
        1fr    /* 13. açıklama */
    !important;
    gap: 0;
    align-items: stretch;  /* stretch ile yükseklik eşitlenir */
    min-width: 1500px !important;
}

.kadro-kaydi .header-row {
    background: linear-gradient(135deg, #0066b3 0%, #3385c6 100%);
    border-radius: 10px 10px 0 0;
}

.kadro-kaydi .content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
}

.kadro-kaydi .header-cell {
    color: var(--beyaz);
    font-weight: 600;
    font-size: 0.7rem;
    text-align: center;
    padding: 10px 4px;
    text-transform: uppercase;
    line-height: 1.3;
    word-break: break-word;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid rgba(255, 255, 255, 0.3);
    min-height: 42px;
    box-sizing: border-box;
}

.kadro-kaydi .content-cell {
    padding: 8px 4px !important;
    text-align: center;
    border-right: 1px solid var(--border-renk);
    font-size: 0.8rem;
    color: var(--text-koyu);
    background: var(--beyaz);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 42px !important;
    line-height: 1.3;
    word-break: break-word;
    box-sizing: border-box;
}

/* Son hücrede border-right yok */
.kadro-kaydi .header-cell:last-child,
.kadro-kaydi .content-cell:last-child {
    border-right: none;
}

/* Terfi nedeni sütunu sola hizalı */
.kadro-kaydi .content-cell:nth-child(3) {
    text-align: left;
    justify-content: flex-start;
}

/* Açıklama sütunu sola hizalı */
.kadro-kaydi .content-cell:last-child {
    text-align: left;
    justify-content: flex-start;
}

/* klasör ikonu - mavi */
.kadro-kaydi .folder-row-icon {
    font-size: 22px;
    color: #0066b3;
    cursor: pointer;
    transition: all 0.3s ease;
}

.kadro-kaydi .folder-row-icon:hover {
    color: #3385c6;
    transform: scale(1.1);
}

/* Satır üzerinde varsayılan cursor */
.kadro-kaydi .content-row {
    cursor: default;
}

/* ===== KADRO FORMU ===== */
#kadroFormu {
    max-width: 800px;
    margin: 20px auto;
    width: 90%;
}

#kadroFormu .personel-content {
    padding: 20px;
}

#kadroFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
}

#kadroFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
}

#kadroFormu .card-header h3 {
    font-size: 1.2rem;
}

#kadroFormu .card-header i {
    font-size: 1.3rem;
}

#kadroFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

#kadroFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 2fr 6fr;  /* BAŞLIK 2/6 - INPUT 6/6 ORANI */
    gap: 1rem;
    align-items: center;
    margin-bottom: 0.1rem;
}

#kadroFormu .form-group-horizontal .form-label {
    font-weight: 600;
    color: var(--text-koyu);
    font-size: 0.9rem;
    margin-bottom: 0;
    text-align: left;
    line-height: 1.4;
}

#kadroFormu .form-group-horizontal .form-control,
#kadroFormu .form-group-horizontal .form-select {
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

/* responsive */
@media (max-width: 768px) {
    #kadroFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }
    
    #kadroFormu .form-group-horizontal .form-label {
        margin-bottom: 0.2rem;
    }
}

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

<!-- UYARI MESAJI TOAST -->
<?php
$warningMsg = SessionManager::getMessage('warning');
if ($warningMsg): ?>
<div class="toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 9999;">
    <div id="warningToast" class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #fff3cd; color: #856404;">
        <div class="d-flex flex-column">
            <div class="toast-header border-0" style="background-color: #fff3cd; color: #856404;">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-4" style="color: #856404;"></i>
                <strong class="me-auto fs-5" style="color: #856404;">Uyarı!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.4);"></button>
            </div>
            <div class="toast-body text-center py-3" style="background-color: #fff3cd; color: #856404;">
                <p class="mb-3 fs-5"><?= htmlspecialchars($warningMsg) ?></p>
                <div class="progress" style="height: 4px; background-color: #ffeeba;">
                    <div id="warningToastProgressBar" class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #856404;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modern Personel Kimlik Kartı -->
<div class="modern-personel-card kadro-kaydi">
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

    <!-- Kadro Kaydı Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Kadro Kaydı</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Kadro Kaydı" onclick="yeniKadroKontrol()" id="yeniKadroBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
                <button class="btn-action btn-danger" title="Sil" onclick="silKadro()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- KADRO FORMU -->
    <div id="kadroFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_kadro) ? 'block' : 'none' ?>;">
        <form method="POST" action="kadro_kaydi.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_kadro" value="1">
            <input type="hidden" name="kadro_id" id="kadro_id" value="<?= $duzenlenecek_kadro['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_kadro) ? 'Kadro Kaydı Düzenle' : 'Yeni Kadro Kaydı' ?>
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
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-star"></i>
						<h3>Kadro Bilgileri</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<div class="form-group-horizontal">
								<label class="form-label">Terfi Tarihi</label>
								<input type="date" name="terfi_tarihi" id="terfi_tarihi" class="form-control" 
									value="<?= $duzenlenecek_kadro['terfi_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Terfi Nedeni</label>
								<select name="terfi_nedeni" id="terfi_nedeni" class="form-select select2-terfi" style="width: 100%;">
									<option value="">Seçiniz</option>
									<?php foreach ($terfi_nedenleri as $tn): ?>
										<option value="<?= htmlspecialchars($tn['terfi_nedeni']) ?>" 
											<?= (isset($duzenlenecek_kadro) && $duzenlenecek_kadro['terfi_nedeni'] == $tn['terfi_nedeni']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($tn['terfi_nedeni']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-star"></i>
						<h3>Kadro Bilgileri</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<!-- Kadro Derecesi (tek başına) -->
							<div class="form-group-horizontal">
								<label class="form-label">Kadro Derecesi</label>
								<input type="text" name="kadro_derecesi" id="kadro_derecesi" class="form-control" 
									placeholder="Örn: 1, 2, 3..."
									value="<?= htmlspecialchars($duzenlenecek_kadro['kadro_derecesi'] ?? '') ?>">
							</div>

							<!-- Aylık Bilgileri (3'lü grup - yanyana) -->
							<div class="form-group-horizontal">
								<label class="form-label">Aylık Bilgileri</label>
								<div style="display: flex; gap: 10px; width: 100%;">
									<input type="text" name="aylik_derece" id="aylik_derece" class="form-control" 
										placeholder="Aylık Derece"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['aylik_derece'] ?? '') ?>">
									<input type="text" name="aylik_kademe" id="aylik_kademe" class="form-control" 
										placeholder="Aylık Kademe"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['aylik_kademe'] ?? '') ?>">
									<input type="text" name="kha_ek_gosterge" id="kha_ek_gosterge" class="form-control" 
										placeholder="KHA Ek Gösterge"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['kha_ek_gosterge'] ?? '') ?>">
								</div>
							</div>

							<!-- Emekli Bilgileri (3'lü grup - yanyana) -->
							<div class="form-group-horizontal">
								<label class="form-label">Emekli Bilgileri</label>
								<div style="display: flex; gap: 10px; width: 100%;">
									<input type="text" name="emekli_derece" id="emekli_derece" class="form-control" 
										placeholder="Emekli Derece"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['emekli_derece'] ?? '') ?>">
									<input type="text" name="emekli_kadro" id="emekli_kadro" class="form-control" 
										placeholder="Emekli Kadro"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['emekli_kadro'] ?? '') ?>">
									<input type="text" name="emekli_ek_gosterge" id="emekli_ek_gosterge" class="form-control" 
										placeholder="Emekli Ek Gösterge"
										style="flex: 1;"
										value="<?= htmlspecialchars($duzenlenecek_kadro['emekli_ek_gosterge'] ?? '') ?>">
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-star"></i>
						<h3>Kadro Bilgileri</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<div class="form-group-horizontal">
								<label class="form-label">Kararname Tarihi</label>
								<input type="date" name="kararname_tarihi" id="kararname_tarihi" class="form-control" 
									value="<?= $duzenlenecek_kadro['kararname_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Kararname Sayısı</label>
								<input type="text" name="kararname_sayisi" id="kararname_sayisi" class="form-control" 
									placeholder="Kararname numarası"
									value="<?= htmlspecialchars($duzenlenecek_kadro['kararname_sayisi'] ?? '') ?>">
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
        </form>
    </div>
    
	<!-- KADRO LİSTESİ TABLOSU -->
	<div class="personel-content">
		<div class="content-grid">
			<div class="info-card">
				<div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
					<i class="bi bi-star"></i>
					<h3 style="margin: 0;">Kadro Bilgileri Listesi</h3>
				</div>
				<div class="card-body p-0">
					<div class="table-wrapper" style="overflow-x: auto; width: 100%;">
						<div class="table-header">
							<div class="header-row">
								<div class="header-cell"></div>
								<div class="header-cell">Terfi Tarihi</div>
								<div class="header-cell">Terfi Nedeni</div>
								<div class="header-cell">Kadro Derecesi</div>
								<div class="header-cell">Aylık Derece</div>
								<div class="header-cell">Aylık Kademe</div>
								<div class="header-cell">KHA Ek Göst.</div>
								<div class="header-cell">Emekli Derece</div>
								<div class="header-cell">Emekli Kadro</div>
								<div class="header-cell">Emekli Ek Göst.</div>
								<div class="header-cell">Kararname Tarihi</div>
								<div class="header-cell">Kararname No</div>
								<div class="header-cell">Açıklama</div>
							</div>
						</div>

						<div class="table-content">
							<?php if (!empty($kadro_listesi)): ?>
								<?php foreach ($kadro_listesi as $kadro): ?>
								<div class="content-row" data-id="<?= $kadro['id'] ?>">
									<div class="content-cell">
										<i class="bi bi-folder-fill folder-row-icon" 
										   onclick="kadroDuzenle(<?= $kadro['id'] ?? 0 ?>)" 
										   title="Kadro Bilgisini Düzenle"
										   style="cursor: pointer;">
										</i>
									</div>
									<div class="content-cell"><?= !empty($kadro['terfi_tarihi']) ? date('d.m.Y', strtotime($kadro['terfi_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['terfi_nedeni'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['kadro_derecesi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['aylik_derece'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['aylik_kademe'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['kha_ek_gosterge'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['emekli_derece'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['emekli_kadro'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['emekli_ek_gosterge'] ?? '-') ?></div>
									<div class="content-cell"><?= !empty($kadro['kararname_tarihi']) ? date('d.m.Y', strtotime($kadro['kararname_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= htmlspecialchars($kadro['kararname_sayisi'] ?? '-') ?></div>
									<div class="content-cell" style="text-align: left; justify-content: flex-start;">-</div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="empty-state-table">
									<div class="text-center text-muted py-5">
										<i class="bi bi-inbox display-4 d-block mb-3"></i>
										<h5>Kadro Bilgisi Bulunamadı</h5>
										<p class="mb-0">Bu personele ait kadro bilgisi bulunamadı.</p>
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
// KADRO DÜZENLEME
// =============================================================================
function kadroDuzenle(kadroId) {
    if (!kadroId || kadroId === 0) {
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Geçersiz kayıt ID' });
        return;
    }
    window.location.href = 'kadro_kaydi.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + kadroId;
}

// =============================================================================
// YENİ KADRO KAYDI
// =============================================================================
function yeniKadroKontrol() {
    const kadroIdInput = document.getElementById('kadro_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#kadroFormu form');
    const formArea = document.getElementById('kadroFormu');
    
    if (!kadroIdInput || !baslik || !form || !formArea) return;
    
    kadroIdInput.value = '';
    baslik.textContent = 'Yeni Kadro Kaydı';
    form.reset();
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    const form = document.getElementById('kadroFormu');
    if (form) form.style.display = 'none';
    const kadroIdInput = document.getElementById('kadro_id');
    if (kadroIdInput) kadroIdInput.value = '';
    const baslik = document.getElementById('formBaslik');
    if (baslik) baslik.textContent = 'Yeni Kadro Kaydı';
    const formElement = document.querySelector('#kadroFormu form');
    if (formElement) formElement.reset();
}

// =============================================================================
// KADRO KAYDI SİLME
// =============================================================================
function silKadro() {
    const kadroId = document.getElementById('kadro_id').value;
    
    if (!kadroId || kadroId === '0' || kadroId === '') {
        Swal.fire({ icon: 'warning', title: 'Uyarı!', text: 'Lütfen önce silinecek bir kayıt seçin!' });
        return;
    }
    
    Swal.fire({
        title: 'Silme Onayı',
        text: 'Bu kadro kaydını silmek istediğinize emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) executeSilme(kadroId);
    });
}

function executeSilme(kadroId) {
    fetch('api/sil_kadro.php?kadro_id=' + encodeURIComponent(kadroId), {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Hata!', text: data.error || 'Silme hatası' });
        }
    })
    .catch(error => {
        Swal.fire({ icon: 'error', title: 'Hata!', text: error.message });
    });
}

// =============================================================================
// SAYFA YÜKLENDİĞİNDE TOAST YÖNETİMİ
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const successToast = document.getElementById('successToast');
    if (successToast) {
        const toast = new bootstrap.Toast(successToast, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('toastProgressBar');
        if (progressBar) {
            progressBar.style.transition = 'width 3s linear';
            progressBar.style.width = '100%';
            setTimeout(() => { progressBar.style.width = '0%'; }, 50);
        }
        toast.show();
        successToast.addEventListener('hidden.bs.toast', function() { this.closest('.toast-container')?.remove(); });
    }
    
    const warningToast = document.getElementById('warningToast');
    if (warningToast) {
        const toast = new bootstrap.Toast(warningToast, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('warningToastProgressBar');
        if (progressBar) {
            progressBar.style.transition = 'width 3s linear';
            progressBar.style.width = '100%';
            setTimeout(() => { progressBar.style.width = '0%'; }, 50);
        }
        toast.show();
        warningToast.addEventListener('hidden.bs.toast', function() { this.closest('.toast-container')?.remove(); });
        const closeBtn = warningToast.querySelector('.btn-close');
        if (closeBtn) closeBtn.addEventListener('click', function() { toast.hide(); });
    }
});

// =============================================================================
// TABLODAN SATIR TIKLAMA İLE DÜZENLEME (SADECE İKONA TIKLANINCA)
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.folder-row-icon').forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            const row = this.closest('.content-row');
            const id = row.getAttribute('data-id');
            if (id) kadroDuzenle(id);
        });
    });
});

// =============================================================================
// SELECT2 BAŞLATMA (Arama Özelliği)
// =============================================================================
function initSelect2() {
    $('#terfi_nedeni').select2({
        theme: 'bootstrap-5',
        language: 'tr',
        placeholder: 'Terfi nedeni seçin veya yazın',
        allowClear: true,
        width: '100%'
    });
}

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    initSelect2();
});
</script>