<?php
class SessionManager {
    private static $sessionStarted = false;
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
            self::$sessionStarted = true;
            
            // Session fixation koruması
            if (empty($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 3600) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public static function requireAuth() {
        self::start();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            self::setMessage('warning', 'Lütfen tekrar giriş yapın.');
            header('Location: login.php');
            exit;
        }
        
        $timeout = 1800; 
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            self::destroy();
            header('Location: login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
    
    public static function getUsername() {
        self::start();
        return $_SESSION['username'] ?? '';
    }
    
    public static function getUserId() {
        self::start();
        return $_SESSION['user_id'] ?? 0;
    }
    
    public static function generateCSRFToken($formName = 'default') {
        self::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$formName] = [
            'token' => $token,
            'created' => time()
        ];
        
        self::cleanOldCSRFTokens();
        return $token;
    }
    
    public static function verifyCSRFToken($token, $formName = 'default', $maxAge = 3600) {
        self::start();
        
        if (!isset($_SESSION['csrf_tokens'][$formName])) {
            return false;
        }
        
        $storedToken = $_SESSION['csrf_tokens'][$formName];
        
        if (time() - $storedToken['created'] > $maxAge) {
            unset($_SESSION['csrf_tokens'][$formName]);
            return false;
        }
        
        $isValid = hash_equals($storedToken['token'], $token);
        
        if ($isValid) {
            unset($_SESSION['csrf_tokens'][$formName]);
        }
        
        return $isValid;
    }
    
    private static function cleanOldCSRFTokens() {
        $maxAge = 3600;
        // DÜZELTME: $_SESSION['csrf_tokens'] tanımlı değilse hata vermemesi için (array) eklendi
        foreach ((array)($_SESSION['csrf_tokens'] ?? []) as $formName => $tokenData) {
            if (time() - $tokenData['created'] > $maxAge) {
                unset($_SESSION['csrf_tokens'][$formName]);
            }
        }
    }
    
    public static function setMessage($type, $message) {
        self::start();
        $_SESSION['flash_messages'][$type] = $message;
    }

    public static function getMessage($type) {
        self::start();
        $message = $_SESSION['flash_messages'][$type] ?? '';
        
        if (isset($_SESSION['flash_messages'][$type])) {
            unset($_SESSION['flash_messages'][$type]);
            if (empty($_SESSION['flash_messages'])) {
                unset($_SESSION['flash_messages']);
            }
        }
        
        return $message;
    }

    public static function clearMessage($type) {
        self::start();
        if (isset($_SESSION['flash_messages'][$type])) {
            unset($_SESSION['flash_messages'][$type]);
        }
        if (empty($_SESSION['flash_messages'])) {
            unset($_SESSION['flash_messages']);
        }
    }

    public static function getAllMessages() {
        self::start();
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }   
    
    public static function setAktifTC($tc) {
        self::start();
        $_SESSION['aktif_tc'] = $tc;
    }
    
    public static function getAktifTC() {
        self::start();
        return $_SESSION['aktif_tc'] ?? '';
    }
    
    public static function setAktifPersonelID($id) {
        self::start();
        $_SESSION['aktif_personel_id'] = $id;
    }
    
    public static function getAktifPersonelID() {
        self::start();
        return $_SESSION['aktif_personel_id'] ?? 0;
    }
    
    public static function addToSearchHistory($tc, $adSoyadi) {
        self::start();
        if (!isset($_SESSION['search_history'])) {
            $_SESSION['search_history'] = [];
        }
        
        $existingIndex = -1;
        foreach ($_SESSION['search_history'] as $index => $item) {
            if ($item['tc'] === $tc) {
                $existingIndex = $index;
                break;
            }
        }
        
        $newItem = [
            'tc' => $tc,
            'ad_soyadi' => $adSoyadi,
            'timestamp' => time()
        ];
        
        if ($existingIndex !== -1) {
            array_splice($_SESSION['search_history'], $existingIndex, 1);
        }
        
        array_unshift($_SESSION['search_history'], $newItem);
        $_SESSION['search_history'] = array_slice($_SESSION['search_history'], 0, 10);
    }
    
    public static function getSearchHistory() {
        self::start();
        return $_SESSION['search_history'] ?? [];
    }
    
    public static function clearSearchHistory() {
        self::start();
        unset($_SESSION['search_history']);
    }
    
    public static function setUserSetting($key, $value) {
        self::start();
        $_SESSION['user_settings'][$key] = $value;
    }
    
    public static function getUserSetting($key, $default = null) {
        self::start();
        return $_SESSION['user_settings'][$key] ?? $default;
    }
    
    public static function destroy() {
        self::start();
        // DÜZELTME: Session verilerini tamamen temizleyip sonra yok ediyoruz
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$sessionStarted = false;
    }
    
    public static function unset($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function clearAll() {
        self::start();
        session_unset();
    }

    public static function clearMessages() {
        self::start();
        if (isset($_SESSION['flash_messages'])) {
            unset($_SESSION['flash_messages']);
        }
    }   

    public static function getSessionInfo() {
        self::start();
        return [
            'session_id' => session_id(),
            'created' => $_SESSION['created'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ];
    }
}
?>