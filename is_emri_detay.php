<?php
// is_emri_detay.php

require_once 'includes/auth_check.php';
require_once 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Geçersiz ID.";
    header("Location: index.php");
    exit();
}

// 1. Ana İş Emri Bilgilerini Çek
$stmt = $mysqli->prepare("SELECT ie.*, m.firma_adi, u.urun_adi, u.urun_kodu, ie.parti_no 
                          FROM is_emirleri ie 
                          LEFT JOIN musteriler m ON ie.musteri_id = m.id
                          JOIN urunler u ON ie.urun_id = u.id
                          WHERE ie.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$is_emri_result = $stmt->get_result();
if ($is_emri_result->num_rows === 0) {
    $_SESSION['error_message'] = "İş emri bulunamadı.";
    header("Location: index.php");
    exit();
}
$is_emri = $is_emri_result->fetch_assoc();
$stmt->close();

// 2. İş Emri Operasyonlarını Çek
$operasyonlar_stmt = $mysqli->prepare("SELECT * FROM is_emri_operasyonlari WHERE is_emri_id = ? ORDER BY sira_no ASC");
$operasyonlar_stmt->bind_param("i", $id);
$operasyonlar_stmt->execute();
$operasyonlar_result = $operasyonlar_stmt->get_result();
$operasyonlar_stmt->close();

// 3. İş Emri Malzemelerini Çek (Varsa)
// Bu kısım ödevde istenmese de ürünleştirme için güzel bir adımdır. Şimdilik pas geçebiliriz veya ekleyebiliriz.

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>İş Emri Detayı: <?php echo htmlspecialchars($is_emri['is_emri_no']); ?></h4>
    <a href="index.php" class="btn btn-secondary">Geri Dön</a>
</div>

<?php
// Başarı veya hata mesajlarını göster
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Genel Bilgiler</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><b>İş Emri No:</b> <?php echo htmlspecialchars($is_emri['is_emri_no']); ?></li>
                <li class="list-group-item"><b>Parti No:</b> <?php echo htmlspecialchars($is_emri['parti_no'] ?: 'Belirtilmemiş'); ?></li>
                <li class="list-group-item"><b>Müşteri:</b> <?php echo $is_emri['firma_adi'] ? htmlspecialchars($is_emri['firma_adi']) : 'Belirtilmemiş'; ?></li>
                <li class="list-group-item"><b>Ürün:</b> <?php echo htmlspecialchars($is_emri['urun_kodu'] . ' - ' . $is_emri['urun_adi']); ?></li>
                <li class="list-group-item"><b>Miktar:</b> <?php echo htmlspecialchars($is_emri['siparis_miktari']); ?> adet</li>
                <li class="list-group-item"><b>Genel Durum:</b> <span class="badge bg-primary"><?php echo htmlspecialchars($is_emri['genel_durum']); ?></span></li>
                <li class="list-group-item"><b>Öncelik:</b> <?php echo htmlspecialchars($is_emri['oncelik']); ?></li>
                <li class="list-group-item"><b>Sipariş Tarihi:</b> <?php echo date("d.m.Y", strtotime($is_emri['siparis_tarihi'])); ?></li>
                <li class="list-group-item"><b>Teslim Tarihi:</b> <?php echo date("d.m.Y", strtotime($is_emri['teslim_tarihi'])); ?></li>
            </ul>
             <div class="card-footer">
                <a href="is_emri_duzenle.php?id=<?php echo $is_emri['id']; ?>" class="btn btn-warning w-100">Bu İş Emrini Düzenle</a>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Üretim Operasyonları</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Operasyon</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($operasyonlar_result->num_rows > 0): ?>
                            <?php while($op = $operasyonlar_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $op['sira_no']; ?></td>
                                    <td><?php echo htmlspecialchars($op['operasyon_adi']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($op['operasyon_durum']); ?></span></td>
                                    <td>
                                        <?php if($op['operasyon_durum'] == 'Bekliyor'): ?>
                                            <a href="operasyon_guncelle.php?op_id=<?php echo $op['id']; ?>&is_emri_id=<?php echo $id; ?>&action=baslat" class="btn btn-success btn-sm">Başlat</a>
                                        <?php elseif($op['operasyon_durum'] == 'Devam Ediyor'): ?>
                                             <a href="operasyon_guncelle.php?op_id=<?php echo $op['id']; ?>&is_emri_id=<?php echo $id; ?>&action=tamamla" class="btn btn-info btn-sm">Tamamla</a>
                                        <?php else: ?>
                                            <span class="text-muted">✓</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Bu iş emri için operasyon tanımlanmamış.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php require_once 'includes/footer_scripts.php'; // Bir sonraki adımda bunu oluşturabiliriz ?>
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>