<?php
// is_emri_duzenle.php

require_once 'includes/auth_check.php';
require_once 'config/database.php';

$errors = [];
$is_emri = null;
$id = $_GET['id'] ?? null;

// ID'nin geçerli olup olmadığını kontrol et
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Geçersiz iş emri ID'si.";
    header("Location: index.php");
    exit();
}

// Form gönderildi mi (POST isteği)?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $id = $_POST['id'];
    $is_emri_no = trim($_POST['is_emri_no']);
    $parti_no = trim($_POST['parti_no']);
    $musteri_id = $_POST['musteri_id'] ?: null; // Boşsa NULL yap
    $urun_id = $_POST['urun_id'];
    $siparis_miktari = $_POST['siparis_miktari'];
    $oncelik = $_POST['oncelik'];
    $genel_durum = $_POST['genel_durum']; // Durum bilgisini de alalım
    $teslim_tarihi = $_POST['teslim_tarihi'];

    // Doğrulama
    if (empty($is_emri_no)) $errors[] = "İş emri numarası zorunludur.";
    if (empty($urun_id)) $errors[] = "Bir ürün seçmelisiniz.";
    if (empty($siparis_miktari) || !is_numeric($siparis_miktari) || $siparis_miktari <= 0) $errors[] = "Geçerli bir sipariş miktarı girin.";
    if (empty($teslim_tarihi)) $errors[] = "Teslim tarihi zorunludur.";

    // Hata yoksa güncelle
    if (empty($errors)) {
        $stmt = $mysqli->prepare(
            "UPDATE is_emirleri SET is_emri_no = ?, parti_no = ?, musteri_id = ?, urun_id = ?, siparis_miktari = ?, oncelik = ?, genel_durum = ?, teslim_tarihi = ? WHERE id = ?"
        );
        $stmt->bind_param("sssiisssi", 
            $is_emri_no, $parti_no, $musteri_id, $urun_id, $siparis_miktari, $oncelik, $genel_durum, $teslim_tarihi, $id
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "İş emri başarıyla güncellendi!";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Veritabanı hatası: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Sayfa ilk yüklendiğinde (GET isteği) iş emri bilgilerini çek
$stmt = $mysqli->prepare("SELECT * FROM is_emirleri WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $is_emri = $result->fetch_assoc();
} else {
    $_SESSION['error_message'] = "İş emri bulunamadı.";
    header("Location: index.php");
    exit();
}
$stmt->close();

// Dropdown'lar için müşteri ve ürün listelerini çek
$musteriler_result = $mysqli->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi ASC");
$urunler_result = $mysqli->query("SELECT id, urun_adi, urun_kodu FROM urunler ORDER BY urun_adi ASC");

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3>İş Emrini Düzenle - #<?php echo htmlspecialchars($is_emri['is_emri_no']); ?></h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="is_emri_duzenle.php?id=<?php echo $id; ?>" method="POST">
                    <input type="hidden" name="id" value="<?php echo $is_emri['id']; ?>">

                    <div class="mb-3">
                        <label for="is_emri_no" class="form-label">İş Emri Numarası</label>
                        <input type="text" class="form-control" id="is_emri_no" name="is_emri_no" value="<?php echo htmlspecialchars($is_emri['is_emri_no']); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="parti_no" class="form-label">Parti Numarası (İsteğe Bağlı)</label>
                        <input type="text" class="form-control" id="parti_no" name="parti_no" value="<?php echo htmlspecialchars($is_emri['parti_no']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="musteri_id" class="form-label">Müşteri (İsteğe Bağlı)</label>
                        <select class="form-select" id="musteri_id" name="musteri_id">
                            <option value="">-- Müşteri Seçin --</option>
                            <?php while($musteri = $musteriler_result->fetch_assoc()): ?>
                                <option value="<?php echo $musteri['id']; ?>" <?php if($is_emri['musteri_id'] == $musteri['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($musteri['firma_adi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="urun_id" class="form-label">Ürün</label>
                        <select class="form-select" id="urun_id" name="urun_id" required>
                            <option value="">-- Ürün Seçin --</option>
                             <?php while($urun = $urunler_result->fetch_assoc()): ?>
                                <option value="<?php echo $urun['id']; ?>" <?php if($is_emri['urun_id'] == $urun['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($urun['urun_kodu'] . ' - ' . $urun['urun_adi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                             <label for="siparis_miktari" class="form-label">Sipariş Miktarı</label>
                            <input type="number" class="form-control" id="siparis_miktari" name="siparis_miktari" value="<?php echo htmlspecialchars($is_emri['siparis_miktari']); ?>" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="genel_durum" class="form-label">Genel Durum</label>
                            <select class="form-select" id="genel_durum" name="genel_durum" required>
                                <?php $durumlar = ['Planlandı', 'Üretimde', 'Kalite Kontrol', 'Tamamlandı', 'İptal Edildi']; ?>
                                <?php foreach($durumlar as $durum): ?>
                                <option value="<?php echo $durum; ?>" <?php if($is_emri['genel_durum'] == $durum) echo 'selected'; ?>><?php echo $durum; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="oncelik" class="form-label">Öncelik</label>
                            <select class="form-select" id="oncelik" name="oncelik" required>
                                <?php $oncelikler = ['Düşük', 'Normal', 'Yüksek', 'Acil']; ?>
                                <?php foreach($oncelikler as $oncelik): ?>
                                <option value="<?php echo $oncelik; ?>" <?php if($is_emri['oncelik'] == $oncelik) echo 'selected'; ?>><?php echo $oncelik; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teslim_tarihi" class="form-label">Planlanan Teslim Tarihi</label>
                            <input type="date" class="form-control" id="teslim_tarihi" name="teslim_tarihi" value="<?php echo htmlspecialchars($is_emri['teslim_tarihi']); ?>" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                         <a href="index.php" class="btn btn-secondary">İptal</a>
                         <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$mysqli->close();
require_once 'includes/footer.php'; // Gerçek bir footer'ımız olsaydı.. şimdilik script'leri ekleyelim.
?>
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>