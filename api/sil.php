<?php
/**
 * MERKEZİ SİLME API'Sİ
 * Tüm modüller için tek noktadan silme işlemi
 * 
 * Kullanım:
 * DELETE /api/sil.php?modul=emeklilik&id=123
 * DELETE /api/sil.php?modul=gorev&id=456
 * 
 * @version 2.0
 */

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

// Session başlat ve yetki kontrolü
SessionManager::start();
SessionManager::requireAuth();

header('Content-Type: application/json');

// Sadece DELETE ve POST metodlarına izin ver (bazı eski sistemler için POST da desteklenir)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use DELETE or POST.']);
    exit;
}

// Gelen parametreleri al
$modul = $_GET['modul'] ?? $_POST['modul'] ?? null;
$id = $_GET['id'] ?? $_GET[$modul . '_id'] ?? $_POST['id'] ?? $_POST[$modul . '_id'] ?? null;

if (!$modul || !$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Modül ve ID bilgisi gereklidir. Örnek: ?modul=emeklilik&id=123']);
    exit;
}

$id = (int)$id;

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $success = false;
    $message = '';
    $silinen_id = $id;
    
    // =========================================================================
    // MODÜL TANIMLARI
    // =========================================================================
    
    switch ($modul) {
        
        // ----------------------------- GÖREV -----------------------------
        case 'gorev':
            // Önce görevin var olduğunu kontrol et
            $kayit = $database->fetch("SELECT * FROM personel_gorev WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Görev kaydı bulunamadı');
            }
            
            // Silme işlemi
            $stmt = $db->prepare("DELETE FROM personel_gorev WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Görev kaydı başarıyla silindi';
            break;
        
        // ----------------------------- SÖZLEŞME -----------------------------
		case 'sozlesme':
			$kayit = $database->fetch("SELECT id FROM personel_sozlesme WHERE id = ?", [$id]);
			if (!$kayit) {
				throw new Exception('Sözleşme kaydı bulunamadı');
			}
			$stmt = $db->prepare("DELETE FROM personel_sozlesme WHERE id = ?");
			$stmt->execute([$id]);
			$success = true;
			$message = 'Sözleşme kaydı başarıyla silindi';
			break;

		// ----------------------------- KADRO -----------------------------
        case 'kadro':
            // Kaydın var olduğunu kontrol et
            $kayit = $database->fetch("SELECT id FROM personel_kadro WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Kadro kaydı bulunamadı');
            }
            
            // Silme işlemi
            $stmt = $db->prepare("DELETE FROM personel_kadro WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Kadro kaydı başarıyla silindi';
            break;
        
        // ----------------------------- EMEKLİLİK -----------------------------
        case 'emeklilik':
            // Önce silinecek kaydın bilgilerini al (görev bitiş tarihi temizlemek için)
            $kayit = $database->fetch("SELECT * FROM personel_emekli WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Emeklilik kaydı bulunamadı');
            }
            
            $personel_id = $kayit['personel_id'];
            $emeklilik_tarihi = $kayit['emeklilik_tarihi'];
            
            // Silme işlemi
            $stmt = $db->prepare("DELETE FROM personel_emekli WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Emeklilik kaydı başarıyla silindi';
            
            // Emeklilik tarihine sahip görevin bitiş tarihini temizle
            if ($emeklilik_tarihi && $personel_id) {
                $gorev = $database->fetch(
                    "SELECT id FROM personel_gorev 
                     WHERE personel_id = ? AND bitis_tarihi = ? 
                     ORDER BY kurum_baslama_tarihi DESC, id DESC LIMIT 1",
                    [$personel_id, $emeklilik_tarihi]
                );
                
                if ($gorev) {
                    $stmt = $db->prepare("UPDATE personel_gorev SET bitis_tarihi = NULL WHERE id = ?");
                    $stmt->execute([$gorev['id']]);
                }
            }
            break;
        
        // ----------------------------- ÖĞRENİM -----------------------------
		case 'ogrenim':
			$kayit = $database->fetch("SELECT id FROM personel_ogrenim WHERE id = ?", [$id]);
			
			if (!$kayit) {
				throw new Exception('Öğrenim kaydı bulunamadı');
			}
			
			$stmt = $db->prepare("DELETE FROM personel_ogrenim WHERE id = ?");
			$stmt->execute([$id]);
			$success = true;
			$message = 'Öğrenim kaydı başarıyla silindi';
			break;
	
        // ----------------------------- AYRILMA -----------------------------
        case 'ayrilma':
            // Önce silinecek ayrılma kaydının bilgilerini al
            $kayit = $database->fetch("SELECT personel_id, ayrilma_tarihi FROM personel_ayrilma WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Ayrılma kaydı bulunamadı');
            }
            
            $personel_id = $kayit['personel_id'];
            $ayrilma_tarihi = $kayit['ayrilma_tarihi'];
            
            // Ayrılma tarihini temizle (görev kaydındaki bitis_tarihi alanını null yap)
            if ($ayrilma_tarihi && $personel_id) {
                $gorev = $database->fetch(
                    "SELECT id FROM personel_gorev 
                     WHERE personel_id = ? AND bitis_tarihi = ? 
                     ORDER BY kurum_baslama_tarihi DESC, id DESC LIMIT 1",
                    [$personel_id, $ayrilma_tarihi]
                );
                
                if ($gorev) {
                    $stmt = $db->prepare("UPDATE personel_gorev SET bitis_tarihi = NULL WHERE id = ?");
                    $stmt->execute([$gorev['id']]);
                }
            }
            
            // Ayrılma kaydını sil
            $stmt = $db->prepare("DELETE FROM personel_ayrilma WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Ayrılma kaydı başarıyla silindi';
            break;
        
        // ----------------------------- DOSYA GÖNDERME -----------------------------
        case 'dosya':
        case 'dosya_gonderme':
            // Kaydın var olduğunu kontrol et
            $kayit = $database->fetch("SELECT id FROM personel_dosya_gonderme WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Dosya gönderme kaydı bulunamadı');
            }
            
            // Silme işlemi
            $stmt = $db->prepare("DELETE FROM personel_dosya_gonderme WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Dosya gönderme kaydı başarıyla silindi';
            break;
        
        // ----------------------------- İZİN -----------------------------
        case 'izin':
            $kayit = $database->fetch("SELECT id FROM izinler WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('İzin kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM izinler WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'İzin kaydı başarıyla silindi';
            break;
        
        // ----------------------------- ÖDÜL -----------------------------
        case 'odul':
            $kayit = $database->fetch("SELECT id FROM oduller WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Ödül kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM oduller WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Ödül kaydı başarıyla silindi';
            break;
        
        // ----------------------------- ASKERLİK -----------------------------
        case 'askerlik':
            $kayit = $database->fetch("SELECT id FROM personel_askerlik WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Askerlik kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM personel_askerlik WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Askerlik kaydı başarıyla silindi';
            break;
        
        // ----------------------------- MEB DIŞI HİZMET -----------------------------
        case 'mebdisi':
        case 'meb_disi':
            $kayit = $database->fetch("SELECT id FROM personel_meb_disi_hizmet WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('MEB dışı hizmet kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM personel_meb_disi_hizmet WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'MEB dışı hizmet kaydı başarıyla silindi';
            break;
        
        // ----------------------------- DOSYA TAKİBİ -----------------------------
        case 'dosyatakip':
        case 'dosya_takip':
            $kayit = $database->fetch("SELECT id FROM personel_dosya_takip WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Dosya takip kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM personel_dosya_takip WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Dosya takip kaydı başarıyla silindi';
            break;
        
        // ----------------------------- PERSONEL -----------------------------
        case 'personel':
            $kayit = $database->fetch("SELECT id FROM personel WHERE id = ?", [$id]);
            
            if (!$kayit) {
                throw new Exception('Personel kaydı bulunamadı');
            }
            
            $stmt = $db->prepare("DELETE FROM personel WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
            $message = 'Personel kaydı başarıyla silindi';
            break;
        
        // ----------------------------- GEÇERSİZ MODÜL -----------------------------
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Geçersiz modül. Desteklenen modüller: emeklilik, gorev, kadro, ogrenim, ayrilma, dosya, izin, odul, askerlik, mebdisi, dosyatakip, personel'
            ]);
            exit;
    }
    
    // Başarılı yanıt
    if ($success) {
        // Loglama
        error_log("Silme işlemi - Modül: $modul, ID: $id, Kullanıcı: " . SessionManager::getUsername());
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'silinen_id' => $silinen_id,
            'modul' => $modul
        ]);
    } else {
        throw new Exception('Silme işlemi başarısız oldu');
    }
    
} catch (PDOException $e) {
    error_log("Silme hatası ($modul): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Silme hatası ($modul): " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>