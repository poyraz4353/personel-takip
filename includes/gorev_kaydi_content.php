<?php
/**
 * GÖREV KAYDI SİSTEMİ - Personel Takip Sistemi - gorev_kaydi_content.php
 * @version 3.0
 * @author Fatih
 */
?>

<style>
/* TEK SCROLLBAR - SADECE DIŞ WRAPPER'DA */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border-radius: 0 0 10px 10px;
    max-height: 400px;  /* BUNU EKLEYİN */
    overflow-y: auto;   /* BUNU EKLEYİN */
}

.table-container {
    width: 100%;
    overflow-x: auto;
    border-radius: 0 0 10px 10px;
}

/* Tablo stilleri */
.table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

/* görev kaydı tablosu */
.header-row,
.content-row {
    display: grid;
    grid-template-columns: 
            53px   /* 1. klasör */
            99px  /* 2. başlama tarihi */
            99px  /* 3. ayrılma tarihi */
            121px  /* 4. il */
            134px  /* 5. ilçe */
            99px  /* 6. kurum kodu */
            286px  /* 7. kurum adı */
            143px  /* 8. kadro ünvanı */
            153px  /* 9. görev ünvanı */
            253px  /* 10. atama çeşidi */
            253px; /* 11. yer değiştirme çeşidi */
    gap: 0;
    align-items: stretch;
    min-width: 1693px !important;
}

.header-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px 10px 0 0;
}

.content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
    min-height: 43px;
    cursor: default;
}

/* Dikey çizgilerin hizalanması için */
.header-cell,
.content-cell {
    box-sizing: border-box;
}

.header-cell {
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

.content-cell {
    padding: 8px 4px !important;
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
    cursor: default;
}

.header-cell:last-child,
.content-cell:last-child {
    border-right: none;
}

/* Klasör ikonu - sadece burada pointer olsun */
.folder-row-icon {
    font-size: 22px;
    color: #dc3545;
    cursor: pointer;
    transition: all 0.3s ease;
}

.folder-row-icon:hover {
    color: #c82333;
    transform: scale(1.1);
}

/* özel hücre hizalamaları */
.content-cell:nth-child(4),
.content-cell:nth-child(5),
.content-cell:nth-child(7),
.content-cell:nth-child(8),
.content-cell:nth-child(9),
.content-cell:nth-child(10) {
    text-align: left;
    justify-content: flex-start;
    padding: 8px 8px !important;
}

.content-row:hover .content-cell {
    background: #e3f2fd;
    background: transparent !important;
    cursor: default; /* VEYA BUNU EKLEYİN */
}

/* Form stilleri */
#gorevKaydiFormu {
    max-width: 999px;
    margin: 20px auto;
    width: 90%;
}

#gorevKaydiFormu .personel-content {
    padding: 20px;
}

#gorevKaydiFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#gorevKaydiFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

#gorevKaydiFormu .card-header h3 {
    font-size: 1.2rem;
    margin: 0;
}

#gorevKaydiFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

#gorevKaydiFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 1rem;
    align-items: center;
}

#gorevKaydiFormu .form-group-horizontal .form-label {
    font-weight: 600;
    font-size: 0.9rem;
    margin: 0;
    text-align: left;
}

#gorevKaydiFormu .form-group-horizontal .form-control,
#gorevKaydiFormu .form-group-horizontal .form-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

@media (max-width: 768px) {
    #gorevKaydiFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
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

<!-- Modern Personel Kimlik Kartı -->
<div class="modern-personel-card">
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

    <!-- Görev Kaydı Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Görev Kaydı (MEB İçi)</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Görev Kaydı" onclick="yeniGorevKontrol()" id="yeniGorevBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
                <button class="btn-action btn-danger" 
                        onclick="standartSil(document.getElementById('gorev_id').value, 'gorev', 'Görev Kaydı')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- GÖREV KAYDI FORMU -->
    <div id="gorevKaydiFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_gorev) ? 'block' : 'none' ?>;">
        <form method="POST" action="gorev_kaydi.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_gorev" value="1">
            <input type="hidden" name="gorev_id" id="gorev_id" value="<?= $duzenlenecek_gorev['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_gorev) ? 'Görev Düzenle' : 'Yeni Görev Kaydı' ?>
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
                                <select name="il_id" id="il_id" class="form-select" required>
                                    <option value="">İl Seçiniz</option>
                                    <?php foreach ($iller as $il): ?>
                                        <option value="<?= $il['id'] ?>" 
                                            <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['gorev_il_adi'] == $il['il_adi']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($il['il_adi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">İlçe</label>
                                <select name="ilce_id" id="ilce_id" class="form-select">
                                    <option value="">İlçe Seçiniz</option>
                                    <?php if (!empty($ilceler)): ?>
                                        <?php foreach ($ilceler as $ilce): ?>
                                            <option value="<?= $ilce['id'] ?>" 
                                                <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['gorev_ilce_adi'] == $ilce['ilce_adi']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ilce['ilce_adi']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Görev Yeri</label>
                                <select name="gorev_kurum_kodu" id="gorev_kurum_kodu" class="form-select">
                                    <option value="">Okul Seçiniz</option>
                                    <?php if (!empty($okullar)): ?>
                                        <?php foreach ($okullar as $okul): ?>
                                            <option value="<?= $okul['kurum_kodu'] ?>" 
                                                <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['gorev_kurum_kodu'] == $okul['kurum_kodu']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($okul['gorev_yeri']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="kapali_kurum" id="kapali_kurum" value="1" class="form-check-input"
                                        <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['gorev_kapali_kurum'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label">Kapalı Kurumları Dahil Et</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Göreve Başlama Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-calendar-event"></i>
                        <h3>Göreve Başlama Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Görev Başlama Tarihi</label>
                                <input type="date" name="kurum_baslama_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_gorev['kurum_baslama_tarihi'] ?? '' ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Ayrılma Tarihi</label>
                                <input type="date" name="gorev_ayrilma_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_gorev['bitis_tarihi'] ?? '' ?>">
                                <small class="text-muted">Devam ediyorsa boş bırakın</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstihdam Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-briefcase"></i>
                        <h3>İstihdam Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">İstihdam Tipi</label>
								<select name="istihdam_tipi" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($istihdam_tipleri as $tip): ?>
										<option value="<?= htmlspecialchars($tip) ?>" 
											<?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['istihdam_tipi'] == $tip) ? 'selected' : '' ?>>
											<?= htmlspecialchars($tip) ?>
										</option>
									<?php endforeach; ?>
								</select>
                            </div>

							<div class="form-group-horizontal">
								<label class="form-label">Hizmet Sınıfı</label>
								<select name="hizmet_sinifi" id="hizmet_sinifi" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($hizmet_siniflari as $hs): ?>
										<option value="<?= $hs['id'] ?>" 
											<?= (isset($duzenlenecek_gorev) && isset($duzenlenecek_gorev['hizmet_sinifi_id']) && $duzenlenecek_gorev['hizmet_sinifi_id'] == $hs['id']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($hs['sinif_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Kadro Ünvanı</label>
								<select name="kadro_unvani" id="kadro_unvani" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($kadro_unvanlari as $ku): ?>
										<option value="<?= htmlspecialchars($ku['unvan_adi']) ?>" 
											<?= (isset($duzenlenecek_gorev) && trim($duzenlenecek_gorev['kadro_unvani']) == trim($ku['unvan_adi'])) ? 'selected' : '' ?>>
											<?= htmlspecialchars($ku['unvan_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Görev Ünvanı</label>
								<select name="gorev_unvani" id="gorev_unvani" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($gorev_unvanlari as $gu): ?>
										<option value="<?= htmlspecialchars($gu['unvan_adi']) ?>" 
											<?= (isset($duzenlenecek_gorev) && trim($duzenlenecek_gorev['gorev_unvani']) == trim($gu['unvan_adi'])) ? 'selected' : '' ?>>
											<?= htmlspecialchars($gu['unvan_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Kariyer Basamağı</label>
                                <select name="kariyer_basamagi" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <option value="Uzman Öğretmen" <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['kariyer_basamagi'] == 'Uzman Öğretmen') ? 'selected' : '' ?>>Uzman Öğretmen</option>
                                    <option value="Başöğretmen" <?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['kariyer_basamagi'] == 'Başöğretmen') ? 'selected' : '' ?>>Başöğretmen</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

				<!-- Görev Detayları -->
				<div class="info-card">
					<div class="card-header">
						<i class="bi bi-list-task"></i>
						<h3>Görev Detayları</h3>
					</div>
					<div class="card-body">
						<div class="horizontal-form-grid">
							<!-- İstisnai Durum Checkbox'ı -->
							<?php if (!empty($gorevler)): ?>
							<div class="form-group-horizontal">
								<label class="form-label"></label>
								<div class="form-check">
									<input type="checkbox" name="istisnai_durum" id="istisnai_durum" value="1" class="form-check-input">
									<label class="form-check-label" for="istisnai_durum">
										<i class="bi bi-exclamation-triangle"></i> İstisnai Durum (Atama bilgilerini göster)
									</label>
								</div>
							</div>
							<?php endif; ?>

							<!-- Atama Çeşidi - İlk kayıtta göster, sonraki kayıtlarda istisnai durumda göster -->
							<div class="form-group-horizontal <?= empty($gorevler) ? '' : 'atama-gizli' ?>" <?= (!empty($gorevler) ? 'style="display: none;"' : '') ?>>
								<label class="form-label">Atama Çeşidi</label>
								<select name="atama_cesidi" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($atama_cesitleri as $ac): ?>
										<option value="<?= htmlspecialchars($ac['atama_cesidi']) ?>" 
											<?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['atama_cesidi'] == $ac['atama_cesidi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($ac['atama_cesidi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- Yer Değiştirme Çeşidi - HER ZAMAN GÖSTER -->
							<div class="form-group-horizontal">
								<label class="form-label">Yer Değiştirme Çeşidi</label>
								<select name="yer_degistirme_cesidi" class="form-select">
									<option value="">Seçiniz</option>
									<?php foreach ($yer_degistirme_cesitleri as $yd): ?>
										<option value="<?= htmlspecialchars($yd['yer_degistirme_cesidi']) ?>" 
											<?= (isset($duzenlenecek_gorev) && $duzenlenecek_gorev['yer_degistirme_cesidi'] == $yd['yer_degistirme_cesidi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($yd['yer_degistirme_cesidi']) ?>
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
        </form>
    </div>
    
    <!-- GÖREV LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-briefcase"></i>
                    <h3 style="margin: 0;">Personel Görev Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <div class="header-row">
                                <div class="header-cell"></div>
                                <div class="header-cell">Başlama Tarihi</div>
                                <div class="header-cell">Ayrılma Tarihi</div>
                                <div class="header-cell">İl</div>
                                <div class="header-cell">İlçe</div>
                                <div class="header-cell">Kurum Kodu</div>
                                <div class="header-cell">Kurum Adı</div>
                                <div class="header-cell">Kadro Ünvanı</div>
                                <div class="header-cell">Görev Ünvanı</div>
                                <div class="header-cell">Atama Çeşidi</div>
                                <div class="header-cell">Yer Değiştirme Çeşidi</div>
                            </div>
                        </div>

						<div class="table-content">
							<?php if (!empty($gorevler)): ?>
								<?php foreach ($gorevler as $gorev): ?>
								<div class="content-row">
									<div class="content-cell">
										<i class="bi bi-folder-fill folder-row-icon" 
										   onclick="gorevDuzenle(<?= $gorev['id'] ?? 0 ?>)" 
										   title="Görevi Düzenle"
										   style="cursor: pointer;">
										</i>
									</div>

									<div class="content-cell">
										<?php 
										$tarih = $gorev['kurum_baslama_tarihi'] ?? '';
										echo (!empty($tarih) && $tarih !== '0000-00-00') ? date('d.m.Y', strtotime($tarih)) : '-';
										?>
									</div>
									
									<div class="content-cell">
										<?php 
										$bitis = $gorev['bitis_tarihi'] ?? '';
										if (empty($bitis) || $bitis === '0000-00-00'):
											echo '<span class="badge bg-success">Devam Ediyor</span>';
										else:
											echo date('d.m.Y', strtotime($bitis));
										endif;
										?>
									</div>

									<div class="content-cell"><?= htmlspecialchars($gorev['gorev_il_adi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['gorev_ilce_adi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['gorev_kurum_kodu'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['gorev_okul_adi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['kadro_unvani_adi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['gorev_unvani_adi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['atama_cesidi'] ?? '-') ?></div>
									<div class="content-cell"><?= htmlspecialchars($gorev['yer_degistirme_cesidi'] ?? '-') ?></div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="empty-state-table">
									<div class="text-center text-muted py-5">
										<i class="bi bi-inbox display-4 d-block mb-3"></i>
										<h5>Görev Kaydı Bulunamadı</h5>
										<p class="mb-0">Bu personele ait görev kaydı bulunamadı.</p>
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
// STANDART SİLME FONKSİYONU
// =============================================================================
function standartSil(id, modul, itemName, onSuccess) {
    if (!id || id === 0) {
        showWarningToast('Silinecek kayıt seçilmedi.');
        return;
    }
    
    Swal.fire({
        title: 'Silme Onayı',
        html: '<p style="font-size:1.1rem; margin-bottom:10px;">Bu ' + itemName + ' silmek istediğinize emin misiniz?</p><p style="color: #dc3545; font-weight: bold; margin-top:5px;">⚠️ Bu işlem geri alınamaz!</p>',
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
    }).then(function(result) {
        if (result.isConfirmed) {
            var silBtn = document.querySelector('.btn-action.btn-danger, .btn-sil');
            if (silBtn) {
                silBtn.disabled = true;
                silBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Siliniyor...';
            }
            
            fetch('api/sil.php?modul=' + modul + '&id=' + encodeURIComponent(id), {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof kapatForm === 'function') {
                        kapatForm();
                    }
                    
                    showSuccessToast(data.message || itemName + ' başarıyla silindi.');
                    
                    setTimeout(function() {
                        if (onSuccess && typeof onSuccess === 'function') {
                            onSuccess(data);
                        } else {
                            var url = new URL(window.location.href);
                            url.searchParams.delete('duzenle_id');
                            window.location.href = url.toString();
                        }
                    }, 2500);
                } else {
                    throw new Error(data.error || 'Silme işlemi başarısız.');
                }
            })
            .catch(function(error) {
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
// TOAST FONKSİYONLARI
// =============================================================================
function showSuccessToast(message) {
    var existingToasts = document.querySelectorAll('.custom-toast-container');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastHtml = '<div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">' +
        '<div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #d1e7dd; color: #0f5132;">' +
        '<div class="d-flex flex-column">' +
        '<div class="toast-header border-0" style="background-color: #d1e7dd; color: #0f5132;">' +
        '<i class="bi bi-check-circle-fill me-2 fs-4" style="color: #0f5132;"></i>' +
        '<strong class="me-auto fs-5" style="color: #0f5132;">Başarılı!</strong>' +
        '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.2);"></button>' +
        '</div>' +
        '<div class="toast-body text-center py-3" style="background-color: #d1e7dd; color: #0f5132;">' +
        '<p class="mb-3 fs-5">' + message + '</p>' +
        '<div class="progress" style="height: 4px; background-color: #badbcc;">' +
        '<div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #0f5132;"></div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    var toastElement = document.querySelector('.custom-toast-container .toast');
    var toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    var progressBar = toastElement.querySelector('.progress-bar');
    setTimeout(function() { if (progressBar) progressBar.style.width = '0%'; }, 50);
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        var container = this.closest('.custom-toast-container');
        if (container) container.remove();
    });
}

function showWarningToast(message) {
    var existingToasts = document.querySelectorAll('.custom-toast-container');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastHtml = '<div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">' +
        '<div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #fff3cd; color: #856404;">' +
        '<div class="d-flex flex-column">' +
        '<div class="toast-header border-0" style="background-color: #fff3cd; color: #856404;">' +
        '<i class="bi bi-exclamation-triangle-fill me-2 fs-4" style="color: #856404;"></i>' +
        '<strong class="me-auto fs-5" style="color: #856404;">Uyarı!</strong>' +
        '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.4);"></button>' +
        '</div>' +
        '<div class="toast-body text-center py-3" style="background-color: #fff3cd; color: #856404;">' +
        '<p class="mb-3 fs-5">' + message + '</p>' +
        '<div class="progress" style="height: 4px; background-color: #ffeeba;">' +
        '<div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #856404;"></div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    var toastElement = document.querySelector('.custom-toast-container .toast');
    var toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        var container = this.closest('.custom-toast-container');
        if (container) container.remove();
    });
}

function showErrorToast(message) {
    var existingToasts = document.querySelectorAll('.custom-toast-container');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastHtml = '<div class="custom-toast-container position-fixed top-50 start-50 translate-middle" style="z-index: 10000;">' +
        '<div class="toast align-items-center border-0 shadow-lg" role="alert" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 350px; background-color: #f8d7da; color: #721c24;">' +
        '<div class="d-flex flex-column">' +
        '<div class="toast-header border-0" style="background-color: #f8d7da; color: #721c24;">' +
        '<i class="bi bi-x-circle-fill me-2 fs-4" style="color: #721c24;"></i>' +
        '<strong class="me-auto fs-5" style="color: #721c24;">Hata!</strong>' +
        '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="filter: invert(0.3);"></button>' +
        '</div>' +
        '<div class="toast-body text-center py-3" style="background-color: #f8d7da; color: #721c24;">' +
        '<p class="mb-3 fs-5">' + message + '</p>' +
        '<div class="progress" style="height: 4px; background-color: #f5c6cb;">' +
        '<div class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear; background-color: #721c24;"></div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    var toastElement = document.querySelector('.custom-toast-container .toast');
    var toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        var container = this.closest('.custom-toast-container');
        if (container) container.remove();
    });
}

// =============================================================================
// GÖREV DÜZENLEME
// =============================================================================
function gorevDuzenle(gorevId) {
    if (!gorevId || gorevId === 0) {
        alert('Geçersiz görev ID');
        return;
    }
    window.location.href = 'gorev_kaydi.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + gorevId;
}

// =============================================================================
// YENİ GÖREV KAYDI
// =============================================================================
function yeniGorevKontrol() {
    // Devam eden görev var mı kontrol et (PHP'den kontrol)
    <?php
    $devamEdenGorevVar = false;
    $devamEdenGorevBaslangic = '';
    foreach ($gorevler as $gorev) {
        $bitis = $gorev['bitis_tarihi'] ?? '';
        if (empty($bitis) || $bitis === '0000-00-00') {
            $devamEdenGorevVar = true;
            $devamEdenGorevBaslangic = $gorev['kurum_baslama_tarihi'] ?? '';
            break;
        }
    }
    ?>
    
    <?php if ($devamEdenGorevVar): ?>
        Swal.fire({
            title: 'Devam Eden Görev Kaydı Var!',
            html: '<p style="font-size:1.1rem; margin-bottom:10px;">Bu personelin devam eden bir görev kaydı bulunmaktadır.</p>' +
                  '<p style="color: #dc3545; font-weight: bold; margin-top:5px;">⚠️ Yeni görev kaydı ekleyebilmek için önce mevcut görevi sonlandırmanız gerekmektedir.</p>',
            icon: 'warning',
            confirmButtonColor: '#dc3545',
            confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Tamam',
            background: '#fff3cd',
            color: '#856404'
        });
        return false;
    <?php endif; ?>
    
    document.getElementById('gorev_id').value = '';
    document.getElementById('formBaslik').textContent = 'Yeni Görev Kaydı';
    var form = document.querySelector('#gorevKaydiFormu form');
    if (form) form.reset();
    document.getElementById('gorevKaydiFormu').style.display = 'block';
    document.getElementById('gorevKaydiFormu').scrollIntoView({ behavior: 'smooth' });
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    document.getElementById('gorevKaydiFormu').style.display = 'none';
    document.getElementById('gorev_id').value = '';
    document.getElementById('formBaslik').textContent = 'Yeni Görev Kaydı';
    var form = document.querySelector('#gorevKaydiFormu form');
    if (form) form.reset();
}

// =============================================================================
// İL-İLÇE-OKUL ZİNCİRİ
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    var ilSelect = document.getElementById('il_id');
    var ilceSelect = document.getElementById('ilce_id');
    var okulSelect = document.getElementById('gorev_kurum_kodu');
    var kapaliCheck = document.getElementById('kapali_kurum');
    
    if (!ilSelect || !ilceSelect || !okulSelect) return;
    
    // Düzenleme modunda mevcut değerler
    var editModeIlAdi = "<?= isset($duzenlenecek_gorev) ? addslashes($duzenlenecek_gorev['gorev_il_adi']) : '' ?>";
    var editModeIlceAdi = "<?= isset($duzenlenecek_gorev) ? addslashes($duzenlenecek_gorev['gorev_ilce_adi']) : '' ?>";
    var editModeOkulKodu = "<?= isset($duzenlenecek_gorev) ? addslashes($duzenlenecek_gorev['gorev_kurum_kodu']) : '' ?>";
    
    // İl değiştiğinde ilçeleri yükle
    ilSelect.addEventListener('change', function() {
        var ilId = this.value;
        if (!ilId) {
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
        
        var kapali = kapaliCheck && kapaliCheck.checked ? 1 : 0;
        
        fetch('api/get_ilceler.php?il_id=' + ilId + '&kapali_kurum=' + kapali)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
                for (var i = 0; i < data.length; i++) {
                    var option = document.createElement('option');
                    option.value = data[i].id;
                    option.textContent = data[i].ilce_adi;
                    ilceSelect.appendChild(option);
                }
                ilceSelect.disabled = false;
                
                // Düzenleme modunda ilçeyi SEÇ (AJAX yanıtı geldikten HEMEN sonra)
                if (editModeIlceAdi) {
                    for (var i = 0; i < ilceSelect.options.length; i++) {
                        if (ilceSelect.options[i].text === editModeIlceAdi) {
                            ilceSelect.selectedIndex = i;
                            ilceSelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                }
            })
            .catch(function(err) {
                console.error('İlçe yüklenemedi:', err);
                ilceSelect.innerHTML = '<option value="">Hata oluştu</option>';
            });
    });
    
    // İlçe değiştiğinde okulları yükle
    ilceSelect.addEventListener('change', function() {
        var ilceId = this.value;
        if (!ilceId) {
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            okulSelect.disabled = true;
            return;
        }
        okulSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        okulSelect.disabled = true;
        
        var kapali = kapaliCheck && kapaliCheck.checked ? 1 : 0;
        
        fetch('api/get_okullar.php?ilce_id=' + ilceId + '&kapali_kurum=' + kapali)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
                for (var i = 0; i < data.length; i++) {
                    var option = document.createElement('option');
                    option.value = data[i].kurum_kodu;
                    option.textContent = data[i].gorev_yeri;
                    okulSelect.appendChild(option);
                }
                okulSelect.disabled = false;
                
                // Düzenleme modunda okulu SEÇ (AJAX yanıtı geldikten HEMEN sonra)
                if (editModeOkulKodu) {
                    for (var i = 0; i < okulSelect.options.length; i++) {
                        if (okulSelect.options[i].value === editModeOkulKodu) {
                            okulSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            })
            .catch(function(err) {
                console.error('Okullar yüklenemedi:', err);
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
    
    // Düzenleme modunda İL SEÇ (anında, setTimeout yok)
    if (editModeIlAdi) {
        for (var i = 0; i < ilSelect.options.length; i++) {
            if (ilSelect.options[i].text === editModeIlAdi) {
                ilSelect.selectedIndex = i;
                ilSelect.dispatchEvent(new Event('change'));
                break;
            }
        }
    }
});

// =============================================================================
// SAYFA YÜKLENDİĞİNDE TOAST YÖNETİMİ
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Toast yönetimi (mevcut kod)
    var toastElement = document.getElementById('successToast');
    if (toastElement) {
        var toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
        var progressBar = document.getElementById('toastProgressBar');
        if (progressBar) {
            setTimeout(function() { progressBar.style.width = '0%'; }, 50);
        }
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function() {
            var container = this.closest('.toast-container');
            if (container) container.remove();
        });
    }
    
	// ========== DÜZENLEME MODUNDA KADRO/GÖREV ÜNVANLARINI YÜKLE ==========
	<?php if (isset($duzenlenecek_gorev) && !empty($duzenlenecek_gorev['hizmet_sinifi_id'])): ?>
	setTimeout(function() {
		var hizmetId = "<?= $duzenlenecek_gorev['hizmet_sinifi_id'] ?>";
		var mevcutKadro = "<?= addslashes($duzenlenecek_gorev['kadro_unvani']) ?>";
		var mevcutGorev = "<?= addslashes($duzenlenecek_gorev['gorev_unvani']) ?>";
		
		var kadroSelect = document.getElementById('kadro_unvani');
		var gorevSelect = document.getElementById('gorev_unvani');
		
		if (!kadroSelect || !gorevSelect || !hizmetId) return;
		
		// Kadro ünvanlarını yükle
		fetch('api/kadro_unvanlari.php?hizmet_sinif_id=' + encodeURIComponent(hizmetId))
			.then(function(res) { return res.json(); })
			.then(function(data) {
				if (data && data.length > 0) {
					kadroSelect.innerHTML = '<option value="">Seçiniz</option>';
					for (var i = 0; i < data.length; i++) {
						var option = document.createElement('option');
						option.value = data[i].unvan_adi;
						option.textContent = data[i].unvan_adi;
						kadroSelect.appendChild(option);
					}
					if (mevcutKadro) {
						for (var i = 0; i < kadroSelect.options.length; i++) {
							if (kadroSelect.options[i].value === mevcutKadro) {
								kadroSelect.selectedIndex = i;
								break;
							}
						}
					}
				}
			});
		
		// Görev ünvanlarını yükle
		fetch('api/gorev_unvanlari.php?hizmet_sinif_id=' + encodeURIComponent(hizmetId))
			.then(function(res) { return res.json(); })
			.then(function(data) {
				if (data && data.length > 0) {
					gorevSelect.innerHTML = '<option value="">Seçiniz</option>';
					for (var i = 0; i < data.length; i++) {
						var option = document.createElement('option');
						option.value = data[i].unvan_adi;
						option.textContent = data[i].unvan_adi;
						gorevSelect.appendChild(option);
					}
					if (mevcutGorev) {
						for (var i = 0; i < gorevSelect.options.length; i++) {
							if (gorevSelect.options[i].value === mevcutGorev) {
								gorevSelect.selectedIndex = i;
								break;
							}
						}
					}
				}
			});
	}, 100);
	<?php endif; ?>
});

// =============================================================================
// HİZMET SINIFI DEĞİŞİNCE KADRO VE GÖREV ÜNVANLARINI GÜNCELLE
// =============================================================================
var hizmetSinifiSelect = document.getElementById('hizmet_sinifi');
if (hizmetSinifiSelect) {
    hizmetSinifiSelect.addEventListener('change', function() {
        var hizmetId = this.value;
        var kadroSelect = document.getElementById('kadro_unvani');
        var gorevSelect = document.getElementById('gorev_unvani');
        
        if (!hizmetId) {
            if (kadroSelect) kadroSelect.innerHTML = '<option value="">Seçiniz</option>';
            if (gorevSelect) gorevSelect.innerHTML = '<option value="">Seçiniz</option>';
            return;
        }
        
        // Kadro ünvanlarını yükle (ID ile)
        if (kadroSelect) {
            kadroSelect.innerHTML = '<option value="">Yükleniyor...</option>';
            fetch('api/kadro_unvanlari.php?hizmet_sinif_id=' + encodeURIComponent(hizmetId))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    kadroSelect.innerHTML = '<option value="">Seçiniz</option>';
                    if (data && data.length > 0) {
                        for (var i = 0; i < data.length; i++) {
                            var option = document.createElement('option');
                            option.value = data[i].unvan_adi;
                            option.textContent = data[i].unvan_adi;
                            kadroSelect.appendChild(option);
                        }
                    } else {
                        kadroSelect.innerHTML = '<option value="">Veri bulunamadı</option>';
                    }
                })
                .catch(function(err) {
                    console.error('Kadro ünvanları yüklenemedi:', err);
                    kadroSelect.innerHTML = '<option value="">Hata oluştu</option>';
                });
        }
        
        // Görev ünvanlarını yükle (ID ile)
        if (gorevSelect) {
            gorevSelect.innerHTML = '<option value="">Yükleniyor...</option>';
            fetch('api/gorev_unvanlari.php?hizmet_sinif_id=' + encodeURIComponent(hizmetId))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    gorevSelect.innerHTML = '<option value="">Seçiniz</option>';
                    if (data && data.length > 0) {
                        for (var i = 0; i < data.length; i++) {
                            var option = document.createElement('option');
                            option.value = data[i].unvan_adi;
                            option.textContent = data[i].unvan_adi;
                            gorevSelect.appendChild(option);
                        }
                    } else {
                        gorevSelect.innerHTML = '<option value="">Veri bulunamadı</option>';
                    }
                })
                .catch(function(err) {
                    console.error('Görev ünvanları yüklenemedi:', err);
                    gorevSelect.innerHTML = '<option value="">Hata oluştu</option>';
                });
        }
    });
}

// =============================================================================
// İSTİSNAİ DURUM - ATAMA ÇEŞİDİ GÖSTER/GİZLE
// =============================================================================
var istisnaiDurumCheckbox = document.getElementById('istisnai_durum');
if (istisnaiDurumCheckbox) {
    istisnaiDurumCheckbox.addEventListener('change', function() {
        var atamaAlani = document.querySelector('.atama-gizli');
        if (atamaAlani) {
            atamaAlani.style.display = this.checked ? 'grid' : 'none';
        }
    });
    
    // Düzenleme modunda mevcut atama çeşidi varsa göster
    <?php if (isset($duzenlenecek_gorev) && !empty($duzenlenecek_gorev['atama_cesidi'])): ?>
    istisnaiDurumCheckbox.checked = true;
    var atamaAlani = document.querySelector('.atama-gizli');
    if (atamaAlani) atamaAlani.style.display = 'grid';
    <?php endif; ?>
}

</script>