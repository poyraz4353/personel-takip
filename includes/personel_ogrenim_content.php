<?php
/**
 * PERSONEL ÖĞRENİM BİLGİLERİ - İçerik Dosyası
 * @version 1.0
 * @author Fatih
 */
?>

<style>
/* Tablo wrapper - overflow kontrolü */
.personel-ogrenim .table-wrapper {
    overflow-x: auto;
    width: 100%;
    border-radius: 10px;
}

/* Başlık ve içerik satırları AYNI grid yapısını kullanmalı */
.personel-ogrenim .header-row,
.personel-ogrenim .content-row {
    display: grid !important;
    grid-template-columns: 
        50px   /* ikon */
        100px  /* mezuniyet tarihi */
        130px  /* öğrenim durumu */
        180px  /* mezun okul */
        200px  /* üniversite */
        150px  /* fakülte */
        150px  /* anabilim dalı */
        150px  /* program */
        100px  /* belge tarihi */
        100px  /* belge no */
        200px  /* belge cinsi */
        1fr    /* açıklama */
    !important;
    gap: 0;
    align-items: stretch;  /* stretch ile yükseklik eşitlenir */
    min-width: 1600px !important;
}

.personel-ogrenim .header-row {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 10px 10px 0 0;
}

.personel-ogrenim .content-row {
    background: var(--beyaz);
    border-bottom: 1px solid var(--border-renk);
    min-height: 38px;
}

/* Header hücreleri */
.personel-ogrenim .header-cell {
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

/* İçerik hücreleri */
.personel-ogrenim .content-cell {
    padding: 8px 4px !important;  /* padding eşitlendi */
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

/* Son hücrede border-right yok */
.personel-ogrenim .header-cell:last-child,
.personel-ogrenim .content-cell:last-child {
    border-right: none;
}

/* Metin sütunları sola hizalı */
.personel-ogrenim .content-cell:nth-child(4),
.personel-ogrenim .content-cell:nth-child(5),
.personel-ogrenim .content-cell:nth-child(6),
.personel-ogrenim .content-cell:nth-child(7),
.personel-ogrenim .content-cell:nth-child(8),
.personel-ogrenim .content-cell:last-child {
    text-align: left;
    justify-content: flex-start;
}

/* özel hücre hizalamaları */
.personel-ogrenim .content-cell:first-child {
    justify-content: center;
}

/* klasör ikonu */
.personel-ogrenim .folder-row-icon {
    font-size: 22px;
    color: #28a745;
    cursor: pointer;
    transition: all 0.3s ease;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.personel-ogrenim .folder-row-icon:hover {
    color: #20c997;
    transform: scale(1.1);
}

/* Satır üzerinde varsayılan cursor */
.personel-ogrenim .content-row {
    cursor: default;
}

.personel-ogrenim .folder-row-icon:hover {
    color: #20c997;
    transform: scale(1.1);
}

/* ===== ÖĞRENİM FORMU - BAŞLIK 2/6, INPUT 6/6 ORANI ===== */
#ogrenimFormu {
    max-width: 900px;
    margin: 20px auto;
    width: 90%;
}

#ogrenimFormu .personel-content {
    padding: 20px;
}

#ogrenimFormu .info-card {
    padding: 15px;
    margin-bottom: 15px;
}

#ogrenimFormu .card-header {
    margin-bottom: 12px;
    padding-bottom: 10px;
}

#ogrenimFormu .card-header h3 {
    font-size: 1.2rem;
}

#ogrenimFormu .card-header i {
    font-size: 1.3rem;
}

#ogrenimFormu .horizontal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* FORM SATIRLARI - BAŞLIK 2/6, INPUT 6/6 ORANI */
#ogrenimFormu .form-group-horizontal {
    display: grid;
    grid-template-columns: 2fr 6fr;  /* BAŞLIK 2/6 - INPUT 6/6 */
    gap: 1rem;
    align-items: center;
    margin-bottom: 0;
}

#ogrenimFormu .form-group-horizontal .form-label {
    font-weight: 600;
    color: var(--text-koyu);
    font-size: 0.9rem;
    margin-bottom: 0;
    text-align: left;
    line-height: 1.4;
}

#ogrenimFormu .form-group-horizontal .form-control,
#ogrenimFormu .form-group-horizontal .form-select,
#ogrenimFormu .form-group-horizontal textarea {
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

#ogrenimFormu .form-group-horizontal textarea {
    min-height: 70px;
    resize: vertical;
}

/* responsive - mobil görünüm */
@media (max-width: 768px) {
    #ogrenimFormu .form-group-horizontal {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }
    
    #ogrenimFormu .form-group-horizontal .form-label {
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
<div class="modern-personel-card personel-ogrenim">
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

    <!-- Öğrenim Bilgileri Header -->
    <div class="personel-header">
        <div class="header-background"></div>
        <div class="header-content">
            <div class="personel-basic-info">
                <h1 class="personel-name">Öğrenim Bilgileri</h1>
            </div>
            <div class="header-actions">
                <button class="btn-action btn-success" title="Yeni Öğrenim Kaydı" onclick="yeniOgrenimKontrol()" id="yeniOgrenimBtn">
                    <i class="bi bi-plus-circle"></i>
                </button>
				<button class="btn-action btn-danger" 
						onclick="standartSil(document.getElementById('ogrenim_id').value, 'ogrenim', 'Öğrenim Kaydı')">
					<i class="bi bi-trash"></i>
				</button>
            </div>
        </div>
    </div>

    <!-- ÖĞRENİM FORMU -->
    <div id="ogrenimFormu" class="modern-personel-card mt-4" style="display: <?= isset($duzenlenecek_ogrenim) ? 'block' : 'none' ?>;">
        <form method="POST" action="personel_ogrenim.php">
            <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
            <input type="hidden" name="kaydet_ogrenim" value="1">
            <input type="hidden" name="ogrenim_id" id="ogrenim_id" value="<?= $duzenlenecek_ogrenim['id'] ?? '' ?>">
            <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
            
            <div class="personel-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="personel-basic-info">
                        <h1 class="personel-name" id="formBaslik">
                            <?= isset($duzenlenecek_ogrenim) ? 'Öğrenim Bilgisi Düzenle' : 'Yeni Öğrenim Kaydı' ?>
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
                <!-- Öğrenim Durumu -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-mortarboard"></i>
                        <h3>Öğrenim Durumu</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Öğrenim Durumu *</label>
                                <select name="ogrenim_durumu_id" id="ogrenim_durumu_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
									<?php if (is_array($ogrenim_durumlari) && !empty($ogrenim_durumlari)): ?>
										<?php foreach ($ogrenim_durumlari as $od): ?>
											<option value="<?= htmlspecialchars($od['ogrenim_adi'] ?? '') ?>" 
												<?= (isset($duzenlenecek_ogrenim) && ($duzenlenecek_ogrenim['ogrenim_durumu_id'] ?? '') == ($od['ogrenim_adi'] ?? '')) ? 'selected' : '' ?>>
												<?= htmlspecialchars($od['ogrenim_adi'] ?? '') ?>
											</option>
										<?php endforeach; ?>
									<?php else: ?>
										<option value="">Öğrenim durumu verisi bulunamadı</option>
									<?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group-horizontal">
                                <label class="form-label">Mezuniyet Tarihi</label>
                                <input type="date" name="mezuniyet_tarihi" id="mezuniyet_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_ogrenim['mezuniyet_tarihi'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Üniversite Bilgileri (Üniversite seviyesinde gösterilecek) -->
                <div class="info-card" id="universiteKarti">
                    <div class="card-header">
                        <i class="bi bi-building"></i>
                        <h3>Üniversite Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <!-- 1. Üniversite -->
                            <div class="form-group-horizontal">
                                <label class="form-label">Üniversite</label>
                                <select name="universite_id" id="universite_id" class="form-select">
                                    <option value="">Üniversite seçin veya yazın</option>
                                    <?php foreach ($universiteler as $uni): ?>
                                        <option value="<?= htmlspecialchars($uni['universite_adi']) ?>" 
                                            data-universite-id="<?= $uni['id'] ?>"
                                            <?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['universite_adi'] == $uni['universite_adi']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($uni['universite_adi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

							<!-- 2. Fakülte/Enstitü/Yüksekokul -->
							<div class="form-group-horizontal">
								<label class="form-label">Fakülte/Enstitü/Yüksekokul</label>
								<select name="fakulte_yuksekokul_id" id="fakulte_yuksekokul_id" class="form-select">
									<option value="">Fakülte seçin veya yazın</option>
									<?php 
									$fakulte_listesi = isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim['universite_id']) 
										? $database->fetchAll("SELECT fakulte_id as id, fakulte_adi, universite_id FROM fakulte_yuksekokul WHERE universite_id = ? ORDER BY fakulte_adi", [$duzenlenecek_ogrenim['universite_id']])
										: $fakulteler;
									
									foreach ($fakulte_listesi as $fak): 
									?>
										<option value="<?= htmlspecialchars($fak['fakulte_adi']) ?>" 
											data-fakulte-id="<?= $fak['id'] ?>"
											data-universite-id="<?= $fak['universite_id'] ?>"
											<?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['fakulte_adi'] == $fak['fakulte_adi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($fak['fakulte_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- 3. Program / Bölüm -->
							<div class="form-group-horizontal">
								<label class="form-label">Program / Bölüm</label>
								<select name="program_id" id="program_id" class="form-select">
									<option value="">Program seçin veya yazın</option>
									<?php 
									$program_listesi = isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim['fakulte_yuksekokul_id']) 
										? $database->fetchAll("SELECT program_id, program_adi, fakulte_yuksekokul_id FROM program WHERE fakulte_yuksekokul_id = ? ORDER BY program_adi", [$duzenlenecek_ogrenim['fakulte_yuksekokul_id']])
										: $programlar;
									
									foreach ($program_listesi as $prog): 
									?>
										<option value="<?= htmlspecialchars($prog['program_adi']) ?>" 
											data-program-id="<?= $prog['program_id'] ?? $prog['id'] ?>"
											data-fakulte-id="<?= $prog['fakulte_yuksekokul_id'] ?>"
											<?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['program_adi'] == $prog['program_adi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($prog['program_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- 4. Anabilim Dalı -->
							<div class="form-group-horizontal">
								<label class="form-label">Anabilim Dalı</label>
								<select name="anabilim_dali_id" id="anabilim_dali_id" class="form-select">
									<option value="">Anabilim dalı seçin veya yazın</option>
									<?php 
									$anabilim_listesi = isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim['program_id']) 
										? $database->fetchAll("SELECT anabilim_id, anabilim_adi, program_id FROM anabilim_dali WHERE program_id = ? ORDER BY anabilim_adi", [$duzenlenecek_ogrenim['program_id']])
										: $anabilim_dallari;
									
									foreach ($anabilim_listesi as $ad): 
									?>
										<option value="<?= htmlspecialchars($ad['anabilim_adi']) ?>" 
											data-anabilim-id="<?= $ad['anabilim_id'] ?? $ad['id'] ?>"
											data-program-id="<?= $ad['program_id'] ?>"
											<?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['anabilim_adi'] == $ad['anabilim_adi']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($ad['anabilim_adi']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

                        </div>
                    </div>
                </div>

                <!-- Okul Bilgileri (Üniversite dışı öğrenimler için) -->
                <div class="info-card" id="okulKarti" style="display: none;">
                    <div class="card-header">
                        <i class="bi bi-building"></i>
                        <h3>Okul Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">İl</label>
                                <select name="okul_il_id" id="okul_il_id" class="form-select">
                                    <option value="">İl Seçiniz</option>
                                    <?php foreach ($iller as $il): ?>
                                        <option value="<?= $il['id'] ?>" 
                                            <?= (isset($duzenlenecek_il) && $duzenlenecek_il['id'] == $il['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($il['il_adi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group-horizontal">
                                <label class="form-label">İlçe</label>
                                <select name="okul_ilce_id" id="okul_ilce_id" class="form-select">
                                    <option value="">İlçe Seçiniz</option>
                                    <?php if (isset($duzenlenecek_il) && $duzenlenecek_il): ?>
                                        <?php 
                                        $ilce_listesi = $database->fetchAll("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi", [$duzenlenecek_il['id']]);
                                        foreach ($ilce_listesi as $ilce): ?>
                                            <option value="<?= $ilce['id'] ?>" 
                                                <?= (isset($duzenlenecek_ilce) && $duzenlenecek_ilce['id'] == $ilce['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ilce['ilce_adi']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Önce il seçin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group-horizontal">
                                <label class="form-label">Mezun Olduğu Okul</label>
                                <select name="mezun_okul_id" id="mezun_okul_id" class="form-select">
                                    <option value="">Okul Seçiniz</option>
                                    <?php if (isset($duzenlenecek_ilce) && $duzenlenecek_ilce): ?>
                                        <?php 
                                        $okul_listesi = $database->fetchAll("SELECT id, gorev_yeri FROM okullar WHERE ilce_id = ? AND kapali = 0 ORDER BY gorev_yeri", [$duzenlenecek_ilce['id']]);
                                        foreach ($okul_listesi as $okul): ?>
                                            <option value="<?= $okul['id'] ?>" 
                                                <?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['mezun_okul_id'] == $okul['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($okul['gorev_yeri']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Önce ilçe seçin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mezuniyet Belgesi Bilgileri -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="bi bi-file-text"></i>
                        <h3>Mezuniyet Belgesi Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="horizontal-form-grid">
                            <div class="form-group-horizontal">
                                <label class="form-label">Belge Tarihi</label>
                                <input type="date" name="belge_tarihi" id="belge_tarihi" class="form-control" 
                                    value="<?= $duzenlenecek_ogrenim['belge_tarihi'] ?? '' ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Belge No</label>
                                <input type="text" name="belge_no" id="belge_no" class="form-control" 
                                    placeholder="Belge numarası"
                                    value="<?= htmlspecialchars($duzenlenecek_ogrenim['belge_no'] ?? '') ?>">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Belge Cinsi</label>
                                <select name="belge_cinsi" id="belge_cinsi" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($belge_cinsleri as $bc): ?>
                                        <option value="<?= $bc ?>" 
                                            <?= (isset($duzenlenecek_ogrenim) && $duzenlenecek_ogrenim['belge_cinsi'] == $bc) ? 'selected' : '' ?>>
                                            <?= $bc ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label">Açıklama</label>
                                <textarea name="belge_aciklama" id="belge_aciklama" class="form-control" rows="2" 
                                    placeholder="Varsa belgeye dair açıklama"><?= htmlspecialchars($duzenlenecek_ogrenim['belge_aciklama'] ?? '') ?></textarea>
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
    
    <!-- ÖĞRENİM LİSTESİ TABLOSU -->
    <div class="personel-content">
        <div class="content-grid">
            <div class="info-card">
                <div class="card-header" style="padding: 5px 15px; margin-bottom: 10px;">
                    <i class="bi bi-mortarboard"></i>
                    <h3 style="margin: 0;">Öğrenim Bilgileri Listesi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
                        <div class="table-header">
                            <div class="header-row">
                                <div class="header-cell"></div>
                                <div class="header-cell">Mezuniyet Tarihi</div>
                                <div class="header-cell">Öğrenim Durumu</div>
                                <div class="header-cell">Mezun Okul</div>
                                <div class="header-cell">Üniversite</div>
                                <div class="header-cell">Fakülte / Yüksekokul</div>
                                <div class="header-cell">Program / Bölüm</div>
                                <div class="header-cell">Anabilim Dalı</div>
                                <div class="header-cell">Belge Tarihi</div>
                                <div class="header-cell">Belge No</div>
                                <div class="header-cell">Belge Cinsi</div>
                                <div class="header-cell">Açıklama</div>
                            </div>
                        </div>

                        <div class="table-content">
                            <?php if (!empty($ogrenim_listesi)): ?>
                                <?php foreach ($ogrenim_listesi as $ogrenim): ?>
                                <div class="content-row" data-id="<?= $ogrenim['id'] ?>">
                                    <div class="content-cell">
                                        <i class="bi bi-folder-fill folder-row-icon" 
                                           onclick="ogrenimDuzenle(<?= $ogrenim['id'] ?? 0 ?>)" 
                                           title="Öğrenim Bilgisini Düzenle"
                                           style="cursor: pointer;">
                                        </i>
                                    </div>

                                    <div class="content-cell"><?= !empty($ogrenim['mezuniyet_tarihi']) ? date('d.m.Y', strtotime($ogrenim['mezuniyet_tarihi'])) : '-' ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['ogrenim_durumu_id'] ?? '-') ?></div>
                                    
                                    <!-- Mezun Okul: Sadece üniversite seviyesi DEĞİLSE göster, değilse "-" -->
                                    <div class="content-cell">
                                        <?php 
                                        $universiteSeviyeleri = ['Ön Lisans', 'Lisans', 'Yüksek Lisans', 'Doktora'];
                                        $ogrenimDurumu = $ogrenim['ogrenim_durumu_id'] ?? '';
                                        $universiteMi = in_array($ogrenimDurumu, $universiteSeviyeleri);
                                        
                                        if (!$universiteMi && !empty($ogrenim['mezun_okul_adi'])) {
                                            echo htmlspecialchars($ogrenim['mezun_okul_adi']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['universite_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['fakulte_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['program_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['anabilim_adi'] ?? '-') ?></div>
                                    <div class="content-cell"><?= !empty($ogrenim['belge_tarihi']) ? date('d.m.Y', strtotime($ogrenim['belge_tarihi'])) : '-' ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['belge_no'] ?? '-') ?></div>
                                    <div class="content-cell"><?= htmlspecialchars($ogrenim['belge_cinsi'] ?? '-') ?></div>
                                    <div class="content-cell" style="text-align: left; justify-content: flex-start;"><?= htmlspecialchars($ogrenim['belge_aciklama'] ?? '-') ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state-table">
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <h5>Öğrenim Bilgisi Bulunamadı</h5>
                                        <p class="mb-0">Bu personele ait öğrenim bilgisi bulunamadı.</p>
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
// SELECT2 BAŞLATMA (Arama + Yeni Ekleme Özelliği)
// =============================================================================
function initSelect2(selectId, placeholder) {
    $(selectId).select2({
        theme: 'bootstrap-5',
        language: 'tr',
        placeholder: placeholder,
        allowClear: true,
        tags: true,
        width: '100%',
        createTag: function(params) {
            return {
                id: params.term,
                text: params.term,
                newOption: true
            };
        }
    });
}

// =============================================================================
// BAĞIMLI SELECT'LERİ FİLTRELE VE GÜNCELLE
// =============================================================================
function updateDependentSelect(parentSelect, childSelect, dataAttr) {
    const parentValue = $(parentSelect).val();
    
    $(childSelect + ' option').each(function() {
        const option = $(this);
        const parentId = option.data(dataAttr);
        
        if (option.val() === '' || parentId == parentValue || !parentValue) {
            option.show();
        } else {
            option.hide();
        }
    });
    
    $(childSelect).val(null).trigger('change');
}

// =============================================================================
// SAYFA YÜKLENDİĞİNDE
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== BAĞIMLILIKLAR ==========
    
    // 1. Üniversite değişince -> Fakülte listesi filtrelenir
    $('#universite_id').on('change', function() {
        const universiteAdi = $(this).val();
        let universiteId = null;
        $('#universite_id option').each(function() {
            if ($(this).val() === universiteAdi) {
                universiteId = $(this).data('universite-id');
            }
        });
        updateDependentSelectValue(universiteId, '#fakulte_yuksekokul_id', 'universiteId');
        $('#program_id').val(null).trigger('change');
        $('#anabilim_dali_id').val(null).trigger('change');
    });

    // 2. Fakülte değişince -> Program listesi filtrelenir
    $('#fakulte_yuksekokul_id').on('change', function() {
        const fakulteAdi = $(this).val();
        let fakulteId = null;
        $('#fakulte_yuksekokul_id option').each(function() {
            if ($(this).val() === fakulteAdi) {
                fakulteId = $(this).data('fakulte-id');
            }
        });
        updateDependentSelectValue(fakulteId, '#program_id', 'fakulteId');
        $('#anabilim_dali_id').val(null).trigger('change');
    });

    // 3. Program değişince -> Anabilim Dalı listesi filtrelenir
    $('#program_id').on('change', function() {
        const programAdi = $(this).val();
        let programId = null;
        $('#program_id option').each(function() {
            if ($(this).val() === programAdi) {
                programId = $(this).data('program-id');
            }
        });
        updateDependentSelectValue(programId, '#anabilim_dali_id', 'programId');
    });

    function updateDependentSelectValue(parentValue, childSelect, dataAttr) {
        $(childSelect + ' option').each(function() {
            const option = $(this);
            const parentId = option.data(dataAttr);
            
            if (option.val() === '' || parentId == parentValue || !parentValue) {
                option.show();
            } else {
                option.hide();
            }
        });
        $(childSelect).val(null).trigger('change');
    }
    
    // ========== TABLO SATIRI SEÇME ÖZELLİĞİ ==========
    const rows = document.querySelectorAll('.personel-ogrenim .content-row');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.folder-row-icon')) {
                return;
            }
            document.querySelectorAll('.personel-ogrenim .content-row').forEach(r => {
                r.classList.remove('selected');
                r.style.backgroundColor = '';
            });
            this.classList.add('selected');
            this.style.backgroundColor = '#e9ecef';
            const ogrenimIdInput = document.getElementById('ogrenim_id');
            if (ogrenimIdInput) {
                ogrenimIdInput.value = this.getAttribute('data-id');
            }
        });
    });
});

// =============================================================================
// ÖĞRENİM DÜZENLEME
// =============================================================================
function ogrenimDuzenle(ogrenimId) {
    if (!ogrenimId || ogrenimId === 0) {
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Geçersiz kayıt ID' });
        return;
    }
    window.location.href = 'personel_ogrenim.php?tc_search=<?= urlencode($tc) ?>&duzenle_id=' + ogrenimId;
}

// =============================================================================
// YENİ ÖĞRENİM KAYDI
// =============================================================================
function yeniOgrenimKontrol() {
    const ogrenimIdInput = document.getElementById('ogrenim_id');
    const baslik = document.getElementById('formBaslik');
    const form = document.querySelector('#ogrenimFormu form');
    const formArea = document.getElementById('ogrenimFormu');
    
    if (!ogrenimIdInput || !baslik || !form || !formArea) return;
    
    ogrenimIdInput.value = '';
    baslik.textContent = 'Yeni Öğrenim Kaydı';
    form.reset();
    formArea.style.display = 'block';
    formArea.scrollIntoView({ behavior: 'smooth' });
}

// =============================================================================
// FORM KAPATMA
// =============================================================================
function kapatForm() {
    const form = document.getElementById('ogrenimFormu');
    if (form) form.style.display = 'none';
    
    const ogrenimIdInput = document.getElementById('ogrenim_id');
    if (ogrenimIdInput) ogrenimIdInput.value = '';
    
    const baslik = document.getElementById('formBaslik');
    if (baslik) baslik.textContent = 'Yeni Öğrenim Kaydı';
    
    const formElement = document.querySelector('#ogrenimFormu form');
    if (formElement) formElement.reset();
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
        html: `<p>Bu ${itemName} silmek istediğinize emin misiniz?</p><p class="text-danger mt-2"><strong>Bu işlem geri alınamaz!</strong></p>`,
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
                    }, 1500);
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
// YEŞİL TOAST MESAJI
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
// ÖĞRENİM DURUMUNA GÖRE KARTLARI GÖSTER/GİZLE
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const ogrenimDurumuSelect = document.getElementById('ogrenim_durumu_id');
    const universiteKarti = document.getElementById('universiteKarti');
    const okulKarti = document.getElementById('okulKarti');
    const universiteSeviyeleri = ['Ön Lisans', 'Lisans', 'Yüksek Lisans', 'Doktora'];
    
    function toggleKartlar() {
        const secilenDurum = ogrenimDurumuSelect?.options[ogrenimDurumuSelect.selectedIndex]?.text;
        const universiteGoster = universiteSeviyeleri.some(seviye => 
            secilenDurum?.toLowerCase().includes(seviye.toLowerCase())
        );
        
        if (universiteGoster) {
            if (universiteKarti) universiteKarti.style.display = 'block';
            if (okulKarti) okulKarti.style.display = 'none';
        } else if (secilenDurum && secilenDurum !== '') {
            if (universiteKarti) universiteKarti.style.display = 'none';
            if (okulKarti) okulKarti.style.display = 'block';
        } else {
            if (universiteKarti) universiteKarti.style.display = 'none';
            if (okulKarti) okulKarti.style.display = 'none';
        }
    }
    
    if (ogrenimDurumuSelect) {
        ogrenimDurumuSelect.addEventListener('change', toggleKartlar);
        toggleKartlar();
    }
});

// =============================================================================
// İL-İLÇE-OKUL ZİNCİRİ (MEB Okulları için)
// =============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const ilSelect = document.getElementById('okul_il_id');
    const ilceSelect = document.getElementById('okul_ilce_id');
    const okulSelect = document.getElementById('mezun_okul_id');
    if (!ilSelect) return;
    
    ilSelect.addEventListener('change', function() {
        const ilId = this.value;
        if (!ilId) {
            ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
            okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
            return;
        }
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        fetch(`api/get_ilceler.php?il_id=${ilId}`)
            .then(res => res.json())
            .then(data => {
                ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
                data.forEach(ilce => {
                    const option = document.createElement('option');
                    option.value = ilce.id;
                    option.textContent = ilce.ilce_adi;
                    ilceSelect.appendChild(option);
                });
                <?php if (isset($duzenlenecek_ilce) && $duzenlenecek_ilce): ?>
                setTimeout(() => {
                    for (let i = 0; i < ilceSelect.options.length; i++) {
                        if (ilceSelect.options[i].value == '<?= $duzenlenecek_ilce['id'] ?>') {
                            ilceSelect.selectedIndex = i;
                            ilceSelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                }, 100);
                <?php endif; ?>
            });
    });
    
    ilceSelect.addEventListener('change', function() {
        const ilceId = this.value;
        if (!ilceId) {
            okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
            return;
        }
        okulSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        fetch(`api/get_okullar.php?ilce_id=${ilceId}`)
            .then(res => res.json())
            .then(data => {
                okulSelect.innerHTML = '<option value="">Okul Seçiniz</option>';
                data.forEach(okul => {
                    const option = document.createElement('option');
                    option.value = okul.id;
                    option.textContent = okul.gorev_yeri;
                    okulSelect.appendChild(option);
                });
                <?php if (isset($duzenlenecek_ogrenim) && !empty($duzenlenecek_ogrenim['mezun_okul_id'])): ?>
                setTimeout(() => {
                    for (let i = 0; i < okulSelect.options.length; i++) {
                        if (okulSelect.options[i].value == '<?= $duzenlenecek_ogrenim['mezun_okul_id'] ?>') {
                            okulSelect.selectedIndex = i;
                            break;
                        }
                    }
                }, 100);
                <?php endif; ?>
            });
    });
    
    <?php if (isset($duzenlenecek_il) && $duzenlenecek_il): ?>
    setTimeout(() => { if (ilSelect.value) ilSelect.dispatchEvent(new Event('change')); }, 200);
    <?php endif; ?>
});
</script>