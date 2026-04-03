<?php
// DEBUG: Tüm hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tüm istekleri logla
$log_file = __DIR__ . '/debug_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " - " . ($_SERVER['QUERY_STRING'] ?? '') . "\n", FILE_APPEND);

// POST ise verileri logla
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents($log_file, "POST DATA: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents($log_file, "FILES DATA: " . print_r($_FILES, true) . "\n", FILE_APPEND);
}

// AJAX ise sonucu logla
if (isset($_GET['ajax'])) {
    file_put_contents($log_file, "AJAX: " . $_GET['ajax'] . "\n", FILE_APPEND);
}

session_start();

// Tarihi MySQL formatına çevir (d.m.Y -> Y-m-d)
function tarihiMySQLEevir($tarih) {
    if (empty($tarih) || $tarih === 'gg.aa.yyyy') return null;
    
    // d.m.Y formatını kontrol et
    $parcala = explode('.', $tarih);
    if (count($parcala) === 3) {
        $gun = trim($parcala[0]);
        $ay = trim($parcala[1]);
        $yil = trim($parcala[2]);
        
        // Geçerli tarih mi?
        if (checkdate($ay, $gun, $yil)) {
            return "$yil-$ay-$gun";
        }
    }
    return null;
}

// ✅ BAŞARI MESAJI KONTROLÜ - GELİŞTİRİLMİŞ VERSİYON
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $_SESSION['success_message'] = 'Personel başarıyla eklendi.';
    header('Location: personel_ekle.php');
    exit;
}

// ✅ FORM VERİLERİNİ SESSION'DA SAKLA - VALİDASYON HATASINDA KAYBOLMAMASI İÇİN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;
    // Dosya verilerini de saklayalım
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $_SESSION['form_data']['foto_temp'] = $_FILES['foto']['name'];
    }
    
	
	
	
    // ✅ DİNAMİK DROPDOWN VERİLERİNİ DE SAKLA
    $_SESSION['form_data']['gorev_il_id'] = $_POST['gorev_il_id'] ?? '';
    $_SESSION['form_data']['gorev_ilce_id'] = $_POST['gorev_ilce_id'] ?? '';
    $_SESSION['form_data']['gorev_okul_id'] = $_POST['gorev_okul_id'] ?? '';
    $_SESSION['form_data']['hizmet_sinifi'] = $_POST['hizmet_sinifi'] ?? '';
    $_SESSION['form_data']['kadro_unvani'] = $_POST['kadro_unvani'] ?? '';
    $_SESSION['form_data']['gorev_unvani'] = $_POST['gorev_unvani'] ?? '';
    $_SESSION['form_data']['universite_id'] = $_POST['universite_id'] ?? '';
    $_SESSION['form_data']['fakulte_yuksekokul_id'] = $_POST['fakulte_yuksekokul_id'] ?? '';
    $_SESSION['form_data']['anabilim_dali_id'] = $_POST['anabilim_dali_id'] ?? '';
    $_SESSION['form_data']['program_id'] = $_POST['program_id'] ?? '';
}

// GET isteğinde ve başarı mesajı varsa form verilerini temizle
if ($_SERVER['REQUEST_METHOD'] === 'GET' || isset($_SESSION['success_message'])) {
    unset($_SESSION['form_data']);
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$db = Database::getInstance();

// ✅ HATA AYIKLAMA MODU - Sadece geliştirme ortamında aktif
if (defined('DEBUG_MODE') && DEBUG_MODE && isset($_GET['debug'])) {
    error_log("=== PERSONEL EKLE DEBUG MODU ===");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    error_log("SESSION Data: " . print_r($_SESSION, true));
}

// ✅ BAŞARI MESAJI KONTROLÜ - Form temizleme için
if (isset($_SESSION['success_message'])) {
    $mesaj = 'success:' . $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// CSRF token oluştur (yoksa)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Hata mesajı değişkeni - DÜZELTİLDİ
$mesaj = $mesaj ?? null;

// İlleri veritabanından çek
$sorgu = $db->query("SELECT id, il_adi FROM iller ORDER BY il_adi");
$iller = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Öğrenim durumu listesini çek
$sorgu = $db->query("SELECT id, ogrenim_adi FROM ogrenim_durumlari WHERE aktif = 1 ORDER BY id ASC");
$ogrenimListesi = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Üniversiteleri çek
$sorgu = $db->query("
    SELECT universite_id, universite_adi 
    FROM universiteler 
    ORDER BY universite_adi
");
$universiteler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Fakülteleri çek (eğer üniversite seçiliyse)
$fakulteler = [];
if (!empty($_POST['universite_id']) || !empty(getFormData('universite_id'))) {
    $universite_id = $_POST['universite_id'] ?? getFormData('universite_id');
    $stmt = $db->prepare("
        SELECT fakulte_id AS id, fakulte_adi 
        FROM fakulte_yuksekokul
        WHERE universite_id = ? 
        ORDER BY fakulte_adi
    ");
    $stmt->execute([$universite_id]);
    $fakulteler = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Anabilim dallarını çek (eğer fakülte seçiliyse)
$anabilimler = [];
if (!empty($_POST['fakulte_yuksekokul_id']) || !empty(getFormData('fakulte_yuksekokul_id'))) {
    $fakulte_yuksekokul_id = $_POST['fakulte_yuksekokul_id'] ?? getFormData('fakulte_yuksekokul_id');
    $stmt = $db->prepare("
        SELECT anabilim_id AS id, anabilim_adi 
        FROM anabilim_dali 
        WHERE fakulte_yuksekokul_id = ? 
        ORDER BY anabilim_adi
    ");
    $stmt->execute([$fakulte_yuksekokul_id]);
    $anabilimler = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Programları çek (eğer anabilim seçiliyse)
$programlar = [];
if (!empty($_POST['anabilim_id']) || !empty(getFormData('anabilim_dali_id'))) {
    $anabilim_id = $_POST['anabilim_id'] ?? getFormData('anabilim_dali_id');
    $stmt = $db->prepare("
        SELECT program_id AS id, program_adi 
        FROM program 
        WHERE anabilim_dali_id = ? 
        ORDER BY program_adi
    ");
    $stmt->execute([$anabilim_id]);
    $programlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FORM GÖNDERİM İŞLEMLERİ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ✅ DEBUG: Form gönderimi tespit edildi
    if (isset($_GET['debug'])) {
        error_log("=== FORM GÖNDERİMİ ALINDI ===");
        error_log("TC No: " . ($_POST['tc_no'] ?? 'BOŞ'));
        error_log("Ad Soyad: " . ($_POST['ad_soyadi'] ?? 'BOŞ'));
    }
    
    // CSRF doğrulama - Güvenlik için
    if (!checkCSRF($_POST['csrf_token'] ?? '')) {
        $mesaj = 'Geçersiz oturum doğrulaması.';
        $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU
        if (isset($_GET['debug'])) {
            error_log("❌ CSRF DOĞRULAMA HATASI");
        }
    } else {
        // ✅ DEBUG: CSRF başarılı
        if (isset($_GET['debug'])) {
            error_log("✅ CSRF DOĞRULAMA BAŞARILI");
        }
        
        // TÜM FORM VERİLERİNİ GÜVENLİ ŞEKİLDE TEMİZLE
        $cleanData = [];
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $cleanData[$key] = array_map('sanitizeInput', $value);
            } else {
                $cleanData[$key] = sanitizeInput($value);
            }
        }

// TÜM FORM VERİLERİNİ GÜVENLİ ŞEKİLDE TEMİZLE
$cleanData = [];
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        $cleanData[$key] = array_map('sanitizeInput', $value);
    } else {
        $cleanData[$key] = sanitizeInput($value);
    }
}

// ✅ TARİHLERİ MYSQL FORMATINA ÇEVİR
$tarihAlanlari = ['dogum_tarihi', 'memuriyete_baslama_tarihi', 'kurum_baslama_tarihi', 
                  'terfi_tarihi', 'sozlesme_baslangic', 'sozlesme_bitis', 
                  'mezuniyet_tarihi', 'belge_tarihi', 'geldigi_ayrilma_tarihi'];

foreach ($tarihAlanlari as $alan) {
    if (isset($cleanData[$alan])) {
        $cleanData[$alan] = tarihiMySQLEevir($cleanData[$alan]);
    }
}

// Temel alanları al
$tc_no     = $cleanData['tc_no'] ?? '';
$ad_soyadi = $cleanData['ad_soyadi'] ?? '';

        // Temel alanları al
        $tc_no     = $cleanData['tc_no'] ?? '';
        $ad_soyadi = $cleanData['ad_soyadi'] ?? '';
        $baba_adi = $cleanData['baba_adi'] ?? '';
        $dogum_tarihi = $cleanData['dogum_tarihi'] ?? '';

        // ✅ DEBUG: Temel alanlar
        if (isset($_GET['debug'])) {
            error_log("📋 TEMEL ALANLAR:");
            error_log("TC: $tc_no, Ad: $ad_soyadi, Baba: $baba_adi, Doğum: $dogum_tarihi");
        }

        // TC Kimlik No doğrulama - Matematiksel kontrol
        if (!validateTCNo($tc_no)) {
            $mesaj = 'Geçersiz TC Kimlik Numarası!';
            $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU
            if (isset($_GET['debug'])) {
                error_log("❌ TC KİMLİK NO DOĞRULAMA HATASI: $tc_no");
            }
        } else {
            // ✅ DEBUG: TC doğrulama başarılı
            if (isset($_GET['debug'])) {
                error_log("✅ TC KİMLİK NO DOĞRULAMA BAŞARILI");
            }
            
            // Aynı TC ile kayıtlı kişi var mı? - Çift kayıt önleme
            $stmt = $db->prepare("SELECT id, ad_soyadi FROM personel WHERE tc_no = ?");
            $stmt->execute([$tc_no]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // ✅ DÜZELTİLDİ: Sadece TC kontrolü yap, isim kontrolünü kaldır
                $mesaj = "Bu TC numarası ile <strong>{$existing['ad_soyadi']}</strong> zaten kayıtlı. Lütfen TC kimlik numarasını kontrol edin.";
                $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU
                
                if (isset($_GET['debug'])) {
                    error_log("❌ ÇİFT KAYIT HATASI: TC $tc_no zaten mevcut - Kayıtlı kişi: {$existing['ad_soyadi']}");
                }
            } else {
                // ✅ TELEFON DOĞRULAMASI EKLENDİ - DÜZELTİLDİ
                $telefon_hatasi = false;
                if (!empty($cleanData['telefon'])) {
                    $telefon_temiz = cleanPhone($cleanData['telefon']);
                    if (!validatePhone($telefon_temiz)) {
                        $mesaj = 'Geçersiz telefon numarası! 5xx ile başlayan 10 haneli numara giriniz.';
                        $telefon_hatasi = true;
                        $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU
                        
                        if (isset($_GET['debug'])) {
                            error_log("❌ TELEFON DOĞRULAMA HATASI: " . $cleanData['telefon']);
                        }
                    } else {
                        $cleanData['telefon'] = $telefon_temiz;
                        if (isset($_GET['debug'])) {
                            error_log("✅ TELEFON DOĞRULAMA BAŞARILI: " . $telefon_temiz);
                        }
                    }
                }

                // ✅ Eğer telefon doğrulamasında hata varsa, işlemi durdur
                if ($telefon_hatasi) {
                    // Hata mesajı zaten set edilmiş, işlemi durdur.
                    if (isset($_GET['debug'])) {
                        error_log("🛑 TELEFON HATASI NEDENİYLE İŞLEM DURDURULDU");
                    }
                } else {
                    // Transaction başlat - Tüm kayıtların atomik olması için
                    $db->beginTransaction();
                    
                    if (isset($_GET['debug'])) {
                        error_log("🔄 TRANSACTION BAŞLATILDI");
                    }

                    try {
                        if (isset($_GET['debug'])) {
                            error_log("🎯 TRY BLOĞUNA GİRİLDİ");
                        }
                        
                        // Fotoğraf değişkenini başlat
                        $photoFileName = null;

                        // FOTOĞRAF YÜKLEME - Transaction İÇİNDE
                        if (!empty($_FILES['foto']['name'])) {
                            if (isset($_GET['debug'])) {
                                error_log("📸 FOTOĞRAF YÜKLEME İŞLEMİ BAŞLATILDI");
                            }
                            
                            try {
                                // ✅ DOĞRU - Sadece bir kez çağırın
								// Fotoğraf yükleme
								$photoFileName = null;
								if (!empty($_FILES['foto']['name'])) {
									try {
										$photoFileName = processUploadedPhoto(
											$_FILES['foto'], 
											$tc_no, 
											$ad_soyadi, 
											$db, 
											null, 
											__DIR__ . '/uploads/personel_fotolar/'
										);
									} catch (Exception $e) {
										error_log("Fotoğraf yükleme hatası: " . $e->getMessage());
										// Fotoğraf yüklenemezse kayda devam et
									}
								}
                       
                                if (!$photoFileName) {
                                    throw new Exception('Fotoğraf yüklenemedi veya geçersiz dosya.');
                                }
                                
                                if (isset($_GET['debug'])) {
                                    error_log("✅ FOTOĞRAF YÜKLENDİ: " . $photoFileName);
                                }
                                
                            } catch (Exception $e) {
                                // Fotoğraf hatası durumunda transaction'ı durdur
                                $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU
                                if (isset($_GET['debug'])) {
                                    error_log("❌ FOTOĞRAF YÜKLEME HATASI: " . $e->getMessage());
                                }
                                throw new Exception('Fotoğraf yükleme hatası: ' . $e->getMessage());
                            }
                        } else {
                            if (isset($_GET['debug'])) {
                                error_log("ℹ️ FOTOĞRAF YÜKLENMEDİ");
                            }
                        }
                        
                        // İl ve ilçe adlarını al
                        $gorev_il_id = !empty($cleanData['gorev_il_id']) ? intval($cleanData['gorev_il_id']) : null;
                        $gorev_ilce_id = !empty($cleanData['gorev_ilce_id']) ? intval($cleanData['gorev_ilce_id']) : null;
                        $gorev_okul_id = !empty($cleanData['gorev_okul_id']) ? intval($cleanData['gorev_okul_id']) : null;
                        
                        // İl adını getir
                        $gorev_il_adi = '';
                        if ($gorev_il_id) {
                            $stmt_il = $db->prepare("SELECT il_adi FROM iller WHERE id = ?");
                            $stmt_il->execute([$gorev_il_id]);
                            $il_row = $stmt_il->fetch(PDO::FETCH_ASSOC);
                            $gorev_il_adi = $il_row['il_adi'] ?? '';
                        }
                        
                        // İlçe adını getir
                        $gorev_ilce_adi = '';
                        if ($gorev_ilce_id) {
                            $stmt_ilce = $db->prepare("SELECT ilce_adi FROM ilceler WHERE id = ?");
                            $stmt_ilce->execute([$gorev_ilce_id]);
                            $ilce_row = $stmt_ilce->fetch(PDO::FETCH_ASSOC);
                            $gorev_ilce_adi = $ilce_row['ilce_adi'] ?? '';
                        }
                        
                        // Okul adını getir
                        $gorev_okul_adi = '';
                        if ($gorev_okul_id) {
                            $stmt_okul = $db->prepare("SELECT gorev_yeri FROM okullar WHERE id = ?");
                            $stmt_okul->execute([$gorev_okul_id]);
                            $okul_row = $stmt_okul->fetch(PDO::FETCH_ASSOC);
                            $gorev_okul_adi = $okul_row['gorev_yeri'] ?? '';
                        }
                        
                        // 1. ANA PERSONEL KAYDI - Temel bilgiler (GÜVENLİ ŞEKİLDE)
                        $sql_personel = "INSERT INTO personel (
                            tc_no, ad_soyadi, emekli_sicil_no, kurum_sicil_no, 
                            arsiv_no, raf_no, il_adi, ilce_adi, gorev_yeri, kurum_kodu, okul_tur,
                            kayit_tarihi, guncelleme_tarihi, foto_path
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";

                        $stmt_personel = $db->prepare($sql_personel);
                        $stmt_personel->execute([
                            $tc_no, 
                            $ad_soyadi, 
                            $cleanData['emekli_sicil_no'] ?? '',
                            $cleanData['kurum_sicil_no'] ?? '',
                            $cleanData['arsiv_no'] ?? '',
                            $cleanData['raf_no'] ?? '',
                            $gorev_il_adi,
                            $gorev_ilce_adi,
                            $gorev_okul_adi,
                            $cleanData['gorev_kurum_kodu'] ?? '',
                            $cleanData['gorev_okul_tur'] ?? '',
                            $photoFileName                         
                        ]);
                        $personel_id = $db->lastInsertId();
                        
                        if (isset($_GET['debug'])) {
                            error_log("✅ PERSONEL KAYDI OLUŞTURULDU - ID: " . $personel_id);
                        }

                        // 2. KİMLİK BİLGİLERİ - personel_kimlik tablosuna
                        if (!empty($cleanData['baba_adi']) || !empty($cleanData['dogum_tarihi']) || !empty($cleanData['cinsiyeti'])) {
                            $sql_kimlik = "INSERT INTO personel_kimlik (
                                personel_id, baba_adi, dogum_tarihi, dogum_yeri, 
                                cinsiyeti, medeni_durum, kan_grubu,
                                kayit_tarihi, guncelleme_tarihi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                            
                            $stmt_kimlik = $db->prepare($sql_kimlik);
                            $stmt_kimlik->execute([
                                $personel_id,
                                $cleanData['baba_adi'] ?? '',
                                $cleanData['dogum_tarihi'] ?? '',
                                $cleanData['dogum_yeri'] ?? '',
                                $cleanData['cinsiyeti'] ?? '',
                                $cleanData['medeni_durum'] ?? '',
                                $cleanData['kan_grubu'] ?? ''
                            ]);
                            
                            if (isset($_GET['debug'])) {
                                error_log("✅ KİMLİK BİLGİLERİ KAYDEDİLDİ");
                            }
                        }

                        // 3. İLETİŞİM BİLGİLERİ - personel_iletisim tablosuna
                        if (!empty($cleanData['telefon']) || !empty($cleanData['email']) || !empty($cleanData['ikametgah_adresi'])) {
                            $sql_iletisim = "INSERT INTO personel_iletisim (
                                personel_id, telefon, email, ev_adresi, ikametgah_adresi,
                                il_adi, ilce_adi, posta_kodu, kayit_tarihi, guncelleme_tarihi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                            
                            $stmt_iletisim = $db->prepare($sql_iletisim);
                            $stmt_iletisim->execute([
                                $personel_id,
                                $cleanData['telefon'] ?? '',
								!empty($cleanData['email']) ? $cleanData['email'] : null,
                                $cleanData['ikametgah_adresi'] ?? '',
                                $cleanData['ikametgah_adresi'] ?? '',
                                $gorev_il_adi,
                                $gorev_ilce_adi,
                                '' // posta_kodu boş
                            ]);
                            
                            if (isset($_GET['debug'])) {
                                error_log("✅ İLETİŞİM BİLGİLERİ KAYDEDİLDİ");
                            }
                        }

                        // 4. GÖREV BİLGİLERİ - personel_gorev tablosuna
                        // ID'leri metne dönüştür
                        $hizmet_sinifi_text = !empty($cleanData['hizmet_sinifi']) ? 
                            getHizmetSinifiAdi($cleanData['hizmet_sinifi'], $db) : '';
                        
                        $kadro_unvani_text = !empty($cleanData['kadro_unvani']) ? 
                            getKadroUnvaniAdi($cleanData['kadro_unvani'], $db) : '';
                        
                        $gorev_unvani_text = !empty($cleanData['gorev_unvani']) ? 
                            getGorevUnvaniAdi($cleanData['gorev_unvani'], $db) : '';
                        
                        $atama_alani_text = !empty($cleanData['atama_alani']) ? 
                            getAtamaAlaniAdi($cleanData['atama_alani'], $db) : '';
                        
                        $yer_degistirme_text = !empty($cleanData['yer_degistirme_cesidi']) ? 
                            getYerDegistirmeCesidiAdi($cleanData['yer_degistirme_cesidi'], $db) : '';

                        // Atama çeşidi fonksiyonunu kontrol et (eğer yoksa boş bırak)
                        $atama_cesidi_text = '';
                        if (!empty($cleanData['atama_cesidi'])) {
                            try {
                                $stmt_atama = $db->prepare("SELECT atama_cesidi FROM atama_cesidi WHERE id = ?");
                                $stmt_atama->execute([$cleanData['atama_cesidi']]);
                                $atama_row = $stmt_atama->fetch(PDO::FETCH_ASSOC);
                                $atama_cesidi_text = $atama_row['atama_cesidi'] ?? '';
                            } catch (Exception $e) {
                                error_log("Atama çeşidi getirme hatası: " . $e->getMessage());
                            }
                        }

                        $sql_gorev = "INSERT INTO personel_gorev (
                            personel_id,
                            istihdam_tipi, 
                            hizmet_sinifi, 
                            kadro_unvani, 
                            gorev_unvani,
                            kariyer_basamagi, 
                            atama_alani, 
                            atama_cesidi,
                            memuriyete_baslama_tarihi, 
                            kurum_baslama_tarihi,
                            durum,
                            yer_degistirme_cesidi, 
                            gorev_aciklama,
                            gorev_il_adi, 
                            gorev_ilce_adi, 
                            gorev_okul_adi, 
                            gorev_kurum_kodu, 
                            gorev_okul_tur, 
                            gorev_kapali_kurum,
                            kayit_tarihi,
                            guncelleme_tarihi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                        $stmt_gorev = $db->prepare($sql_gorev);
                        $stmt_gorev->execute([
                            $personel_id,
                            $cleanData['istihdam_tipi'] ?? '',
                            $hizmet_sinifi_text,
                            $kadro_unvani_text,
                            $gorev_unvani_text,
                            $cleanData['kariyer_basamagi'] ?? '',
                            $atama_alani_text,
                            $atama_cesidi_text,
                            $cleanData['memuriyete_baslama_tarihi'] ?? '',
                            $cleanData['kurum_baslama_tarihi'] ?? '',
                            $cleanData['durum'] ?? 'Görevde',
                            $yer_degistirme_text,
                            $cleanData['gorev_aciklama'] ?? '',
                            $gorev_il_adi,
                            $gorev_ilce_adi,
                            $gorev_okul_adi,
                            $cleanData['gorev_kurum_kodu'] ?? '',
                            $cleanData['gorev_okul_tur'] ?? '',
                            !empty($cleanData['gorev_kapali_kurum']) ? 1 : 0
                        ]);
                        
                        if (isset($_GET['debug'])) {
                            error_log("✅ GÖREV BİLGİLERİ KAYDEDİLDİ");
                        }

                        // 5. KADRO BİLGİLERİ - personel_kadro tablosuna
                        if (!empty($cleanData['terfi_tarihi']) || !empty($cleanData['kadro_derecesi'])) {
                            $terfi_nedeni_text = !empty($cleanData['terfi_nedeni']) ? 
                                getTerfiNedeniAdi($cleanData['terfi_nedeni'], $db) : '';
                            
                            $sql_kadro = "INSERT INTO personel_kadro (
                                personel_id, terfi_tarihi, terfi_nedeni, kadro_derecesi, 
                                aylik_derece, aylik_kademe, kha_ek_gosterge,
                                kayit_tarihi, guncelleme_tarihi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                            
                            $stmt_kadro = $db->prepare($sql_kadro);
                            $stmt_kadro->execute([
                                $personel_id,
                                $cleanData['terfi_tarihi'] ?? '',
                                $terfi_nedeni_text,
                                $cleanData['kadro_derecesi'] ?? '',
                                $cleanData['aylik_derece'] ?? '',
                                $cleanData['aylik_kademe'] ?? '',
                                $cleanData['kha_ek_gosterge'] ?? ''
                            ]);
                            
                            if (isset($_GET['debug'])) {
                                error_log("✅ KADRO BİLGİLERİ KAYDEDİLDİ");
                            }
                        }
                        
// 6. SÖZLEŞME BİLGİLERİ - personel_sozlesme tablosuna (AYNI KALIYOR, DOĞRU)
if (!empty($cleanData['sozlesme_il_id']) || !empty($cleanData['sozlesme_baslangic'])) {
    // Sözleşme il, ilçe ve okul adlarını al
    $sozlesme_il_adi = '';
    $sozlesme_ilce_adi = '';
    $sozlesme_okul_adi = '';
    
    if (!empty($cleanData['sozlesme_il_id'])) {
        $stmt_il = $db->prepare("SELECT il_adi FROM iller WHERE id = ?");
        $stmt_il->execute([$cleanData['sozlesme_il_id']]);
        $il_row = $stmt_il->fetch(PDO::FETCH_ASSOC);
        $sozlesme_il_adi = $il_row['il_adi'] ?? '';
    }
    
    if (!empty($cleanData['sozlesme_ilce_id'])) {
        $stmt_ilce = $db->prepare("SELECT ilce_adi FROM ilceler WHERE id = ?");
        $stmt_ilce->execute([$cleanData['sozlesme_ilce_id']]);
        $ilce_row = $stmt_ilce->fetch(PDO::FETCH_ASSOC);
        $sozlesme_ilce_adi = $ilce_row['ilce_adi'] ?? '';
    }
    
    if (!empty($cleanData['sozlesme_okul_id'])) {
        $stmt_okul = $db->prepare("SELECT gorev_yeri FROM okullar WHERE id = ?");
        $stmt_okul->execute([$cleanData['sozlesme_okul_id']]);
        $okul_row = $stmt_okul->fetch(PDO::FETCH_ASSOC);
        $sozlesme_okul_adi = $okul_row['gorev_yeri'] ?? '';
    }
    
    $sql_sozlesme = "INSERT INTO personel_sozlesme (
        personel_id,
        sozlesme_il_adi, sozlesme_ilce_adi, sozlesme_okul_adi, 
        sozlesme_kurum_kodu, sozlesme_okul_tur, sozlesme_kapali_kurum,
        sozlesme_turu, sozlesmeli_baslama_tarihi, sozlesmeli_bitis_tarihi, 
        sozlesme_suresi, sozlesme_aciklama,
        kayit_tarihi, guncelleme_tarihi
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt_sozlesme = $db->prepare($sql_sozlesme);
    $stmt_sozlesme->execute([
        $personel_id,
        $sozlesme_il_adi,
        $sozlesme_ilce_adi,
        $sozlesme_okul_adi,
        $cleanData['sozlesme_kurum_kodu'] ?? '',
        $cleanData['sozlesme_okul_tur'] ?? '',
        !empty($cleanData['sozlesme_kapali_kurum']) ? 1 : 0,
        $cleanData['sozlesme_turu'] ?? '',
        $cleanData['sozlesme_baslangic'] ?? '',
        $cleanData['sozlesme_bitis'] ?? '',
        $cleanData['sozlesme_suresi'] ?? '',
        $cleanData['sozlesme_aciklama'] ?? ''
    ]);
    
    if (isset($_GET['debug'])) {
        error_log("✅ SÖZLEŞME BİLGİLERİ KAYDEDİLDİ");
    }
}

// 7. ÖĞRENİM BİLGİLERİ - personel_ogrenim tablosuna (DÜZELTİLDİ - ID'ler kaydediliyor!)
if (!empty($cleanData['mezuniyet_tarihi']) || !empty($cleanData['ogrenim_durumu'])) {
    
    // ÖĞRENİM DURUMU - ID olarak kaydet
    $ogrenim_durumu_id = !empty($cleanData['ogrenim_durumu']) ? $cleanData['ogrenim_durumu'] : null;
    
    // ÜNİVERSİTE - ID olarak kaydet
    $universite_id = !empty($cleanData['universite_id']) ? $cleanData['universite_id'] : null;
    
    // FAKÜLTE - ID olarak kaydet
    $fakulte_id = !empty($cleanData['fakulte_yuksekokul_id']) ? $cleanData['fakulte_yuksekokul_id'] : null;
    
    // ANABİLİM DALI - ID olarak kaydet
    $anabilim_id = !empty($cleanData['anabilim_dali_id']) ? $cleanData['anabilim_dali_id'] : null;
    
    // PROGRAM - ID olarak kaydet
    $program_id = !empty($cleanData['program_id']) ? $cleanData['program_id'] : null;
    
    $sql_ogrenim = "INSERT INTO personel_ogrenim (
        personel_id, 
        ogrenim_durumu_id,
        mezun_okul_id,
        universite_id, 
        fakulte_yuksekokul_id, 
        anabilim_dali_id, 
        program_id, 
        mezuniyet_tarihi,
        belge_tarihi, 
        belge_no, 
        belge_cinsi, 
        belge_aciklama,
        kayit_tarihi,
        guncelleme_tarihi
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt_ogrenim = $db->prepare($sql_ogrenim);
    $stmt_ogrenim->execute([
        $personel_id,
        $ogrenim_durumu_id,           // ID olarak
        $cleanData['mezun_okul_id'] ?? '',
        $universite_id,                // ID olarak
        $fakulte_id,                   // ID olarak
        $anabilim_id,                  // ID olarak
        $program_id,                   // ID olarak
        $cleanData['mezuniyet_tarihi'] ?? '',
        $cleanData['belge_tarihi'] ?? '',
        $cleanData['belge_no'] ?? '',
        $cleanData['belge_cinsi'] ?? '',
        $cleanData['belge_aciklama'] ?? ''
    ]);
    
    if (isset($_GET['debug'])) {
        error_log("✅ ÖĞRENİM BİLGİLERİ KAYDEDİLDİ (ID'ler ile)");
        error_log("📌 Öğrenim ID: $ogrenim_durumu_id, Üniversite ID: $universite_id, Fakülte ID: $fakulte_id, Anabilim ID: $anabilim_id, Program ID: $program_id");
    }
}

                        // 8. ÖNCEKİ KURUM BİLGİLERİ
                        if (!empty($cleanData['geldigi_il_id']) || !empty($cleanData['geldigi_ayrilma_tarihi'])) {
                            // Geldiği kurum il, ilçe ve okul adlarını al
                            $geldigi_il_adi = '';
                            $geldigi_ilce_adi = '';
                            $geldigi_okul_adi = '';
                            
                            if (!empty($cleanData['geldigi_il_id'])) {
                                $stmt_il = $db->prepare("SELECT il_adi FROM iller WHERE id = ?");
                                $stmt_il->execute([$cleanData['geldigi_il_id']]);
                                $il_row = $stmt_il->fetch(PDO::FETCH_ASSOC);
                                $geldigi_il_adi = $il_row['il_adi'] ?? '';
                            }
                            
                            if (!empty($cleanData['geldigi_ilce_id'])) {
                                $stmt_ilce = $db->prepare("SELECT ilce_adi FROM ilceler WHERE id = ?");
                                $stmt_ilce->execute([$cleanData['geldigi_ilce_id']]);
                                $ilce_row = $stmt_ilce->fetch(PDO::FETCH_ASSOC);
                                $geldigi_ilce_adi = $ilce_row['ilce_adi'] ?? '';
                            }
                            
                            if (!empty($cleanData['geldigi_okul_id'])) {
                                $stmt_okul = $db->prepare("SELECT gorev_yeri FROM okullar WHERE id = ?");
                                $stmt_okul->execute([$cleanData['geldigi_okul_id']]);
                                $okul_row = $stmt_okul->fetch(PDO::FETCH_ASSOC);
                                $geldigi_okul_adi = $okul_row['gorev_yeri'] ?? '';
                            }
                            
                            // Önceki görev unvanını al
                            $onceki_gorev_text = !empty($cleanData['geldigi_gorev_unvani']) ? 
                                getOncekiGorevUnvaniAdi($cleanData['geldigi_gorev_unvani'], $db) : '';

                            $sql_onceki = "INSERT INTO personel_onceki_kurum (
                                personel_id,
                                il_adi, 
                                ilce_adi, 
                                kurum_kodu,
                                okul_tur, 
                                kapali_kurum,
                                onceki_gorev_unvani_id,
                                ayrilma_tarihi, 
                                ayrilma_nedeni,
                                kayit_tarihi,
                                guncelleme_tarihi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                            $stmt_onceki = $db->prepare($sql_onceki);
                            $stmt_onceki->execute([
                                $personel_id,
                                $geldigi_il_adi,
                                $geldigi_ilce_adi,
                                $cleanData['geldigi_kurum_kodu'] ?? '',
                                $cleanData['geldigi_okul_tur'] ?? '',
                                !empty($cleanData['geldigi_kapali_kurum']) ? 1 : 0,
                                $onceki_gorev_text,
                                $cleanData['geldigi_ayrilma_tarihi'] ?? '',
                                $cleanData['geldigi_ayrilma_nedeni'] ?? ''
                            ]);
                            
                            if (isset($_GET['debug'])) {
                                error_log("✅ ÖNCEKİ KURUM BİLGİLERİ KAYDEDİLDİ");
                            }
                        }

                        // Tüm işlemler başarılı - Commit
                        $db->commit();
                        
                        if (isset($_GET['debug'])) {
                            error_log("🎉 TRANSACTION BAŞARIYLA TAMAMLANDI - PERSONEL ID: $personel_id");
                        }

                        // Başarı mesajını session'a kaydet ve yönlendir
                        $_SESSION['success_message'] = 'Personel başarıyla eklendi.';
                        unset($_SESSION['form_data']); // ✅ BAŞARILI KAYITTA FORM VERİLERİNİ TEMİZLE
                        if (isset($_GET['debug'])) {
                            error_log("🔀 YÖNLENDİRME YAPILACAK: personel_ekle.php");
                        }
                        header('Location: personel_ekle.php');
                        exit;

                    } catch (Exception $e) {
                        // Hata durumunda rollback
                        $db->rollBack();
                        $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $_POST); // FORM VERİLERİNİ KORU

                        if (isset($_GET['debug'])) {
                            error_log("❌ TRANSACTION HATASI - ROLLBACK YAPILDI: " . $e->getMessage());
                            error_log("📂 Dosya: " . $e->getFile() . ":" . $e->getLine());
                            error_log("🔍 Stack Trace: " . $e->getTraceAsString());
                        }

                        // Geliştirme ortamında detaylı hata, production'da genel hata mesajı
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            $errorMessage = $e->getMessage();
                        } else {
                            $errorMessage = 'Kayıt işlemi sırasında bir hata oluştu.';
                            
                            // Belirli hata türlerini kullanıcıya daha anlamlı şekilde ilet
                            if (strpos($e->getMessage(), 'Fotoğraf') !== false) {
                                $errorMessage = $e->getMessage();
                            } else if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                $errorMessage = 'Bu kayıt zaten mevcut. Lütfen bilgileri kontrol edin.';
                            } else if (strpos($e->getMessage(), 'foreign key') !== false) {
                                $errorMessage = 'İlgili kayıt bulunamadı. Lütfen seçimlerinizi kontrol edin.';
                            }
                        }

                        // Loglama
                        logError("Personel Ekleme Hatası: " . $e->getMessage(), [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        $mesaj = $errorMessage;
                    }
                } // ✅ Burada telefon hatası kontrolü kapanıyor
            } // ✅ Burada else ($existing) kapanıyor
        } // ✅ Burada else (validateTCNo) kapanıyor
    } // ✅ Burada else (checkCSRF) kapanıyor
} // ✅ Burada POST kapanıyor


// AJAX İSTEKLERİNİ İŞLEME - Dinamik dropdown'lar için
if (isset($_GET['ajax'])) {
    // Güvenlik header'ları
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

    try {
        // AJAX için basitleştirilmiş
        $db = Database::getInstance();

        // AJAX tipini güvenli şekilde al
        $ajaxType = $_GET['ajax'];
        if (!preg_match('/^[a-z_]+$/', $ajaxType)) {
            throw new Exception("Geçersiz AJAX isteği");
        }

        switch ($ajaxType) {
            case 'ilceler':
                if (!isset($_GET['il_id'])) {
                    throw new Exception("İl ID gereklidir");
                }
                $ilId = filter_var($_GET['il_id'], FILTER_VALIDATE_INT);
                if (!$ilId || $ilId < 1) {
                    throw new Exception("Geçersiz il ID");
                }
                $kapaliKurum = isset($_GET['kapali_kurum']) && $_GET['kapali_kurum'] == '1';
                if ($kapaliKurum) {
                    $stmt = $db->prepare("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? ORDER BY ilce_adi");
                } else {
                    $stmt = $db->prepare("SELECT id, ilce_adi FROM ilceler WHERE il_id = ? AND (ilce_tipi = 'A' OR ilce_tipi = '(A)') ORDER BY ilce_adi");
                }
                $stmt->execute([$ilId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'okullar':
                if (!isset($_GET['il_id']) || !isset($_GET['ilce_id'])) {
                    throw new Exception("İl ve ilçe ID gereklidir");
                }
                $ilId = filter_var($_GET['il_id'], FILTER_VALIDATE_INT);
                $ilceId = filter_var($_GET['ilce_id'], FILTER_VALIDATE_INT);
                if (!$ilId || $ilId < 1 || !$ilceId || $ilceId < 1) {
                    throw new Exception("Geçersiz il veya ilçe ID");
                }
                $kapaliKurum = isset($_GET['kapali_kurum']) && $_GET['kapali_kurum'] == '1';
                $sql = "SELECT id, gorev_yeri, okul_tur, kurum_kodu FROM okullar WHERE il_id = ? AND ilce_id = ?";
                if (!$kapaliKurum) {
                    $sql .= " AND kapali = 0";
                }
                $sql .= " ORDER BY gorev_yeri";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ilId, $ilceId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'hizmet_siniflari':
                $stmt = $db->prepare("SELECT id, sinif_adi FROM hizmet_siniflari WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'kadro_unvanlari':
                if (!isset($_GET['hizmet_sinif_id'])) {
                    throw new Exception("Hizmet sınıfı ID gereklidir");
                }
                $hizmetSinifId = filter_var($_GET['hizmet_sinif_id'], FILTER_VALIDATE_INT);
                if (!$hizmetSinifId || $hizmetSinifId < 1) {
                    throw new Exception("Geçersiz hizmet sınıfı ID");
                }
                $stmt = $db->prepare("SELECT id, unvan_adi FROM kadro_unvanlari WHERE hizmet_sinif_id = ? AND durum = 1 ORDER BY id ASC");
                $stmt->execute([$hizmetSinifId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'gorev_unvanlari':
                if (!isset($_GET['hizmet_sinif_id'])) {
                    throw new Exception("Hizmet sınıfı ID gereklidir");
                }
                $hizmetSinifId = filter_var($_GET['hizmet_sinif_id'], FILTER_VALIDATE_INT);
                if (!$hizmetSinifId || $hizmetSinifId < 1) {
                    throw new Exception("Geçersiz hizmet sınıfı ID");
                }
                $stmt = $db->prepare("SELECT id, unvan_adi FROM gorev_unvanlari WHERE hizmet_sinif_id = ? AND durum = 1 ORDER BY id ASC");
                $stmt->execute([$hizmetSinifId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'durum':
                $stmt = $db->prepare("SELECT id, durum_adi FROM durumu WHERE aktif = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'atama_alani':
                $stmt = $db->prepare("SELECT id, alan_adi FROM atama_alani WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'yer_degistirme_cesidi':
                $stmt = $db->prepare("SELECT id, yer_degistirme_cesidi FROM yer_degistirme_cesidi WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
            
            case 'terfi_nedenleri':
                $stmt = $db->prepare("SELECT id, terfi_nedeni FROM terfi_nedenleri WHERE durum = 1 ORDER BY id ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'tum_gorev_unvanlari':
                $stmt = $db->prepare("SELECT id, unvan_adi FROM gorev_unvanlari WHERE durum = 1 ORDER BY unvan_adi ASC");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

			case 'universiteler':
				$stmt = $db->prepare("SELECT universite_id AS id, universite_adi FROM universiteler ORDER BY universite_adi ASC");
				$stmt->execute();
				echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
				break;

			case 'fakulteler':
                if (!isset($_GET['universite_id'])) {
                    throw new Exception("Üniversite ID gereklidir");
                }
                $universiteId = filter_var($_GET['universite_id'], FILTER_VALIDATE_INT);
                if (!$universiteId || $universiteId < 1) {
                    throw new Exception("Geçersiz üniversite ID");
                }
                $stmt = $db->prepare("SELECT fakulte_id AS id, fakulte_adi FROM fakulte_yuksekokul WHERE universite_id = ? ORDER BY fakulte_adi");
                $stmt->execute([$universiteId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'anabilimler':
                if (!isset($_GET['fakulte_yuksekokul_id'])) {
                    throw new Exception("Fakülte ID gereklidir");
                }
                $fakulteId = filter_var($_GET['fakulte_yuksekokul_id'], FILTER_VALIDATE_INT);
                if (!$fakulteId || $fakulteId < 1) {
                    throw new Exception("Geçersiz fakülte ID");
                }
                $stmt = $db->prepare("SELECT anabilim_id AS id, anabilim_adi FROM anabilim_dali WHERE fakulte_yuksekokul_id = ? ORDER BY anabilim_adi");
                $stmt->execute([$fakulteId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'programlar':
                if (!isset($_GET['anabilim_id'])) {
                    throw new Exception("Anabilim ID gereklidir");
                }
                $anabilimId = filter_var($_GET['anabilim_id'], FILTER_VALIDATE_INT);
                if (!$anabilimId || $anabilimId < 1) {
                    throw new Exception("Geçersiz anabilim ID");
                }
                $stmt = $db->prepare("SELECT program_id AS id, program_adi FROM program WHERE anabilim_dali_id = ? ORDER BY program_adi");
                $stmt->execute([$anabilimId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            default:
                throw new Exception("Geçersiz AJAX isteği");
        }
		
    } catch (Exception $e) {
        error_log("AJAX Hatası: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}
?>
<!-- HTML KODU BURAYA GELECEK -->

	<?php include 'head.php'; ?>
	<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Personel Ekle</title>
  
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
<style>

/* ============================================================
   PERSONEL TAKİP SİSTEMİ - CSS STİL DOSYASI
   Tüm bileşenlerin stil tanımlamalarını içerir
   ============================================================ */

/* ------------------------------------------------------------
   1. GENEL SAYFA DÜZENİ VE TEMEL YAPILANDIRMA
   - HTML ve Body elementleri için temel ayarlar
   - Box model ve genel layout yapılandırması
------------------------------------------------------------ */

/* Sayfa genel kaydırma ve box model ayarları */
html {
  overflow-x: hidden;           /* Yatay kaydırmayı gizle - yan taşmayı önler */
  overflow-y: scroll;           /* Dikey kaydırmayı her zaman etkin tut */
}

/* Ana sayfa gövdesi stil tanımlamaları */
body {
  background-color: #f4f6f9;    /* Açık gri arka plan - göz yorgunluğunu azaltır */
  margin: 0;                    /* Varsayılan margin'i sıfırla - tarayıcı uyumsuzluğunu giderir */
  padding: 113px;               /* Tüm kenarlarda 24px boşluk - içerik nefes alır */
  overflow-x: hidden;           /* Yatay taşmayı önle - responsive tasarım için */
  box-sizing: border-box;       /* Padding ve border'ı genişliğe dahil et - hesaplama kolaylığı */
}

/* ------------------------------------------------------------
   2. SEKMELİ NAVİGASYON SİSTEMİ
   - Ana sekmelerin ve içerik alanlarının stil tanımlamaları
   - Kullanıcı gezinme deneyimini iyileştiren stiller
------------------------------------------------------------ */

/* Sekme konteyneri - ana navigasyon çubuğu */
.main-tabs-container {
  margin-top: 24px;             /* Üstten 24px boşluk - başlıktan ayrım */
  border-bottom: 2px solid #e9ecef; /* Sekmeler altında çizgi - görsel ayırıcı */
}

/* Sekme başlıkları listesi */
.nav-tabs {
  border-bottom: none;          /* Bootstrap varsayılan border'ını kaldır */
  flex-wrap: wrap;              /* Çok satıra sarmaya izin ver - responsive */
  gap: 4px;                     /* Sekmeler arası 4px boşluk - modern spacing */
}

/* Bireysel sekme bağlantıları */
.nav-tabs .nav-link {
  font-weight: 500;             /* Orta kalınlıkta yazı - okunabilirlik */
  color: #495057;               /* Koyu gri renk - nötr ton */
  border: 1px solid transparent; /* Şeffaf border - hover efekti için hazırlık */
  border-radius: 8px 8px 0 0;   /* Üst köşeleri yuvarlat - modern görünüm */
  padding: 12px 20px;           /* İç boşluk - dokunma alanı genişliği */
  transition: all 0.3s ease;    /* Geçiş efekti - smooth hover animasyonu */
  position: relative;           /* Pozisyonlandırma - aktif çizgi için */
  background-color: #f8f9fa;    /* Açık gri arka plan - inaktif durum */
  margin-bottom: -2px;          /* Alt çizgiyle birleşim için - görsel bütünlük */
  box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* Hafif gölge - derinlik hissi */
}

/* Sekme hover durumu - kullanıcı etkileşimi */
.nav-tabs .nav-link:hover {
  background-color: #e9ecef;    /* Hoverda daha koyu gri - geri bildirim */
  border-color: #dee2e6;        /* Border rengi - görünürlük artışı */
  color: #0d6efd;               /* Mavi yazı rengi - marka rengi */
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); /* Gradient efekti */
}

/* Aktif sekme stilleri - geçerli sayfa göstergesi */
.nav-tabs .nav-link.active {
  background-color: white;      /* Beyaz arka plan - içerikle uyum */
  color: #0d6efd;               /* Mavi yazı rengi - aktif durum */
  font-weight: 600;             /* Kalın yazı - vurgu */
  border: 1px solid #dee2e6;    /* Gri border - çerçeveleme */
  border-bottom: 2px solid white; /* Alt border'ı beyaz yap - çizgiyle birleşim */
  position: relative;           /* Pozisyonlandırma - z-index için */
  z-index: 1;                   /* Diğer elementlerin üstünde - öncelik */
  box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2); /* Mavi gölge - aktif vurgu */
}

/* Aktif sekmenin altındaki mavi çizgi - görsel indikatör */
.nav-tabs .nav-link.active::after {
  content: '';                  /* İçerik yok - pseudo element */
  position: absolute;           /* Mutlak pozisyon - konumlandırma */
  bottom: -2px;                 /* Alt hizala - çizgi konumu */
  left: 0;                      /* Sol hizala - tam genişlik */
  width: 100%;                  /* Tam genişlik - sekme boyunca */
  height: 3px;                  /* 3px yükseklik - ince çizgi */
  background-color: #0d6efd;    /* Mavi renk - marka rengi */
  border-radius: 2px 2px 0 0;   /* Üst köşeleri yuvarlat - estetik */
}

/* Sekme ikonları - görsel destek */
.nav-tabs .nav-link i {
  margin-right: 8px;            /* Sağda 8px boşluk - ikon-metin arası */
  font-size: 1rem;              /* 1rem boyut - orantılı */
}

/* Sekme içerik alanı - form bileşenleri konteyneri */
.tab-content {
  background: white;            /* Beyaz arka plan - içerik vurgusu */
  border: 1px solid #dee2e6;    /* Gri border - çerçeveleme */
  border-top: none;             /* Üst border'ı kaldır - sekmelerle birleşim */
  border-radius: 0 0 8px 8px;   /* Sadece alt köşeleri yuvarlat - modern */
  padding: 24px;                /* İç boşluk - içerik nefes alır */
  min-height: 420px;            /* Minimum yükseklik - layout bütünlüğü */
  overflow-x: hidden;           /* Yatay taşmayı gizle - temiz görünüm */
  box-sizing: border-box;       /* Box model - tutarlı ölçüm */
  box-shadow: 0 4px 6px rgba(0,0,0,0.05); /* Hafif gölge - derinlik */
}

/* Bireysel sekme paneli */
.tab-pane {
  overflow-x: hidden;           /* Yatay taşmayı gizle - responsive */
}

/* ------------------------------------------------------------
   3. KART (CARD) BİLEŞENLERİ
   - İçerik bölümlerini gruplayan kart yapıları
   - Form alanlarını düzenli gruplama
------------------------------------------------------------ */

/* Ana kart yapısı - bölüm konteyneri */
.section-card {
  background-color: #fff;       /* Beyaz arka plan - içerik vurgusu */
  border: 1px solid #dee2e6;    /* Gri border - ayırıcı çizgi */
  border-radius: 8px;           /* Köşeleri yuvarlat - modern tasarım */
  padding: 24px;                /* İç boşluk - içerik marjı */
  padding-bottom: 10px;         /* Alt boşluğu azalt - kompakt görünüm */
  margin-bottom: 24px;          /* Alt boşluk - kartlar arası mesafe */
  box-shadow: 0 2px 8px rgba(0,0,0,0.04); /* Hafif gölge - derinlik */
}

/* Kart içi son eleman marjin ayarı */
.section-card > .row:last-child,
.section-card > .form-group:last-child {
  margin-bottom: 0;             /* Son elementin margin'ini sıfırla - temiz bitiş */
}

/* Son kart marjin ayarı */
.section-card:last-child {
  margin-bottom: 0;             /* Son kartın margin'ini sıfırla - konteyner uyumu */
}

/* Kart hover efekti - etkileşim geri bildirimi */
.section-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.08); /* Hoverda daha belirgin gölge */
}

/* Kart başlık alanı - bölüm tanımlayıcı */
.section-title {
  background-color: #f0f2f5;    /* Açık gri arka plan - başlık vurgusu */
  color: #2c3e50;               /* Koyu mavi-gri renk - okunabilirlik */
  font-weight: 600;             /* Kalın yazı - önem vurgusu */
  font-size: 1.1rem;            /* 1.1rem boyut - hiyerarşi */
  padding: 12px 16px;           /* İç boşluk - dengeli spacing */
  border-left: 4px solid #0d6efd; /* Sol mavi çizgi - görsel indikatör */
  border-radius: 6px;           /* Köşeleri yuvarlat - uyumluluk */
  margin-bottom: 20px;          /* Alt boşluk - içerikten ayrım */
  display: flex;                /* Flex container - ikon-metin hizalama */
  align-items: center;          /* Dikeyde ortala - düzgün görünüm */
  gap: 8px;                     /* Elementler arası boşluk - modern spacing */
}

/* Başlık ikonları */
.section-title i {
  color: #2c3e50;               /* Koyu mavi-gri renk - başlıkla uyum */
  font-size: 1.2rem;            /* 1.2rem boyut - görsel denge */
}

/* Fotoğraf kartı yükseklik eşitleme */
.section-card.photo-equal {
  min-height: 288px;            /* Minimum yükseklik - layout dengeleme */
}

/* ------------------------------------------------------------
   4. FORM ELEMENTLERİ VE GİRİŞ KONTROLLERİ
   - Input, select, textarea ve diğer form bileşenleri
   - Kullanıcı girişi için optimize edilmiş stiller
------------------------------------------------------------ */

/* Form grup konteyneri - alan gruplama */
.form-group {
  margin-bottom: 16px;          /* Alt boşluk - alanlar arası mesafe */
}

/* Form etiketleri - alan açıklayıcı */
.form-label {
  font-weight: 500;             /* Orta kalınlıkta yazı - okunabilirlik */
  margin-bottom: 6px;           /* Alt boşluk - input ile mesafe */
  display: block;               /* Blok element - tam genişlik */
}

/* Temel form kontrolleri - input, select, textarea */
.form-control,
.form-select,
textarea {
  padding: 10px 12px;           /* İç boşluk - dokunma alanı */
  border-radius: 6px;           /* Köşeleri yuvarlat - modern */
  border: 1px solid #ced4da;    /* Gri border - standart çerçeve */
  width: 100%;                  /* Tam genişlik - responsive */
  font-size: 0.95rem;           /* Yazı boyutu - okunabilirlik */
  box-sizing: border-box;       /* Box model - tutarlı ölçüm */
  max-width: 100%;              /* Maksimum genişlik - taşma önleme */
}

/* Form kontrolleri odak durumu - erişilebilirlik */
.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;        /* Odaklandığında mavi border - görsel geri bildirim */
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25); /* Mavi gölge - vurgu */
}

/* Zorunlu alan göstergesi - kullanıcı rehberi */
.required-field::after {
  content: " *";                /* Yıldız işareti - standart gösterge */
  color: red;                   /* Kırmızı renk - dikkat çekici */
}

/* Tarih seçici alanları - özel stiller */
.flatpickr-input,
input.datepicker {
  background-color: white !important; /* Beyaz arka plan - tutarlılık */
  cursor: pointer;              /* Pointer imleci - tıklanabilir olduğunu göster */
}

/* Devre dışı bırakılmış form alanları */
input:disabled, select:disabled, textarea:disabled {
  background-color: #f8f9fa !important; /* Açık gri arka plan - devre dışı görünüm */
  color: #6c757d !important;    /* Gri yazı rengi - pasif durum */
  border-color: #dee2e6 !important; /* Gri border - uyumluluk */
  cursor: not-allowed !important; /* Yasak imleci - etkileşim yok */
}

/* ------------------------------------------------------------
   5. FORMLAR ARASI GEZİNME (NAVİGASYON) SİSTEMİ
   - Önceki/Sonraki butonları ve kaydetme işlemleri
   - Kullanıcı form gezinme deneyimi
------------------------------------------------------------ */

/* Navigasyon konteyneri - buton grubu */
.form-navigation {
  margin-top: 30px;             /* Üst boşluk - içerikten ayrım */
  padding-top: 20px;            /* İç üst boşluk - denge */
  border-top: 2px solid #e9ecef; /* Üst çizgi - görsel ayırıcı */
  display: flex;                /* Flex container - buton hizalama */
  justify-content: space-between; /* Aralı hizalama - sol-sağ dağılım */
  align-items: center;          /* Dikeyde ortala - düzgün görünüm */
}

/* Navigasyon butonları - temel stil */
.btn-navigation {
  padding: 10px 25px;           /* İç boşluk - dokunma alanı */
  font-weight: 500;             /* Orta kalınlıkta yazı - okunabilirlik */
  border-radius: 6px;           /* Köşeleri yuvarlat - modern */
  transition: all 0.3s ease;    /* Geçiş efekti - smooth animasyon */
  display: flex;                /* Flex container - ikon-metin hizalama */
  align-items: center;          /* Dikeyde ortala - düzgün görünüm */
  justify-content: center;      /* Yatayda ortala - merkezi hizalama */
  min-width: 140px;             /* Minimum genişlik - tutarlı boyut */
}

/* Önceki butonu - ikincil işlem */
.btn-prev {
  background-color: #6c757d;    /* Gri arka plan - ikincil renk */
  color: white;                 /* Beyaz yazı - kontrast */
  border: 1px solid #6c757d;    /* Gri border - uyumluluk */
}

/* Önceki butonu hover durumu */
.btn-prev:hover {
  background-color: #5a6268;    /* Koyu gri arka plan - etkileşim */
  border-color: #545b62;        /* Koyu gri border - uyumluluk */
  color: white;                 /* Beyaz yazı - sabit */
  transform: translateY(-2px);  /* Yukarı kaldırma efekti - derinlik */
  box-shadow: 0 4px 8px rgba(0,0,0,0.15); /* Gölge - kaldırma hissi */
}

/* Sonraki ve Kaydet butonları - birincil işlem */
.btn-next, .btn-save {
  background-color: #0d6efd;    /* Mavi arka plan - birincil renk */
  color: white;                 /* Beyaz yazı - kontrast */
  border: 1px solid #0d6efd;    /* Mavi border - uyumluluk */
}

/* Sonraki ve Kaydet butonları hover durumu */
.btn-next:hover, .btn-save:hover {
  background-color: #0b5ed7;    /* Koyu mavi arka plan - etkileşim */
  border-color: #0a58ca;        /* Koyu mavi border - uyumluluk */
  color: white;                 /* Beyaz yazı - sabit */
  transform: translateY(-2px);  /* Yukarı kaldırma efekti - derinlik */
  box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25); /* Mavi gölge - marka uyumu */
}

/* Kaydet butonu özel stilleri - onay işlemi */
.btn-save {
  background-color: #198754;    /* Yeşil arka plan - başarı/onay rengi */
  border-color: #198754;        /* Yeşil border - uyumluluk */
}

/* Kaydet butonu hover durumu */
.btn-save:hover {
  background-color: #157347;    /* Koyu yeşil arka plan - etkileşim */
  border-color: #146c43;        /* Koyu yeşil border - uyumluluk */
}

/* İlk sekmede Önceki butonunu gizle - mantıksal kısıtlama */
#temel-bilgiler .btn-prev {
  visibility: hidden;           /* Görünmez yap - yer kaplamasın */
  pointer-events: none;         /* Tıklanmayı engelle - işlevsiz */
}

/* Son sekmede Sonraki butonunu gizle - mantıksal kısıtlama */
#geldigi-kurum .btn-next {
  display: none;                /* Tamamen gizle - layout düzeni */
}

/* Navigasyon buton ikonları */
.btn-navigation i {
  font-size: 0.9rem;            /* Küçük ikon boyutu - orantılı */
}

/* ------------------------------------------------------------
   6. BİLDİRİM VE UYARI SİSTEMLERİ
   - Başarı/hata mesajları, bilgilendirme kutuları
   - Kullanıcı geri bildirim bileşenleri
------------------------------------------------------------ */

/* Otomatik kaybolan mesaj kutusu - sistem bildirimi */
.auto-hide-alert {
  position: fixed;              							/* Sabit pozisyon - sayfa scroll'dan bağımsız */
  top: 50%;                     							/* Dikeyde ortala - görünür konum */
  left: 50%;                    							/* Yatayda ortala - görünür konum */
  transform: translate(-50%, -50%); 						/* Tam ortala - transform ile */
  z-index: 9999;                							/* En üst katman - her şeyin üstünde */
  min-width: 400px;            								/* Minimum genişlik - içerik için */
  text-align: center;          			 					/* Metin ortala - düzenli görünüm */
  box-shadow: 0 4px 15px rgba(0,0,0,0.2); 					/* Belirgin gölge - dikkat çekici */
  border: none;                 							/* Border yok - temiz görünüm */
  border-radius: 12px;          							/* Köşeleri yuvarlat - modern */
  animation: fadeIn 0.5s ease-in-out; 						/* Açılış animasyonu - smooth */
}

/* Başarı mesajı stilleri - olumlu geri bildirim */
.auto-hide-alert.success {
  background: linear-gradient(135deg, #d4edda, #c3e6cb);	/* Yeşil gradient - doğal */
  border-left: 6px solid #28a745; 							/* Sol yeşil çizgi - vurgu */
  color: #155724;               							/* Koyu yeşil yazı - okunabilirlik */
}

/* Hata mesajı stilleri - olumsuz geri bildirim */
.auto-hide-alert.error {
  background: linear-gradient(135deg, #f8d7da, #f5c6cb);	/* Kırmızı gradient - uyarı */
  border-left: 6px solid #dc3545; 							/* Sol kırmızı çizgi - vurgu */
  color: #721c24;               							/* Koyu kırmızı yazı - okunabilirlik */
}

/* Bildirim ikonu */
.auto-hide-alert .alert-icon {
  font-size: 2rem;              /* Büyük ikon boyutu - dikkat çekici */
  margin-bottom: 10px;          /* Alt boşluk - metinden ayrım */
}

/* Bildirim başlığı */
.auto-hide-alert .alert-title {
  font-weight: 600;             /* Kalın yazı - önem vurgusu */
  font-size: 1.2rem;            /* Büyük yazı boyutu - okunabilirlik */
  margin-bottom: 5px;           /* Alt boşluk - açıklamadan ayrım */
}

/* Bilgilendirme kutusu - yardımcı bilgi */
.info-box {
  background-color: #f8f9fc;    /* Çok açık mavi arka plan - nötr vurgu */
  border-radius: 8px;           /* Köşeleri yuvarlat - modern */
  padding: 15px;                /* İç boşluk - içerik marjı */
  border-left: 6px solid transparent; /* Şeffaf sol border - gradient için */
  box-shadow: 0 2px 6px rgba(0,0,0,0.05); /* Hafif gölge - derinlik */
  display: flex;                /* Flex container - ikon-metin hizalama */
  align-items: center;          /* Dikeyde ortala - düzgün görünüm */
  gap: 12px;                    /* Elementler arası boşluk - modern spacing */
  position: relative;           /* Pozisyonlandırma - pseudo element için */
}

/* Bilgilendirme kutusu gradient çizgisi */
.info-box::before {
  content: "";                  /* İçerik yok - pseudo element */
  position: absolute;           /* Mutlak pozisyon - konumlandırma */
  left: 0;                      /* Sol hizala - kenar çizgisi */
  top: 0;                       /* Üstten başla - tam yükseklik */
  bottom: 0;                    /* Alttan başla - tam yükseklik */
  width: 6px;                   /* 6px genişlik - ince çizgi */
  border-radius: 8px 0 0 8px;   /* Sol köşeleri yuvarlat - uyumluluk */
  background: linear-gradient(to bottom, #d32f2f, #6a1b9a, #1976d2); /* Renkli gradient - dikkat çekici */
}

/* Bilgilendirme kutusu hover durumu */
.info-box:hover {
  background-color: #eef1f7;    /* Hoverda daha koyu arka plan - etkileşim */
  cursor: default;              /* Varsayılan imleç - tıklanmaz */
}

/* Tooltip özelleştirmesi - yardımcı ipuçları */
.tooltip-inner {
  background-color: #2c3e50;    /* Koyu mavi-gri arka plan - profesyonel */
  font-size: 0.85rem;           /* Küçük yazı boyutu - kompakt */
  padding: 6px 10px;            /* İç boşluk - dengeli */
}

/* Tooltip ok rengi - üst konumlandırma */
.tooltip.bs-tooltip-top .tooltip-arrow::before {
  border-top-color: #2c3e50;    /* Üst tooltip ok rengi - uyumluluk */
}

/* ------------------------------------------------------------
   7. ANİMASYON VE GEÇİŞ EFEKTLERİ
   - Görsel geri bildirim için animasyonlar
   - Kullanıcı deneyimini zenginleştiren efektler
------------------------------------------------------------ */

/* Bildirim kutusu açılış animasyonu */
@keyframes fadeIn {
  from { 
    opacity: 0;                 /* Tamamen şeffaf - başlangıç */
    transform: translate(-50%, -60%); /* Yukarıda - kayma efekti */
  }
  to { 
    opacity: 1;                 /* Tamamen opak - bitiş */
    transform: translate(-50%, -50%); /* Merkez - son konum */
  }
}

/* Bildirim kutusu kapanış animasyonu */
@keyframes fadeOut {
  from { 
    opacity: 1;                 /* Tamamen opak - başlangıç */
    transform: translate(-50%, -50%); /* Merkez - mevcut konum */
  }
  to { 
    opacity: 0;                 /* Tamamen şeffaf - bitiş */
    transform: translate(-50%, -40%); /* Aşağı kayma - çıkış efekti */
  }
}

/* Kapanış animasyonu sınıfı */
.fade-out {
  animation: fadeOut 0.5s ease-in-out forwards; /* Kapanış animasyonu - smooth */
}

/* Progress bar konteyneri - zamanlayıcı göstergesi */
.progress-container {
  width: 100%;                  /* Tam genişlik - konteyner uyumu */
  height: 4px;                  /* İnce yükseklik - kompakt */
  background: #e9ecef;          /* Açık gri arka plan - nötr */
  border-radius: 2px;           /* Köşeleri yuvarlat - uyumluluk */
  margin-top: 10px;             /* Üst boşluk - içerikten ayrım */
  overflow: hidden;             /* Taşmayı gizle - temiz görünüm */
}

/* Progress bar doluluk göstergesi */
.progress-bar {
  height: 100%;                 /* Tam yükseklik - konteyner uyumu */
  width: 100%;                  /* Tam genişlik - başlangıç durumu */
  background: #28a745;          /* Yeşil renk - ilerleme */
  border-radius: 2px;           /* Köşeleri yuvarlat - uyumluluk */
  animation: progress 6s linear forwards; /* İlerleme animasyonu - zamanlı */
}

/* Hata durumunda progress bar rengi */
.error .progress-bar {
  background: #dc3545;          /* Kırmızı renk - hata durumu */
}

/* Progress bar animasyonu - zamanlayıcı */
@keyframes progress {
  from { 
    width: 100%;                /* Tam genişlik - başlangıç */
  }
  to { 
    width: 0%;                  /* Sıfır genişlik - bitiş */
  }
}

/* ------------------------------------------------------------
   8. DURUM GÖSTERGELERİ VE ÖZEL DURUMLAR
   - Pasif alanlar, devre dışı bölümler
   - Sistem durumuna göre görsel değişiklikler
------------------------------------------------------------ */

/* Pasif bölümler için başlık rengi - devre dışı durum */
.section-card.disabled-section .section-title {
  color: #6c757d !important;    /* Gri yazı rengi - pasif durum */
  background-color: #f8f9fa !important; /* Açık gri arka plan - devre dışı */
}

/* ------------------------------------------------------------
   9. RESPONSIVE TASARIM - MOBİL UYUM
   - Farklı ekran boyutları için uyarlanabilir stiller
   - Mobil cihazlarda optimize edilmiş deneyim
------------------------------------------------------------ */

/* Mobil cihazlar için medya sorgusu - tablet ve telefon */
@media (max-width: 768px) {
  /* Sekme başlıkları - mobil uyarlama */
  .nav-tabs {
    overflow-x: auto;           /* Yatay kaydırma - dar ekranlar için */
    white-space: nowrap;        /* Tek satır - kaydırma için */
    flex-wrap: nowrap;          /* Sarmayı engelle - yatay düzen */
    padding-bottom: 4px;        /* Alt padding - kaydırma çubuğu için */
  }
  
  /* Sekme öğeleri - mobil uyarlama */
  .nav-item {
    flex: 0 0 auto;             /* Esnek olmayan boyut - kaydırma için */
  }
  
  /* Sekme bağlantıları - mobil uyarlama */
  .nav-tabs .nav-link {
    padding: 10px 16px;         /* Daha küçük padding - kompakt */
    font-size: 0.9rem;          /* Daha küçük yazı - orantılı */
  }
  
  /* Sekme ikonları - mobil uyarlama */
  .nav-tabs .nav-link i {
    font-size: 0.9rem;          /* Daha küçük ikon - orantılı */
    margin-right: 6px;          /* Daha az boşluk - kompakt */
  }
  
  /* Form navigasyonu - mobil uyarlama */
  .form-navigation {
    flex-direction: column;     /* Dikey düzen - dar ekranlar */
    gap: 15px;                  /* Elemanlar arası boşluk - ayrım */
  }
  
  /* Navigasyon butonları - mobil uyarlama */
  .btn-navigation {
    width: 100%;                /* Tam genişlik - mobil uyum */
    min-width: auto;            /* Minimum genişlik kaldır - esneklik */
  }
  
  /* Önceki butonu - mobil sıralama */
  .btn-prev {
    order: 2;                   /* İkinci sıra - mantıksal akış */
  }
  
  /* Sonraki ve Kaydet butonları - mobil sıralama */
  .btn-next, .btn-save {
    order: 1;                   /* İlk sıra - öncelikli işlem */
  }
}




/* Telefon input'u için özel stiller */
input[name="telefon"].is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
    background-color: #fff8f8;
}

.phone-warning-text {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 4px;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    animation: pulseWarning 2s infinite;
}

@keyframes pulseWarning {
    0% { background-color: #f8d7da; }
    50% { background-color: #fdf2f3; }
    100% { background-color: #f8d7da; }
}

.form-text.text-danger {
    font-weight: 600;
}





.validation-error-alert {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    min-width: 400px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    border: none;
    border-radius: 12px;
    animation: fadeIn 0.5s ease-in-out;
}

.validation-error-alert .alert-icon {
    font-size: 2rem;
    margin-bottom: 10px;
}

.validation-error-alert .alert-title {
    font-weight: 600;
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.validation-error-alert .progress-container {
    width: 100%;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin-top: 10px;
    overflow: hidden;
}

.validation-error-alert .progress-bar {
    height: 100%;
    width: 100%;
    background: #dc3545;
    border-radius: 2px;
    animation: progress 6s linear forwards;
}

@keyframes progress {
    from { width: 100%; }
    to { width: 0%; }
}
</style>

</head>



<!-- GENEL SAYFA YAPISI -->
<body>  
  <!-- ANA KAPSAYICI - Tüm içeriği merkezde tutan ana div -->
  <div class="container mt-4">
    
    <!-- SAYFA BAŞLIĞI VE NAVİGASYON -->
    <!-- Üst kısımda sistem başlığı ve navigasyon butonları -->
    <header class="bg-primary text-white py-3 mb-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0"></h1>
        <nav>
          <!-- Ana Sayfa ve Personel Listesi navigasyon butonları -->
          <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">🏠 Dashboard</a>
          <a href="dashboard_Anasayfa.php" class="btn btn-outline-light btn-sm me-2">🏠 Ana Sayfa</a>
          <a href="personel_listesi.php" class="btn btn-outline-light btn-sm">👥 Personel Listesi</a>
        </nav>
      </div>
    </header>

    <!-- ANA FORM - Tüm personel bilgilerini toplayan form -->
    <form method="POST" enctype="multipart/form-data">
      <!-- CSRF Token - Güvenlik için oturum doğrulama -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <!-- ✅ OTOMATİK KAYBOLAN MESAJ ALANI -->
      <!-- Başarı veya hata mesajlarını gösteren dinamik bildirim alanı -->
      <?php if (isset($mesaj)): ?>
        <?php
        $alertType = str_starts_with($mesaj, 'success:') ? 'success' : 'error';
        $alertMessage = str_starts_with($mesaj, 'success:') ? substr($mesaj, 8) : $mesaj;
        $alertIcon = $alertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        $alertTitle = $alertType === 'success' ? 'Başarılı!' : 'Hata!';
        ?>
        <div class="alert auto-hide-alert <?= $alertType ?>" id="autoHideAlert" role="alert">
            <div class="alert-icon">
                <i class="fas <?= $alertIcon ?>"></i>
            </div>
            <div class="alert-title"><?= $alertTitle ?></div>
            <div class="alert-message"><?= $alertMessage ?></div>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>
      <?php endif; ?>
      

	  <!-- FORM DOĞRULAMA UYARISI -->
	  <div id="form-validation-alert" class="alert alert-warning d-none mb-3">
		  <i class="fas fa-exclamation-triangle me-2"></i>
		  <span id="validation-message"></span>
	  </div>
	  
      <!-- FORM İÇERİK BÖLÜMÜ - Tüm sekmeleri içeren ana alan -->
      <div class="form-body">

        <!-- SEKMELER - ANA NAVİGASYON -->
        <!-- Kullanıcının farklı bilgi kategorileri arasında geçiş yapmasını sağlar -->
        <div class="main-tabs-container">
          <ul class="nav nav-tabs" id="mainTabs" role="tablist">
      
            <!-- TEMEL BİLGİLER SEKME BUTONU - Kişisel ve dosyalama bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#temel-bilgiler" type="button">
                <i class="fas fa-id-card me-1"></i> Temel Bilgiler
              </button>
            </li>
            
            <!-- KİMLİK BİLGİLERİ SEKME BUTONU - Nüfus ve iletişim bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#kimlik-bilgileri" type="button">
                <i class="fas fa-user me-1"></i> Kimlik Bilgileri
              </button>
            </li>
            
            <!-- GÖREV KAYDI SEKME BUTONU - İstihdam ve görev bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#gorev-kaydi" type="button">
                <i class="fas fa-building me-1"></i> Görev Kaydı
              </button>
            </li>
            
            <!-- KADRO KAYDI SEKME BUTONU - Terfi ve derece bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#kadro-kaydi" type="button">
                <i class="fas fa-briefcase me-1"></i> Kadro Kaydı
              </button>
            </li>
            
            <!-- SÖZLEŞME BİLGİLERİ SEKME BUTONU - Sözleşmeli personel bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sozlesme-bilgileri" type="button">
                <i class="fas fa-file-contract me-1"></i> Sözleşme Bilgileri
              </button>
            </li>
            
            <!-- ÖĞRENİM BİLGİLERİ SEKME BUTONU - Eğitim ve mezuniyet bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ogrenim_bilgileri" type="button">
                <i class="fas fa-graduation-cap me-1"></i> Öğrenim Bilgileri
              </button>
            </li>
            
            <!-- GELDİĞİ KURUM SEKME BUTONU - Önceki iş yeri bilgileri -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#geldigi-kurum" type="button">
                <i class="fas fa-arrow-left me-1"></i> Geldiği Kurum
              </button>
            </li>
          </ul>

          <!-- SEKMELERİN İÇERİK ALANLARI -->
          <!-- Her sekme butonuna karşılık gelen içerik bölümleri -->
          <div class="tab-content" id="mainTabContent">

            <!-- 1. TEMEL BİLGİLER SEKMESİ -->
            <!-- Personelin temel kimlik ve dosyalama bilgileri -->
            <div class="tab-pane show active" id="temel-bilgiler" role="tabpanel">
              
              <!-- KİŞİSEL BİLGİLER KARTI -->
              <!-- Ad, soyad ve TC kimlik numarası gibi temel bilgiler -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-user-circle"></i>Kişisel Bilgiler</div>
                <div class="row compact-row">
					<div class="col-md-6">
					  <div class="form-group">
						<label for="adSoyadiInput" class="form-label required-field">Adı Soyadı</label>
						<input type="text" id="adSoyadiInput" name="ad_soyadi" class="form-control" required value="<?= getFormData('ad_soyadi') ?>" placeholder="Adını ve Soyadını giriniz">
					  </div>
					</div>
					<div class="col-md-6">
					  <div class="form-group">
						<label for="tcNoInput" class="form-label required-field">TC Kimlik No</label>
							<input type="text" id="tcNoInput" name="tc_no" class="form-control" required maxlength="11" pattern="\d{11}" placeholder="11 haneli TC Kimlik No giriniz">  
						<div id="tcNoMessage" class="tc-validation-message" style="display: none;"></div>
					  </div>
					</div>
                </div>
              </div>

              <!-- SİCİL BİLGİLERİ KARTI -->
              <!-- Emekli ve kurum sicil numaraları -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-file-signature"></i>Sicil Bilgileri</div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Emekli Sicil No</label>
                      <input type="text" name="emekli_sicil_no" class="form-control" value="<?= getFormData('emekli_sicil_no') ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Kurum Sicil No</label>
                      <input type="text" name="kurum_sicil_no" class="form-control" value="<?= getFormData('kurum_sicil_no') ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- ARŞİV BİLGİLERİ ve FOTOĞRAF YÜKLEME -->
              <!-- Dosyalama bilgileri ve personel fotoğrafı -->
              <div class="row compact-row">
                <div class="col-md-6">
                  <div class="section-card">
                    <div class="section-title"><i class="fas fa-archive"></i>Arşiv Bilgileri</div>
                    <div class="row compact-row">
                      <div class="col-12">
                        <div class="form-group">
                          <label class="form-label">Arşiv No</label>
                          <input type="text" name="arsiv_no" class="form-control" value="<?= getFormData('arsiv_no') ?>">
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-group">
                          <label class="form-label">Raf No</label>
                          <input type="text" name="raf_no" class="form-control" value="<?= getFormData('raf_no') ?>">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="section-card photo-equal">
                    <div class="section-title"><i class="fas fa-camera"></i>Fotoğraf Yükleme</div>
                    <div class="row compact-row">
                      <div class="col-12">
                        <div class="form-group">
                          <label class="form-label required-field">Personel Fotoğrafı</label>
                          <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.gif" id="photoUpload">
                          <small class="file-upload-info text-muted">JPG, JPEG, PNG, GIF - Maks: 5MB</small>
                          <img id="photoPreview" class="photo-preview d-block mt-2 rounded" style="max-width: 100%; height: auto; display: none;">
                          <div id="photoError" class="text-danger mt-2" style="display: none;"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Navigasyon Butonları - TEMEL BİLGİLER -->
              <!-- Sekmeler arasında geçiş yapmayı sağlayan butonlar -->
              <div class="form-navigation">
                <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('temel-bilgiler')">
                  <i class="fas fa-arrow-left me-2"></i>Önceki
                </button>
                <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('temel-bilgiler')">
                  Sonraki <i class="fas fa-arrow-right ms-2"></i>
                </button>
              </div>
              <!-- Navigasyon Butonları - TEMEL BİLGİLER SONU -->        
            </div>
            <!-- TEMEL BİLGİLER SEKMESİ SONU -->



            <!-- 2. KİMLİK BİLGİLERİ SEKMESİ -->
            <!-- Personelin nüfus ve iletişim bilgileri -->
            <div class="tab-pane" id="kimlik-bilgileri" role="tabpanel">
              
              <!-- TEMEL KİMLİK BİLGİLERİ KARTI -->
              <!-- Baba adı, doğum bilgileri ve cinsiyet -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-user"></i>Kimlik Bilgileri</div>
                <div class="row compact-row">
                  <!-- BABA ADI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Baba Adı</label>
                      <input type="text" name="baba_adi" class="form-control" value="<?= getFormData('baba_adi') ?>">
                    </div>
                  </div>
                  
                  <!-- DOĞUM TARİHİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Doğum Tarihi</label>
                      <input type="text" name="dogum_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy"
                           value="<?= getFormData('dogum_tarihi') ?>">
                    </div>
                  </div>
                  
                  <!-- DOĞUM YERİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Doğum Yeri</label>
                      <input type="text" name="dogum_yeri" class="form-control" value="<?= getFormData('dogum_yeri') ?>">
                    </div>
                  </div>
                  
                  <!-- CİNSİYET -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Cinsiyet</label>
                      <select name="cinsiyeti" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="Erkek" <?= getFormData('cinsiyeti') == 'Erkek' ? 'selected' : '' ?>>Erkek</option>
                        <option value="Kadın" <?= getFormData('cinsiyeti') == 'Kadın' ? 'selected' : '' ?>>Kadın</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- NÜFUS KAYIT BİLGİLERİ KARTI -->
              <!-- Medeni durum ve kan grubu gibi detay bilgiler -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-address-card"></i>Detay Bilgiler</div>
                
                <div class="row compact-row">
                  
                  <!-- MEDENİ DURUM -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Medeni Durumu</label>
                      <select name="medeni_durum" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="Bekar" <?= getFormData('medeni_durum') == 'Bekar' ? 'selected' : '' ?>>Bekâr</option>
                        <option value="Evli" <?= getFormData('medeni_durum') == 'Evli' ? 'selected' : '' ?>>Evli</option>
                        <option value="Boşanmış" <?= getFormData('medeni_durum') == 'Boşanmış' ? 'selected' : '' ?>>Boşanmış</option>
                        <option value="Dul" <?= getFormData('medeni_durum') == 'Dul' ? 'selected' : '' ?>>Dul</option>
                      </select>
                    </div>
                  </div>
                  
                  <!-- KAN GRUBU -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Kan Grubu</label>
                      <select name="kan_grubu" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="A Rh+" <?= getFormData('kan_grubu') == 'A Rh+' ? 'selected' : '' ?>>A Rh+</option>
                        <option value="A Rh-" <?= getFormData('kan_grubu') == 'A Rh-' ? 'selected' : '' ?>>A Rh-</option>
                        <option value="B Rh+" <?= getFormData('kan_grubu') == 'B Rh+' ? 'selected' : '' ?>>B Rh+</option>
                        <option value="B Rh-" <?= getFormData('kan_grubu') == 'B Rh-' ? 'selected' : '' ?>>B Rh-</option>
                        <option value="AB Rh+" <?= getFormData('kan_grubu') == 'AB Rh+' ? 'selected' : '' ?>>AB Rh+</option>
                        <option value="AB Rh-" <?= getFormData('kan_grubu') == 'AB Rh-' ? 'selected' : '' ?>>AB Rh-</option>
                        <option value="0 Rh+" <?= getFormData('kan_grubu') == '0 Rh+' ? 'selected' : '' ?>>0 Rh+</option>
                        <option value="0 Rh-" <?= getFormData('kan_grubu') == '0 Rh-' ? 'selected' : '' ?>>0 Rh-</option>
                      </select>
                    </div>
                  </div>
                </div>
                
              </div>

              <!-- İLETİŞİM BİLGİLERİ KARTI -->
              <!-- Telefon, e-posta ve adres bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-phone"></i>İletişim Bilgileri</div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Telefon No</label>
                      <input type="tel" name="telefon" class="form-control" placeholder="(5xx) xxx xx xx" value="<?= getFormData('telefon') ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">E-posta Adresi</label>
                      <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= getFormData('email') ?>">
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group">
                      <label class="form-label">Adresi</label>
                      <textarea name="ikametgah_adresi" class="form-control" rows="2" placeholder="Merkez Mah. Siyah Gül Cad. Sk. No: 3/2 Beykoz / İSTANBUL"><?= getFormData('ikametgah_adresi') ?></textarea>
                    </div>
                  </div>
                </div>
              </div>
                            
              <!-- Navigasyon Butonları - KİMLİK BİLGİLERİ -->
              <div class="form-navigation">
                <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('kimlik-bilgileri')">
                  <i class="fas fa-arrow-left me-2"></i>Önceki
                </button>
                <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('kimlik-bilgileri')">
                  Sonraki <i class="fas fa-arrow-right ms-2"></i>
                </button>
              </div>
              <!-- Navigasyon Butonları - KİMLİK BİLGİLERİ SONU -->

            </div>
            <!-- KİMLİK BİLGİLERİ SEKMESİ SONU-->


            <!-- 3. GÖREV KAYDI SEKMESİ -->
            <!-- Personelin görev yeri, istihdam ve görev bilgileri -->
            <div class="tab-pane" id="gorev-kaydi" role="tabpanel">

              <!-- KURUM/OKUL BİLGİLERİ KARTI -->
              <!-- Görev yapılan kurumun iletişim ve tanımlama bilgileri -->
              <div class="section-card kurum-okul">
                <div class="section-title"><i class="fas fa-school"></i> Kurum/Okul Bilgileri</div>
                <div class="row compact-row">

                  <!-- İL SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">İl</label>
                      <select name="gorev_il_id" id="gorev_il_id" class="form-select dynamic-select">
                        <option value="">İl Seçiniz</option>
                        <?php foreach ($iller as $il): ?>
                          <option value="<?= $il['id'] ?>" <?= getFormData('gorev_il_id') == $il['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($il['il_adi']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- İLÇE SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">İlçe</label>
                      <select name="gorev_ilce_id" id="gorev_ilce_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce il seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- GÖREV YERİ SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">Görev Yeri</label>
                      <select name="gorev_okul_id" id="gorev_okul_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce ilçe seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- KURUM KODU -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Kurum Kodu</label>
                      <input type="text" name="gorev_kurum_kodu" id="gorev_kurum_kodu" class="form-control" readonly value="<?= getFormData('gorev_kurum_kodu') ?>">
                    </div>
                  </div>

                  <!-- OKUL TÜRÜ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Okul Türü</label>
                      <input type="text" name="gorev_okul_tur" id="gorev_okul_tur" class="form-control" readonly value="<?= getFormData('gorev_okul_tur') ?>">
                    </div>
                  </div>

                  <!-- KAPALI KURUM FİLTRELEME -->
                  <div class="col-md-4">
                    <div class="form-group mt-4">
                      <div class="form-check form-switch">
                        <input type="checkbox" name="gorev_kapali_kurum" id="gorev_kapali_kurum" value="1" class="form-check-input"
                          <?= getFormData('gorev_kapali_kurum') ? 'checked' : '' ?>>
                        <label for="gorev_kapali_kurum" class="form-check-label">
                          <strong>Kapalı Kurumları Dahil Et</strong>
                          <i class="fas fa-info-circle info-icon ms-1" data-bs-toggle="tooltip" title="Kapalı kurumları listelemek için bu kutuyu işaretleyin"></i>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- GÖREVE BAŞLAMA BİLGİLERİ KARTI -->
              <!-- Memuriyet ve kurum başlama tarihleri -->
              <div class="section-card gorev-baslama">
                <div class="section-title"><i class="fas fa-calendar-alt"></i> Göreve Başlama Bilgileri</div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Memuriyete Başlama Tarihi</label>
                      <input type="text" name="memuriyete_baslama_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy"
                        value="<?= getFormData('memuriyete_baslama_tarihi') ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Kurumda Başlama Tarihi</label>
                      <input type="text" name="kurum_baslama_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy"
                        value="<?= getFormData('kurum_baslama_tarihi') ?>">
                    </div>
                  </div>
                  <!-- GÖREV AÇIKLAMASI -->
                  <div class="col-12">
                    <div class="form-group">
                      <label class="form-label">Görev Açıklama</label>
                      <textarea name="gorev_aciklama" class="form-control" rows="3" placeholder="Görev ile ilgili detaylı açıklama"><?= getFormData('gorev_aciklama') ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- İSTİHDAM BİLGİLERİ KARTI -->
              <!-- Hizmet sınıfı, istihdam tipi ve ünvan bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-archive"></i>İstihdam Bilgileri</div>
                <div class="row compact-row">

                  <!-- HİZMET SINIFI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Hizmet Sınıfı</label>
                      <select name="hizmet_sinifi" class="form-select">
                        <option value="">Seçiniz</option>
                        <!-- AJAX ile doldurulacak -->
                      </select>
                    </div>
                  </div>

                  <!-- İSTİHDAM TİPİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">İstihdam Tipi</label>
                      <select name="istihdam_tipi" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="Kadrolu" <?= getFormData('istihdam_tipi') == 'Kadrolu' ? 'selected' : '' ?>>Kadrolu</option>
                        <option value="Sözleşmeli Personel" <?= getFormData('istihdam_tipi') == 'Sözleşmeli Personel' ? 'selected' : '' ?>>Sözleşmeli Personel</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Kurumlarası Görevlendirme(Bakanlık)' ? 'selected' : '' ?>>Kurumlarası Görevlendirme(Bakanlık)</option>
                        <option value="İşçi" <?= getFormData('istihdam_tipi') == 'İşçi' ? 'selected' : '' ?>>İşçi</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Geçici Personel' ? 'selected' : '' ?>>Geçici Personel</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'İşçi (696 K.H.K.)' ? 'selected' : '' ?>>İşçi (696 K.H.K.)</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Kurumlarası Görevlendirme(Valilik)' ? 'selected' : '' ?>>Kurumlarası Görevlendirme(Valilik)</option>
                        <option value="Geçici Personel" <?= getFormData('istihdam_tipi') == 'Görevlendirme' ? 'selected' : '' ?>>Görevlendirme</option>
                      </select>

                      <!-- UYARI KUTUSU -->
                      <div id="istihdam-uyari" class="alert alert-warning d-none mt-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                          Yukarıda pasif olan bilgileri <strong>"Sözleşme Bilgileri"</strong> sekmesinden tamamlayınız.
                      </div>
                    </div>
                  </div>

                  <!-- KADRO ÜNVANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Kadro Ünvanı</label>
                      <select name="kadro_unvani" class="form-select">
                        <option value="">Önce hizmet sınıfı seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- GÖREV ÜNVANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Görev Ünvanı</label>
                      <select name="gorev_unvani" class="form-select">
                        <option value="">Önce hizmet sınıfı seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- ÖĞRETMENLİK KARİYER BASAMAĞI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Öğretmenlik Kariyer Basamağı</label>
                      <select name="kariyer_basamagi" class="form-select" disabled>
                        <option value="">Seçiniz</option>
                        <option value="Uzman Öğretmen">Uzman Öğretmen</option>
                        <option value="Başöğretmen">Başöğretmen</option>
                      </select>
                    </div>
                  </div>

                  <!-- DURUMU -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Durumu</label>
                      <select name="durum" class="form-select">
                        <option value="">Seçiniz</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- GÖREV DETAYLARI KARTI -->
              <!-- Atama alanı ve yer değiştirme çeşidi -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-tasks"></i>Görev Detayları</div>
                <div class="row compact-row">

                  <!-- BAKANLIK ATAMA ALANI -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Bakanlık Atama Alanı</label>
                      <select name="atama_alani" class="form-select">
                        <option value="">Seçiniz</option>
                      </select>
                    </div>
                  </div>

                  <!-- ATAMA/YERDEĞİŞTİRME ÇEŞİDİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label required-field">Atama/Yerdeğiştirme Çeşidi</label>
                      <select name="yer_degistirme_cesidi" class="form-select">
                        <option value="">Seçiniz</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Navigasyon Butonları - GÖREV KAYDI -->
              <div class="form-navigation">
                <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('gorev-kaydi')">
                  <i class="fas fa-arrow-left me-2"></i>Önceki
                </button>
                <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('gorev-kaydi')">
                  Sonraki <i class="fas fa-arrow-right ms-2"></i>
                </button>
              </div>
              <!-- Navigasyon Butonları - GÖREV KAYDI SONU -->
            </div>
            <!-- GÖREV KAYDI SEKMESİ SONU -->



<!-- 4. KADRO KAYDI SEKMESİ -->
<div class="tab-pane" id="kadro-kaydi" role="tabpanel">

  <!-- KADRO KAYDI UYARISI -->
  <div id="kadro-uyari" class="alert alert-warning d-flex align-items-center gap-3 mb-3 d-none" role="alert">
    <i class="fas fa-exclamation-triangle fs-5 text-warning"></i>
    <div>
      <strong id="uyari-baslik">İşçilerin Kadro Kaydı girilemez.</strong>
      <div><span id="uyari-aciklama">Sadece sözleşme bilgileri düzenlenebilir.</span></div>
    </div>
  </div>

  <!-- KADRO FORMU -->
  <div id="kadro-form">

    <!-- Personel Kadro Bilgileri -->
    <div class="section-card">
      <div class="section-title"><i class="fas fa-tasks"></i> Personel Kadro Bilgileri</div>
      <div class="row compact-row">
        <!-- Terfi Tarihi -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">Terfi Tarihi</label>
          <input type="text" name="terfi_tarihi" class="form-control datepicker" 
                 placeholder="gg.aa.yyyy" value="<?= getFormData('terfi_tarihi') ?>">
        </div>

        <!-- Terfi Nedeni -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">Terfi Nedeni</label>
          <select name="terfi_nedeni" class="form-select">
            <option value="">Seçiniz</option>
            <!-- AJAX ile doldurulacak -->
          </select>
        </div>
      </div>
    </div>

    <!-- Terfi Bilgileri -->
    <div class="section-card">
      <div class="section-title"><i class="fas fa-tasks"></i> Terfi Bilgileri</div>
      <div class="row compact-row">
        <!-- Kadro Derecesi -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">Kadro Derecesi</label>
          <input type="text" name="kadro_derecesi" class="form-control" placeholder="Örn: 1/1">
        </div>

        <!-- KHA Ek Gösterge -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">KHA Ek Gösterge</label>
          <input type="text" name="kha_ek_gosterge" class="form-control" placeholder="Örn: 2200">
        </div>
      </div>

      <div class="row compact-row">
        <!-- Aylık Derece -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">Aylık Derece</label>
          <input type="text" name="aylik_derece" class="form-control" placeholder="Örn: 3">
        </div>

        <!-- Aylık Kademe -->
        <div class="col-md-6 mb-3">
          <label class="form-label required-field">Aylık Kademe</label>
          <input type="text" name="aylik_kademe" class="form-control" placeholder="Örn: 1">
        </div>
      </div>

      <!-- Öğrenim Durumu Uyarısı -->
      <div id="terfi-uyari" class="alert alert-info d-none mt-3"></div>
    </div>
  </div>

  <!-- Navigasyon Butonları -->
  <div class="form-navigation">
    <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('kadro-kaydi')">
      <i class="fas fa-arrow-left me-2"></i> Önceki
    </button>
    <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('kadro-kaydi')">
      Sonraki <i class="fas fa-arrow-right ms-2"></i>
    </button>
  </div>
</div>
<!-- KADRO KAYDI SEKMESİ SONU -->




            <!-- 5. SÖZLEŞME BİLGİLERİ SEKMESİ -->
            <!-- Sözleşmeli personel bilgileri -->
            <div class="tab-pane" id="sozlesme-bilgileri" role="tabpanel">
			  
              <!-- KURUM/OKUL BİLGİLERİ KARTI -->
              <!-- Sözleşme yapılan kurum bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-building"></i> Kurum/Okul Bilgileri</div>
                <div class="row compact-row">

                  <!-- İL SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">İl</label>
                      <select name="sozlesme_il_id" id="sozlesme_il_id" class="form-select dynamic-select">
                        <option value="">İl Seçiniz</option>
                        <?php foreach ($iller as $il): ?>
                          <option value="<?= $il['id'] ?>" <?= getFormData('sozlesme_il_id') == $il['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($il['il_adi']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- İLÇE SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">İlçe</label>
                      <select name="sozlesme_ilce_id" id="sozlesme_ilce_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce il seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- GÖREV YERİ SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Görev Yeri</label>
                      <select name="sozlesme_okul_id" id="sozlesme_okul_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce ilçe seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- KURUM KODU -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Kurum Kodu</label>
                      <input type="text" name="sozlesme_kurum_kodu" id="sozlesme_kurum_kodu" class="form-control" readonly value="<?= getFormData('sozlesme_kurum_kodu') ?>">
                    </div>
                  </div>

                  <!-- OKUL TÜRÜ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Okul Türü</label>
                      <input type="text" name="sozlesme_okul_tur" id="sozlesme_okul_tur" class="form-control" readonly value="<?= getFormData('sozlesme_okul_tur') ?>">
                    </div>
                  </div>

                  <!-- KAPALI KURUM FİLTRELEME -->
                  <div class="col-md-4">
                    <div class="form-group mt-4">
                      <div class="form-check form-switch">
                        <input type="checkbox" name="sozlesme_kapali_kurum" id="sozlesme_kapali_kurum" value="1" class="form-check-input"
                               <?= getFormData('sozlesme_kapali_kurum') ? 'checked' : '' ?>>
                        <label for="sozlesme_kapali_kurum" class="form-check-label">
                          <strong>Kapalı Kurumları Dahil Et</strong>
                          <i class="fas fa-info-circle info-icon ms-1" data-bs-toggle="tooltip" title="Kapalı kurumları listelemek için bu kutuyu işaretleyin"></i>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- SÖZLEŞME BİLGİLERİ KARTI -->
              <!-- Sözleşme tarihleri, süresi ve türü -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-file-contract"></i> Sözleşme Bilgileri</div>

                <div class="row compact-row">
                  <!-- SÖZLEŞME BAŞLANGIÇ TARİHİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Sözleşme Başlangıç Tarihi</label>
                      <input type="text" name="sozlesme_baslangic" class="form-control datepicker" placeholder="gg.aa.yyyy"
                        value="<?= getFormData('sozlesme_baslangic') ?>">
                    </div>
                  </div>

                  <!-- SÖZLEŞME BİTİŞ TARİHİ -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Sözleşme Bitiş Tarihi</label>
                      <input type="text" name="sozlesme_bitis" class="form-control datepicker" placeholder="gg.aa.yyyy" 
                        value="<?= getFormData('sozlesme_bitis') ?>">
                    </div>
                  </div>

                  <!-- SÖZLEŞME SÜRESİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Sözleşme Süresi (Ay)</label>
                      <input type="number" name="sozlesme_suresi" class="form-control" placeholder="Örn: 12" value="<?= getFormData('sozlesme_suresi') ?>">
                    </div>
                  </div>

                  <!-- SÖZLEŞME TÜRÜ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Sözleşme Türü</label>
                      <select name="sozlesme_turu" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="Tam Zamanlı" <?= getFormData('sozlesme_turu') == 'Tam Zamanlı' ? 'selected' : '' ?>>Tam Zamanlı</option>
                        <option value="Yarı Zamanlı" <?= getFormData('sozlesme_turu') == 'Yarı Zamanlı' ? 'selected' : '' ?>>Yarı Zamanlı</option>
                        <option value="Geçici" <?= getFormData('sozlesme_turu') == 'Geçici' ? 'selected' : '' ?>>Geçici</option>
                        <option value="Belirsiz Süreli" <?= getFormData('sozlesme_turu') == 'Belirsiz Süreli' ? 'selected' : '' ?>>Belirsiz Süreli</option>
                      </select>
                    </div>
                  </div>

                  <!-- SÖZLEŞME AÇIKLAMA -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Sözleşme Açıklama</label>
                      <input type="text" name="sozlesme_aciklama" class="form-control" placeholder="İsteğe bağlı açıklama" value="<?= getFormData('sozlesme_aciklama') ?>">
                    </div>
                  </div>
                </div>
              </div>
        
              <!-- Navigasyon Butonları - SÖZLEŞME BİLGİLERİ -->
              <div class="form-navigation">
                <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('sozlesme-bilgileri')">
                  <i class="fas fa-arrow-left me-2"></i>Önceki
                </button>
                <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('sozlesme-bilgileri')">
                  Sonraki <i class="fas fa-arrow-right ms-2"></i>
                </button>
              </div>
              <!-- Navigasyon Butonları - SÖZLEŞME BİLGİLERİ SONU -->
            </div>
            <!-- SÖZLEŞME BİLGİLERİ SEKMESİ SONU -->



<!-- 6. ÖĞRENİM BİLGİLERİ SEKMESİ -->
<div class="tab-pane" id="ogrenim_bilgileri" role="tabpanel">

  <!-- ÖĞRENİM DURUMU KARTI -->
  <div class="section-card">
    <div class="section-title"><i class="fas fa-graduation-cap"></i> Öğrenim Durumu</div>
    <div class="row compact-row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label required-field">Öğrenim Durumu</label>
          <select name="ogrenim_durumu" id="ogrenim_durumu" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach ($ogrenimListesi as $ogrenim): ?>
              <option value="<?= $ogrenim['id'] ?>" <?= getFormData('ogrenim_durumu') == $ogrenim['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ogrenim['ogrenim_adi']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label required-field">Mezuniyet Tarihi</label>
          <input type="text" name="mezuniyet_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy" 
                 value="<?= getFormData('mezuniyet_tarihi') ?>">
        </div>
      </div>
    </div>
  </div>

<!-- ÜNİVERSİTE BİLGİLERİ KARTI -->
<div class="section-card" id="universite_bilgileri">
  <div class="section-title"><i class="fas fa-university"></i> Üniversite Bilgileri</div>
  <div class="row compact-row">
    <!-- Üniversite -->
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-label">Üniversite</label>
<select name="universite_id" id="universite_id" class="form-select">
  <option value="">Üniversite Seçiniz</option>
  <?php foreach ($universiteler as $uni): ?>
    <option value="<?= $uni['universite_id'] ?>"
      <?= getFormData('universite_id') == $uni['universite_id'] ? 'selected' : '' ?>>
      <?= htmlspecialchars($uni['universite_adi']) ?>
    </option>
  <?php endforeach; ?>
</select>
      </div>
    </div>

    <!-- Fakülte -->
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-label">Fakülte/Enstitü/Yüksekokul</label>
<select name="fakulte_yuksekokul_id" id="fakulte_yuksekokul_id" class="form-select"
        data-selected="<?= getFormData('fakulte_yuksekokul_id') ?>">
  <option value="">Önce Üniversite Seçiniz</option>
</select>
      </div>
    </div>

    <!-- Anabilim Dalı -->
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-label">Anabilim Dalı/Bölüm</label>
        <select name="anabilim_dali_id" id="anabilim_id" class="form-select"
                data-selected="<?= getFormData('anabilim_dali_id') ?>">
          <option value="">Önce Fakülte Seçiniz</option>
        </select>
      </div>
    </div>

    <!-- Program -->
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-label">Bilim Dalı/Program/Bölüm</label>
        <select name="program_id" id="program_id" class="form-select"
                data-selected="<?= getFormData('program_id') ?>">
          <option value="">Önce Anabilim Dalı Seçiniz</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- OKUL BİLGİLERİ KARTI -->
<div class="section-card" id="okul_bilgileri">
  <div class="section-title"><i class="fas fa-school"></i> Okul Bilgileri</div>
  <div class="row compact-row">
    <div class="col-md-12">
      <div class="form-group">
        <label class="form-label">Mezun Olduğu Okul</label>
        <input type="text" name="mezun_okul_id" class="form-control" placeholder="Okul adı" 
               value="<?= getFormData('mezun_okul_id') ?>">
      </div>
    </div>
  </div>
</div>

  <!-- MEZUNİYET BELGESİ BİLGİLERİ KARTI -->
  <div class="section-card">
    <div class="section-title"><i class="fas fa-file-certificate"></i> Mezuniyet Belgesi Bilgileri</div>
    <div class="row compact-row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label required-field">Belge Tarihi</label>
          <input type="text" name="belge_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy" 
                 value="<?= getFormData('belge_tarihi') ?>">
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label required-field">Belge No</label>
          <input type="text" name="belge_no" class="form-control" placeholder="Belge numarası" 
                 value="<?= getFormData('belge_no') ?>">
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label required-field">Belge Cinsi</label>
          <select name="belge_cinsi" class="form-select">
            <option value="">Seçiniz</option>
            <option value="Diploma" <?= getFormData('belge_cinsi') == 'Diploma' ? 'selected' : '' ?>>Diploma</option>
            <option value="Geçici Mezuniyet Belgesi" <?= getFormData('belge_cinsi') == 'Çıkış/Geçici Mezuniyet Belgesi' ? 'selected' : '' ?>>Çıkış/Geçici Mezuniyet Belgesi</option>
            <option value="Tasdikname" <?= getFormData('belge_cinsi') == 'Tasdikname' ? 'selected' : '' ?>>Tasdikname</option>
          </select>
        </div>
      </div>                  
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-label">Açıklama</label>
          <input type="text" name="belge_aciklama" class="form-control" placeholder="Varsa belgeye dair açıklama" 
                 value="<?= getFormData('belge_aciklama') ?>">
        </div>
      </div>                  
    </div>
  </div>
        
  <!-- Navigasyon Butonları -->
  <div class="form-navigation">
    <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('ogrenim_bilgileri')">
      <i class="fas fa-arrow-left me-2"></i>Önceki
    </button>
    <button type="button" class="btn btn-next btn-navigation" onclick="nextTab('ogrenim_bilgileri')">
      Sonraki <i class="fas fa-arrow-right ms-2"></i>
    </button>
  </div>
</div>
<!-- ÖĞRENİM BİLGİLERİ SEKMESİ SONU -->



            <!-- 7. GELDİĞİ KURUM SEKMESİ -->
            <!-- Personelin önceki iş yeri bilgileri -->
            <div class="tab-pane" id="geldigi-kurum" role="tabpanel">

              <!-- GELDİĞİ KURUM BİLGİLERİ KARTI -->
              <!-- Önceki kurumun iletişim ve tanımlama bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-building"></i> Geldiği Kurum Bilgileri</div>
                <div class="row compact-row">

                  <!-- İL SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">İl</label>
                      <select name="geldigi_il_id" id="geldigi_il_id" class="form-select dynamic-select">
                        <option value="">İl Seçiniz</option>
                        <?php foreach ($iller as $il): ?>
                          <option value="<?= $il['id'] ?>" <?= getFormData('geldigi_il_id') == $il['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($il['il_adi']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- İLÇE SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">İlçe</label>
                      <select name="geldigi_ilce_id" id="geldigi_ilce_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce il seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- GÖREV YERİ SEÇİMİ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label required-field">Görev Yeri</label>
                      <select name="geldigi_okul_id" id="geldigi_okul_id" class="form-select dynamic-select" disabled>
                        <option value="">Önce ilçe seçin</option>
                      </select>
                    </div>
                  </div>

                  <!-- KURUM KODU -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Kurum Kodu</label>
                      <input type="text" name="geldigi_kurum_kodu" id="geldigi_kurum_kodu" class="form-control" readonly value="<?= getFormData('geldigi_kurum_kodu') ?>">
                    </div>
                  </div>

                  <!-- OKUL TÜRÜ -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Okul Türü</label>
                      <input type="text" name="geldigi_okul_tur" id="geldigi_okul_tur" class="form-control" readonly value="<?= getFormData('geldigi_okul_tur') ?>">
                    </div>
                  </div>

                  <!-- KAPALI KURUM FİLTRELEME -->
                  <div class="col-md-4">
                    <div class="form-group mt-4">
                      <div class="form-check form-switch">
                        <input type="checkbox" name="geldigi_kapali_kurum" id="geldigi_kapali_kurum" value="1" class="form-check-input"
                               <?= getFormData('geldigi_kapali_kurum') ? 'checked' : '' ?>>
                        <label for="geldigi_kapali_kurum" class="form-check-label">
                          <strong>Kapalı Kurumları Dahil Et</strong>
                          <i class="fas fa-info-circle info-icon ms-1" data-bs-toggle="tooltip" title="Kapalı kurumları listelemek için bu kutuyu işaretleyin"></i>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ÖNCEKİ KURUM DETAYLARI KARTI -->
              <!-- Ayrılma tarihi, nedeni ve önceki görev bilgileri -->
              <div class="section-card">
                <div class="section-title"><i class="fas fa-history"></i>Önceki Kurum Detayları</div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Önceki Görevi</label>
                      <select name="geldigi_gorev_unvani" id="geldigi_gorev_unvani" class="form-select">
                        <option value="">Yükleniyor...</option>
                        <!-- JavaScript ile doldurulacak -->
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Ayrılma Tarihi</label>
                      <input type="text" name="geldigi_ayrilma_tarihi" class="form-control datepicker" placeholder="gg.aa.yyyy"
                             value="<?= getFormData('geldigi_ayrilma_tarihi') ?>">
                    </div>
                  </div>
                </div>
                <div class="row compact-row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Ayrılma Nedeni</label>
                      <select name="geldigi_ayrilma_nedeni" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="İstifa" <?= getFormData('geldigi_ayrilma_nedeni') == 'İstifa' ? 'selected' : '' ?>>İstifa</option>
                        <option value="Emeklilik" <?= getFormData('geldigi_ayrilma_nedeni') == 'Emeklilik' ? 'selected' : '' ?>>Emeklilik</option>
                        <option value="Nakil" <?= getFormData('geldigi_ayrilma_nedeni') == 'Nakil' ? 'selected' : '' ?>>Nakil</option>
                        <option value="İşten Çıkarma" <?= getFormData('geldigi_ayrilma_nedeni') == 'İşten Çıkarma' ? 'selected' : '' ?>>İşten Çıkarma</option>
                        <option value="Sözleşme Bitimi" <?= getFormData('geldigi_ayrilma_nedeni') == 'Sözleşme Bitimi' ? 'selected' : '' ?>>Sözleşme Bitimi</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Navigasyon Butonları - GELDİĞİ KURUM -->
              <!-- Son sekmede Kaydet butonu görünür -->
              <div class="form-navigation">
                <button type="button" class="btn btn-prev btn-navigation" onclick="prevTab('geldigi-kurum')">
                  <i class="fas fa-arrow-left me-2"></i>Önceki
                </button>
                <button type="submit" class="btn btn-save btn-navigation">
                  <i class="fas fa-save me-2"></i>Personeli Kaydet
                </button>
              </div>
              <!-- Navigasyon Butonları - GELDİĞİ KURUM SONU -->
            </div>
            <!-- GELDİĞİ KURUM SEKMESİ SONU -->

          </div>
          <!-- SEKMELERİN İÇERİK ALANLARI SONU -->

        </div>
        <!-- SEKMELER - ANA NAVİGASYON SONU -->

      </div>
      <!-- FORM İÇERİK BÖLÜMÜ SONU -->

    </form>
    <!-- ANA FORM SONU -->
    
  </div>
  <!-- ANA KAPSAYICI SONU -->

</body>
<!-- GENEL SAYFA YAPISI SONU -->


<!-- jQuery ekle (CDN üzerinden) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Bootstrap JS (gerekli) -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<script>
// ✅ AJAX İstek Yöneticisi - CSRF Token Desteği
function makeAjaxRequest(url, params = {}) {
    // URL'ye CSRF token ekle
    const separator = url.includes('?') ? '&' : '?';
    const urlWithToken = url + separator + 'csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token'] ?>');
    
    return fetch(urlWithToken, {
        headers: {
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    });
}

// ✅ Otomatik Kaybolan Mesaj Yönetimi
document.addEventListener('DOMContentLoaded', function() {
    const autoHideAlert = document.getElementById('autoHideAlert');
    
    if (autoHideAlert) {
        // 13 saniye sonra mesajı kaldır
        setTimeout(() => {
            autoHideAlert.classList.add('fade-out');
            setTimeout(() => {
                autoHideAlert.remove();
            }, 500);
        }, 13000);
        
        // Tıklayınca da kapatılabilir
        autoHideAlert.addEventListener('click', function() {
            this.classList.add('fade-out');
            setTimeout(() => {
                this.remove();
            }, 500);
        });
    }
});

// ✅ TELEFON NUMARASI REAL-TIME FORMATLAMA VE KALICI SIFIR UYARISI
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="telefon"]');
    
    if (phoneInput) {
        phoneInput.placeholder = "(555) 123 45 67 - Başında 0 kullanmayın!";
        
        const warningElement = document.createElement('small');
        warningElement.className = 'form-text text-danger mt-1 fw-bold';
        warningElement.style.display = 'none';
        warningElement.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> <span>Başında 0 kullanılmaz! Lütfen 0 olmadan girin.</span>';
        phoneInput.parentNode.appendChild(warningElement);
        
        let zeroWarningActive = false;
        
        phoneInput.addEventListener('input', function(e) {
            let input = e.target;
            let value = input.value.replace(/\D/g, '');
            
            if (value.startsWith('0') && value.length > 0) {
                value = value.substring(1);
                
                if (!zeroWarningActive) {
                    warningElement.style.display = 'block';
                    zeroWarningActive = true;
                    phoneInput.classList.add('is-invalid');
                }
            } else if (zeroWarningActive && value.length > 0 && !value.startsWith('0')) {
                warningElement.style.display = 'none';
                zeroWarningActive = false;
                phoneInput.classList.remove('is-invalid');
            }
            
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = '(' + value.substring(0, 3);
            }
            if (value.length > 3) {
                formattedValue += ') ' + value.substring(3, 6);
            }
            if (value.length > 6) {
                formattedValue += ' ' + value.substring(6, 8);
            }
            if (value.length > 8) {
                formattedValue += ' ' + value.substring(8, 10);
            }
            
            input.value = formattedValue;
        });
        
        phoneInput.addEventListener('keydown', function(e) {
            if (e.key === '0' && this.selectionStart === 0) {
                e.preventDefault();
                
                if (!zeroWarningActive) {
                    warningElement.style.display = 'block';
                    zeroWarningActive = true;
                    phoneInput.classList.add('is-invalid');
                }
                return;
            }
        });
        
        phoneInput.addEventListener('focus', function() {
            if (zeroWarningActive) {
                warningElement.style.display = 'block';
            }
        });
        
        const form = phoneInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const value = phoneInput.value.replace(/\D/g, '');
                if (value.startsWith('0')) {
                    e.preventDefault();
                    warningElement.style.display = 'block';
                    zeroWarningActive = true;
                    phoneInput.classList.add('is-invalid');
                    phoneInput.focus();
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }
        
        if (phoneInput.value && phoneInput.value !== '') {
            let value = phoneInput.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = value.substring(1);
                warningElement.style.display = 'block';
                zeroWarningActive = true;
                phoneInput.classList.add('is-invalid');
            }
            
            if (value.length === 10) {
                phoneInput.value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + ' ' + value.substring(6, 8) + ' ' + value.substring(8, 10);
            }
        }
    }
});

// ✅ Takvim Datepicker başlatma
document.addEventListener('DOMContentLoaded', function () {
    function initVisibleDatepickers() {
        document.querySelectorAll('.tab-pane.active .datepicker').forEach(function (input) {
            if (!input._flatpickr) {
                flatpickr(input, {
                    locale: "tr",
                    dateFormat: "d.m.Y",
                    allowInput: true,
                    clickOpens: true,
                    disableMobile: true,
                    position: "below",
                    appendTo: document.body,
                    animate: false,
                    monthSelectorType: "static",
                    prevArrow: '<i class="fas fa-chevron-left"></i>',
                    nextArrow: '<i class="fas fa-chevron-right"></i>',
                    onReady: function (selectedDates, dateStr, instance) {
                        if (!dateStr && instance.input) {
                            instance.input.placeholder = "gg.aa.yyyy";
                        }
                    }
                });
            }
        });
    }

    initVisibleDatepickers();

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', function () {
            setTimeout(initVisibleDatepickers, 100);
        });
    });
});

// ✅ Dinamik İl–İlçe–Okul Seçimi Başlatıcı Fonksiyon
function setupDropdowns(config) {
    const ilSelect = document.getElementById(config.il);
    const ilceSelect = document.getElementById(config.ilce);
    const okulSelect = document.getElementById(config.okul);
    const kapaliKurumCheckbox = document.getElementById(config.kapali);

    if (!ilSelect || !ilceSelect || !okulSelect) {
        console.error('❌ Elementler bulunamadı:', config);
        return;
    }

    ilSelect.addEventListener('change', function () {
        const ilId = ilSelect.value;
        const kapaliDurum = kapaliKurumCheckbox?.checked ? 1 : 0;

        if (!ilId) {
            ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
            ilceSelect.disabled = true;
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            okulSelect.disabled = true;
            return;
        }

        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        ilceSelect.disabled = true;

        fetch(`personel_ekle.php?ajax=ilceler&il_id=${ilId}&kapali_kurum=${kapaliDurum}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
                data.forEach(ilce => {
                    const option = document.createElement('option');
                    option.value = ilce.id;
                    option.textContent = ilce.ilce_adi;
                    ilceSelect.appendChild(option);
                });
                ilceSelect.disabled = false;
                
                // ✅ Session'daki değeri geri yükle
                const sessionValue = ilceSelect.getAttribute('data-session-value');
                if (sessionValue) {
                    ilceSelect.value = sessionValue;
                    ilceSelect.removeAttribute('data-session-value');
                    ilceSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => {
                console.error('❌ İlçeler yüklenemedi:', error);
                ilceSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    });

    ilceSelect.addEventListener('change', function () {
        const ilId = ilSelect.value;
        const ilceId = ilceSelect.value;
        const kapaliDurum = kapaliKurumCheckbox?.checked ? 1 : 0;

        if (!ilId || !ilceId) {
            okulSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            okulSelect.disabled = true;
            return;
        }

        okulSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        okulSelect.disabled = true;

        fetch(`personel_ekle.php?ajax=okullar&il_id=${ilId}&ilce_id=${ilceId}&kapali_kurum=${kapaliDurum}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                okulSelect.innerHTML = '<option value="">Görev Yeri Seçiniz</option>';
                data.forEach(okul => {
                    const option = document.createElement('option');
                    option.value = okul.id;
                    option.textContent = okul.gorev_yeri;
                    option.setAttribute('data-kurum', okul.kurum_kodu || '');
                    option.setAttribute('data-tur', okul.okul_tur || '');
                    okulSelect.appendChild(option);
                });
                okulSelect.disabled = false;
                
                // ✅ Session'daki değeri geri yükle
                const sessionValue = okulSelect.getAttribute('data-session-value');
                if (sessionValue) {
                    okulSelect.value = sessionValue;
                    okulSelect.removeAttribute('data-session-value');
                    okulSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => {
                console.error('❌ Okullar yüklenemedi:', error);
                okulSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    });

    okulSelect.addEventListener('change', function () {
        const selected = okulSelect.options[okulSelect.selectedIndex];
        const kurumKoduInput = document.getElementById(config.kurum_kodu);
        const okulTurInput = document.getElementById(config.okul_tur);

        if (kurumKoduInput) kurumKoduInput.value = selected.getAttribute('data-kurum') || '';
        if (okulTurInput) okulTurInput.value = selected.getAttribute('data-tur') || '';
    });

    kapaliKurumCheckbox?.addEventListener('change', function () {
        if (ilceSelect.value) {
            ilceSelect.dispatchEvent(new Event('change'));
        } else if (ilSelect.value) {
            ilSelect.dispatchEvent(new Event('change'));
        }
    });
}

// ✅ SAYFA YÜKLENDİĞİNDE DİNAMİK DROPDOWN'LARI YENİDEN YÜKLE
function restoreDynamicDropdowns() {
    console.log('🔄 Dinamik dropdownlar yeniden yükleniyor...');
    
    // Görev ili seçiliyse ilçeleri yükle
    const gorevIlSelect = document.getElementById('gorev_il_id');
    if (gorevIlSelect && gorevIlSelect.value) {
        console.log('📍 Görev ili seçili:', gorevIlSelect.value);
        setTimeout(() => {
            gorevIlSelect.dispatchEvent(new Event('change'));
        }, 300);
    }

    // Hizmet sınıfı seçiliyse bağlı alanları yükle
    const hizmetSinifSelect = document.querySelector('select[name="hizmet_sinifi"]');
    if (hizmetSinifSelect && hizmetSinifSelect.value) {
        console.log('🎯 Hizmet sınıfı seçili:', hizmetSinifSelect.value);
        setTimeout(() => {
            hizmetSinifSelect.dispatchEvent(new Event('change'));
        }, 800);
    }

    // Üniversite seçiliyse bağlı alanları yükle
    const universiteSelect = document.getElementById('universite_id');
    if (universiteSelect && universiteSelect.value) {
        console.log('🎓 Üniversite seçili:', universiteSelect.value);
        setTimeout(() => {
            universiteSelect.dispatchEvent(new Event('change'));
        }, 1200);
    }
}

// Sayfa yüklendiğinde dropdown'ları başlat
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sayfa yüklendi, dropdownlar başlatılıyor...');
    
    // Dropdown'ları başlat
    setupDropdowns({
        il: 'gorev_il_id',
        ilce: 'gorev_ilce_id',
        okul: 'gorev_okul_id',
        kapali: 'gorev_kapali_kurum',
        kurum_kodu: 'gorev_kurum_kodu',
        okul_tur: 'gorev_okul_tur'
    });

    setupDropdowns({
        il: 'sozlesme_il_id',
        ilce: 'sozlesme_ilce_id', 
        okul: 'sozlesme_okul_id',
        kapali: 'sozlesme_kapali_kurum',
        kurum_kodu: 'sozlesme_kurum_kodu',
        okul_tur: 'sozlesme_okul_tur'
    });

    setupDropdowns({
        il: 'geldigi_il_id',
        ilce: 'geldigi_ilce_id', 
        okul: 'geldigi_okul_id',
        kapali: 'geldigi_kapali_kurum',
        kurum_kodu: 'geldigi_kurum_kodu',
        okul_tur: 'geldigi_okul_tur'
    });

    // 2 saniye sonra dinamik dropdown'ları restore et
    setTimeout(restoreDynamicDropdowns, 2000);
});

//✅ T.C. Kimlik No Doğrulama Sistemi
document.addEventListener('DOMContentLoaded', function() {
    const tcInput = document.getElementById('tcNoInput');
    const msg = document.getElementById('tcNoMessage');

    if (tcInput && msg) {
        tcInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });

        tcInput.addEventListener('blur', function () {
            const tc = this.value;
            const digits = tc.split('').map(Number);
            const isValid =
                tc.length === 11 &&
                tc[0] !== '0' &&
                digits[9] === ((digits[0] + digits[2] + digits[4] + digits[6] + digits[8]) * 7 - (digits[1] + digits[3] + digits[5] + digits[7])) % 10 &&
                digits[10] === digits.slice(0, 10).reduce((a, b) => a + b) % 10;

            msg.style.display = 'block';
            msg.textContent = isValid ? "✅ Geçerli TC Kimlik No" : "❌ Geçersiz TC Kimlik No";
            msg.className = isValid ? "tc-validation-message text-success" : "tc-validation-message text-danger";
        });
    }
});

//✅ Fotoğraf Önizleme + format + boyut kontrolü
document.addEventListener('DOMContentLoaded', function() {
    const photoUpload = document.getElementById('photoUpload');
    if (photoUpload) {
        photoUpload.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photoPreview');
            const error = document.getElementById('photoError');

            if (preview) preview.style.display = 'none';
            if (error) {
                error.style.display = 'none';
                error.textContent = '';
            }

            if (!file) return;

            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024;

            if (!validTypes.includes(file.type)) {
                if (error) {
                    error.textContent = 'Yalnızca JPG, JPEG, PNG veya GIF dosyaları yüklenebilir.';
                    error.style.display = 'block';
                }
                e.target.value = '';
                return;
            }

            if (file.size > maxSize) {
                if (error) {
                    error.textContent = 'Dosya boyutu 5MB\'dan büyük olamaz.';
                    error.style.display = 'block';
                }
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                if (preview) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        });
    }
});

// ✅ HİZMET SINIFI İLE İLGİLİ TÜM İŞLEMLER
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Hizmet sınıfı işlemleri başlatılıyor...');
    
    const hizmetSelect = document.querySelector('select[name="hizmet_sinifi"]');
    const kadroSelect = document.querySelector('select[name="kadro_unvani"]');
    const gorevSelect = document.querySelector('select[name="gorev_unvani"]');
    const istihdamSelect = document.querySelector('select[name="istihdam_tipi"]');
    const kariyerSelect = document.querySelector('select[name="kariyer_basamagi"]');
    const istihdamUyari = document.getElementById('istihdam-uyari');

    if (!hizmetSelect) {
        console.error('❌ Hizmet sınıfı select elementi bulunamadı!');
        return;
    }

    // 3.1 HİZMET SINIFLARINI YÜKLE
    function loadHizmetSiniflari() {
        console.log('🔄 Hizmet sınıfları yükleniyor...');
        
        fetch('personel_ekle.php?ajax=hizmet_siniflari')
            .then(res => {
                if (!res.ok) throw new Error('HTTP error: ' + res.status);
                return res.json();
            })
            .then(data => {
                console.log('✅ Hizmet sınıfları yüklendi:', data);
                
                hizmetSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.sinif_adi;
                    hizmetSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('❌ Hizmet sınıfları yüklenemedi:', err);
                hizmetSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    // 3.2 İSTİHDAM TİPLERİ KONTROLÜ
    const kurumOkulAlanlari = document.querySelectorAll('.kurum-okul input, .kurum-okul select, .kurum-okul textarea');
    const gorevBaslamaAlanlari = document.querySelectorAll('.gorev-baslama input, .gorev-baslama select, .gorev-baslama textarea');

    const istihdamGruplari = {
        'normal': [
            'Kadrolu',
            'Kurumlararası Görevlendirme(Bakanlık)',
            'Kurumlararası Görevlendirme(Valilik)',
            'Görevlendirme'
        ],
        'diger': [
            'Sözleşmeli Personel',
            'İşçi',
            'Geçici Personel',
            'İşçi (696 K.H.K.)'
        ]
    };

    const hizmetSinifiGruplari = {
        'Diğer Statüler': 'diger',
        'Yardımcı Hizmetler': 'diger'
    };

    function kontrolIstihdamTipleri() {
        const secilenSinif = hizmetSelect.options[hizmetSelect.selectedIndex]?.textContent?.trim();
        console.log('🎯 Hizmet sınıfı değişti:', secilenSinif);
        
        const mevcutSecili = istihdamSelect.value;
        istihdamSelect.innerHTML = '<option value="">Seçiniz</option>';

        const grup = hizmetSinifiGruplari[secilenSinif] || 'normal';
        const uygunTipler = istihdamGruplari[grup];

        console.log('📋 Kullanılacak istihdam tipleri:', uygunTipler);

        uygunTipler.forEach(tip => {
            const option = document.createElement('option');
            option.value = tip;
            option.textContent = tip;
            if (tip === mevcutSecili) option.selected = true;
            istihdamSelect.appendChild(option);
        });

        kontrolIstihdamTipi();
    }

    function alanlariPasiflestir(alanlar) {
        alanlar.forEach(el => {
            el.disabled = true;
            el.style.setProperty('background-color', '#e9ecef', 'important');
            el.style.setProperty('color', '#6c757d', 'important');
            el.style.setProperty('border-color', '#ced4da', 'important');
        });
    }

    function alanlariAktiflestir(alanlar) {
        alanlar.forEach(el => {
            el.disabled = false;
            el.style.removeProperty('background-color');
            el.style.removeProperty('color');
            el.style.removeProperty('border-color');
        });
    }

    function kontrolIstihdamTipi() {
        const secilenTip = istihdamSelect.value;
        console.log('🔍 İstihdam tipi kontrolü:', secilenTip);

        if (secilenTip === 'Sözleşmeli Personel') {
            alanlariPasiflestir(kurumOkulAlanlari);
            alanlariPasiflestir(gorevBaslamaAlanlari);
            if (istihdamUyari) istihdamUyari.classList.remove('d-none');
        } else {
            alanlariAktiflestir(kurumOkulAlanlari);
            alanlariAktiflestir(gorevBaslamaAlanlari);
            if (istihdamUyari) istihdamUyari.classList.add('d-none');
        }
    }

    // 3.3 KADRO ÜNVANLARINI YÜKLE
    function loadKadroUnvanlari(hizmetSinifId) {
        if (!hizmetSinifId) {
            if (kadroSelect) kadroSelect.innerHTML = '<option value="">Önce hizmet sınıfı seçin</option>';
            return;
        }

        if (kadroSelect) {
            kadroSelect.innerHTML = '<option value="">Yükleniyor...</option>';
            
            fetch(`personel_ekle.php?ajax=kadro_unvanlari&hizmet_sinif_id=${hizmetSinifId}`)
                .then(res => res.json())
                .then(unvanlar => {
                    kadroSelect.innerHTML = '<option value="">Seçiniz</option>';
                    unvanlar.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.unvan_adi;
                        kadroSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    console.error('Kadro ünvanları yüklenemedi:', err);
                    kadroSelect.innerHTML = '<option value="">Yükleme hatası</option>';
                });
        }
    }

    // 3.4 GÖREV ÜNVANLARINI YÜKLE
    function loadGorevUnvanlari(hizmetSinifId) {
        if (!hizmetSinifId) {
            if (gorevSelect) gorevSelect.innerHTML = '<option value="">Önce hizmet sınıfı seçin</option>';
            return;
        }

        if (gorevSelect) {
            gorevSelect.innerHTML = '<option value="">Yükleniyor...</option>';

            fetch(`personel_ekle.php?ajax=gorev_unvanlari&hizmet_sinif_id=${hizmetSinifId}`)
                .then(res => res.json())
                .then(gorevler => {
                    gorevSelect.innerHTML = '<option value="">Seçiniz</option>';
                    gorevler.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.unvan_adi;
                        gorevSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    console.error('Görev ünvanları yüklenemedi:', err);
                    gorevSelect.innerHTML = '<option value="">Yükleme hatası</option>';
                });
        }
    }

    // 3.5 KARİYER BASAMAĞI KONTROLÜ
    function kontrolKariyerBasamagi() {
        const selectedText = hizmetSelect.options[hizmetSelect.selectedIndex]?.textContent?.trim();
        
        if (kariyerSelect) {
            if (selectedText === 'Eğitim / Öğretim') {
                kariyerSelect.disabled = false;
                kariyerSelect.style.removeProperty('background-color');
                kariyerSelect.style.removeProperty('color');
            } else {
                kariyerSelect.disabled = true;
                kariyerSelect.value = '';
                kariyerSelect.style.setProperty('background-color', '#e9ecef', 'important');
                kariyerSelect.style.setProperty('color', '#6c757d', 'important');
            }
        }
    }

    // EVENT LISTENER'LAR
    hizmetSelect.addEventListener('change', function() {
        const hizmetSinifId = this.value;
        console.log('🔄 Hizmet sınıfı değişti:', hizmetSinifId);
        
        kontrolIstihdamTipleri();
        kontrolKariyerBasamagi();
        loadKadroUnvanlari(hizmetSinifId);
        loadGorevUnvanlari(hizmetSinifId);
    });

    istihdamSelect.addEventListener('change', kontrolIstihdamTipi);

    // SAYFA YÜKLENDİĞİNDE BAŞLAT
    loadHizmetSiniflari();
});

//✅ Durumu bilgisini yükle
document.addEventListener('DOMContentLoaded', function () {
    const durumSelect = document.querySelector('select[name="durum"]');
    if (durumSelect) {
        fetch('personel_ekle.php?ajax=durum')
            .then(res => res.json())
            .then(data => {
                durumSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.durum_adi;
                    durumSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Durumlar yüklenemedi:', err);
                durumSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }
});

//✅ Atama Alanını ve Yer Değiştirme Çeşitlerini yükle
document.addEventListener('DOMContentLoaded', function () {
    const atamaSelect = document.querySelector('select[name="atama_alani"]');
    const yerSelect = document.querySelector('select[name="yer_degistirme_cesidi"]');

    if (atamaSelect) {
        fetch('personel_ekle.php?ajax=atama_alani')
            .then(res => res.json())
            .then(data => {
                atamaSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.alan_adi;
                    atamaSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Atama alanları yüklenemedi:', err);
                atamaSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    if (yerSelect) {
        fetch('personel_ekle.php?ajax=yer_degistirme_cesidi')
            .then(res => res.json())
            .then(data => {
                yerSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.yer_degistirme_cesidi;
                    yerSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Yer değiştirme çeşitleri yüklenemedi:', err);
                yerSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }
});

//✅ Terfi Nedenlerini yükle
document.addEventListener('DOMContentLoaded', function () {
    const terfiNedeniSelect = document.querySelector('select[name="terfi_nedeni"]');
    if (terfiNedeniSelect) {
        fetch('personel_ekle.php?ajax=terfi_nedenleri')
            .then(res => res.json())
            .then(data => {
                terfiNedeniSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.terfi_nedeni;
                    terfiNedeniSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Terfi nedenleri yüklenemedi:', err);
                terfiNedeniSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }
});

//✅ Sözleşmeli personelin kadro kaydı yapılamaz
document.addEventListener('DOMContentLoaded', function() {
    const uyariBaslik = document.getElementById('uyari-baslik');
    const uyariAciklama = document.getElementById('uyari-aciklama');
    const kadroUyari = document.getElementById('kadro-uyari');
    const kadroForm = document.getElementById('kadro-form');
    const terfiTarihiInput = document.querySelector('input[name="terfi_tarihi"]');
    const istihdamSelect = document.querySelector('select[name="istihdam_tipi"]');

    if (!istihdamSelect) return;

    const engelliTipler = {
        'Sözleşmeli Personel': {
            baslik: 'Sözleşmeli personelin Kadro Kaydı girilemez.',
            aciklama: 'Sadece sözleşme bilgileri düzenlenebilir.'
        },
        'Geçici Personel': {
            baslik: 'Geçici personelin Kadro Kaydı girilemez.',
            aciklama: 'Sadece sözleşme bilgileri düzenlenebilir.'
        },
        'İşçi': {
            baslik: 'İşçilerin Kadro Kaydı girilemez.',
            aciklama: 'Sadece sözleşme bilgileri düzenlenebilir.'
        },
        'İşçi (696 K.H.K.)': {
            baslik: '696 KHK kapsamındaki işçilerin Kadro Kaydı girilemez.',
            aciklama: 'Sadece sözleşme bilgileri düzenlenebilir.'
        }
    };

    istihdamSelect.addEventListener('change', function () {
        const secilen = this.options[this.selectedIndex].text.trim();
        const uyari = engelliTipler[secilen];

        if (uyari && kadroUyari && uyariBaslik && uyariAciklama && kadroForm) {
            uyariBaslik.textContent = uyari.baslik;
            uyariAciklama.textContent = uyari.aciklama;
            kadroUyari.classList.remove('d-none');

            kadroForm.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = true;
            });

            if (terfiTarihiInput) {
                terfiTarihiInput.style.setProperty('background-color', '#e9ecef', 'important');
                terfiTarihiInput.style.setProperty('color', '#6c757d', 'important');
                terfiTarihiInput.style.setProperty('border-color', '#ced4da', 'important');
                terfiTarihiInput.style.setProperty('cursor', 'default', 'important');
            }
        } else if (kadroUyari && kadroForm) {
            kadroUyari.classList.add('d-none');

            kadroForm.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = false;
                el.style.removeProperty('background-color');
                el.style.removeProperty('color');
                el.style.removeProperty('border-color');
                el.style.removeProperty('cursor');
            });
        }
    });
});

//✅ ÖĞRENİM DURUMUNA GÖRE FORMLARI AYARLA
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Öğrenim form kontrolü başlatılıyor...');
    
    const ogrenimSelect = document.getElementById('ogrenim_durumu');
    const universiteSection = document.getElementById('universite_bilgileri');
    const okulSection = document.getElementById('okul_bilgileri');

    if (!ogrenimSelect || !universiteSection || !okulSection) {
        console.error('❌ Gerekli elementler bulunamadı!');
        return;
    }

    const universiteOgrenimleri = [
        'Doktora', 'İlköğretmen Okulu', 'Lisans', 'Lisans+Lisansüstü(TEZLİ)', 
        'Lisans+Lisansüstü(TEZSİZ)', 'Öğretmen Okulu', 'Ön Lisans/Yüksekokul', 
        'Yüksek Lisans(TEZLİ)', 'Yüksek Lisans', 'Yüksek Lisans(TEZSİZ)',
        '2 Yıllık Eğitim Enstitüsü', '3 Yıllık Eğitim Enstitüsü', '3 Yıllık Sağlık Eğitim Enstitüsü'
    ];

    function initializeAllFields() {
        console.log('🚀 Sayfa yüklendi - Tüm alanlar aktif hale getiriliyor...');
        
        const allFields = document.querySelectorAll('#universite_bilgileri input, #universite_bilgileri select, #okul_bilgileri input, #okul_bilgileri select');
        
        allFields.forEach(el => {
            el.disabled = false;
            el.required = false;
            el.style.removeProperty('background-color');
            el.style.removeProperty('color');
            el.style.removeProperty('border-color');
            el.style.removeProperty('cursor');
        });
        
        universiteSection.style.display = 'block';
        okulSection.style.display = 'block';
        
        console.log('✅ Tüm alanlar aktif hale getirildi');
    }

    function updateOgrenimForm() {
        const secilenOption = ogrenimSelect.options[ogrenimSelect.selectedIndex];
        const secilenText = secilenOption ? secilenOption.textContent.trim() : '';
        
        console.log('📝 Seçilen öğrenim:', secilenText || 'Hiçbiri');

        if (!secilenText || secilenText === "Seçiniz") {
            console.log('🔘 Hiçbir öğrenim seçilmedi - Tüm alanlar AKTİF');
            
            document.querySelectorAll('#universite_bilgileri input, #universite_bilgileri select, #okul_bilgileri input, #okul_bilgileri select').forEach(el => {
                el.disabled = false;
                el.required = false;
                el.style.removeProperty('background-color');
                el.style.removeProperty('color');
                el.style.removeProperty('border-color');
                el.style.removeProperty('cursor');
            });
            return;
        }

        console.log('🎯 Öğrenim durumu seçildi:', secilenText);
        
        if (universiteOgrenimleri.includes(secilenText)) {
            console.log('🎓 Üniversite öğrenimi - Üniversite AKTİF, Okul PASİF');
            
            document.querySelectorAll('#universite_bilgileri input, #universite_bilgileri select').forEach(el => {
                el.disabled = false;
                el.required = true;
                el.style.removeProperty('background-color');
                el.style.removeProperty('color');
                el.style.removeProperty('border-color');
                el.style.removeProperty('cursor');
            });
            
            document.querySelectorAll('#okul_bilgileri input, #okul_bilgileri select').forEach(el => {
                el.disabled = true;
                el.required = false;
                el.value = '';
                el.style.setProperty('background-color', '#f8f9fa', 'important');
                el.style.setProperty('color', '#6c757d', 'important');
                el.style.setProperty('border-color', '#ced4da', 'important');
                el.style.setProperty('cursor', 'not-allowed', 'important');
            });
        } else {
            console.log('🏫 Okul öğrenimi - Üniversite PASİF, Okul AKTİF');
            
            document.querySelectorAll('#universite_bilgileri input, #universite_bilgileri select').forEach(el => {
                el.disabled = true;
                el.required = false;
                el.value = '';
                el.style.setProperty('background-color', '#f8f9fa', 'important');
                el.style.setProperty('color', '#6c757d', 'important');
                el.style.setProperty('border-color', '#ced4da', 'important');
                el.style.setProperty('cursor', 'not-allowed', 'important');
            });
            
            document.querySelectorAll('#okul_bilgileri input, #okul_bilgileri select').forEach(el => {
                el.disabled = false;
                el.required = true;
                el.style.removeProperty('background-color');
                el.style.removeProperty('color');
                el.style.removeProperty('border-color');
                el.style.removeProperty('cursor');
            });
        }
    }

    ogrenimSelect.addEventListener('change', updateOgrenimForm);
    
    initializeAllFields();
    
    setTimeout(() => {
        const secilenOption = ogrenimSelect.options[ogrenimSelect.selectedIndex];
        const secilenText = secilenOption ? secilenOption.textContent.trim() : '';
        
        if (secilenText && secilenText !== "Seçiniz") {
            console.log('🔄 Sayfa yüklendi, önceden seçilmiş öğrenim durumu tespit edildi:', secilenText);
            updateOgrenimForm();
        }
    }, 500);
});

//✅ Öğrenim durumuna göre kadro ve derece/kademe alanlarını öner
document.addEventListener('DOMContentLoaded', function () {
    const ogrenimSelect = document.querySelector('select[name="ogrenim_durumu"]');
    const kadroDerecesiInput = document.querySelector('input[name="kadro_derecesi"]');
    const aylikDereceInput = document.querySelector('input[name="aylik_derece"]');
    const aylikKademeInput = document.querySelector('input[name="aylik_kademe"]');
    const terfiUyari = document.getElementById('terfi-uyari');

    // ✅ DÜZELTİLDİ: Sadece mevcut inputları kontrol et
    if (!ogrenimSelect || !kadroDerecesiInput || !aylikDereceInput || !aylikKademeInput) {
        console.log("⚠️ Bazı input elementleri bulunamadı:", {
            ogrenimSelect: !!ogrenimSelect,
            kadroDerecesiInput: !!kadroDerecesiInput,
            aylikDereceInput: !!aylikDereceInput,
            aylikKademeInput: !!aylikKademeInput
        });
        return;
    }

    const ogrenimDereceKademeTablosu = {
        'İlkokul': { giris: '15/1', maxDerece: 7, maxKademe: 'Son' },
        'Ortaokul': { giris: '14/2', maxDerece: 5, maxKademe: 'Son' },
        'Ortaokul dengi mesleki veya teknik': { giris: '14/3', maxDerece: 5, maxKademe: 'Son' },
        'Ortaokul üstü 1 yıl mesleki veya teknik': { giris: '13/1', maxDerece: 4, maxKademe: 'Son' },
        'Ortaokul üstü 2 yıl mesleki veya teknik': { giris: '13/2', maxDerece: 4, maxKademe: 'Son' },
        'Lise': { giris: '13/3', maxDerece: 3, maxKademe: 'Son' },
        'Lise dengi mesleki veya teknik': { giris: '12/2', maxDerece: 3, maxKademe: 'Son' },
        'Lise üstü 1 yıl mesleki veya teknik': { giris: '11/1', maxDerece: 2, maxKademe: 'Son' },
        'Lise üstü 2 yıl veya Ortaokul üstü 5 yıl': { giris: '10/1', maxDerece: 2, maxKademe: 'Son' },
        'Lise üstü 3 yıl teknik veya mesleki': { giris: '10/2', maxDerece: 2, maxKademe: 'Son' },
        '2 yıl yüksek öğrenim': { giris: '10/2', maxDerece: 1, maxKademe: 'Son' },
        '3 yıl yüksek öğrenim': { giris: '10/3', maxDerece: 1, maxKademe: 'Son' },
        '4 yıl yüksek öğrenim': { giris: '9/1', maxDerece: 1, maxKademe: 'Son' },
        '5 yıl yüksek öğrenim': { giris: '9/2', maxDerece: 1, maxKademe: 'Son' },
        '6 yıl yüksek öğrenim': { giris: '9/3', maxDerece: 1, maxKademe: 'Son' }
    };

    ogrenimSelect.addEventListener('change', function () {
        const secilen = ogrenimSelect?.options[ogrenimSelect.selectedIndex]?.textContent?.trim();
        const veri = ogrenimDereceKademeTablosu[secilen];

        if (veri && terfiUyari) {
            // Tüm 3 inputu doldur
            kadroDerecesiInput.value = veri.giris; // Örn: "15"
            
            // Derece ve kademeyi ayır
            const dereceKademe = veri.giris.split('/');
            if (dereceKademe.length === 2) {
                aylikDereceInput.value = dereceKademe[0]; // Örn: "15"
                aylikKademeInput.value = dereceKademe[1]; // Örn: "1"
            }

            // Uyarı mesajını göster
            terfiUyari.innerHTML = `
                <i class="fas fa-info-circle me-2"></i> 
                Bu öğrenim durumuna göre:<br>
                <strong>Kadro Derecesi:</strong> ${veri.giris}<br>
                <strong>Aylık Derece:</strong> ${dereceKademe[0] || ''}<br>
                <strong>Aylık Kademe:</strong> ${dereceKademe[1] || ''}<br>
                En fazla <strong>${veri.maxDerece}. derece</strong>ye ve 
                <strong>${veri.maxKademe} kademe</strong>ye yükselebilir.
            `;
            terfiUyari.classList.remove('d-none');
            
            console.log('✅ Öğrenim değişti - Inputlar dolduruldu:', {
                secilen,
                kadroDerecesi: veri.giris,
                aylikDerece: dereceKademe[0],
                aylikKademe: dereceKademe[1]
            });
        } else if (terfiUyari) {
            // Öğrenim seçilmediyse inputları temizle
            terfiUyari.classList.add('d-none');
            kadroDerecesiInput.value = '';
            aylikDereceInput.value = '';
            aylikKademeInput.value = '';
            
            console.log('ℹ️ Öğrenim seçilmedi - Inputlar temizlendi');
        }
    });
    
    // Sayfa yüklendiğinde, eğer öğrenim durumu seçiliyse tetikle
    setTimeout(() => {
        if (ogrenimSelect.value) {
            ogrenimSelect.dispatchEvent(new Event('change'));
        }
    }, 1000);
});

// ✅ ÜNİVERSİTE-FAKÜLTE-ANABİLİM-PROGRAM ZİNCİRİ
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎓 Üniversite zinciri başlatılıyor...');
    
    const universiteSelect = document.getElementById('universite_id');
    const fakulteSelect = document.getElementById('fakulte_yuksekokul_id');
    const anabilimSelect = document.getElementById('anabilim_id');
    const programSelect = document.getElementById('program_id');

    if (!universiteSelect || !fakulteSelect || !anabilimSelect || !programSelect) {
        console.error('❌ Üniversite zinciri elementleri bulunamadı!');
        return;
    }

    // Sayfa yüklendiğinde önceden seçilmiş değerleri yükle
    loadInitialValues();

    universiteSelect.addEventListener('change', function() {
        const universiteId = this.value;
        console.log('🔄 Üniversite değişti:', universiteId);
        
        if (universiteId) {
            loadFakulteler(universiteId);
        } else {
            resetFakulte();
            resetAnabilim();
            resetProgram();
        }
    });

    fakulteSelect.addEventListener('change', function() {
        const fakulteId = this.value;
        console.log('🔄 Fakülte değişti:', fakulteId);
        
        if (fakulteId) {
            loadAnabilimler(fakulteId);
        } else {
            resetAnabilim();
            resetProgram();
        }
    });

    anabilimSelect.addEventListener('change', function() {
        const anabilimId = this.value;
        console.log('🔄 Anabilim dalı değişti:', anabilimId);
        
        if (anabilimId) {
            loadProgramlar(anabilimId);
        } else {
            resetProgram();
        }
    });

    function loadFakulteler(universiteId) {
        fakulteSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        fakulteSelect.disabled = true;

        fetch(`personel_ekle.php?ajax=fakulteler&universite_id=${universiteId}`)
            .then(response => {
                if (!response.ok) throw new Error('Ağ hatası');
                return response.json();
            })
            .then(data => {
                console.log('✅ Fakülteler yüklendi:', data);
                
                fakulteSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(fakulte => {
                    const option = document.createElement('option');
                    option.value = fakulte.id;
                    option.textContent = fakulte.fakulte_adi;
                    fakulteSelect.appendChild(option);
                });
                
                fakulteSelect.disabled = false;
                
                const selectedFakulte = fakulteSelect.getAttribute('data-selected');
                if (selectedFakulte) {
                    fakulteSelect.value = selectedFakulte;
                    fakulteSelect.removeAttribute('data-selected');
                    
                    if (selectedFakulte) {
                        loadAnabilimler(selectedFakulte);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Fakülteler yüklenemedi:', error);
                fakulteSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    function loadAnabilimler(fakulteId) {
        anabilimSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        anabilimSelect.disabled = true;

        fetch(`personel_ekle.php?ajax=anabilimler&fakulte_yuksekokul_id=${fakulteId}`)
            .then(response => {
                if (!response.ok) throw new Error('Ağ hatası');
                return response.json();
            })
            .then(data => {
                console.log('✅ Anabilim dalları yüklendi:', data);
                
                anabilimSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(anabilim => {
                    const option = document.createElement('option');
                    option.value = anabilim.id;
                    option.textContent = anabilim.anabilim_adi;
                    anabilimSelect.appendChild(option);
                });
                
                anabilimSelect.disabled = false;
                
                const selectedAnabilim = anabilimSelect.getAttribute('data-selected');
                if (selectedAnabilim) {
                    anabilimSelect.value = selectedAnabilim;
                    anabilimSelect.removeAttribute('data-selected');
                    
                    if (selectedAnabilim) {
                        loadProgramlar(selectedAnabilim);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Anabilim dalları yüklenemedi:', error);
                anabilimSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    function loadProgramlar(anabilimId) {
        programSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        programSelect.disabled = true;

        fetch(`personel_ekle.php?ajax=programlar&anabilim_id=${anabilimId}`)
            .then(response => {
                if (!response.ok) throw new Error('Ağ hatası');
                return response.json();
            })
            .then(data => {
                console.log('✅ Programlar yüklendi:', data);
                
                programSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(program => {
                    const option = document.createElement('option');
                    option.value = program.id;
                    option.textContent = program.program_adi;
                    programSelect.appendChild(option);
                });
                
                programSelect.disabled = false;
                
                const selectedProgram = programSelect.getAttribute('data-selected');
                if (selectedProgram) {
                    programSelect.value = selectedProgram;
                    programSelect.removeAttribute('data-selected');
                }
            })
            .catch(error => {
                console.error('❌ Programlar yüklenemedi:', error);
                programSelect.innerHTML = '<option value="">Yükleme hatası</option>';
            });
    }

    function loadInitialValues() {
        const selectedUniversite = universiteSelect.value;
        const selectedFakulte = fakulteSelect.getAttribute('data-selected');
        const selectedAnabilim = anabilimSelect.getAttribute('data-selected');
        const selectedProgram = programSelect.getAttribute('data-selected');

        console.log('📥 Önceden seçilmiş değerler:', {
            universite: selectedUniversite,
            fakulte: selectedFakulte,
            anabilim: selectedAnabilim,
            program: selectedProgram
        });

        if (selectedUniversite) {
            loadFakulteler(selectedUniversite);
        }
    }

    function resetFakulte() {
        fakulteSelect.innerHTML = '<option value="">Önce Üniversite Seçiniz</option>';
        fakulteSelect.disabled = true;
    }

    function resetAnabilim() {
        anabilimSelect.innerHTML = '<option value="">Önce Fakülte Seçiniz</option>';
        anabilimSelect.disabled = true;
    }

    function resetProgram() {
        programSelect.innerHTML = '<option value="">Önce Anabilim Dalı Seçiniz</option>';
        programSelect.disabled = true;
    }
});

//✅ Geldiği Kurum Görev Ünvanlarını Yükle
function loadOncekiGorevUnvanlari() {
    const select = document.getElementById('geldigi_gorev_unvani');
    if (!select) {
        console.error('❌ Geldiği görev unvanı select elementi bulunamadı!');
        return;
    }

    console.log('🔄 Geldiği kurum görev ünvanları yükleniyor...');
    
    fetch('personel_ekle.php?ajax=tum_gorev_unvanlari')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('✅ Görev ünvanları başarıyla yüklendi:', data);
            
            select.innerHTML = '<option value="">Seçiniz</option>';
            data.forEach(unvan => {
                const option = document.createElement('option');
                option.value = unvan.id;
                option.textContent = unvan.unvan_adi;
                select.appendChild(option);
            });
            
            console.log('✅ Geldiği görev unvanları dropdown başarıyla dolduruldu');
        })
        .catch(error => {
            console.error('❌ Görev ünvanları yüklenemedi:', error);
            select.innerHTML = '<option value="">Yükleme hatası</option>';
        });
}

// Sayfa yüklendiğinde çağır
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sayfa yüklendi, geldiği görev unvanları yükleniyor...');
    loadOncekiGorevUnvanlari();
    
    const geldigiKurumTab = document.querySelector('[data-bs-target="#geldigi-kurum"]');
    if (geldigiKurumTab) {
        geldigiKurumTab.addEventListener('shown.bs.tab', function() {
            console.log('🔁 Geldiği kurum sekmesi açıldı, görev unvanları yeniden yükleniyor...');
            loadOncekiGorevUnvanlari();
        });
    }
});

//✅ Sekme Geçiş İşlemleri
const tabOrder = [
    'temel-bilgiler',
    'kimlik-bilgileri', 
    'gorev-kaydi',
    'kadro-kaydi',
    'sozlesme-bilgileri',
    'ogrenim_bilgileri',
    'geldigi-kurum'
];

function nextTab(currentTab) {
    const currentIndex = tabOrder.indexOf(currentTab);
    if (currentIndex < tabOrder.length - 1) {
        const nextTabId = tabOrder[currentIndex + 1];
        const nextTabElement = document.querySelector(`[data-bs-target="#${nextTabId}"]`);
        if (nextTabElement) {
            const tab = new bootstrap.Tab(nextTabElement);
            tab.show();
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevTab(currentTab) {
    const currentIndex = tabOrder.indexOf(currentTab);
    if (currentIndex > 0) {
        const prevTabId = tabOrder[currentIndex - 1];
        const prevTabElement = document.querySelector(`[data-bs-target="#${prevTabId}"]`);
        if (prevTabElement) {
            const tab = new bootstrap.Tab(prevTabElement);
            tab.show();
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Klavye kısayolları
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'ArrowRight') {
        const activeTab = document.querySelector('.tab-pane.active');
        if (activeTab) {
            nextTab(activeTab.id);
        }
    }
    
    if (e.ctrlKey && e.key === 'ArrowLeft') {
        const activeTab = document.querySelector('.tab-pane.active');
        if (activeTab) {
            prevTab(activeTab.id);
        }
    }
});


// ✅ GELİŞMİŞ FORM DOĞRULAMA - SİSTEMİ (AKTİF)
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if (!form) {
        console.error('❌ Form elementi bulunamadı!');
        return;
    }

    console.log('✅ Form elementi bulundu, event listener ekleniyor...');

    // Form submit event listener'ını ekle
    form.addEventListener('submit', function(e) {
        console.log('🔴 FORM SUBMIT EDİLDİ!');
        console.log('🔍 Gelişmiş form validasyonu başlatıldı...');

        // Tüm hata mesajlarını ve vurgulamaları temizle
        clearAllFieldErrors();

        // VALİDASYON AKTİF
        const errors = validateForm();

        console.log('📋 Validasyon hataları:', errors.length);

        if (errors.length > 0) {
            console.log('❌ Hatalar bulundu, form gönderimi engelleniyor...');
            e.preventDefault();
            
            // İlk hataya odaklan
            const firstError = errors[0];
            showPageCenterValidationError(firstError.message, firstError.element, firstError.tab);
            
            console.log('❌ Form gönderimi engellendi. Hatalar:', errors);
            return false;
        }

        // Disabled alanlardaki required kısıtlamasını kaldır
        document.querySelectorAll('input:disabled, select:disabled, textarea:disabled').forEach(el => {
            el.removeAttribute('required');
        });

        console.log('✅ Form gönderim onaylandı, form gönderiliyor...');
        return true;
    });

    // TC Kimlik No anlık validasyonu
    const tcInput = document.getElementById('tcNoInput');
    if (tcInput) {
        tcInput.addEventListener('blur', function() {
            const tc = this.value;
            if (tc && !validateTCKimlik(tc)) {
                showFieldError(this, 'Geçersiz TC Kimlik Numarası!');
            } else {
                clearFieldError(this);
            }
        });
    }

    // Telefon anlık validasyonu
    const phoneInput = document.querySelector('input[name="telefon"]');
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            const phone = this.value.replace(/\D/g, '');
            if (phone && !validatePhone(phone)) {
                showFieldError(this, 'Geçersiz telefon numarası! 5xx ile başlayan 10 haneli numara giriniz.');
            } else {
                clearFieldError(this);
            }
        });
    }

    console.log('✅ Form validasyon sistemi başlatıldı (AKTİF)');
});

// ✅ TÜM FORM VALIDASYONLARINI TOPLAYAN ANA FONKSİYON (AKTİF)
function validateForm() {
    console.log('🔍 Validasyon başlatılıyor...');
    let errors = [];

    // ============================================
    // 1. TEMEL BİLGİLER VALIDASYONU
    // ============================================
    
    // Ad Soyad
    const adSoyad = document.querySelector('input[name="ad_soyadi"]');
    if (!adSoyad || !adSoyad.value.trim()) {
        errors.push({ 
            message: 'Ad Soyad alanı zorunludur.', 
            element: adSoyad, 
            tab: 'temel-bilgiler' 
        });
    }
    
    // TC Kimlik No
    const tcNo = document.querySelector('input[name="tc_no"]');
    if (!tcNo || !tcNo.value.trim()) {
        errors.push({ 
            message: 'TC Kimlik No alanı zorunludur.', 
            element: tcNo, 
            tab: 'temel-bilgiler' 
        });
    } else if (!validateTCKimlik(tcNo.value)) {
        errors.push({ 
            message: 'Geçersiz TC Kimlik Numarası.', 
            element: tcNo, 
            tab: 'temel-bilgiler' 
        });
    }
    
    // Fotoğraf (isteğe bağlı - zorunlu değil)
    // const foto = document.querySelector('input[name="foto"]');
    // if (!foto || !foto.files || foto.files.length === 0) {
    //     errors.push({ 
    //         message: 'Fotoğraf seçimi zorunludur.', 
    //         element: foto, 
    //         tab: 'temel-bilgiler' 
    //     });
    // }

    // ============================================
    // 2. KİMLİK BİLGİLERİ VALIDASYONU
    // ============================================
    
    // Doğum Tarihi
    const dogumTarihi = document.querySelector('input[name="dogum_tarihi"]');
    if (!dogumTarihi || !dogumTarihi.value.trim()) {
        errors.push({ 
            message: 'Doğum Tarihi alanı zorunludur.', 
            element: dogumTarihi, 
            tab: 'kimlik-bilgileri' 
        });
    }
    
    // Cinsiyet
    const cinsiyet = document.querySelector('select[name="cinsiyeti"]');
    if (!cinsiyet || !cinsiyet.value) {
        errors.push({ 
            message: 'Cinsiyet alanı zorunludur.', 
            element: cinsiyet, 
            tab: 'kimlik-bilgileri' 
        });
    }
    
    // Telefon (isteğe bağlı ama doldurulmuşsa doğrula)
    const telefon = document.querySelector('input[name="telefon"]');
    if (telefon && telefon.value.trim()) {
        const phoneValue = telefon.value.replace(/\D/g, '');
        if (!validatePhone(phoneValue)) {
            errors.push({ 
                message: 'Geçersiz telefon numarası! 5xx ile başlayan 10 haneli numara giriniz.', 
                element: telefon, 
                tab: 'kimlik-bilgileri' 
            });
        }
    }

    // ============================================
    // 3. GÖREV BİLGİLERİ VALIDASYONU
    // ============================================
    
    const istihdamTipi = document.querySelector('select[name="istihdam_tipi"]')?.value;
    
    // Sözleşmeli personel için görev bilgileri gerekmez
    if (istihdamTipi !== 'Sözleşmeli Personel') {
        const requiredGorevFields = [
            { selector: 'select[name="gorev_il_id"]', message: 'Görev İli zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="gorev_ilce_id"]', message: 'Görev İlçesi zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="gorev_okul_id"]', message: 'Görev Yeri zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'input[name="kurum_baslama_tarihi"]', message: 'Kurumda Başlama Tarihi zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="hizmet_sinifi"]', message: 'Hizmet Sınıfı zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="istihdam_tipi"]', message: 'İstihdam Tipi zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="kadro_unvani"]', message: 'Kadro Ünvanı zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="gorev_unvani"]', message: 'Görev Ünvanı zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="durum"]', message: 'Durum zorunludur.', tab: 'gorev-kaydi' },
            { selector: 'select[name="yer_degistirme_cesidi"]', message: 'Yer Değiştirme Çeşidi zorunludur.', tab: 'gorev-kaydi' }
        ];
        
        requiredGorevFields.forEach(field => {
            const element = document.querySelector(field.selector);
            if (element && !element.disabled && (!element.value || element.value.trim() === '')) {
                errors.push({ 
                    message: field.message, 
                    element: element, 
                    tab: field.tab 
                });
            }
        });
    }

    // ============================================
    // 4. ÖĞRENİM BİLGİLERİ VALIDASYONU
    // ============================================
    
    const ogrenimSelect = document.getElementById('ogrenim_durumu');
    const secilenOgrenim = ogrenimSelect?.options[ogrenimSelect.selectedIndex]?.textContent?.trim();
    
    if (secilenOgrenim && secilenOgrenim !== "Seçiniz" && ogrenimSelect.value) {
        // Mezuniyet Tarihi Zorunlu
        const mezuniyetTarihi = document.querySelector('input[name="mezuniyet_tarihi"]');
        if (!mezuniyetTarihi || !mezuniyetTarihi.value.trim()) {
            errors.push({ 
                message: 'Mezuniyet Tarihi zorunludur.', 
                element: mezuniyetTarihi, 
                tab: 'ogrenim_bilgileri' 
            });
        }
        
        // Belge Bilgileri Zorunlu
        const belgeTarihi = document.querySelector('input[name="belge_tarihi"]');
        if (!belgeTarihi || !belgeTarihi.value.trim()) {
            errors.push({ 
                message: 'Belge Tarihi zorunludur.', 
                element: belgeTarihi, 
                tab: 'ogrenim_bilgileri' 
            });
        }
        
        const belgeNo = document.querySelector('input[name="belge_no"]');
        if (!belgeNo || !belgeNo.value.trim()) {
            errors.push({ 
                message: 'Belge No zorunludur.', 
                element: belgeNo, 
                tab: 'ogrenim_bilgileri' 
            });
        }
        
        const belgeCinsi = document.querySelector('select[name="belge_cinsi"]');
        if (!belgeCinsi || !belgeCinsi.value) {
            errors.push({ 
                message: 'Belge Cinsi zorunludur.', 
                element: belgeCinsi, 
                tab: 'ogrenim_bilgileri' 
            });
        }
        
        // Üniversite öğrenimi için ek kontroller
        const universiteOgrenimleri = [
            'Doktora', 'İlköğretmen Okulu', 'Lisans', 'Lisans+Lisansüstü(TEZLİ)', 
            'Lisans+Lisansüstü(TEZSİZ)', 'Öğretmen Okulu', 'Ön Lisans/Yüksekokul', 
            'Yüksek Lisans(TEZLİ)', 'Yüksek Lisans', 'Yüksek Lisans(TEZSİZ)',
            '2 Yıllık Eğitim Enstitüsü', '3 Yıllık Eğitim Enstitüsü', '3 Yıllık Sağlık Eğitim Enstitüsü'
        ];
        
        if (universiteOgrenimleri.includes(secilenOgrenim)) {
            const universite = document.querySelector('select[name="universite_id"]');
            if (!universite || !universite.value) {
                errors.push({ 
                    message: 'Üniversite seçimi zorunludur.', 
                    element: universite, 
                    tab: 'ogrenim_bilgileri' 
                });
            }
            
            const fakulte = document.querySelector('select[name="fakulte_yuksekokul_id"]');
            if (fakulte && fakulte.disabled === false && !fakulte.value) {
                errors.push({ 
                    message: 'Fakülte seçimi zorunludur.', 
                    element: fakulte, 
                    tab: 'ogrenim_bilgileri' 
                });
            }
        } else {
            // Okul öğrenimi için
            const mezunOkul = document.querySelector('input[name="mezun_okul_id"]');
            if (mezunOkul && !mezunOkul.disabled && !mezunOkul.value.trim()) {
                errors.push({ 
                    message: 'Mezun Olduğu Okul zorunludur.', 
                    element: mezunOkul, 
                    tab: 'ogrenim_bilgileri' 
                });
            }
        }
    }

    console.log('📋 Validasyon tamamlandı. Hata sayısı:', errors.length);
    return errors;
}

// TC KİMLİK DOĞRULAMA FONKSİYONU
function validateTCKimlik(tc) {
    if (tc.length !== 11) return false;
    if (isNaN(tc)) return false;
    if (tc[0] === '0') return false;
    
    const digits = tc.split('').map(Number);
    const tek = digits[0] + digits[2] + digits[4] + digits[6] + digits[8];
    const cift = digits[1] + digits[3] + digits[5] + digits[7];
    const tenth = (tek * 7 - cift) % 10;
    const total = digits.slice(0, 10).reduce((a, b) => a + b, 0);
    const eleventh = total % 10;
    
    return digits[9] === tenth && digits[10] === eleventh;
}

// TELEFON DOĞRULAMA FONKSİYONU
function validatePhone(phone) {
    return phone.length === 10 && phone.startsWith('5');
}

// ALAN HATA GÖSTERİMİ
function showFieldError(element, message) {
    if (!element) return;
    
    element.classList.add('field-error-highlight');
    
    let errorElement = element.parentNode.querySelector('.field-error-message');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error-message text-danger mt-1 small';
        element.parentNode.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

// ALAN HATASINI TEMİZLE
function clearFieldError(element) {
    if (!element) return;
    
    element.classList.remove('field-error-highlight');
    const errorElement = element.parentNode.querySelector('.field-error-message');
    if (errorElement && errorElement.parentNode) {
        errorElement.parentNode.removeChild(errorElement);
    }
}

// TÜM ALAN HATALARINI TEMİZLE
function clearAllFieldErrors() {
    document.querySelectorAll('.field-error-highlight').forEach(el => {
        el.classList.remove('field-error-highlight');
    });
    document.querySelectorAll('.field-error-message').forEach(el => {
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
    });
}

// SAYFA ORTASINDA VALIDASYON HATASI GÖSTERİMİ
function showPageCenterValidationError(message, focusElement, tabId) {
    const existingAlerts = document.querySelectorAll('.validation-error-alert');
    existingAlerts.forEach(alert => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    });

    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert auto-hide-alert error validation-error-alert';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <div class="alert-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="alert-title">Eksik Bilgi!</div>
        <div class="alert-message">${message}</div>
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
    `;

    document.body.appendChild(alertDiv);

    switchToTab(tabId);

    setTimeout(() => {
        if (focusElement) {
            showFieldError(focusElement, message);
            focusElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            focusElement.focus();
        }
    }, 300);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.add('fade-out');
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 500);
        }
    }, 6000);

    alertDiv.addEventListener('click', function() {
        this.classList.add('fade-out');
        setTimeout(() => {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        }, 500);
    });
}

// SEKMELER ARASI GEÇİŞ
function switchToTab(tabId) {
    try {
        const tabElement = document.querySelector(`[data-bs-target="#${tabId}"]`);
        if (tabElement && typeof bootstrap !== 'undefined') {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    } catch (error) {
        console.log('Bootstrap tab geçişi başarısız:', error);
    }
}

// ELEMENT GÖRÜNÜR MÜ KONTROLÜ
function isElementVisible(element) {
    if (!element) return false;
    const style = window.getComputedStyle(element);
    return style.display !== 'none' && style.visibility !== 'hidden' && element.offsetParent !== null;
}

// ✅ CSS STİLLERİ
const additionalStyles = `
.field-error-highlight {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    background-color: #fff8f8 !important;
    transition: all 0.3s ease;
}

.field-error-message {
    font-size: 0.875rem;
    font-weight: 500;
    display: block;
    margin-top: 4px;
}

.validation-error-alert {
    z-index: 10000;
    min-width: 450px;
    max-width: 90vw;
}

.auto-hide-alert.error .progress-bar {
    background: #dc3545;
}
`;

// CSS stillerini ekle
if (!document.querySelector('#form-validation-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'form-validation-styles';
    styleSheet.textContent = additionalStyles;
    document.head.appendChild(styleSheet);
}



// =============================================================================
// TEST VERİLERİ İLE FORMU OTOMATİK DOLDUR
// =============================================================================
function testVerileriyleDoldur() {
    console.log('🔧 Test verileri dolduruluyor...');
    
    // 1. TEMEL BİLGİLER
    document.querySelector('input[name="ad_soyadi"]').value = 'TEST PERSONEL';
    document.querySelector('input[name="tc_no"]').value = '12345678901';
    document.querySelector('input[name="emekli_sicil_no"]').value = '12345';
    document.querySelector('input[name="kurum_sicil_no"]').value = '67890';
    document.querySelector('input[name="arsiv_no"]').value = 'ARCH-001';
    document.querySelector('input[name="raf_no"]').value = 'R-01';
    
    // 2. KİMLİK BİLGİLERİ
    document.querySelector('input[name="baba_adi"]').value = 'BABA ADI';
    document.querySelector('input[name="dogum_tarihi"]').value = '01.01.1990';
    document.querySelector('input[name="dogum_yeri"]').value = 'İSTANBUL';
    document.querySelector('select[name="cinsiyeti"]').value = 'Erkek';
    document.querySelector('select[name="medeni_durum"]').value = 'Evli';
    document.querySelector('select[name="kan_grubu"]').value = 'A Rh+';
    document.querySelector('input[name="telefon"]').value = '(555) 123 45 67';
    document.querySelector('input[name="email"]').value = 'test@example.com';
    document.querySelector('textarea[name="ikametgah_adresi"]').value = 'Test Adresi, İstanbul';
    
    // 3. GÖREV KAYDI - İl seçimi (Muş = 49)
    setTimeout(() => {
        const ilSelect = document.getElementById('gorev_il_id');
        if (ilSelect) {
            ilSelect.value = '49'; // Muş ili ID'si (veritabanınıza göre değişebilir)
            ilSelect.dispatchEvent(new Event('change'));
        }
    }, 100);
    
    // İlçe ve okul seçimleri 500ms sonra doldurulacak
    setTimeout(() => {
        const ilceSelect = document.getElementById('gorev_ilce_id');
        if (ilceSelect && ilceSelect.options.length > 1) {
            ilceSelect.selectedIndex = 1;
            ilceSelect.dispatchEvent(new Event('change'));
        }
    }, 600);
    
    setTimeout(() => {
        const okulSelect = document.getElementById('gorev_okul_id');
        if (okulSelect && okulSelect.options.length > 1) {
            okulSelect.selectedIndex = 1;
            okulSelect.dispatchEvent(new Event('change'));
        }
    }, 1200);
    
    // Diğer görev alanları
    setTimeout(() => {
        document.querySelector('input[name="memuriyete_baslama_tarihi"]').value = '01.09.2010';
        document.querySelector('input[name="kurum_baslama_tarihi"]').value = '01.09.2020';
        document.querySelector('textarea[name="gorev_aciklama"]').value = 'Test görev açıklaması';
        
        // Hizmet sınıfı seçimi (Eğitim / Öğretim = 1)
        const hizmetSelect = document.querySelector('select[name="hizmet_sinifi"]');
        if (hizmetSelect && hizmetSelect.options.length > 1) {
            hizmetSelect.value = '1';
            hizmetSelect.dispatchEvent(new Event('change'));
        }
    }, 500);
    
    setTimeout(() => {
        document.querySelector('select[name="istihdam_tipi"]').value = 'Kadrolu';
        document.querySelector('select[name="durum"]').value = '1';
        document.querySelector('select[name="yer_degistirme_cesidi"]').value = '1';
        document.querySelector('select[name="atama_alani"]').value = '1';
    }, 800);
    
    // 4. KADRO KAYDI
    document.querySelector('input[name="terfi_tarihi"]').value = '01.01.2020';
    document.querySelector('input[name="kadro_derecesi"]').value = '1/1';
    document.querySelector('input[name="aylik_derece"]').value = '1';
    document.querySelector('input[name="aylik_kademe"]').value = '1';
    document.querySelector('input[name="kha_ek_gosterge"]').value = '2200';
    
    // 5. SÖZLEŞME BİLGİLERİ
    setTimeout(() => {
        const sozlesmeIlSelect = document.getElementById('sozlesme_il_id');
        if (sozlesmeIlSelect) {
            sozlesmeIlSelect.value = '49';
            sozlesmeIlSelect.dispatchEvent(new Event('change'));
        }
    }, 1500);
    
    setTimeout(() => {
        const sozlesmeIlceSelect = document.getElementById('sozlesme_ilce_id');
        if (sozlesmeIlceSelect && sozlesmeIlceSelect.options.length > 1) {
            sozlesmeIlceSelect.selectedIndex = 1;
            sozlesmeIlceSelect.dispatchEvent(new Event('change'));
        }
    }, 2000);
    
    setTimeout(() => {
        const sozlesmeOkulSelect = document.getElementById('sozlesme_okul_id');
        if (sozlesmeOkulSelect && sozlesmeOkulSelect.options.length > 1) {
            sozlesmeOkulSelect.selectedIndex = 1;
            sozlesmeOkulSelect.dispatchEvent(new Event('change'));
        }
    }, 2600);
    
    setTimeout(() => {
        document.querySelector('input[name="sozlesme_baslangic"]').value = '01.01.2020';
        document.querySelector('input[name="sozlesme_bitis"]').value = '31.12.2020';
        document.querySelector('input[name="sozlesme_suresi"]').value = '12';
        document.querySelector('select[name="sozlesme_turu"]').value = 'Tam Zamanlı';
    }, 2800);
    
    // 6. ÖĞRENİM BİLGİLERİ
    const ogrenimSelect = document.getElementById('ogrenim_durumu');
    if (ogrenimSelect && ogrenimSelect.options.length > 1) {
        ogrenimSelect.value = '4'; // Lisans (ID'si veritabanınıza göre değişebilir)
        ogrenimSelect.dispatchEvent(new Event('change'));
    }
    document.querySelector('input[name="mezuniyet_tarihi"]').value = '01.06.2015';
    document.querySelector('input[name="belge_tarihi"]').value = '15.06.2015';
    document.querySelector('input[name="belge_no"]').value = 'BELGE-12345';
    document.querySelector('select[name="belge_cinsi"]').value = 'Diploma';
    
    // Üniversite seçimi
    setTimeout(() => {
        const universiteSelect = document.getElementById('universite_id');
        if (universiteSelect && universiteSelect.options.length > 1) {
            universiteSelect.selectedIndex = 1;
            universiteSelect.dispatchEvent(new Event('change'));
        }
    }, 3000);
    
    // 7. GELDİĞİ KURUM
    setTimeout(() => {
        const geldigiIlSelect = document.getElementById('geldigi_il_id');
        if (geldigiIlSelect) {
            geldigiIlSelect.value = '34'; // İstanbul
            geldigiIlSelect.dispatchEvent(new Event('change'));
        }
    }, 3500);
    
    setTimeout(() => {
        const geldigiIlceSelect = document.getElementById('geldigi_ilce_id');
        if (geldigiIlceSelect && geldigiIlceSelect.options.length > 1) {
            geldigiIlceSelect.selectedIndex = 1;
            geldigiIlceSelect.dispatchEvent(new Event('change'));
        }
    }, 4100);
    
    setTimeout(() => {
        const geldigiOkulSelect = document.getElementById('geldigi_okul_id');
        if (geldigiOkulSelect && geldigiOkulSelect.options.length > 1) {
            geldigiOkulSelect.selectedIndex = 1;
            geldigiOkulSelect.dispatchEvent(new Event('change'));
        }
    }, 4700);
    
    setTimeout(() => {
        document.querySelector('input[name="geldigi_ayrilma_tarihi"]').value = '31.08.2020';
        document.querySelector('select[name="geldigi_ayrilma_nedeni"]').value = 'Nakil';
    }, 5000);
    
    console.log('✅ Test verileri doldurma işlemi başlatıldı');
}

// Buton ekle
function testVerileriButonuEkle() {
    const headerActions = document.querySelector('.header-actions');
    if (headerActions && !document.getElementById('testVerileriBtn')) {
        const testBtn = document.createElement('button');
        testBtn.id = 'testVerileriBtn';
        testBtn.className = 'btn-action btn-info';
        testBtn.title = 'Test Verileri ile Doldur';
        testBtn.innerHTML = '<i class="bi bi-database"></i>';
        testBtn.onclick = testVerileriyleDoldur;
        headerActions.appendChild(testBtn);
        console.log('✅ Test verileri butonu eklendi');
    }
}

// Sayfa yüklendiğinde butonu ekle
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testVerileriButonuEkle, 1000);
});

  </script>

</body>
</html>









