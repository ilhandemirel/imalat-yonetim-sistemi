<?php
// kullanicilar_liste.php
require_once 'includes/admin_check.php'; // Bu sayfanın en başına admin kontrolünü koyuyoruz!
require_once 'config/database.php';
require_once 'includes/header.php';

$result = $mysqli->query("SELECT id, kullanici_adi, ad_soyad, eposta, rol FROM kullanicilar ORDER BY id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="display-6">Kullanıcı Yönetimi</h1>
    </div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['kullanici_adi']); ?></td>
                        <td><?php echo htmlspecialchars($user['ad_soyad']); ?></td>
                        <td><?php echo htmlspecialchars($user['eposta']); ?></td>
                        <td><?php echo htmlspecialchars($user['rol']); ?></td>
                        <td>
                            <a href="kullanici_duzenle.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 