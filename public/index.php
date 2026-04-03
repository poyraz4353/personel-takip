<?php
require_once '../config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Kullanıcı adı veya şifre yanlış.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Personel Takip - Giriş</title>
</head>
<body>
    <h2>Giriş Yap</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?=htmlspecialchars($error)?></p>
    <?php endif; ?>
    <form method="post">
        <label>Kullanıcı Adı:</label><br/>
        <input type="text" name="username" required /><br/>
        <label>Şifre:</label><br/>
        <input type="password" name="password" required /><br/><br/>
        <button type="submit">Giriş</button>
    </form>
</body>
</html>
