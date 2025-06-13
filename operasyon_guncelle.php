<?php
// operasyon_guncelle.php

require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Gerekli parametreleri al ve doğrula
$op_id = $_GET['op_id'] ?? null;
$is_emri_id = $_GET['is_emri_id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$op_id || !$is_emri_id || !$action || !in_array($action, ['baslat', 'tamamla'])) {
    $_SESSION['error_message'] = "Geçersiz parametreler.";
    // Eğer iş emri ID'si varsa oraya, yoksa ana sayfaya yönlendir
    header("Location: " . ($is_emri_id ? "is_emri_detay.php?id=$is_emri_id" : "index.php"));
    exit();
}

$yeni_durum = '';
$zaman_kolonu = '';
if ($action == 'baslat') {
    $yeni_durum = 'Devam Ediyor';
    $zaman_kolonu = 'baslama_zamani = NOW()';
} elseif ($action == 'tamamla') {
    $yeni_durum = 'Tamamlandı';
    $zaman_kolonu = 'bitis_zamani = NOW()';
}

// Veritabanını güncelle
$stmt = $mysqli->prepare("UPDATE is_emri_operasyonlari SET operasyon_durum = ?, $zaman_kolonu WHERE id = ?");
$stmt->bind_param("si", $yeni_durum, $op_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Operasyon durumu güncellendi.";
} else {
    $_SESSION['error_message'] = "Güncelleme sırasında bir hata oluştu: " . $stmt->error;
}
$stmt->close();
$mysqli->close();

// Kullanıcıyı detay sayfasına geri yönlendir
header("Location: is_emri_detay.php?id=" . $is_emri_id);
exit();
?>