<?php
// logout.php

session_start(); // Oturumu kullanabilmek için başlat.

// 1. Tüm oturum değişkenlerini temizle.
$_SESSION = array();

// 2. Oturum çerezini sil.
// Bu, oturumun tamamen yok edilmesini sağlar. Eğer sadece session_destroy()
// kullanılırsa, çerez tarayıcıda kalabilir.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Son olarak, oturumu yok et.
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir.
header("Location: login.php");
exit;
?>