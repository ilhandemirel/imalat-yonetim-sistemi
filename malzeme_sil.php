<?php
// malzeme_sil.php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$id = $_GET['id'] ?? null;

if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Geçersiz ID.";
    header("Location: malzemeler_liste.php");
    exit();
}

// Veri Bütünlüğü Kontrolü: Bu malzeme herhangi bir reçetede kullanılıyor mu?
$check_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM is_emri_recetesi WHERE malzeme_id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if ($check_result['count'] > 0) {
    // Eğer kullanılıyorsa, silme işlemini engelle ve kullanıcıyı bilgilendir.
    $_SESSION['error_message'] = "Bu malzeme bir veya daha fazla iş emri reçetesinde kullanıldığı için silinemez.";
    header("Location: malzemeler_liste.php");
    exit();
}

// Eğer kullanılmıyorsa, silme işlemine devam et.
$stmt = $mysqli->prepare("DELETE FROM malzemeler WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Malzeme başarıyla silindi.";
    } else {
        $_SESSION['error_message'] = "Silinecek malzeme bulunamadı.";
    }
} else {
    $_SESSION['error_message'] = "Malzeme silinirken bir hata oluştu.";
}

$stmt->close();
$mysqli->close();

header("Location: malzemeler_liste.php");
exit();
?> 