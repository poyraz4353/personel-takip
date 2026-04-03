// =============================================================================
// KİMLİK BİLGİLERİ JAVASCRIPT FONKSİYONLARI
// =============================================================================

console.log('=== KİMLİK BİLGİLERİ SAYFASI YÜKLENDİ ===');

// ============================================================
// SAYFA YÜKLENDİĞİNDE ÇALIŞACAK OLAN İNİT
// ============================================================
document.addEventListener("DOMContentLoaded", function () {
    console.log('=== SAYFA YÜKLENİYOR ===');
    
    // 1. TÜM "GÜNCELLE" BUTONLARINI AKTİF YAP
    const allButtons = document.querySelectorAll('button');
    
    allButtons.forEach((btn) => {
        const btnText = btn.textContent.trim().toLowerCase();
        
        // GÜNCELLE butonunu bul ve AKTİF yap
        if (btnText.includes('güncelle')) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            console.log(`✅ GÜNCELLE aktif: "${btn.textContent.trim()}"`);
        }
        
        // KAYDET butonunu bul ve PASİF yap
        if (btnText.includes('kaydet') && !btnText.includes('güncelle')) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            console.log(`⛔ KAYDET pasif: "${btn.textContent.trim()}"`);
        }
    });
    
    // 2. Diğer fonksiyonları çalıştır
    checkForSuccessMessage();
    initializeAutoCloseAlerts();
    setupEditButtons();
    
    console.log('=== SAYFA HAZIR ===');
});

// ============================================================
// HANGİ SAYFADA OLDUĞUMUZU KONTROL ET
// ============================================================
function checkIfDigerBilgilerPage() {
    // 1. Sayfa başlığına göre kontrol
    const pageTitle = document.querySelector('h1, h2, h3');
    if (pageTitle) {
        const titleText = pageTitle.textContent.toLowerCase();
        if (titleText.includes('diğer') || titleText.includes('diger')) {
            return true;
        }
    }
    
    // 2. Form alanlarına göre kontrol (kan grubu, vs.)
    const inputs = document.querySelectorAll('input, select, textarea');
    for (let input of inputs) {
        const label = document.querySelector(`label[for="${input.id}"]`);
        if (label && label.textContent.toLowerCase().includes('kan grubu')) {
            return true;
        }
    }
    
    // 3. URL'e göre kontrol
    const currentUrl = window.location.href.toLowerCase();
    if (currentUrl.includes('diger') || currentUrl.includes('kan-grubu')) {
        return true;
    }
    
    // 4. Butonlara göre kontrol (İptal ve Güncelle butonları varsa)
    const allButtons = document.querySelectorAll('button');
    let hasIptal = false;
    let hasGuncelle = false;
    
    allButtons.forEach((btn) => {
        const btnText = btn.textContent.trim().toLowerCase();
        if (btnText.includes('iptal')) hasIptal = true;
        if (btnText.includes('güncelle') || btnText.includes('guncelle')) hasGuncelle = true;
    });
    
    if (hasIptal && hasGuncelle) {
        return true;
    }
    
    return false;
}

// ============================================================
// GÜNCELLE BUTONUNU AKTİF YAPMA (DİĞER BİLGİLER İÇİN)
// ============================================================
function activateUpdateButton() {
    console.log('=== GÜNCELLE BUTONU AKTİF YAPILIYOR ===');
    
    const allButtons = document.querySelectorAll('button');
    let guncelleButton = null;
    let iptalButton = null;
    
    allButtons.forEach((btn) => {
        const btnText = btn.textContent.trim().toLowerCase();
        
        // "Güncelle" butonunu bul
        if (btnText.includes('güncelle') || btnText.includes('guncelle')) {
            guncelleButton = btn;
        }
        
        // "İptal" butonunu bul
        if (btnText.includes('iptal')) {
            iptalButton = btn;
        }
    });
    
    // Güncelle butonunu aktif yap
    if (guncelleButton) {
        guncelleButton.disabled = false;
        guncelleButton.style.opacity = '1';
        guncelleButton.style.cursor = 'pointer';
        
        // Butona tıklama eventi ekle (form submit için)
        guncelleButton.onclick = function(e) {
            e.preventDefault();
            handleGuncelleClick(guncelleButton);
        };
        
        console.log(`✅ Güncelle butonu aktif yapıldı: "${guncelleButton.textContent.trim()}"`);
    }
    
    // İptal butonuna event ekle
    if (iptalButton) {
        iptalButton.onclick = function(e) {
            e.preventDefault();
            handleIptalClick();
        };
        console.log(`✅ İptal butonu bağlandı: "${iptalButton.textContent.trim()}"`);
    }
    
    if (!guncelleButton) {
        console.warn('⚠️ Güncelle butonu bulunamadı!');
    }
}

// ============================================================
// GÜNCELLE BUTONU TIKLANDIĞINDA
// ============================================================
function handleGuncelleClick(button) {
    console.log('=== GÜNCELLE BUTONU TIKLANDI ===');
    
    // Butonu loading durumuna getir
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Güncelleniyor...';
    
    // Formu submit et
    const form = button.closest('form') || document.querySelector('form');
    if (form) {
        console.log('✅ Form submit ediliyor...');
        form.submit();
    } else {
        console.error('❌ Form bulunamadı!');
        
        // Hata durumunda butonu eski haline getir
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        }, 2000);
    }
}

// ============================================================
// İPTAL BUTONU TIKLANDIĞINDA
// ============================================================
function handleIptalClick() {
    console.log('=== İPTAL BUTONU TIKLANDI ===');
    
    // Form alanlarını resetle
    const form = document.querySelector('form');
    if (form) {
        form.reset();
        console.log('✅ Form resetlendi');
    }
    
    // Başarı mesajı göster
    showSuccessMessage('İşlem iptal edildi', 3000);
}

// ============================================================
// KAYDET BUTONLARINI BAŞLANGIÇTA PASİF YAPMA (KİMLİK BİLGİLERİ İÇİN)
// ============================================================
function disableAllSaveButtons() {
    console.log('=== BAŞLANGIÇ: TÜM KAYDET BUTONLARI PASİF YAPILIYOR ===');
    
    const allButtons = document.querySelectorAll('button');
    let disabledCount = 0;
    
    allButtons.forEach((btn) => {
        const btnText = btn.textContent.trim().toLowerCase();
        const btnClass = btn.className.toLowerCase();

        // Kaydet butonunu belirleme kriterleri:
        // - "kaydet" yazısı
        // - name="kaydet_kimlik"
        // - btn-primary class'ı olan (yazdır/düzenle değil)
        if (
            btnText.includes('kaydet') || 
            btn.name === 'kaydet_kimlik' ||
            (btnClass.includes('btn-primary') && !btnText.includes('yazdır') && !btnText.includes('düzenle'))
        ) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            console.log(`Buton pasif yapıldı: "${btn.textContent.trim()}"`);
            disabledCount++;
        }
    });

    console.log(`Toplam ${disabledCount} adet kaydet butonu pasif yapıldı`);
}

// ============================================================
// DÜZENLE BUTONLARINI BAĞLAMA
// ============================================================
function setupEditButtons() {
    const editButtons = document.querySelectorAll('[onclick*="acDuzenleModali"], .edit-btn, .btn-warning');

    editButtons.forEach(button => {
        if (!button.getAttribute('onclick') || !button.getAttribute('onclick').includes('acDuzenleModali')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                acDuzenleModali();
            });
            console.log(`Düzenle butonu bağlandı: ${button.textContent}`);
        }
    });

    console.log(`${editButtons.length} adet düzenle butonu bulundu`);
}

// ============================================================
// DÜZENLEME MODU - KAYDET BUTONUNU AKTİF YAP
// ============================================================
function acDuzenleModali() {
    console.log('=== DÜZENLE MODU AÇILIYOR ===');

    // 1. Kaydet butonunu bul ve aktif yap
    let saveButton = document.querySelector('button[name="kaydet_kimlik"]') ||
                     document.getElementById('submitButton') ||
                     document.getElementById('kaydetKimlikBtn');

    if (!saveButton) {
        // "Kaydet" yazan tüm butonları bul
        const allButtons = document.querySelectorAll('button');
        allButtons.forEach(btn => {
            if (btn.textContent.includes('Kaydet') && !btn.textContent.includes('Güncelle')) {
                saveButton = btn;
            }
        });
    }

    if (saveButton) {
        saveButton.disabled = false;
        saveButton.style.opacity = '1';
        saveButton.style.cursor = 'pointer';
        saveButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Güncelle';
        saveButton.classList.remove('btn-secondary');
        saveButton.classList.add('btn-success');

        saveButton.onclick = function(e) {
            e.preventDefault();
            kaydetKimlik();
        };

        console.log('✅ Kaydet butonu aktif edildi ve "Güncelle" yapıldı');
    } else {
        console.error('❌ Kaydet butonu bulunamadı!');
    }

    // 2. Form alanlarını editable yap
    const formContainer = document.getElementById('kimlikBilgileriFormu');
    if (formContainer) {
        const form = formContainer.querySelector('form');
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (!input.disabled) input.readOnly = false;
                console.log(`${input.name || input.id} editable yapıldı`);
            });
        }

        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        console.log('✅ Form görünür yapıldı');
    }

    console.log('=== DÜZENLEME MODU AKTİF ===');
}

// ============================================================
// KİMLİK BİLGİLERİNİ KAYDETME / GÜNCELLEME - TAMAMLANMIŞ VERSİYON
// ============================================================
function kaydetKimlik() {
    console.log('=== KİMLİK KAYDETME BAŞLATILIYOR (GÜNCELLENMİŞ) ===');

    // 1. Form validasyonu
    if (!validateKimlikForm()) {
        console.log('❌ Form validasyonu başarısız');
        return false;
    }

    // 2. Kaydet butonunu loading yap
    const saveButton = document.getElementById('submitButton');
    let originalText = '';
    
    if (saveButton) {
        originalText = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Kaydediliyor...';
        console.log('✅ Buton loading durumunda');
    }

    // 3. Form verilerini topla
    const form = document.querySelector('#kimlikBilgileriFormu form');
    if (!form) {
        console.error('❌ Form bulunamadı!');
        showFormError('Form bulunamadı!');
        resetSaveButton(saveButton, originalText);
        return false;
    }

    // 4. FormData oluştur ve DEBUG için içeriği göster
    const formData = new FormData(form);
    
    console.log('=== GÖNDERİLECEK VERİLER ===');
    const formDataObj = {};
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
        formDataObj[key] = value;
    }
    
    // 5. PERSONEL ID kontrolü (çok önemli!)
    if (!formDataObj.personel_id && window.personelId) {
        formData.append('personel_id', window.personelId);
        console.log('✅ Personel ID eklendi:', window.personelId);
    }

    // 6. DEBUG için test verisi ekle
    formData.append('debug', 'true');
    formData.append('timestamp', new Date().toISOString());

    // 7. FETCH ile gönder (DÜZELTMİŞ VERSİYON)
    console.log('🔄 AJAX isteği gönderiliyor...');
    
    fetch('api/kaydet_kimlik.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest', // BU SATIR ÇOK ÖNEMLİ!
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('📥 Response alındı, status:', response.status, response.statusText);
        console.log('Headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('✅ AJAX Response:', data);
        
        if (data.success) {
            // BAŞARILI
            showSuccessMessage(data.message || 'Kimlik bilgileri başarıyla kaydedildi!', 3000);
            console.log('🎉 Kayıt başarılı!');
            
            // 2 saniye sonra sayfayı yenile
            setTimeout(() => {
                console.log('🔄 Sayfa yenileniyor...');
                window.location.reload();
            }, 2000);
            
        } else {
            // HATA
            const errorMsg = data.error || 'Kayıt başarısız!';
            showFormError('Hata: ' + errorMsg);
            console.error('❌ Kayıt hatası:', errorMsg);
            resetSaveButton(saveButton, originalText);
        }
    })
    .catch(error => {
        console.error('❌ AJAX Hatası:', error);
        showFormError('Sunucu hatası: ' + error.message);
        resetSaveButton(saveButton, originalText);
    });

    // 8. Form submit'i engelle
    return false;
}

// ============================================================
// BUTONU ESKİ HALİNE GETİRME YARDIMCI FONKSİYONU
// ============================================================
function resetSaveButton(button, originalText) {
    if (button && originalText) {
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        }, 1000);
    }
}

// ============================================================
// FORM VALİDASYONU
// ============================================================
function validateKimlikForm() {
    console.log('=== FORM VALİDASYONU ===');

    const requiredFields = ['baba_adi', 'dogum_tarihi', 'cinsiyeti'];
    let isValid = true;
    let firstErrorField = null;

    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
                if (!firstErrorField) firstErrorField = field;
                console.log(`❌ ${fieldName}: Boş`);
            } else {
                field.classList.remove('is-invalid');
                console.log(`✅ ${fieldName}: Dolu (${field.value})`);
            }
        }
    });

    if (!isValid && firstErrorField) {
        showFormError('Lütfen zorunlu alanları doldurun!');
        firstErrorField.focus();
        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    console.log('✅ Form validasyonu başarılı');
    return true;
}

// ============================================================
// FORM KAPATMA & YAZDIRMA
// ============================================================
function kapatForm() {
    const form = document.getElementById('kimlikBilgileriFormu');
    if (form) form.style.display = 'none';
}

function yazdirKimlik() {
    window.print();
}

function acFotoYukleModali() {
    alert('Fotoğraf yükleme özelliği yakında eklenecek');
}

// ============================================================
// ALERT / FEEDBACK SİSTEMİ (Aynı kalacak)
// ============================================================
function checkForSuccessMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showSuccessMessage('Kimlik bilgileri başarıyla kaydedildi!', 6000);

        setTimeout(() => {
            const newUrl = window.location.protocol + "//" + window.location.host + 
                          window.location.pathname + "?tc=" + encodeURIComponent(window.aktifTc || '');
            window.history.replaceState({}, document.title, newUrl);
        }, 100);
    }
}

function initializeAutoCloseAlerts() {
    const alerts = document.querySelectorAll('.modern-alert[data-auto-close]');
    
    alerts.forEach(alert => {
        const duration = parseInt(alert.getAttribute('data-auto-close'));
        if (duration > 0) {
            const progressBar = document.createElement('div');
            progressBar.className = 'alert-progress';
            alert.appendChild(progressBar);

            const timer = setTimeout(() => closeAlert(alert), duration);

            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) closeBtn.addEventListener('click', () => { clearTimeout(timer); closeAlert(alert); });

            alert.addEventListener('mouseenter', () => progressBar.style.animationPlayState = 'paused');
            alert.addEventListener('mouseleave', () => progressBar.style.animationPlayState = 'running');
        }
    });
}

function closeAlert(alert) {
    alert.classList.add('alert-closing');
    setTimeout(() => { if (alert.parentNode) alert.remove(); }, 500);
}

function showSuccessMessage(message, duration = 6000) {
    const feedbackContainer = document.querySelector('.feedback-container');
    if (!feedbackContainer) return;

    const alert = document.createElement('div');
    alert.className = 'modern-alert modern-alert-success';
    alert.setAttribute('data-auto-close', duration);
    alert.innerHTML = `
        <div class="alert-content">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close alert-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="alert-progress"></div>
    `;
    feedbackContainer.appendChild(alert);
    initializeAutoCloseAlerts();
}

function showFormError(message) {
    const feedbackContainer = document.querySelector('.feedback-container');
    if (!feedbackContainer) return;

    const alert = document.createElement('div');
    alert.className = 'modern-alert modern-alert-danger';
    alert.innerHTML = `
        <div class="alert-content">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close alert-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    feedbackContainer.appendChild(alert);

    setTimeout(() => { if (alert.parentNode) alert.remove(); }, 5000);
}

// ============================================================
// DEBUG & MANUEL ÇAĞRI FONKSİYONLARI
// ============================================================
window.debugKimlik = function() {
    console.log('=== DEBUG MODE ===');
    console.log('Aktif TC:', window.aktifTc);
    document.querySelectorAll('button').forEach((btn, index) => {
        console.log(`${index}. "${btn.textContent.trim()}"`, {
            id: btn.id, name: btn.name, disabled: btn.disabled, display: btn.style.display, class: btn.className
        });
    });
    acDuzenleModali();
};

window.findSaveButtons = function() {
    console.log('=== YAZDIR BUTONU SAĞINDAKİ KAYDET ===');
    const allButtons = document.querySelectorAll('button');
    allButtons.forEach((btn, index) => {
        if (btn.textContent.includes('Yazdır')) {
            console.log(`${index}. Yazdır butonu bulundu:`, btn);
            const nextButton = allButtons[index + 1];
            if (nextButton) console.log(`Sağındaki buton: "${nextButton.textContent.trim()}"`, nextButton);
        }
    });
};

// ============================================================
// DEBUG FONKSİYONU
// ============================================================
window.checkButtons = function() {
    console.log('=== BUTON DURUMLARI ===');
    document.querySelectorAll('button').forEach((btn, index) => {
        console.log(`${index}. "${btn.textContent.trim()}"`, {
            disabled: btn.disabled,
            text: btn.textContent
        });
    });
};

// ============================================================
// FORM SUBMIT EVENTİNİ ENGELLEYEN GENEL KOD
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Tüm formların submit olayını dinle
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            console.log('Form submit engellendi, AJAX kullanılacak');
            e.preventDefault();
            return false;
        });
    });
});