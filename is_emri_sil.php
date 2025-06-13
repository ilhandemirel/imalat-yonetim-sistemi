<?php
// is_emri_sil.php

// Güvenlik: Silme işlemini sadece giriş yapmış kullanıcılar yapabilir.
require_once 'includes/auth_check.php';
// Veritabanı bağlantısı
require_once 'config/database.php';

// Silinecek kaydın ID'sini URL'den al (GET metodu).
$id = $_GET['id'] ?? null;

// Gelen ID'nin geçerli bir tamsayı olup olmadığını kontrol et. Bu, basit bir güvenlik önlemidir.
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    // Geçerli bir ID değilse, hata mesajı ver ve ana sayfaya yönlendir.
    $_SESSION['error_message'] = "Geçersiz iş emri ID'si.";
    header("Location: index.php");
    exit();
}

// Veritabanından kaydı silmek için Prepared Statement kullanalım.
// Bu, SQL Injection saldırılarına karşı en güvenli yöntemdir.
$stmt = $mysqli->prepare("DELETE FROM is_emirleri WHERE id = ?");
$stmt->bind_param("i", $id);

// Sorguyu çalıştır ve sonucu kontrol et.
if ($stmt->execute()) {
    // execute() başarılı olsa bile, bir kaydın gerçekten silinip silinmediğini
    // affected_rows ile kontrol etmek daha sağlıklıdır.
    if ($stmt->affected_rows > 0) {
        // Başarılı olursa, başarı mesajını oturuma kaydet.
        $_SESSION['success_message'] = "İş emri başarıyla silindi.";
    } else {
        // Kayıt bulunamadıysa (belki başka bir kullanıcı sildi), hata mesajı ver.
        $_SESSION['error_message'] = "Silinecek iş emri bulunamadı.";
    }
} else {
    // Sorgu çalışmazsa, veritabanı hatasını bildir.
    $_SESSION['error_message'] = "İş emri silinirken bir veritabanı hatası oluştu.";
}

// Statement ve veritabanı bağlantısını kapat.
$stmt->close();
$mysqli->close();

// İşlem ne olursa olsun, kullanıcıyı ana sayfaya geri yönlendir.
header("Location: index.php");
exit();

?>