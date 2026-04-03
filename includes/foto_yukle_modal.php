<?php
/**
 * FOTOĞRAF YÜKLEME MODALI
 */

// Değişkenlerin gelip gelmediğini kontrol et
if (!isset($personel_id) || !isset($personel)) {
    die('Hata: Modal değişkenleri tanımlı değil!');
}
?>

<!-- Fotoğraf Yükleme Modalı -->
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
                <!-- Personel Bilgisi -->
                <div class="personel-info-card mb-4 p-3 rounded" style="background: #f8f9fa;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div id="currentPhotoPreview" class="photo-preview-current">
                                <?php
                                // Mevcut fotoğrafı göster
                                if (!empty($personel['foto_path'])) {
                                    $currentPhoto = 'uploads/personel_fotolar/' . basename($personel['foto_path']);
                                    if (file_exists($currentPhoto)) {
                                        echo '<img src="' . htmlspecialchars($currentPhoto) . '" 
                                               alt="Mevcut Fotoğraf">';
                                    } else {
                                        echo '<i class="bi bi-person-circle text-muted"></i>';
                                    }
                                } else {
                                    echo '<i class="bi bi-person-circle text-muted"></i>';
                                }
                                ?>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($personel['ad_soyadi'] ?? '') ?></h6>
                            <p class="text-muted mb-0">TC: <?= htmlspecialchars($personel['tc_no'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Yükleme Formu -->
                <form id="fotoYukleForm" method="POST" enctype="multipart/form-data" action="kimlik_bilgileri.php">
                    <input type="hidden" name="simple_token" value="<?= $simpleToken ?>">
                    <input type="hidden" name="foto_yukle" value="1">
                    <input type="hidden" name="personel_id" value="<?= $personel_id ?>">
                    <input type="hidden" name="tc_search" value="<?= htmlspecialchars($tc) ?>">
                    
                    <!-- Dosya Seçme -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fotoğraf Seçin</label>
                        <input type="file" class="form-control" id="fotoInput" name="foto" 
                               accept=".jpg,.jpeg,.png,.gif" required>
                        <div class="form-text">
                            JPG, JPEG, PNG, GIF formatları (Maksimum 5MB)
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


<style>
.photo-preview-container {
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    background-color: #f8f9fa;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-preview-empty {
    color: #6c757d;
}

.photo-preview-current {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #198754;
}

.photo-preview-current img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-preview-loaded {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.file-upload-area {
    position: relative;
}

.progress {
    height: 8px;
    margin-top: 10px;
}
</style>