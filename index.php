<?php
// index.php

// Güvenlik: Bu sayfanın en başına yetki kontrolünü ekliyoruz.
require_once 'includes/auth_check.php';
// Veritabanı bağlantısı
require_once 'config/database.php';
// Şablon: Sayfanın üst kısmını (navigasyon barı vb.) dahil et.
require_once 'includes/header.php';

// --- DASHBOARD KARTLARI İÇİN VERİ ÇEKME ---
$total_result = $mysqli->query("SELECT COUNT(id) as total FROM is_emirleri");
$total_orders = $total_result->fetch_assoc()['total'];

$ongoing_result = $mysqli->query("SELECT COUNT(id) as ongoing FROM is_emirleri WHERE genel_durum IN ('Planlandı', 'Üretimde')");
$ongoing_orders = $ongoing_result->fetch_assoc()['ongoing'];

$completed_result = $mysqli->query("SELECT COUNT(id) as completed FROM is_emirleri WHERE genel_durum = 'Tamamlandı'");
$completed_orders = $completed_result->fetch_assoc()['completed'];

$urgent_query = "SELECT COUNT(id) as urgent FROM is_emirleri WHERE teslim_tarihi BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY AND genel_durum NOT IN ('Tamamlandı', 'İptal Edildi')";
$urgent_result = $mysqli->query($urgent_query);
$urgent_orders = $urgent_result->fetch_assoc()['urgent'];

// --- ANA LİSTE İÇİN SORGULAMAYI HAZIRLAMA (FİLTRELİ) ---
$filter = $_GET['filter'] ?? 'all'; // URL'den filtreyi al, yoksa 'all' varsay

$base_query = "
    SELECT ie.id, ie.is_emri_no, ie.parti_no, ie.genel_durum, ie.oncelik, ie.teslim_tarihi, u.urun_adi, m.firma_adi
    FROM is_emirleri AS ie
    JOIN urunler AS u ON ie.urun_id = u.id
    LEFT JOIN musteriler AS m ON ie.musteri_id = m.id
";

$where_clause = "";
switch ($filter) {
    case 'ongoing':
        $where_clause = " WHERE ie.genel_durum IN ('Planlandı', 'Üretimde')";
        break;
    case 'completed':
        $where_clause = " WHERE ie.genel_durum = 'Tamamlandı'";
        break;
    case 'urgent':
        $where_clause = " WHERE ie.teslim_tarihi BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY AND ie.genel_durum NOT IN ('Tamamlandı', 'İptal Edildi')";
        break;
}

$list_query = $base_query . $where_clause . " ORDER BY ie.id DESC";
$list_result = $mysqli->query($list_query);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="display-6">Yönetim Paneli</h1>
    <a class="btn btn-primary" href="is_emri_ekle.php" role="button"><i class="bi bi-plus-lg"></i> Yeni İş Emri Oluştur</a>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <a href="index.php?filter=all" class="text-decoration-none">
            <div class="card text-white bg-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Toplam İş Emri</h5>
                        <p class="display-4"><?php echo $total_orders; ?></p>
                    </div>
                    <i class="bi bi-stack display-3 opacity-50"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="index.php?filter=ongoing" class="text-decoration-none">
            <div class="card text-dark bg-warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Devam Eden</h5>
                        <p class="display-4"><?php echo $ongoing_orders; ?></p>
                    </div>
                    <i class="bi bi-gear-wide-connected display-3 opacity-50"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="index.php?filter=completed" class="text-decoration-none">
            <div class="card text-white bg-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Tamamlanan</h5>
                        <p class="display-4"><?php echo $completed_orders; ?></p>
                    </div>
                    <i class="bi bi-check2-circle display-3 opacity-50"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="index.php?filter=urgent" class="text-decoration-none">
            <div class="card text-white bg-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Acil/Yaklaşan</h5>
                        <p class="display-4"><?php echo $urgent_orders; ?></p>
                    </div>
                    <i class="bi bi-alarm-fill display-3 opacity-50"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>İş Emri No</th>
                        <th>Parti No</th>
                        <th>Müşteri</th>
                        <th>Ürün</th>
                        <th>Durum</th>
                        <th>Öncelik</th>
                        <th>Teslim Tarihi</th>
                        <th style="width: 150px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_result && $list_result->num_rows > 0): ?>
                        <?php while($row = $list_result->fetch_assoc()): ?>
                            <tr>
                                <td><a href="is_emri_detay.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['is_emri_no']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['parti_no']); ?></td>
                                <td><?php echo $row['firma_adi'] ? htmlspecialchars($row['firma_adi']) : '<span class="text-muted">Stok İçin</span>'; ?></td>
                                <td><?php echo htmlspecialchars($row['urun_adi']); ?></td>
                                <td>
                                    <?php
                                    $durum = $row['genel_durum'];
                                    $badge_class = 'bg-secondary';
                                    switch ($durum) {
                                        case 'Planlandı': $badge_class = 'bg-primary'; break;
                                        case 'Üretimde': $badge_class = 'bg-warning text-dark'; break;
                                        case 'Tamamlandı': $badge_class = 'bg-success'; break;
                                        case 'İptal Edildi': $badge_class = 'bg-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($durum); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['oncelik']); ?></td>
                                <td><?php echo date("d.m.Y", strtotime($row['teslim_tarihi'])); ?></td>
                                <td>
                                    <a href="is_emri_duzenle.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                                    <a href="is_emri_sil.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu iş emrini silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Bu filtreye uygun iş emri bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Veritabanı bağlantısını kapat
$mysqli->close();
?>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>