<?php
// kullanici_duzenle.php
require_once 'includes/admin_check.php';
require_once 'config/database.php';

$errors = [];
$id = $_GET['id'] ?? null;
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    header("Location: kullanicilar_liste.php"); exit();
}

// Form gönderildi mi?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_soyad = trim($_POST['ad_soyad']);
    $eposta = trim($_POST['eposta']);
    $rol = $_POST['rol'];
    $yeni_sifre = $_POST['yeni_sifre'];

    // Şifre alanı sadece doluysa güncellenecek
    if (!empty($yeni_sifre)) {
        $hashed_sifre = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE kullanicilar SET ad_soyad = ?, eposta = ?, rol = ?, sifre = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $ad_soyad, $eposta, $rol, $hashed_sifre, $id);
    } else {
        // Şifre boşsa, şifre alanını GÜNCELLEME
        $stmt = $mysqli->prepare("UPDATE kullanicilar SET ad_soyad = ?, eposta = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("sssi", $ad_soyad, $eposta, $rol, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Kullanıcı bilgileri başarıyla güncellendi.";
        header("Location: kullanicilar_liste.php");
        exit();
    } else {
        $errors[] = "Güncelleme sırasında bir hata oluştu: " . $stmt->error;
    }
    $stmt->close();
}

// Sayfa ilk yüklendiğinde kullanıcı bilgilerini çek
$stmt = $mysqli->prepare("SELECT id, kullanici_adi, ad_soyad, eposta, rol FROM kullanicilar WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header("Location: kullanicilar_liste.php"); exit();
}
$stmt->close();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <h3>Kullanıcıyı Düzenle: <?php echo htmlspecialchars($user['kullanici_adi']); ?></h3>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p>$error</p>"; ?></div>
        <?php endif; ?>

        <form action="kullanici_duzenle.php?id=<?php echo $id; ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['kullanici_adi']); ?>" disabled>
                <div class="form-text">Kullanıcı adı güvenlik nedeniyle değiştirilemez.</div>
            </div>
             <div class="mb-3"><label for="ad_soyad" class="form-label">Ad Soyad</label><input type="text" class="form-control" id="ad_soyad" name="ad_soyad" value="<?php echo htmlspecialchars($user['ad_soyad']); ?>" required></div>
             <div class="mb-3"><label for="eposta" class="form-label">E-posta</label><input type="email" class="form-control" id="eposta" name="eposta" value="<?php echo htmlspecialchars($user['eposta']); ?>" required></div>
             <div class="mb-3">
                 <label for="rol" class="form-label">Rol</label>
                 <select class="form-select" id="rol" name="rol">
                     <option value="admin" <?php if($user['rol'] == 'admin') echo 'selected'; ?>>Admin</option>
                     <option value="planlamaci" <?php if($user['rol'] == 'planlamaci') echo 'selected'; ?>>Planlamacı</option>
                     <option value="operator" <?php if($user['rol'] == 'operator') echo 'selected'; ?>>Operatör</option>
                 </select>
             </div>
             <hr>
             <p>Şifreyi değiştirmek istemiyorsanız bu alanı boş bırakın.</p>
             <div class="mb-3"><label for="yeni_sifre" class="form-label">Yeni Şifre</label><input type="password" class="form-control" id="yeni_sifre" name="yeni_sifre"></div>
             
             <button type="submit" class="btn btn-primary">Güncelle</button>
             <a href="kullanicilar_liste.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 