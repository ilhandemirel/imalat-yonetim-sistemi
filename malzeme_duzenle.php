<?php
// malzeme_duzenle.php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$errors = [];
$id = $_GET['id'] ?? null;

if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Geçersiz ID.";
    header("Location: malzemeler_liste.php");
    exit();
}

// Form gönderildi mi?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $malzeme_kodu = trim($_POST['malzeme_kodu']);
    $malzeme_adi = trim($_POST['malzeme_adi']);
    $tedarikci = trim($_POST['tedarikci']);
    $sertifika_no = trim($_POST['sertifika_no']);
    $birim = trim($_POST['birim']);
    $stok_miktari = $_POST['stok_miktari'];

    if (empty($malzeme_kodu) || empty($malzeme_adi) || empty($birim)) {
        $errors[] = "Malzeme kodu, adı ve birim alanları zorunludur.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("UPDATE malzemeler SET malzeme_kodu = ?, malzeme_adi = ?, tedarikci = ?, sertifika_no = ?, birim = ?, stok_miktari = ? WHERE id = ?");
        $stmt->bind_param("sssssdi", $malzeme_kodu, $malzeme_adi, $tedarikci, $sertifika_no, $birim, $stok_miktari, $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Malzeme başarıyla güncellendi.";
            $_SESSION['success_page'] = 'malzemeler_liste.php';
            header("Location: malzemeler_liste.php");
            exit();
        } else {
            $errors[] = "Veritabanı hatası: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Sayfa ilk yüklendiğinde malzeme bilgilerini çek
$stmt = $mysqli->prepare("SELECT * FROM malzemeler WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $malzeme = $result->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Malzeme bulunamadı.";
    header("Location: malzemeler_liste.php");
    exit();
}
$stmt->close();

require_once 'includes/header.php';
?>
<div class="row">
    <div class="col-md-6 mx-auto">
        <h3>Malzemeyi Düzenle</h3>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p>$error</p>"; ?></div>
        <?php endif; ?>
        <form action="malzeme_duzenle.php?id=<?php echo $id; ?>" method="POST">
            <div class="mb-3"><label for="malzeme_kodu" class="form-label">Malzeme Kodu</label><input type="text" class="form-control" name="malzeme_kodu" value="<?php echo htmlspecialchars($malzeme['malzeme_kodu']); ?>" required></div>
            <div class="mb-3"><label for="malzeme_adi" class="form-label">Malzeme Adı</label><input type="text" class="form-control" name="malzeme_adi" value="<?php echo htmlspecialchars($malzeme['malzeme_adi']); ?>" required></div>
            <div class="mb-3"><label for="tedarikci" class="form-label">Tedarikçi</label><input type="text" class="form-control" name="tedarikci" value="<?php echo htmlspecialchars($malzeme['tedarikci']); ?>"></div>
            <div class="mb-3"><label for="sertifika_no" class="form-label">Sertifika No</label><input type="text" class="form-control" name="sertifika_no" value="<?php echo htmlspecialchars($malzeme['sertifika_no']); ?>"></div>
            <div class="mb-3"><label for="birim" class="form-label">Birim</label><input type="text" class="form-control" name="birim" value="<?php echo htmlspecialchars($malzeme['birim']); ?>" required></div>
            <div class="mb-3"><label for="stok_miktari" class="form-label">Mevcut Stok</label><input type="number" step="0.01" class="form-control" name="stok_miktari" value="<?php echo htmlspecialchars($malzeme['stok_miktari']); ?>" required></div>
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="malzemeler_liste.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>
<?php $mysqli->close(); ?>
</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html> 