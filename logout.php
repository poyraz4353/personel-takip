<?php
// logout.php – ÇALIŞAN VE KESİN ÇÖZÜM
require_once __DIR__ . '/config/session_manager.php';

// Session'ı tamamen yok et
session_start();
session_unset();
session_destroy();

// Cookie varsa sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Login sayfasına yönlendir
header("Location: login.php");
exit;
?>