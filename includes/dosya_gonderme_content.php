<?php
/**
 * DOSYA GÖNDERME - Personel Takip Sistemi - dosya_gonderme_content.php
 * @version 1.0
 * @author Fatih
 */
?>

<style>
/* DOSYA GÖNDERME SAYFASI ÖZEL STİLLERİ */
.dosya-gonderme .table-wrapper {
    overflow-x: auto;
    width: 100%;
    border-radius: 10px;
}

.dosya-gonderme .table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

.dosya-gonderme .header-row,
.dosya-gonderme .content-row {
    display: grid !important;
    grid-template-columns: 
        53px   /* 1. klasör/ikon */
        121px  /* 4. gittiği il */
        134px  /* 5. gittiği ilçe */
        286px  /* 6. gittiği okul */
        99px   /* 2. gönderme tarihi */
        86px   /* 3. gönderme sayısı */
        113px  /* 7. gönderme çeşidi */
        143px  /* 8. teslim alan kurye */
        99px   /* 9. kurye teslim tarihi */
        120px  /* 10. dosya durumu */
        99px   /* 11. teslim tarihi */
        86px   /* 12. teslim sayısı */
        143px  /* 13. teslim alan */
        120px  /* 14. posta barkod no */
        86px   /* 15. raf no */
    !important;
    gap: 0;
    align-items: stretch !important;
    min-width: 1788px !important;
}

.dosya-gonderme .header-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

.dosya-gonderme .content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
    min-height: 38px;
}

.dosya-gonderme .header-cell {
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

.dosya-gonderme .content-cell {
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

.dosya-gonderme .header-cell:last-child,
.dosya-gonderme .content-cell:last-child {
    border-right: none;
}

/* özel hücre hizalamaları */
.dosya-gonderme .content-cell:nth-child(2),
.dosya-gonderme .content-cell:nth-child(3),
.dosya-gonderme .content-cell:nth-child(4),
.dosya-gonderme .content-cell:nth-child(5),
.dosya-gonderme .content-cell:nth-child(7),
.dosya-gonderme .content-cell:nth-child(8),
.dosya-gonderme .content-cell:nth-child(10),
.dosya-gonderme .content-cell:nth-child(13){
    text-align: left;
    justify-content: flex-start;
    padding: 5px 8px !important;
}

/* klasör ikonu */
.dosya-gonderme .folder-row-icon {
    font-size: 22px;
    color: #dc3545;
    cursor: pointer;
    transition: all 0.3s ease;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dosya-gonderme .folder-row-icon:hover {
    color: #c82333;
    transform: scale(1.1);
}

/* ===== DOSYA GÖNDERME FORMU - ORTADA VE DAR ===== */
#dosyaGondermeFormu {
    max-width: 900px;
    margin: 20px auto;
    width: 90%;
}

#dosyaGondermeFormu .personel-content {
    padding: 20px;
}

#dosyaGondermeFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
}

#dosyaGondermeFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
}

#dosyaGondermeFormu .card-header h3 {
    font-size: 1.2rem;
}

#dosyaGondermeFormu .card-header i {
    font-size: 1.3rem;
}

#dosyaGondermeFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

#dosyaGondermeFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 1rem;
    align-items: center;
    margin-bottom: 0.1rem;
}

#dosyaGondermeFormu .form-group-horizontal .form-label {
    font-weight: 600;
    color: var(--text-koyu);
    font-size: 0.9rem;
    margin-bottom: 0;
    text-align: left;
    line-height: 1.4;
}

#dosyaGondermeFormu .form-group-horizontal .form-control,
#dosyaGondermeFormu .form-group-horizontal .form-select,
#dosyaGondermeFormu .form-group-horizontal textarea {
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

#dosyaGondermeFormu .form-group-horizontal textarea {
    min-height: 70px;
    resize: vertical;
}

/* responsive */
@media (max-width: 768px) {
    #dosyaGondermeFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }
    
    #dosyaGondermeFormu .form-group-horizontal .form-label {
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

<!-- UYARI MESAJI TOAST (Turuncu) -->
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
<div class="modern-personel-card dosya-gonderme">
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

    <!-- Dosya Gönderme Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Dosya Gönderme</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Dosya Kaydı" onclick="yeniDosyaKontrol()" id="yeniDosyaBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
				<button class="btn-action btn-danger" 
						onclick="standartSil(document.getElementById('dosya_id').value, 'dosya', 'Dosya Kaydı')">
					<i class="bi bi-trash"></i>
				</button>
            </div>
        </div>
    </div>

    <!-- DOSYA GÖNDERME FORMU -->
    <div id="dosyaGondermeFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_dosya) ? 'block' : 'none' ?>;">
        <form method="POST" action="dosya_gonderme.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_dosya" value="1">
            <input type="hidden" name="dosya_id" id="dosya_id" value="<?= $duzenlenecek_dosya['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_dosya) ? 'Dosya Kaydı Düzenle' : 'Yeni Dosya Kaydı' ?>
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
				<!-- Gönderim Bilgileri -->
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-send"></i>
						<h3>Gönderim Bilgileri</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<!-- İL SEÇİMİ (Dinamik) -->
							<div class="form-group-horizontal">
								<label class="form-label">Gittiği İl</label>
							<select name="gittigi_il" id="gittigi_il" class="form-select dynamic-select"
								data-edit-il="<?= htmlspecialchars($duzenlenecek_dosya['gittigi_il'] ?? '') ?>">
								<option value="">İl Seçiniz</option>
								<?php if (!empty($iller)): ?>
									<?php foreach ($iller as $il): ?>
										<option value="<?= htmlspecialchars($il['il_adi']) ?>" 
											data-il-id="<?= $il['id'] ?>"
											<?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gittigi_il'] == $il['il_adi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($il['il_adi']) ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							</div>

							<!-- İLÇE SEÇİMİ (Dinamik) -->
							<div class="form-group-horizontal">
								<label class="form-label">Gittiği İlçe</label>
								<select name="gittigi_ilce" id="gittigi_ilce" class="form-select dynamic-select"
									data-edit-ilce="<?= htmlspecialchars($duzenlenecek_dosya['gittigi_ilce'] ?? '') ?>">
									<option value="">Önce il seçin</option>
									<?php if (!empty($ilceler)): ?>
										<?php foreach ($ilceler as $ilce): ?>
											<option value="<?= htmlspecialchars($ilce['ilce_adi']) ?>" 
												<?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gittigi_ilce'] == $ilce['ilce_adi']) ? 'selected' : '' ?>>
												<?= htmlspecialchars($ilce['ilce_adi']) ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>

							<!-- OKUL/KURUM SEÇİMİ (Dinamik) -->
							<div class="form-group-horizontal">
								<label class="form-label">Gittiği Okul/Kurum</label>
								<select name="gittigi_okul" id="gittigi_okul" class="form-select dynamic-select"
									data-edit-okul="<?= htmlspecialchars($duzenlenecek_dosya['gittigi_okul'] ?? '') ?>">
									<option value="">Önce ilçe seçin</option>
									<?php if (!empty($okullar)): ?>
										<?php foreach ($okullar as $okul): ?>
											<option value="<?= htmlspecialchars($okul['gorev_yeri']) ?>"
												<?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gittigi_okul'] == $okul['gorev_yeri']) ? 'selected' : '' ?>>
												<?= htmlspecialchars($okul['gorev_yeri']) ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>

							<!-- GÖNDERME TARİHİ -->
							<div class="form-group-horizontal">
								<label class="form-label">Gönderme Tarihi</label>
								<input type="date" name="gonderme_tarihi" id="gonderme_tarihi" class="form-control" 
									value="<?= $duzenlenecek_dosya['gonderme_tarihi'] ?? '' ?>">
							</div>

							<!-- GÖNDERME SAYISI -->
							<div class="form-group-horizontal">
								<label class="form-label">Gönderme Sayısı</label>
								<input type="number" name="gonderme_sayisi" id="gonderme_sayisi" class="form-control" min="1" 
									value="<?= $duzenlenecek_dosya['gonderme_sayisi'] ?? 1 ?>">
							</div>

							<!-- GÖNDERME ÇEŞİDİ -->
							<div class="form-group-horizontal">
								<label class="form-label">Gönderme Çeşidi</label>
								<select name="gonderme_cesidi" id="gonderme_cesidi" class="form-select">
									<option value="Elden" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gonderme_cesidi'] == 'Elden') ? 'selected' : '' ?>>Elden</option>
									<option value="Posta" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gonderme_cesidi'] == 'Posta') ? 'selected' : '' ?>>Posta</option>
									<option value="Kargo" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gonderme_cesidi'] == 'Kargo') ? 'selected' : '' ?>>Kargo</option>
									<option value="Kurye" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gonderme_cesidi'] == 'Kurye') ? 'selected' : '' ?>>Kurye</option>
									<option value="DYS" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['gonderme_cesidi'] == 'DYS') ? 'selected' : '' ?>>DYS</option>
								</select>
							</div>

						</div>
					</div>
				</div>

                <!-- Kurye Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-truck"></i>
                        <h3>Kurye Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Teslim Alan Evrak Kayıt/Kurye</label>
                                <input type="text" name="teslim_alan_kurye" id="teslim_alan_kurye" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_dosya['teslim_alan_kurye'] ?? '') ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Evrak Kayıt/Kurye Teslim Tarihi</label>
                                <input type="date" name="kurye_teslim_tarihi" id="kurye_teslim_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_dosya['kurye_teslim_tarihi'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teslim Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-check2-circle"></i>
                        <h3>Teslim Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Dosya Durumu</label>
                                <select name="dosya_durumu" id="dosya_durumu" class="form-select">
                                    <option value="Hazırlanıyor" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['dosya_durumu'] == 'Hazırlanıyor') ? 'selected' : '' ?>>Hazırlanıyor</option>
                                    <option value="Gönderildi" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['dosya_durumu'] == 'Gönderildi') ? 'selected' : '' ?>>Gönderildi</option>
                                    <option value="Yolda" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['dosya_durumu'] == 'Yolda') ? 'selected' : '' ?>>Yolda</option>
                                    <option value="Teslim Edildi" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['dosya_durumu'] == 'Teslim Edildi') ? 'selected' : '' ?>>Teslim Edildi</option>
                                    <option value="İade Edildi" <?= (isset($duzenlenecek_dosya) && $duzenlenecek_dosya['dosya_durumu'] == 'İade Edildi') ? 'selected' : '' ?>>İade Edildi</option>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Teslim Tarihi</label>
                                <input type="date" name="teslim_tarihi" id="teslim_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_dosya['teslim_tarihi'] ?? '' ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Teslim Sayısı</label>
                                <input type="number" name="teslim_sayisi" id="teslim_sayisi" class="form-control" min="0" 
                                    value="<?= $duzenlenecek_dosya['teslim_sayisi'] ?? 0 ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Teslim Alan</label>
                                <input type="text" name="teslim_alan" id="teslim_alan" class="form-control" 
                                    value="<?= htmlspecialchars($duzenlenecek_dosya['teslim_alan'] ?? '') ?>">
                            </div>

							<!-- POSTA/KARGO BARKOD NO -->
							<div class="form-group-horizontal">
								<label class="form-label">Posta/Kargo Barkod No</label>
								<input type="text" name="posta_barkod_no" id="posta_barkod_no" class="form-control" 
									value="<?= htmlspecialchars($duzenlenecek_dosya['posta_barkod_no'] ?? '') ?>">
							</div>
							
							<!-- RAF NO (ARŞİV) -->
							<div class="form-group-horizontal">
								<label class="form-label">Raf No (Arşiv)</label>
								<input type="text" name="raf_no" id="raf_no" class="form-control" 
									value="<?= htmlspecialchars($duzenlenecek_dosya['raf_no'] ?? '') ?>">
							</div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" id="aciklama" class="form-control" rows="3"><?= htmlspecialchars($duzenlenecek_dosya['aciklama'] ?? '') ?></textarea>
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
    
    <!-- DOSYA GÖNDERME LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-send"></i>
                    <h3 style="margin: 0;">Gönderilen Dosyalar Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
                        <div class="table-header">
							<div class="header-row">
								<div class="header-cell"></div>
								<div class="header-cell">Gittiği İl</div>
								<div class="header-cell">Gittiği İlçe</div>
								<div class="header-cell">Gittiği Okul</div>
								<div class="header-cell">Gönderme Tarihi</div>
								<div class="header-cell">Gönderme Sayısı</div>
								<div class="header-cell">Gönderme Çeşidi</div>
								<div class="header-cell">Teslim Alan Kurye</div>
								<div class="header-cell">Kurye Teslim Tarihi</div>
								<div class="header-cell">Dosya Durumu</div>
								<div class="header-cell">Teslim Tarihi</div>
								<div class="header-cell">Teslim Sayısı</div>
								<div class="header-cell">Teslim Alan</div>
								<div class="header-cell">Barkod No</div>
								<div class="header-cell">Raf No</div>
							</div>
                        </div>

						<div class="table-content">
							<?php if (!empty($dosya_listesi)): ?>
								<?php foreach ($dosya_listesi as $dosya): ?>
								<div class="content-row" data-id="<?= $dosya['id'] ?>">
									<div class="content-cell">
										<i class="bi bi-folder-fill folder-row-icon" 
										   onclick="dosyaDuzenle(<?= $dosya['id'] ?? 0 ?>)" 
										   title="Kaydı Düzenle"
										   style="cursor: pointer;">
										</i>
									</div>

									<div class="content-cell"><?= !empty($dosya['gittigi_il']) ? htmlspecialchars($dosya['gittigi_il']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['gittigi_ilce']) ? htmlspecialchars($dosya['gittigi_ilce']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['gittigi_okul']) ? htmlspecialchars($dosya['gittigi_okul']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['gonderme_tarihi']) ? date('d.m.Y', strtotime($dosya['gonderme_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['gonderme_sayisi']) ? $dosya['gonderme_sayisi'] : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['gonderme_cesidi']) ? htmlspecialchars($dosya['gonderme_cesidi']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['teslim_alan_kurye']) ? htmlspecialchars($dosya['teslim_alan_kurye']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['kurye_teslim_tarihi']) ? date('d.m.Y', strtotime($dosya['kurye_teslim_tarihi'])) : '-' ?></div>
									<div class="content-cell">
										<?php
										$durum = $dosya['dosya_durumu'] ?? '';
										if (empty($durum)) {
											echo '-';
										} else {
											$renk = 'secondary';
											if ($durum == 'Hazırlanıyor') $renk = 'warning';
											elseif ($durum == 'Gönderildi') $renk = 'info';
											elseif ($durum == 'Yolda') $renk = 'primary';
											elseif ($durum == 'Teslim Edildi') $renk = 'success';
											elseif ($durum == 'İade Edildi') $renk = 'danger';
											echo '<span class="badge bg-'.$renk.'">'.htmlspecialchars($durum).'</span>';
										}
										?>
									</div>
									<div class="content-cell"><?= !empty($dosya['teslim_tarihi']) ? date('d.m.Y', strtotime($dosya['teslim_tarihi'])) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['teslim_sayisi']) ? $dosya['teslim_sayisi'] : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['teslim_alan']) ? htmlspecialchars($dosya['teslim_alan']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['posta_barkod_no']) ? htmlspecialchars($dosya['posta_barkod_no']) : '-' ?></div>
									<div class="content-cell"><?= !empty($dosya['raf_no']) ? htmlspecialchars($dosya['raf_no']) : '-' ?></div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="empty-state-table">
									<div class="text-center text-muted py-5">
										<i class="bi bi-inbox display-4 d-block mb-3"></i>
										<h5>Gönderilen Dosya Bulunamadı</h5>
										<p class="mb-0">Bu personele ait gönderilen dosya kaydı bulunamadı.</p>
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
// DOSYA DÜZENLEME FONKSİYONU - BİTMEMİŞ GÖREV KONTROLÜ
// =============================================================================
function dosyaDuzenle(dosyaId) {
    if (!dosyaId || dosyaId === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Geçersiz kayıt ID',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // BİTMEMİŞ GÖREV KONTROLÜ
    if (window.hasBitmemisGorev === true) {
        Swal.fire({
            icon: 'warning',
            title: 'Uyarı!',
            text: 'Personelin bitmemiş görev kaydı olduğu için düzenleme yapılamaz!',
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Tamam'
        });
        return;
    }
    
    window.location.href = 'dosya_gonderme.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + dosyaId;
}

// =============================================================================
// YENİ DOSYA KAYDI - BİTMEMİŞ GÖREV KONTROLÜ
// =============================================================================
function yeniDosyaKontrol() {
    // BİTMEMİŞ GÖREV KONTROLÜ
    if (window.hasBitmemisGorev === true) {
        Swal.fire({
            icon: 'warning',
            title: 'Uyarı!',
            text: 'Personelin bitmemiş görev kaydı olduğu için işlem yapılamaz!',
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Tamam'
        });
        return;
    }
    
    console.log('➕ Yeni dosya kaydı açılıyor...');
    
    const dosyaIdInput = document.getElementById('dosya_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#dosyaGondermeFormu form');
    const formArea = document.getElementById('dosyaGondermeFormu');
    
    if (!dosyaIdInput || !baslik || !form || !formArea) {
        console.error('❌ Form elementleri bulunamadı!');
        return;
    }
    
    dosyaIdInput.value = '';
    baslik.textContent = 'Yeni Dosya Kaydı';
    form.reset();
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
    
    // Varsayılan değerler
    const gondermeSayisi = document.getElementById('gonderme_sayisi');
    const teslimSayisi = document.getElementById('teslim_sayisi');
    const gondermeCesidi = document.getElementById('gonderme_cesidi');
    const dosyaDurumu = document.getElementById('dosya_durumu');
    
    if (gondermeSayisi) gondermeSayisi.value = 1;
    if (teslimSayisi) teslimSayisi.value = 0;
    if (gondermeCesidi) gondermeCesidi.value = 'Elden';
    if (dosyaDurumu) dosyaDurumu.value = 'Gönderildi';
    
    console.log('✅ Yeni dosya kaydı formu açıldı');
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    console.log('🔒 Form kapatılıyor...');
    
    const form = document.getElementById('dosyaGondermeFormu');
    if (form) {
        form.style.display = 'none';
    }
    
    const dosyaIdInput = document.getElementById('dosya_id');
    if (dosyaIdInput) dosyaIdInput.value = '';
    
    const baslik = document.getElementById('formBaslik');
    if (baslik) baslik.textContent = 'Yeni Dosya Kaydı';
    
    const formElement = document.querySelector('#dosyaGondermeFormu form');
    if (formElement) formElement.reset();
    
    console.log('✅ Form kapatıldı');
}

// =============================================================================
// STANDART SİLME FONKSİYONU - TÜM SAYFALAR İÇİN TEK
// =============================================================================
function standartSil(id, modul, itemName, onSuccess) {
    if (!id || id === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Uyarı!',
            text: 'Silinecek kayıt seçilmedi.',
            confirmButtonText: 'Tamam'
        });
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
                    
                    // Toast mesajını göster
                    showSuccessToast(data.message || `${itemName} başarıyla silindi.`);
                    
                    // Toast gösterildikten SONRA sayfayı yenile
                    setTimeout(function() {
                        if (onSuccess && typeof onSuccess === 'function') {
                            onSuccess(data);
                        } else {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('duzenle_id');
                            window.location.href = url.toString();
                        }
                    }, 2500); // Toast 3 saniye, 2.5 saniye sonra yenile
                } else {
                    throw new Error(data.error || 'Silme işlemi başarısız.');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: error.message,
                    confirmButtonText: 'Tamam'
                });
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

// Uyarı Toast Yönetimi
document.addEventListener('DOMContentLoaded', function() {
    // Başarı toast'ı
    const successToast = document.getElementById('successToast');
    if (successToast) {
        const toast = new bootstrap.Toast(successToast, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('toastProgressBar');
        setTimeout(() => { if(progressBar) progressBar.style.width = '0%'; }, 50);
        toast.show();
        
        // Toast kapandığında DOM'dan temizle
        successToast.addEventListener('hidden.bs.toast', function() {
            this.closest('.toast-container')?.remove();
        });
    }
    
    // Uyarı toast'ı
    const warningToast = document.getElementById('warningToast');
    if (warningToast) {
        const toast = new bootstrap.Toast(warningToast, { autohide: true, delay: 3000 });
        const progressBar = document.getElementById('warningToastProgressBar');
        
        // Progress bar animasyonu
        setTimeout(() => { 
            if(progressBar) progressBar.style.width = '0%'; 
        }, 50);
        
        toast.show();
        
        // Toast kapandığında DOM'dan temizle
        warningToast.addEventListener('hidden.bs.toast', function() {
            this.closest('.toast-container')?.remove();
        });
        
        // Manuel kapatma butonu
        const closeBtn = warningToast.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                toast.hide();
            });
        }
    }
});

// =============================================================================
// FORM GÖNDERİMİNİ ENGELLE (bitmemiş görev varsa)
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#dosyaGondermeFormu form');
    if (form && window.hasBitmemisGorev === true) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Uyarı!',
                text: 'Personelin bitmemiş görev kaydı olduğu için işlem yapılamaz!',
                confirmButtonColor: '#ffc107'
            });
        });
        
        // Butonları da pasif yap
        const submitBtn = document.getElementById('submitButton');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.title = 'Bitmemiş görev kaydı nedeniyle işlem yapılamaz';
        }
        
        const yeniBtn = document.getElementById('yeniDosyaBtn');
        if (yeniBtn) {
            yeniBtn.disabled = true;
            yeniBtn.title = 'Bitmemiş görev kaydı nedeniyle işlem yapılamaz';
        }
    }
});


// =============================================================================
// İL-İLÇE-OKUL ZİNCİRİ (Dosya Gönderme Sayfası İçin)
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const ilSelect = document.getElementById('gittigi_il');
    const ilceSelect = document.getElementById('gittigi_ilce');
    const okulSelect = document.getElementById('gittigi_okul');
    
    if (!ilSelect || !ilceSelect || !okulSelect) {
        console.log('İl-İlçe-Okul elementleri bulunamadı');
        return;
    }
    
    // Düzenleme modunda mevcut değerler
    const editModeIlAdi = ilSelect.getAttribute('data-edit-il');
    const editModeIlceAdi = ilceSelect.getAttribute('data-edit-ilce');
    const editModeOkulAdi = okulSelect.getAttribute('data-edit-okul');
    
    console.log('🔧 Düzenleme modu - İl:', editModeIlAdi, 'İlçe:', editModeIlceAdi, 'Okul:', editModeOkulAdi);
    
    // İl değiştiğinde ilçeleri yükle
    ilSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const ilId = selectedOption?.getAttribute('data-il-id');
        const ilAdi = this.value;
        
        console.log('🔄 İl değişti - ID:', ilId, 'Ad:', ilAdi);
        
        if (!ilId || !ilAdi) {
            ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
            ilceSelect.disabled = true;
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            okulSelect.disabled = true;
            return;
        }
        
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        ilceSelect.disabled = true;
        okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
        okulSelect.disabled = true;
        
        // Mevcut get_ilceler.php API'sini kullan (il_id ile çalışıyor)
        fetch(`api/get_ilceler.php?il_id=${ilId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                console.log('✅ İlçeler yüklendi:', data.length);
                ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
                
                data.forEach(ilce => {
                    const option = document.createElement('option');
                    option.value = ilce.ilce_adi;
                    option.textContent = ilce.ilce_adi;
                    option.setAttribute('data-ilce-id', ilce.id);
                    ilceSelect.appendChild(option);
                });
                
                ilceSelect.disabled = false;
                
                // Düzenleme modunda ilçe seç
                if (editModeIlceAdi) {
                    console.log('🎯 Düzenleme modu, ilçe seçiliyor:', editModeIlceAdi);
                    setTimeout(() => {
                        for (let i = 0; i < ilceSelect.options.length; i++) {
                            if (ilceSelect.options[i].value === editModeIlceAdi) {
                                ilceSelect.selectedIndex = i;
                                ilceSelect.dispatchEvent(new Event('change'));
                                console.log('✅ İlçe seçildi:', editModeIlceAdi);
                                break;
                            }
                        }
                    }, 100);
                }
            })
            .catch(err => {
                console.error('❌ İlçe yüklenemedi:', err);
                ilceSelect.innerHTML = '<option value="">Hata: ' + err.message + '</option>';
                ilceSelect.disabled = false;
            });
    });
    
    // İlçe değiştiğinde okulları yükle
    ilceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const ilceId = selectedOption?.getAttribute('data-ilce-id');
        const ilceAdi = this.value;
        const ilAdi = ilSelect.value;
        
        console.log('🔄 İlçe değişti - ID:', ilceId, 'Ad:', ilceAdi);
        
        if (!ilceId || !ilceAdi || !ilAdi) {
            okulSelect.innerHTML = '<option value="">Önce il ve ilçe seçin</option>';
            okulSelect.disabled = true;
            return;
        }
        
        okulSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        okulSelect.disabled = true;
        
        // Mevcut get_okullar.php API'sini kullan (ilce_id ile çalışıyor)
        fetch(`api/get_okullar.php?ilce_id=${ilceId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                console.log('✅ Okullar yüklendi:', data.length);
                okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
                
                data.forEach(okul => {
                    const option = document.createElement('option');
                    option.value = okul.gorev_yeri;
                    option.textContent = okul.gorev_yeri;
                    okulSelect.appendChild(option);
                });
                
                okulSelect.disabled = false;
                
                // Düzenleme modunda okul seç
                if (editModeOkulAdi) {
                    console.log('🎯 Düzenleme modu, okul seçiliyor:', editModeOkulAdi);
                    setTimeout(() => {
                        for (let i = 0; i < okulSelect.options.length; i++) {
                            if (okulSelect.options[i].value === editModeOkulAdi) {
                                okulSelect.selectedIndex = i;
                                console.log('✅ Okul seçildi:', editModeOkulAdi);
                                break;
                            }
                        }
                    }, 100);
                }
            })
            .catch(err => {
                console.error('❌ Okullar yüklenemedi:', err);
                okulSelect.innerHTML = '<option value="">Hata: ' + err.message + '</option>';
                okulSelect.disabled = false;
            });
    });
    
    // Düzenleme modunda il seç (il adına göre, data-il-id'yi kullan)
    if (editModeIlAdi) {
        console.log('🎯 Düzenleme modu, il seçiliyor:', editModeIlAdi);
        setTimeout(() => {
            for (let i = 0; i < ilSelect.options.length; i++) {
                if (ilSelect.options[i].value === editModeIlAdi) {
                    ilSelect.selectedIndex = i;
                    // Change event'i manuel tetikle
                    const event = new Event('change', { bubbles: true });
                    ilSelect.dispatchEvent(event);
                    console.log('✅ İl seçildi:', editModeIlAdi);
                    break;
                }
            }
        }, 100);
    }
});

</script>