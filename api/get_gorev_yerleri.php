<?php
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['ilce_id'])) {
        throw new Exception("İlçe ID gereklidir");
    }
    
    $ilceId = filter_var($_GET['ilce_id'], FILTER_VALIDATE_INT);
    if (!$ilceId) {
        throw new Exception("Geçersiz ilçe ID");
    }
    
    $kapaliKurum = isset($_GET['kapali_kurum']) ? (int)$_GET['kapali_kurum'] : 0;
    
    $db = Database::getInstance();
    
    // Gerçek uygulamada kurumlar/kuruluşlar tablosundan çekilecek
    // Şimdilik örnek veri döndürelim
    $sql = "SELECT 
                id,
                kurum_adi as gorev_yeri,
                kurum_kodu
            FROM kurumlar 
            WHERE ilce_id = ? 
            AND (kapali = 0 OR ? = 1)
            ORDER BY kurum_adi";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$ilceId, $kapaliKurum]);
    $gorevYerleri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eğer tablo yoksa örnek veri döndür
    if (empty($gorevYerleri)) {
        $gorevYerleri = [
            ['id' => 1, 'gorev_yeri' => 'Merkez Lisesi', 'kurum_kodu' => 'ML001'],
            ['id' => 2, 'gorev_yeri' => 'İlçe Milli Eğitim Müdürlüğü', 'kurum_kodu' => 'MEM001'],
            ['id' => 3, 'gorev_yeri' => 'Anaokulu', 'kurum_kodu' => 'AK001']
        ];
        
        if ($kapaliKurum) {
            $gorevYerleri[] = ['id' => 4, 'gorev_yeri' => 'Kapalı Kurum Örneği', 'kurum_kodu' => 'KK001'];
        }
    }
    
    echo json_encode($gorevYerleri);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>