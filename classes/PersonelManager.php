<?php
/**
 * PERSONEL YÖNETİM SINIFI
 * Personel ekleme, güncelleme ve doğrulama işlemleri
 */

class PersonelManager {
    private $db;
    private $errors = [];

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Personel ekleme işlemi
     */
    public function addPersonel($cleanData, $files) {
        try {
            $this->db->beginTransaction();

            // Temel doğrulamalar
            $this->validateRequiredFields($cleanData);
            $this->validateTCNo($cleanData['tc_no']);
            $this->validateEmail($cleanData['email'] ?? '');
            $this->validatePhone($cleanData['telefon'] ?? '');

            // TC kimlik kontrolü
            if ($this->isDuplicateTC($cleanData['tc_no'], $cleanData['ad_soyadi'])) {
                throw new Exception('Bu TC numarası ile kayıtlı personel bulunmaktadır');
            }

            // Fotoğraf işleme
            $photoFileName = $this->processPhoto($files['foto'] ?? [], $cleanData['tc_no'], $cleanData['ad_soyadi']);

            // Ana personel kaydı
            $personel_id = $this->insertPersonel($cleanData, $photoFileName);
            
            // İlişkili kayıtlar
            $this->insertKimlikBilgileri($personel_id, $cleanData);
            $this->insertIletisimBilgileri($personel_id, $cleanData);
            $this->insertGorevBilgileri($personel_id, $cleanData);
            $this->insertKadroBilgileri($personel_id, $cleanData);
            $this->insertSozlesmeBilgileri($personel_id, $cleanData);
            $this->insertEgitimBilgileri($personel_id, $cleanData);
            $this->insertOncekiKurum($personel_id, $cleanData);

            $this->db->commit();
            return $personel_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Zorunlu alanları kontrol et
     */
    private function validateRequiredFields($data) {
        $required = [
            'tc_no', 'ad_soyadi', 'dogum_tarihi', 'cinsiyeti',
            'gorev_il_id', 'gorev_ilce_id', 'gorev_okul_id',
            'hizmet_sinifi', 'istihdam_tipi', 'kadro_unvani', 
            'gorev_unvani', 'durum', 'yer_degistirme_cesidi'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("{$field} alanı zorunludur");
            }
        }
    }

    /**
     * TC kimlik doğrulama
     */
    private function validateTCNo($tcno) {
        if (!validateTCNo($tcno)) {
            throw new Exception('Geçersiz TC Kimlik Numarası');
        }
    }

    /**
     * Email doğrulama
     */
    private function validateEmail($email) {
        if (!empty($email) && !validateEmail($email)) {
            throw new Exception('Geçersiz email adresi');
        }
    }

    /**
     * Telefon doğrulama
     */
    private function validatePhone($phone) {
        if (!empty($phone) && !validatePhone($phone)) {
            throw new Exception('Geçersiz telefon numarası');
        }
    }

    /**
     * TC kimlik çakışması kontrolü
     */
    private function isDuplicateTC($tc_no, $ad_soyadi) {
        $stmt = $this->db->prepare("SELECT id, ad_soyadi FROM personel WHERE tc_no = ?");
        $stmt->execute([$tc_no]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (mb_strtolower(trim($existing['ad_soyadi']), 'UTF-8') === mb_strtolower(trim($ad_soyadi), 'UTF-8')) {
                throw new Exception("Bu TC numarası ile <strong>{$existing['ad_soyadi']}</strong> zaten kayıtlı");
            } else {
                throw new Exception("Bu TC numarası başka bir kişiyle eşleşiyor: <strong>{$existing['ad_soyadi']}</strong>");
            }
        }

        return false;
    }

    /**
     * Fotoğraf işleme
     */
    private function processPhoto($file, $tc_no, $ad_soyadi) {
        if (!empty($file['name'])) {
            $photoFileName = processUploadedPhoto($file, $tc_no, $ad_soyadi);
            if (!$photoFileName) {
                throw new Exception('Fotoğraf yüklenemedi veya geçersiz dosya');
            }
            return $photoFileName;
        }
        return null;
    }

    /**
     * Ana personel kaydı
     */
    private function insertPersonel($data, $photoFileName) {
        $sql = "INSERT INTO personel (
            tc_no, ad_soyadi, emekli_sicil_no, kurum_sicil_no, 
            arsiv_no, raf_no, il_id, ilce_id, kurum_kodu, okul_tur,
            created_at, updated_at, foto_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['tc_no'], 
            $data['ad_soyadi'], 
            $data['emekli_sicil_no'] ?? '',
            $data['kurum_sicil_no'] ?? '',
            $data['arsiv_no'] ?? '',
            $data['raf_no'] ?? '',
            $data['gorev_il_id'] ?? null,
            $data['gorev_ilce_id'] ?? null,
            $data['gorev_kurum_kodu'] ?? '',
            $data['gorev_okul_tur'] ?? '',
            $photoFileName
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Kimlik bilgileri
     */
    private function insertKimlikBilgileri($personel_id, $data) {
        if (!empty($data['baba_adi']) || !empty($data['dogum_tarihi']) || !empty($data['cinsiyeti'])) {
            $sql = "INSERT INTO personel_kimlik (
                personel_id, baba_adi, dogum_tarihi, dogum_yeri, 
                cinsiyeti, medeni_durum, kan_grubu,
                kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $data['baba_adi'] ?? '',
                $data['dogum_tarihi'] ?? '',
                $data['dogum_yeri'] ?? '',
                $data['cinsiyeti'] ?? '',
                $data['medeni_durum'] ?? '',
                $data['kan_grubu'] ?? ''
            ]);
        }
    }

    /**
     * İletişim bilgileri
     */
    private function insertIletisimBilgileri($personel_id, $data) {
        if (!empty($data['telefon']) || !empty($data['email']) || !empty($data['ikametgah_adresi'])) {
            $sql = "INSERT INTO personel_iletisim (
                personel_id, telefon, email, ev_adresi, ikametgah_adresi,
                il_id, ilce_id, posta_kodu, kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $data['telefon'] ?? '',
                $data['email'] ?? '',
                $data['ikametgah_adresi'] ?? '',
                $data['ikametgah_adresi'] ?? '',
                $data['gorev_il_id'] ?? null,
                $data['gorev_ilce_id'] ?? null,
                ''
            ]);
        }
    }

    /**
     * Görev bilgileri
     */
    private function insertGorevBilgileri($personel_id, $data) {
        $sql = "INSERT INTO personel_gorev (
            personel_id, istihdam_tipi, hizmet_sinifi, kadro_unvani, 
            gorev_unvani, kariyer_basamagi, atama_alani, atama_cesidi,
            memuriyete_baslama_tarihi, kurum_baslama_tarihi, durum,
            yer_degistirme_cesidi, gorev_aciklama, gorev_il_id, 
            gorev_ilce_id, gorev_okul_id, gorev_kurum_kodu, gorev_okul_tur, 
            gorev_kapali_kurum, kayit_tarihi, guncelleme_tarihi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $personel_id,
            $data['istihdam_tipi'] ?? '',
            $data['hizmet_sinifi'] ?? '',
            $data['kadro_unvani'] ?? '',
            $data['gorev_unvani'] ?? '',
            $data['kariyer_basamagi'] ?? '',
            $data['atama_alani'] ?? '',
            $data['atama_cesidi'] ?? '',
            $data['memuriyete_baslama_tarihi'] ?? '',
            $data['kurum_baslama_tarihi'] ?? '',
            $data['durum'] ?? 'Görevde',
            $data['yer_degistirme_cesidi'] ?? '',
            $data['gorev_aciklama'] ?? '',
            $data['gorev_il_id'] ?? null,
            $data['gorev_ilce_id'] ?? null,
            $data['gorev_okul_id'] ?? null,
            $data['gorev_kurum_kodu'] ?? '',
            $data['gorev_okul_tur'] ?? '',
            !empty($data['gorev_kapali_kurum']) ? 1 : 0
        ]);
    }

    /**
     * Kadro bilgileri
     */
    private function insertKadroBilgileri($personel_id, $data) {
        if (!empty($data['terfi_tarihi']) || !empty($data['kadro_derecesi'])) {
            $sql = "INSERT INTO personel_kadro (
                personel_id, terfi_tarihi, terfi_nedeni, kadro_derecesi, 
                aylik_derece_kademe, kha_ek_gosterge,
                kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $data['terfi_tarihi'] ?? '',
                $data['terfi_nedeni'] ?? '',
                $data['kadro_derecesi'] ?? '',
                $data['aylik_derece_kademe'] ?? '',
                $data['kha_ek_gosterge'] ?? ''
            ]);
        }
    }

    /**
     * Sözleşme bilgileri
     */
    private function insertSozlesmeBilgileri($personel_id, $data) {
        if (!empty($data['sozlesme_il_id']) || !empty($data['sozlesme_baslangic'])) {
            $sql = "INSERT INTO personel_sozlesme (
                personel_id, sozlesme_il_id, sozlesme_ilce_id, sozlesme_okul_id, 
                sozlesme_kurum_kodu, sozlesme_okul_tur, sozlesme_kapali_kurum,
                sozlesme_turu, sozlesmeli_baslama_tarihi, sozlesmeli_bitis_tarihi, 
                sozlesme_suresi, sozlesme_aciklama, kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $data['sozlesme_il_id'] ?? null,
                $data['sozlesme_ilce_id'] ?? null,
                $data['sozlesme_okul_id'] ?? null,
                $data['sozlesme_kurum_kodu'] ?? '',
                $data['sozlesme_okul_tur'] ?? '',
                !empty($data['sozlesme_kapali_kurum']) ? 1 : 0,
                $data['sozlesme_turu'] ?? '',
                $data['sozlesme_baslangic'] ?? '',
                $data['sozlesme_bitis'] ?? '',
                $data['sozlesme_suresi'] ?? '',
                $data['sozlesme_aciklama'] ?? ''
            ]);
        }
    }

    /**
     * Eğitim bilgileri
     */
    private function insertEgitimBilgileri($personel_id, $data) {
        if (!empty($data['mezuniyet_tarihi']) || !empty($data['ogrenim_durumu'])) {
            $mezuniyet_yili = !empty($data['mezuniyet_tarihi']) ? 
                date('Y', strtotime($data['mezuniyet_tarihi'])) : null;
            
            $mezun_okul = !empty($data['mezun_okul']) ? $data['mezun_okul'] : $data['universite'] ?? '';
            
            $sql = "INSERT INTO personel_egitim (
                personel_id, mezun_okul, universite, fakulte, yuksek_okul, 
                bolum, mezuniyet_tarihi, mezuniyet_yili, belge_tarihi, 
                belge_no, belge_cinsi, belge_aciklama, ogrenim_durumu,
                kayit_tarihi, guncelleme_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $mezun_okul,
                $data['universite'] ?? '',
                $data['fakulte'] ?? '',
                $data['yuksek_okul'] ?? '',
                $data['bolum'] ?? '',
                $data['mezuniyet_tarihi'] ?? '',
                $mezuniyet_yili,
                $data['belge_tarihi'] ?? '',
                $data['belge_no'] ?? '',
                $data['belge_cinsi'] ?? '',
                $data['belge_aciklama'] ?? '',
                $data['ogrenim_durumu'] ?? ''
            ]);
        }
    }

    /**
     * Önceki kurum bilgileri
     */
    private function insertOncekiKurum($personel_id, $data) {
        if (!empty($data['geldigi_il_id']) || !empty($data['geldigi_ayrilma_tarihi'])) {
            $sql = "INSERT INTO personel_onceki_kurum (
                personel_id, il_id, ilce_id, kurum_kodu, okul_tur, 
                kapali_kurum, onceki_gorev_unvani_id, ayrilma_tarihi, 
                ayrilma_nedeni, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personel_id,
                $data['geldigi_il_id'] ?? null,
                $data['geldigi_ilce_id'] ?? null,
                $data['geldigi_kurum_kodu'] ?? '',
                $data['geldigi_okul_tur'] ?? '',
                !empty($data['geldigi_kapali_kurum']) ? 1 : 0,
                $data['geldigi_gorev_unvani'] ?? null,
                $data['geldigi_ayrilma_tarihi'] ?? '',
                $data['geldigi_ayrilma_nedeni'] ?? ''
            ]);
        }
    }

    /**
     * Hata mesajlarını getir
     */
    public function getErrors() {
        return $this->errors;
    }
}
?>