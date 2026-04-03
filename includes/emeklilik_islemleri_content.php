<?php
/**
 * EMEKLİLİK İŞLEMLERİ - İçerik Dosyası
 * @version 2.0
 * @author Fatih
 */
?>

<style>
/* Emeklilik İşlemleri Sayfası Özel Stilleri */
.emeklilik-islemleri .table-wrapper {
    overflow-x: auto;
    width: 100%;
    border-radius: 10px;
}

.emeklilik-islemleri .header-row,
.emeklilik-islemleri .content-row {
    display: grid !important;
    grid-template-columns: 
        50px   /* 1. ikon */
        100px  /* 2. emeklilik tarihi */
        120px  /* 3. emeklilik türü */
        180px  /* 4. son görev yeri */
        120px  /* 5. görev ünvanı */
        100px  /* 6. görev başlama */
        100px  /* 7. başvuru tarihi */
        100px  /* 8. onay tarihi */
        120px  /* 9. onay no */
        100px  /* 10. maaş bağlanma */
        120px  /* 11. emekli maaşı */
        150px  /* 12. ikramiye */
        1fr    /* 13. açıklama */
    !important;
    gap: 0;
    align-items: center;
    min-width: 1550px !important;
}

.emeklilik-islemleri .header-row {
    background: linear-gradient(135deg, #6f42c1 0%, #8b5cf6 100%);
    border-radius: 10px 10px 0 0;
}

.emeklilik-islemleri .content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
    min-height: 38px;
}

.emeklilik-islemleri .header-cell {
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
    min-height: 34px;
    box-sizing: border-box;
}

.emeklilik-islemleri .content-cell {
    padding: 5px 4px !important;
    text-align: center;
    border-right: 1px solid var(--border-renk);
    font-size: 0.8rem;
    color: var(--text-koyu);
    background: var(--beyaz);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 38px !important;
    line-height: 1.3;
    word-break: break-word;
    box-sizing: border-box;
}

.emeklilik-islemleri .header-cell:last-child,
.emeklilik-islemleri .content-cell:last-child {
    border-right: none;
}

/* metin sütunları sola hizalı */
.emeklilik-islemleri .content-cell:nth-child(4),
.emeklilik-islemleri .content-cell:nth-child(5),
.emeklilik-islemleri .content-cell:last-child {
    text-align: left;
    justify-content: flex-start;
}

/* klasör ikonu */
.emeklilik-islemleri .folder-row-icon {
    font-size: 22px;
    color: #6f42c1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.emeklilik-islemleri .folder-row-icon:hover {
    color: #8b5cf6;
    transform: scale(1.1);
}

/* Satır üzerinde varsayılan cursor */
.emeklilik-islemleri .content-row {
    cursor: default;
}

.text-danger {
    color: #dc3545;
    font-size: 0.8rem;
}

/* ===== EMEKLİLİK FORMU - BAŞLIK 2/6, INPUT 6/6 ORANI ===== */
#emekliFormu {
    max-width: 900px;
    margin: 20px auto;
    width: 90%;
}

#emekliFormu .personel-content {
    padding: 20px;
}

#emekliFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
}

#emekliFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
}

#emekliFormu .card-header h3 {
    font-size: 1.2rem;
}

#emekliFormu .card-header i {
    font-size: 1.3rem;
}

#emekliFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* FORM SATIRLARI - BAŞLIK 2/6, INPUT 6/6 ORANI */
#emekliFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 2fr 6fr;  /* BAŞLIK 2/6 - INPUT 6/6 (yani 1/3 oranı) */
    gap: 1rem;
    align-items: center;
    margin-bottom: 0;
}

#emekliFormu .form-group-horizontal .form-label {
    font-weight: 600;
    color: var(--text-koyu);
    font-size: 0.9rem;
    margin-bottom: 0;
    text-align: left;
    line-height: 1.4;
}

#emekliFormu .form-group-horizontal .form-control,
#emekliFormu .form-group-horizontal .form-select,
#emekliFormu .form-group-horizontal textarea {
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

#emekliFormu .form-group-horizontal textarea {
    min-height: 70px;
    resize: vertical;
}

/* responsive - mobil görünüm */
@media (max-width: 768px) {
    #emekliFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }
    
    #emekliFormu .form-group-horizontal .form-label {
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
<div class="modern-personel-card emeklilik-islemleri">
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

    <!-- Emeklilik İşlemleri Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Emeklilik İşlemleri</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Emeklilik Kaydı" onclick="yeniEmekliKontrol()" id="yeniEmekliBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
				<!-- Silme butonu -->
				<button class="btn-action btn-danger" 
						onclick="standartSil(document.getElementById('emekli_id').value, 'emeklilik', 'Emeklilik Kaydı')">
					<i class="bi bi-trash"></i>
				</button>
            </div>
        </div>
    </div>

    <!-- EMEKLİLİK FORMU -->
    <div id="emekliFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_emekli) ? 'block' : 'none' ?>;">
        <form method="POST" action="emeklilik_islemleri.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_emekli" value="1">
            <input type="hidden" name="emekli_id" id="emekli_id" value="<?= $duzenlenecek_emekli['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_emekli) ? 'Emeklilik Kaydı Düzenle' : 'Yeni Emeklilik Kaydı' ?>
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
                <!-- 1. Son Görev Bilgileri -->
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
                                    value="<?= htmlspecialchars($duzenlenecek_emekli['son_gorev_yeri'] ?? '') ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Görev Ünvanı</label>
                                <input type="text" name="gorev_unvani" id="gorev_unvani" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_emekli['gorev_unvani'] ?? '') ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Göreve Başlama Tarihi</label>
                                <input type="date" name="gorev_baslama_tarihi" id="gorev_baslama_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_emekli['gorev_baslama_tarihi'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

				<!-- 2. Emeklilik Bilgileri -->
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-bank"></i>
						<h3>Emeklilik Bilgileri <span class="text-danger">*</span></h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<div class="form-group-horizontal">
								<label class="form-label">Emeklilik Başvuru Tarihi <span class="text-danger">*</span></label>
								<input type="date" name="emeklilik_basvuru_tarihi" id="emeklilik_basvuru_tarihi" class="form-control" required
									value="<?= $duzenlenecek_emekli['emeklilik_basvuru_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Emeklilik Tarihi <span class="text-danger">*</span></label>
								<input type="date" name="emeklilik_tarihi" id="emeklilik_tarihi" class="form-control" required
									value="<?= $duzenlenecek_emekli['emeklilik_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Emeklilik Türü <span class="text-danger">*</span></label>
								<select name="emeklilik_tipi" id="emeklilik_tipi" class="form-select" required>
									<option value="">Seçiniz</option>
									<?php foreach ($emeklilik_turleri as $tur): ?>
										<option value="<?= htmlspecialchars($tur, ENT_QUOTES, 'UTF-8') ?>" 
											<?= (isset($duzenlenecek_emekli) && $duzenlenecek_emekli['emeklilik_tipi'] == $tur) ? 'selected' : '' ?>>
											<?= htmlspecialchars($tur) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Açıklama</label>
								<textarea name="aciklama" id="aciklama" class="form-control" rows="2"><?= htmlspecialchars($duzenlenecek_emekli['aciklama'] ?? '') ?></textarea>
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Ev Adresi</label>
								<textarea name="ev_adresi" id="ev_adresi" class="form-control" rows="2"><?= htmlspecialchars($duzenlenecek_emekli['ev_adresi'] ?? '') ?></textarea>
							</div>
						</div>
					</div>
				</div>

				<!-- 3. Emeklilik Belgesi Onay Bilgileri -->
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-file-check"></i>
						<h3>Emeklilik Belgesi Onay Bilgileri <span class="text-danger">*</span></h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<div class="form-group-horizontal">
								<label class="form-label">Onay Tarihi <span class="text-danger">*</span></label>
								<input type="date" name="onay_tarihi" id="onay_tarihi" class="form-control" required
									value="<?= $duzenlenecek_emekli['onay_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Onay Sayısı <span class="text-danger">*</span></label>
								<input type="text" name="onay_sayisi" id="onay_sayisi" class="form-control" required
									placeholder="Onay numarası"
									value="<?= htmlspecialchars($duzenlenecek_emekli['onay_sayisi'] ?? '') ?>">
							</div>
						</div>
					</div>
				</div>

				<!-- Emekli Maaş Bilgileri -->
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-cash-coin"></i>
						<h3>Emekli Maaş Bilgileri</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<div class="form-group-horizontal">
								<label class="form-label">Maaş Bağlanma Tarihi</label>
								<input type="date" name="maas_baglanma_tarihi" id="maas_baglanma_tarihi" class="form-control" 
									value="<?= $duzenlenecek_emekli['maas_baglanma_tarihi'] ?? '' ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">Emekli Maaşı (TL)</label>
								<input type="number" step="0.01" name="emekli_maas" id="emekli_maas" class="form-control" 
									placeholder="Emekli maaşı tutarı"
									value="<?= htmlspecialchars($duzenlenecek_emekli['emekli_maas'] ?? '') ?>">
							</div>

							<div class="form-group-horizontal">
								<label class="form-label">İkramiye Bilgisi</label>
								<textarea name="ikramiye_bilgisi" id="ikramiye_bilgisi" class="form-control" rows="3" 
									placeholder="İkramiye tutarı"><?= htmlspecialchars($duzenlenecek_emekli['ikramiye_bilgisi'] ?? '') ?></textarea>
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
    
    <!-- EMEKLİLİK LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-bank"></i>
                    <h3 style="margin: 0;">Emeklilik Bilgileri Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
                        <div class="table-header">
							<div class="header-row">
								<div class="header-cell"></div>
								<div class="header-cell">Emeklilik Tarihi</div>
								<div class="header-cell">Emeklilik Türü</div>
								<div class="header-cell">Son Görev Yeri</div>
								<div class="header-cell">Görev Ünvanı</div>
								<div class="header-cell">Görev Başlama</div>
								<div class="header-cell">Başvuru Tarihi</div>
								<div class="header-cell">Onay Tarihi</div>
								<div class="header-cell">Onay No</div>
								<div class="header-cell">Maaş Bağlanma</div>
								<div class="header-cell">Emekli Maaşı</div>
								<div class="header-cell">İkramiye</div>
								<div class="header-cell">Açıklama</div>
							</div>
                        </div>

						<div class="table-content">
							<?php if (!empty($emekli_listesi)): ?>
								<?php foreach ($emekli_listesi as $emekli): ?>
								<div class="content-row" data-id="<?= $emekli['id'] ?>">
									<div class="content-cell">
										<i class="bi bi-folder-fill folder-row-icon" 
										   onclick="emekliDuzenle(<?= $emekli['id'] ?? 0 ?>)" 
										   title="Emeklilik Bilgisini Düzenle"
										   style="cursor: pointer;">
										</i>
									</div>
									<div class="content-cell"><?= !empty($emekli['emeklilik_tarihi']) ? date('d.m.Y', strtotime($emekli['emeklilik_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= htmlspecialchars($emekli['emeklilik_tipi'] ?? '-') ?></div>
									<div class="content-cell" style="text-align: left;"><?= htmlspecialchars($emekli['son_gorev_yeri'] ?? '-') ?></div>
									<div class="content-cell" style="text-align: left;"><?= htmlspecialchars($emekli['gorev_unvani'] ?? '-') ?></div>
									<div class="content-cell"><?= !empty($emekli['gorev_baslama_tarihi']) ? date('d.m.Y', strtotime($emekli['gorev_baslama_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= !empty($emekli['emeklilik_basvuru_tarihi']) ? date('d.m.Y', strtotime($emekli['emeklilik_basvuru_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= !empty($emekli['onay_tarihi']) ? date('d.m.Y', strtotime($emekli['onay_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= htmlspecialchars($emekli['onay_sayisi'] ?? '-') ?></div>
									<div class="content-cell"><?= !empty($emekli['maas_baglanma_tarihi']) ? date('d.m.Y', strtotime($emekli['maas_baglanma_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= !empty($emekli['emekli_maas']) ? number_format($emekli['emekli_maas'], 2) . ' TL' : '-' ?></div>
									<div class="content-cell" style="text-align: left;"><?= !empty($emekli['ikramiye_bilgisi']) ? htmlspecialchars($emekli['ikramiye_bilgisi']) : '-' ?></div>
									<div class="content-cell" style="text-align: left;"><?= !empty($emekli['aciklama']) ? htmlspecialchars($emekli['aciklama']) : '-' ?></div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="empty-state-table">
									<div class="text-center text-muted py-5">
										<i class="bi bi-inbox display-4 d-block mb-3"></i>
										<h5>Emeklilik Bilgisi Bulunamadı</h5>
										<p class="mb-0">Bu personele ait emeklilik bilgisi bulunamadı.</p>
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
// EMEKLİLİK DÜZENLEME
// =============================================================================
function emekliDuzenle(emekliId) {
    if (!emekliId || emekliId === 0) {
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Geçersiz kayıt ID' });
        return;
    }
    window.location.href = 'emeklilik_islemleri.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + emekliId;
}

// =============================================================================
// YENİ EMEKLİLİK KAYDI - SON GÖREV BİLGİLERİNİ OTOMATİK DOLDUR
// =============================================================================
function yeniEmekliKontrol() {
    console.log('➕ Yeni emeklilik kaydı açılıyor...');
    
    const emekliIdInput = document.getElementById('emekli_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#emekliFormu form');
    const formArea = document.getElementById('emekliFormu');
    const sonGorevYeri = document.getElementById('son_gorev_yeri');
    const gorevBaslama = document.getElementById('gorev_baslama_tarihi');
    const gorevUnvani = document.getElementById('gorev_unvani');
    
    if (!emekliIdInput || !baslik || !form || !formArea) return;
    
    emekliIdInput.value = '';
    baslik.textContent = 'Yeni Emeklilik Kaydı';
    form.reset();
    
    // Son görev bilgilerini otomatik doldur
    if (window.sonGorevYeri && window.sonGorevYeri !== '') {
        if (sonGorevYeri) sonGorevYeri.value = window.sonGorevYeri;
    }
    if (window.sonGorevBaslamaTarihi && window.sonGorevBaslamaTarihi !== '') {
        if (gorevBaslama) gorevBaslama.value = window.sonGorevBaslamaTarihi;
    }
    if (window.sonGorevUnvani && window.sonGorevUnvani !== '') {
        if (gorevUnvani) gorevUnvani.value = window.sonGorevUnvani;
    }
    
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    const form = document.getElementById('emekliFormu');
    if (form) {
        form.style.display = 'none';
    }
    const emekliIdInput = document.getElementById('emekli_id');
    if (emekliIdInput) {
        emekliIdInput.value = '';
    }
    const baslik = document.getElementById('formBaslik');
    if (baslik) {
        baslik.textContent = 'Yeni Emeklilik Kaydı';
    }
    const formElement = document.querySelector('#emekliFormu form');
    if (formElement) {
        formElement.reset();
    }
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
            setTimeout(function() { progressBar.style.width = '0%'; }, 50);
        }
        toast.show();
        successToast.addEventListener('hidden.bs.toast', function() { 
            this.closest('.toast-container')?.remove(); 
        });
    }
    
    const warningToast = document.getElementById('warningToast');
    if (warningToast) {
        const toast = new bootstrap.Toast(warningToast, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('warningToastProgressBar');
        if (progressBar) {
            progressBar.style.transition = 'width 3s linear';
            progressBar.style.width = '100%';
            setTimeout(function() { progressBar.style.width = '0%'; }, 50);
        }
        toast.show();
        warningToast.addEventListener('hidden.bs.toast', function() { 
            this.closest('.toast-container')?.remove(); 
        });
        const closeBtn = warningToast.querySelector('.btn-close');
        if (closeBtn) closeBtn.addEventListener('click', function() { toast.hide(); });
    }
});

// =============================================================================
// TABLODAN SATIR TIKLAMA İLE DÜZENLEME (SADECE İKONA TIKLANINCA)
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.folder-row-icon').forEach(function(icon) {
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            var row = this.closest('.content-row');
            var id = row.getAttribute('data-id');
            if (id) emekliDuzenle(id);
        });
    });
});

// =============================================================================
// BOŞ ALANIN ÜSTÜNDE UYARI MESAJI GÖSTER
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#emekliFormu form');
    if (!form) return;

    // HTML'deki required attribute'lerini kaldır
    const requiredInputs = form.querySelectorAll('[required]');
    requiredInputs.forEach(input => {
        input.removeAttribute('required');
    });

    // Zorunlu alanlar
    const requiredFields = [
        { id: 'emeklilik_basvuru_tarihi', name: 'Emeklilik Başvuru Tarihi' },
        { id: 'emeklilik_tarihi', name: 'Emeklilik Tarihi' },
        { id: 'emeklilik_tipi', name: 'Emeklilik Türü' },
        { id: 'onay_tarihi', name: 'Onay Tarihi' },
        { id: 'onay_sayisi', name: 'Onay Sayısı' }
    ];

    // Placeholder ekle
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element && !element.getAttribute('placeholder')) {
            element.setAttribute('placeholder', `${field.name} giriniz`);
        }
    });

    // Uyarı mesajı oluşturma fonksiyonu
    function showWarningAboveInput(inputElement, message) {
        // Varsa eski uyarıyı kaldır
        const existingWarning = inputElement.parentElement.querySelector('.field-warning-message');
        if (existingWarning) existingWarning.remove();
        
        // Uyarı div'i oluştur
        const warningDiv = document.createElement('div');
        warningDiv.className = 'field-warning-message';
        warningDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            ${message}
        `;
        warningDiv.style.cssText = `
            position: absolute;
            top: -30px;
            left: 0;
            background-color: #dc3545;
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            white-space: nowrap;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            animation: fadeInUp 0.2s ease-out;
            pointer-events: none;
        `;
        
        // Input'un bulunduğu container'a relative pozisyon ver
        const container = inputElement.parentElement;
        const originalPosition = window.getComputedStyle(container).position;
        if (originalPosition === 'static') {
            container.style.position = 'relative';
        }
        
        container.appendChild(warningDiv);
        
        // 3 saniye sonra otomatik kaybol
        setTimeout(() => {
            if (warningDiv.parentElement) {
                warningDiv.style.opacity = '0';
                warningDiv.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    if (warningDiv.parentElement) warningDiv.remove();
                }, 300);
            }
        }, 3000);
    }
    
    // CSS animasyonu ekle
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .field-warning-message {
            animation: fadeInUp 0.2s ease-out;
        }
    `;
    document.head.appendChild(style);

    // Form submit olayı
    form.addEventListener('submit', function(e) {
        for (const field of requiredFields) {
            const element = document.getElementById(field.id);
            if (!element) continue;

            let isEmpty = false;
            
            if (element.tagName === 'SELECT') {
                if (!element.value || element.value === '' || element.value === 'Seçiniz') {
                    isEmpty = true;
                }
            } else {
                if (!element.value || element.value.trim() === '') {
                    isEmpty = true;
                }
            }
            
            if (isEmpty) {
                e.preventDefault();
                
                // Uyarı mesajını input'un üstünde göster
                const warningMessage = `Lütfen ${field.name} alanını doldurunuz`;
                showWarningAboveInput(element, warningMessage);
                
                // Input'a odaklan ve vurgula
                element.focus();
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Vurgu efekti
                element.style.border = '2px solid #dc3545';
                element.style.backgroundColor = '#fff8f8';
                element.style.transition = 'all 0.2s';
                
                // Odaklanınca vurguyu kaldır
                const removeHighlight = function() {
                    element.style.border = '';
                    element.style.backgroundColor = '';
                    element.removeEventListener('focus', removeHighlight);
                };
                element.addEventListener('focus', removeHighlight, { once: true });
                
                return false;
            }
        }
        
        // Submit butonuna loading efekti
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Kaydediliyor...';
        }
        
        return true;
    });
    
    // Input'a tıklanınca border sıfırla
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('focus', function() {
            this.style.border = '';
            this.style.backgroundColor = '';
        });
    });
});

// =============================================================================
// ALAN DOLDURULUNCA KIRMIZI VURGUYU KALDIR
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#emekliFormu form');
    if (!form) return;
    
    // Tüm input, select ve textarea alanlarını dinle
    const allFields = form.querySelectorAll('input, select, textarea');
    
    allFields.forEach(field => {
        // Alan değiştiğinde (doldurulduğunda) kontrol et
        field.addEventListener('input', function() {
            if (this.value && this.value.trim() !== '') {
                // Alan dolu, kırmızı vurguyu kaldır
                this.style.border = '';
                this.style.borderColor = '';
                this.style.backgroundColor = '';
                this.style.boxShadow = '';
            }
        });
        
        // Select için change event'i
        if (field.tagName === 'SELECT') {
            field.addEventListener('change', function() {
                if (this.value && this.value !== '' && this.value !== 'Seçiniz') {
                    this.style.border = '';
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                    this.style.boxShadow = '';
                }
            });
        }
        
        // Odaklanınca da vurguyu kaldır
        field.addEventListener('focus', function() {
            this.style.border = '';
            this.style.borderColor = '';
            this.style.backgroundColor = '';
            this.style.boxShadow = '';
        });
    });
});

// =============================================================================
// EMEKLİ KAYDI VARSA YENİ KAYIT BUTONUNU PASİF YAP
// =============================================================================
function checkAndDisableNewButton() {
    const yeniEmekliBtn = document.getElementById('yeniEmekliBtn');
    if (!yeniEmekliBtn) return;
    
    // Emekli listesi var mı kontrol et
    const emekliListesi = document.querySelectorAll('.emeklilik-islemleri .content-row:not(.empty-state-table)');
    const emptyState = document.querySelector('.emeklilik-islemleri .empty-state-table');
    
    // Eğer emekli kaydı varsa ve boş liste değilse butonu pasif yap
    if (emekliListesi.length > 0 && !emptyState) {
        yeniEmekliBtn.disabled = true;
        yeniEmekliBtn.style.opacity = '0.5';
        yeniEmekliBtn.style.cursor = 'not-allowed';
        yeniEmekliBtn.title = 'Bu personelin zaten emekli kaydı bulunmaktadır. Yeni kayıt eklenemez.';
        
        // Tıklama olayını engelle
        yeniEmekliBtn.onclick = function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Kayıt Mevcut!',
                text: 'Bu personelin zaten emekli kaydı bulunmaktadır. Yeni kayıt eklenemez.',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#6f42c1'
            });
            return false;
        };
    } else {
        yeniEmekliBtn.disabled = false;
        yeniEmekliBtn.style.opacity = '1';
        yeniEmekliBtn.style.cursor = 'pointer';
        yeniEmekliBtn.title = 'Yeni Emeklilik Kaydı';
        
        // Orijinal fonksiyonu geri yükle
        yeniEmekliBtn.onclick = function() {
            yeniEmekliKontrol();
        };
    }
}

// Sayfa yüklendiğinde kontrol et
document.addEventListener('DOMContentLoaded', function() {
    checkAndDisableNewButton();
});

// Eğer emekli kaydı silinirse butonu tekrar aktif etmek için
// Silme işlemi başarılı olduğunda bu fonksiyonu çağırın
function enableNewButtonAfterDelete() {
    const yeniEmekliBtn = document.getElementById('yeniEmekliBtn');
    if (yeniEmekliBtn) {
        yeniEmekliBtn.disabled = false;
        yeniEmekliBtn.style.opacity = '1';
        yeniEmekliBtn.style.cursor = 'pointer';
        yeniEmekliBtn.title = 'Yeni Emeklilik Kaydı';
        yeniEmekliBtn.onclick = function() {
            yeniEmekliKontrol();
        };
    }
}
</script>