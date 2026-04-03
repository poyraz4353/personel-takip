<?php
/**
 * AJAX Kimlik Kaydetme - Personel Kimlik Bilgileri
 * @version 2.7
 * @author Fatih
 */

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/db_config.php';

SessionManager::start();
header('Content-Type: application/json');

// 1. Sadece AJAX isteklerini kabul et
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek tipi']);
    exit;
}

// 2. Sadece POST kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek methodu']);
    exit;
}

// 3. Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // 4. Gerekli alanları kontrol et
    $personel_id = $_POST['personel_id'] ?? 0;
    
    if (empty($personel_id)) {
        echo json_encode(['success' => false, 'error' => 'Personel ID eksik']);
        exit;
    }
    
    // DEBUG: Gelen verileri logla
    error_log("=== AJAX KAYDET_KİMLİK DEBUG ===");
    error_log("Personel ID: " . $personel_id);
    error_log("POST Data: " . print_r($_POST, true));
    
    // 5. Temizlenmiş veriler
    $clean_data = [
        'baba_adi' => !empty($_POST['baba_adi']) ? trim($_POST['baba_adi']) : null,
        'dogum_yeri' => !empty($_POST['dogum_yeri']) ? trim($_POST['dogum_yeri']) : null,
        'dogum_tarihi' => !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null,
        'medeni_durum' => !empty($_POST['medeni_durum']) ? trim($_POST['medeni_durum']) : null,
        'kan_grubu' => !empty($_POST['kan_grubu']) ? trim($_POST['kan_grubu']) : null,
        'cinsiyeti' => !empty($_POST['cinsiyeti']) ? trim($_POST['cinsiyeti']) : null
    ];
    
    // 6. Mevcut kayıt kontrolü (personel_kimlik tablosunda)
    $check_sql = "SELECT id FROM personel_kimlik WHERE personel_id = ?";
    $existing = $database->fetch($check_sql, [$personel_id]);
    
    if ($existing) {
        // 7. GÜNCELLEME (personel_kimlik tablosunu güncelle)
        $update_sql = "UPDATE personel_kimlik SET 
            baba_adi = :baba_adi,
            dogum_yeri = :dogum_yeri,
            dogum_tarihi = :dogum_tarihi,
            medeni_durum = :medeni_durum,
            kan_grubu = :kan_grubu,
            cinsiyeti = :cinsiyeti,
            guncelleme_tarihi = NOW()
            WHERE personel_id = :personel_id";
        
        $clean_data['personel_id'] = $personel_id;
        
        // Doğrudan PDO ile çalış
        $stmt = $db->prepare($update_sql);
        $result = $stmt->execute($clean_data);
        
        $affected_rows = $stmt->rowCount();
        error_log("UPDATE Sonucu: " . ($result ? 'BAŞARILI' : 'BAŞARISIZ'));
        error_log("Etkilenen satır: " . $affected_rows);
        
        if ($result && $affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Kimlik bilgileri başarıyla güncellendi',
                'affected_rows' => $affected_rows
            ]);
        } else {
            // Değişiklik yoksa da başarılı say
            if ($affected_rows === 0) {
                error_log("Güncelleme başarılı ama değişiklik yok (veriler aynı)");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Kimlik bilgileri güncel (değişiklik yapılmadı)',
                    'affected_rows' => 0
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Güncelleme başarısız']);
            }
        }
        
    } else {
        // 8. YENİ KAYIT (personel_kimlik tablosuna ekle)
        $insert_sql = "INSERT INTO personel_kimlik 
            (personel_id, baba_adi, dogum_yeri, dogum_tarihi, 
             medeni_durum, kan_grubu, cinsiyeti, kayit_tarihi)
            VALUES (:personel_id, :baba_adi, :dogum_yeri, :dogum_tarihi, 
                    :medeni_durum, :kan_grubu, :cinsiyeti, NOW())";
        
        $clean_data['personel_id'] = $personel_id;
        
        // Doğrudan PDO ile çalış
        $stmt = $db->prepare($insert_sql);
        $result = $stmt->execute($clean_data);
        
        $last_insert_id = $db->lastInsertId();
        error_log("INSERT Sonucu: " . ($result ? 'BAŞARILI' : 'BAŞARISIZ'));
        error_log("Yeni kayıt ID: " . $last_insert_id);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Kimlik bilgileri başarıyla kaydedildi',
                'insert_id' => $last_insert_id
            ]);
        } else {
            $error_info = $stmt->errorInfo();
            echo json_encode([
                'success' => false, 
                'error' => 'Kayıt başarısız: ' . $error_info[2]
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("AJAX Kayıt hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Genel hata: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Sistem hatası: ' . $e->getMessage()]);
}
?>