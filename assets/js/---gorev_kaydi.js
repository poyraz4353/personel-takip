/**
 * İl-İlçe İsim Bazlı Eşleştirme Sistemi
 * Tıklama sorunları ve yazım hataları giderilmiş versiyon.
 */

// --- İL-İLÇE-KURUM SİSTEMİ ---
class IlIlceKurumSistemi {
    constructor() {
        this.ilSelect = document.getElementById('il_id');
        this.ilceSelect = document.getElementById('ilce_id');
        this.kurumSelect = document.getElementById('gorev_kurum_kodu');
        this.kapaliKurumCheckbox = document.getElementById('kapali_kurum');
        this.init();
    }

    init() {
        if (!this.ilSelect) return;
        this.ilSelect.addEventListener('change', () => this.handleIlChange());
        this.ilceSelect.addEventListener('change', () => this.handleIlceChange());
        if (this.kapaliKurumCheckbox) {
            this.kapaliKurumCheckbox.addEventListener('change', () => this.handleKapaliKurumChange());
        }
    }

    // Listede olmayan değeri zorla ekleyen yardımcı fonksiyon
    forceSelect(el, value, text) {
        if (!el || !value) return;
        let found = false;
        for (let i = 0; i < el.options.length; i++) {
            if (el.options[i].value == value) {
                el.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) {
            const newOpt = new Option(text || value, value, true, true);
            el.add(newOpt);
        }
    }

    async loadIlceler(ilId) {
        if (!ilId) return;
        try {
            const response = await fetch(`api/get_ilceler.php?il_id=${ilId}`);
            const data = await response.json();
            this.ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';
            data.forEach(item => this.ilceSelect.add(new Option(item.ilce_adi, item.id)));
        } catch (e) { console.error('İlçe yüklenemedi'); }
    }

    async loadKurumlar(ilceId) {
        if (!ilceId) return;
        const kapali = this.kapaliKurumCheckbox?.checked ? 1 : 0;
        try {
            const response = await fetch(`api/get_okullar.php?ilce_id=${ilceId}&kapali_kurum=${kapali}`);
            const data = await response.json();
            this.kurumSelect.innerHTML = '<option value="">Kurum Seçin</option>';
            data.forEach(item => this.kurumSelect.add(new Option(item.gorev_yeri, item.kurum_kodu)));
        } catch (e) { console.error('Kurumlar yüklenemedi'); }
    }

    handleIlChange() { this.loadIlceler(this.ilSelect.value); }
    handleIlceChange() { this.loadKurumlar(this.ilceSelect.value); }
    handleKapaliKurumChange() { if(this.ilceSelect.value) this.loadKurumlar(this.ilceSelect.value); }
}

let ilIlceKurumSistemi;
document.addEventListener('DOMContentLoaded', () => { ilIlceKurumSistemi = new IlIlceKurumSistemi(); });


// Token'ın kaybolmadığından emin olalım
const tokenEl = document.getElementById('security_token');
if (tokenEl && !tokenEl.value) {
    console.warn("Uyarı: Güvenlik anahtarı boş görünüyor!");
}


// --- GÖREV DÜZENLEME FONKSİYONU ---
async function gorevDuzenle(gorevId) {
    alert('Görev düzenleme çalıştı! ID: ' + gorevId);
    if (!gorevId) return;
    
    try {
        console.log('🔍 API çağrılıyor...');
        const response = await fetch(`api/get_gorev_detay.php?gorev_id=${gorevId}`);
        const result = await response.json();
        console.log('📥 API Yanıtı:', result);
        
        if (result.success) {
            alert('API başarılı! Veri geldi.');
            const g = result.data;

            // 1. Temel Bilgiler
            document.getElementById('gorev_id').value = g.id;
            document.getElementById('duzenlenecek_gorev_id').value = g.id;
            document.getElementById('kurum_baslama_tarihi').value = g.kurum_baslama_tarihi || '';
            document.getElementById('gorev_ayrilma_tarihi').value = g.bitis_tarihi || '';

            // 2. Sabit Select'ler
            const mapping = {
                'istihdam_tipi': g.istihdam_tipi,
                'hizmet_sinifi': g.hizmet_sinifi,
                'kadro_unvani': g.kadro_unvani,
                'gorev_unvani': g.gorev_unvani,
                'kariyer_basamagi': g.kariyer_basamagi,
                'atama_cesidi': g.atama_cesidi,
                'yer_degistirme_cesidi': g.yer_degistirme_cesidi
            };

            Object.entries(mapping).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el) {
                    // Select içinde bu değer var mı kontrol et
                    let found = false;
                    for (let i = 0; i < el.options.length; i++) {
                        if (el.options[i].value == val || el.options[i].text == val) {
                            el.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    // Yoksa ekle ve seç
                    if (!found && val) {
                        const newOpt = new Option(val, val, true, true);
                        el.add(newOpt);
                    }
                }
            });

            // 3. İl-İlçe-Okul zinciri
            if (ilIlceKurumSistemi) {
                // İl'i seç
                const ilSelect = document.getElementById('il_id');
                if (ilSelect && g.gorev_il_adi) {
                    for (let i = 0; i < ilSelect.options.length; i++) {
                        if (ilSelect.options[i].text === g.gorev_il_adi) {
                            ilSelect.selectedIndex = i;
                            ilSelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                }

                // İlçeler yüklendikten sonra ilçeyi seç
                setTimeout(async () => {
                    const ilceSelect = document.getElementById('ilce_id');
                    if (ilceSelect && g.gorev_ilce_adi) {
                        for (let i = 0; i < ilceSelect.options.length; i++) {
                            if (ilceSelect.options[i].text === g.gorev_ilce_adi) {
                                ilceSelect.selectedIndex = i;
                                ilceSelect.dispatchEvent(new Event('change'));
                                break;
                            }
                        }
                    }
                }, 500);

                // Okullar yüklendikten sonra okulu seç
                setTimeout(() => {
                    const okulSelect = document.getElementById('gorev_kurum_kodu');
                    if (okulSelect && g.gorev_kurum_kodu) {
                        for (let i = 0; i < okulSelect.options.length; i++) {
                            if (okulSelect.options[i].value === g.gorev_kurum_kodu) {
                                okulSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }, 1000);
            }

            // 4. Kapalı kurum checkbox'ı
            const kapaliCheck = document.getElementById('kapali_kurum');
            if (kapaliCheck) {
                kapaliCheck.checked = g.gorev_kapali_kurum == 1;
            }

            // 5. Formu göster
            const formArea = document.getElementById('gorevKaydiFormu');
            formArea.style.display = 'block';
            formArea.scrollIntoView({ behavior: 'smooth' });
        }
    } catch (error) {
        console.error('❌ Düzenleme Hatası:', error);
    }
}