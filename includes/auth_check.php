<?php
// includes/auth_check.php

// Oturumun zaten başlatılıp başlatılmadığını kontrol et, değilse başlat.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcının giriş yapıp yapmadığını kontrol et.
// Eğer $_SESSION['logged_in'] değişkeni yoksa veya false ise, kullanıcı giriş yapmamıştır.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Kullanıcıyı bir uyarı mesajıyla birlikte giriş sayfasına yönlendir.
    $_SESSION['error_message'] = "Bu sayfayı görüntülemek için giriş yapmalısınız.";
    header("Location: login.php");
    exit;
}

// Bu dosya, dahil edildiği sayfanın geri kalanının çalışmasına sadece kullanıcı giriş yapmışsa izin verir.
?>