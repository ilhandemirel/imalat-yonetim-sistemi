<?php
// malzemeler_liste.php
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/header.php';

$result = $mysqli->query("SELECT * FROM malzemeler ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="display-6">Malzeme Listesi</h1>
    <a class="btn btn-primary" href="malzeme_ekle.php" role="button">
        <i class="bi bi-plus-circle me-2"></i>Yeni Malzeme Ekle
    </a>
</div>

<?php
if (isset($_SESSION['success_message']) && isset($_SESSION['success_page']) && $_SESSION['success_page'] === 'malzemeler_liste.php') {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . 
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['success_message']);
    unset($_SESSION['success_page']);
}
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Kod</th>
                        <th>Adı</th>
                        <th>Tedarikçi</th>
                        <th>Sertifika No</th>
                        <th>Birim</th>
                        <th>Stok</th>
                        <th style="width: 150px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['malzeme_kodu']); ?></td>
                                <td><?php echo htmlspecialchars($row['malzeme_adi']); ?></td>
                                <td><?php echo htmlspecialchars($row['tedarikci']); ?></td>
                                <td><?php echo htmlspecialchars($row['sertifika_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['birim']); ?></td>
                                <td><?php echo htmlspecialchars($row['stok_miktari']); ?></td>
                                <td>
                                    <a href="malzeme_duzenle.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                                    <a href="malzeme_sil.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu malzemeyi silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Kayıtlı malzeme bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $mysqli->close(); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 