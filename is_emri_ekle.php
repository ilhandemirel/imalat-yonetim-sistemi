<?php
// is_emri_ekle.php

// Güvenlik: Sadece giriş yapmış kullanıcılar erişebilir.
require_once 'includes/auth_check.php';
// Veritabanı bağlantısı
require_once 'config/database.php';

// --- YENİ İŞ EMRİ NUMARASI ÜRETME ---
$bugun_tarih = date('Ymd');
$prefix = 'IE-' . $bugun_tarih . '-';

// Bugün oluşturulmuş en son iş emrini bul
$stmt_numara = $mysqli->prepare("SELECT MAX(is_emri_no) as last_order FROM is_emirleri WHERE is_emri_no LIKE ?");
$like_prefix = $prefix . '%';
$stmt_numara->bind_param("s", $like_prefix);
$stmt_numara->execute();
$result_numara = $stmt_numara->get_result()->fetch_assoc();
$stmt_numara->close();

if ($result_numara['last_order']) {
    // Eğer bugün daha önce iş emri oluşturulmuşsa, son numarayı alıp bir artır
    $son_numara = (int) substr($result_numara['last_order'], -3);
    $yeni_numara = $son_numara + 1;
} else {
    // Bugünün ilk iş emri
    $yeni_numara = 1;
}

// Yeni numarayı 3 haneli olacak şekilde sıfırlarla doldur (örn: 1 -> 001)
$yeni_is_emri_no = $prefix . str_pad($yeni_numara, 3, '0', STR_PAD_LEFT);
// --- BİTİŞ ---

// Formdaki seçim kutularını (dropdown) doldurmak için verileri çekelim.
$musteriler_result = $mysqli->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi ASC");
$urunler_result = $mysqli->query("SELECT id, urun_adi, urun_kodu FROM urunler ORDER BY urun_adi ASC");
$standart_ops_result = $mysqli->query("SELECT id, operasyon_adi FROM standart_operasyonlar ORDER BY operasyon_adi ASC");

$errors = [];

// Form gönderildi mi?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug için POST verilerini kontrol et
    error_log("POST verileri: " . print_r($_POST, true));

    // Form verilerini alırken, anahtarın var olup olmadığını kontrol et
    $is_emri_no = trim($_POST['is_emri_no'] ?? '');
    $parti_no = trim($_POST['parti_no'] ?? '');
    $musteri_id = $_POST['musteri_id'] ?? null;
    $urun_id = $_POST['urun_id'] ?? null;
    $siparis_miktari = $_POST['miktar'] ?? 0;
    $oncelik = $_POST['oncelik'] ?? 'Normal';
    $teslim_tarihi = $_POST['teslim_tarihi'] ?? '';
    $siparis_tarihi = date('Y-m-d'); 
    $olusturan_kullanici_id = $_SESSION['user_id'];

    // Operasyonları yeni formata göre işle
    $operasyon_adlari = [];
    $sira_numaralari = [];
    if (isset($_POST['operasyonlar']) && is_array($_POST['operasyonlar'])) {
        foreach ($_POST['operasyonlar'] as $op) {
            if (!empty($op['operasyon_adi'])) {
                $operasyon_adlari[] = $op['operasyon_adi'];
                $sira_numaralari[] = $op['sira_no'];
            }
        }
    }

    // Debug için alınan verileri kontrol et
    error_log("İşlenmiş form verileri: " . print_r([
        'is_emri_no' => $is_emri_no,
        'parti_no' => $parti_no,
        'musteri_id' => $musteri_id,
        'urun_id' => $urun_id,
        'siparis_miktari' => $siparis_miktari,
        'oncelik' => $oncelik,
        'teslim_tarihi' => $teslim_tarihi,
        'operasyon_adlari' => $operasyon_adlari,
        'sira_numaralari' => $sira_numaralari
    ], true));

    // Doğrulama
    if (empty($urun_id)) $errors[] = "Bir ürün seçmelisiniz.";
    if (empty($siparis_miktari) || !is_numeric($siparis_miktari) || $siparis_miktari <= 0) $errors[] = "Geçerli bir sipariş miktarı girin.";
    if (empty($teslim_tarihi)) $errors[] = "Teslim tarihi zorunludur.";
    
    // Operasyon var mı diye daha sağlam bir kontrol yapalım.
    $gecerli_operasyon_var_mi = false;
    if (!empty($operasyon_adlari)) {
        foreach ($operasyon_adlari as $op_adi) {
            if (!empty(trim($op_adi))) {
                $gecerli_operasyon_var_mi = true;
                break;
            }
        }
    }
    if (!$gecerli_operasyon_var_mi) {
        $errors[] = "En az bir geçerli üretim operasyonu eklemelisiniz.";
    }

    // Debug için hataları kontrol et
    if (!empty($errors)) {
        error_log("Form doğrulama hataları: " . print_r($errors, true));
    }

    // Hata yoksa veritabanına ekle
    if (empty($errors)) {
        try {
            $mysqli->begin_transaction();

            $stmt = $mysqli->prepare(
                "INSERT INTO is_emirleri (is_emri_no, parti_no, musteri_id, urun_id, siparis_miktari, oncelik, teslim_tarihi, siparis_tarihi, olusturan_kullanici_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $musteri_id = !empty($musteri_id) ? $musteri_id : null;
            $stmt->bind_param("ssiiisssi", $is_emri_no, $parti_no, $musteri_id, $urun_id, $siparis_miktari, $oncelik, $teslim_tarihi, $siparis_tarihi, $olusturan_kullanici_id);

            if (!$stmt->execute()) {
                throw new Exception("İş emri kaydedilirken hata oluştu: " . $stmt->error);
            }

            $son_is_emri_id = $mysqli->insert_id;
            $op_stmt = $mysqli->prepare("INSERT INTO is_emri_operasyonlari (is_emri_id, operasyon_adi, sira_no) VALUES (?, ?, ?)");

            foreach ($operasyon_adlari as $index => $op_adi) {
                if (!empty(trim($op_adi))) {
                    $sira_no = $sira_numaralari[$index];
                    $op_stmt->bind_param("isi", $son_is_emri_id, $op_adi, $sira_no);
                    if (!$op_stmt->execute()) {
                        throw new Exception("Operasyon kaydedilirken hata oluştu: " . $op_stmt->error);
                    }
                }
            }
            $op_stmt->close();

            $mysqli->commit();
            $_SESSION['success_message'] = "İş emri ve operasyonları başarıyla oluşturuldu!";
            $_SESSION['success_page'] = 'is_emri_ekle.php';
            header("Location: is_emri_ekle.php");
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            $errors[] = "Hata: " . $e->getMessage();
            error_log("Veritabanı hatası: " . $e->getMessage());
        }
        $stmt->close();
    }
}

// Şablonun üst kısmını dahil et
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="display-6">Yeni İş Emri Oluştur</h1>
    <a href="index.php" class="btn btn-secondary">Geri Dön</a>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Hata!</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Temel Bilgiler</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="is_emri_no" class="form-label">İş Emri Numarası (Otomatik)</label>
                        <input type="text" class="form-control" id="is_emri_no" name="is_emri_no" value="<?php echo htmlspecialchars($yeni_is_emri_no); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="musteri_id" class="form-label">Müşteri</label>
                        <select class="form-select" id="musteri_id" name="musteri_id">
                            <option value="">Müşteri Seçin</option>
                            <?php while($musteri = $musteriler_result->fetch_assoc()): ?>
                                <option value="<?php echo $musteri['id']; ?>"><?php echo htmlspecialchars($musteri['firma_adi']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="urun_id" class="form-label">Ürün</label>
                        <select class="form-select" id="urun_id" name="urun_id" required>
                            <option value="">Ürün Seçin</option>
                            <?php while($urun = $urunler_result->fetch_assoc()): ?>
                                <option 
                                    value="<?php echo $urun['id']; ?>" 
                                    data-urunkodu="<?php echo htmlspecialchars($urun['urun_kodu']); ?>">
                                    <?php echo htmlspecialchars($urun['urun_kodu'] . ' - ' . $urun['urun_adi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="parti_no" class="form-label">Parti Numarası</label>
                        <input type="text" class="form-control" id="parti_no" name="parti_no" required>
                    </div>

                    <div class="mb-3">
                        <label for="miktar" class="form-label">Üretim Miktarı</label>
                        <input type="number" class="form-control" id="miktar" name="miktar" required min="1">
                    </div>

                    <div class="mb-3">
                        <label for="oncelik" class="form-label">Öncelik</label>
                        <select class="form-select" id="oncelik" name="oncelik" required>
                            <option value="Düşük">Düşük</option>
                            <option value="Normal">Normal</option>
                            <option value="Yüksek">Yüksek</option>
                            <option value="Acil">Acil</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="teslim_tarihi" class="form-label">Planlanan Teslim Tarihi</label>
                        <input type="date" class="form-control" id="teslim_tarihi" name="teslim_tarihi" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Üretim Operasyonları</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Operasyon Seçin</label>
                        <div class="input-group">
                            <select class="form-select" id="operasyon_secim">
                                <option value="">Operasyon Seçin</option>
                                <?php while($op = $standart_ops_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($op['operasyon_adi']); ?>">
                                        <?php echo htmlspecialchars($op['operasyon_adi']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn btn-primary" id="operasyon_ekle">Ekle</button>
                        </div>
                    </div>

                    <div id="operasyonlar_listesi">
                        <!-- Operasyonlar buraya dinamik olarak eklenecek -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">Kaydet</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Parti numarası otomasyonu
    const urunSecimInput = document.getElementById('urun_id');
    const partiNoInput = document.getElementById('parti_no');

    urunSecimInput.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const urunKodu = selectedOption.getAttribute('data-urunkodu');
        
        if (urunKodu) {
            const d = new Date();
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const tarihStr = `${year}${month}${day}`;
            
            partiNoInput.value = `${urunKodu}-${tarihStr}`;
        } else {
            partiNoInput.value = '';
        }
    });

    // Operasyon ekleme fonksiyonları
    const operasyonSecim = document.getElementById('operasyon_secim');
    const operasyonEkleBtn = document.getElementById('operasyon_ekle');
    const operasyonlarListesi = document.getElementById('operasyonlar_listesi');
    let operasyonSayisi = 0;

    function getNextSiraNo() {
        return operasyonSayisi + 1;
    }

    operasyonEkleBtn.addEventListener('click', function() {
        const seciliOperasyon = operasyonSecim.value;
        if (!seciliOperasyon) {
            alert('Lütfen bir operasyon seçin!');
            return;
        }

        const siraNo = getNextSiraNo();
        const yeniOperasyon = `
            <div class="operasyon-item mb-2">
                <div class="input-group">
                    <input type="text" class="form-control" name="operasyonlar[${operasyonSayisi}][operasyon_adi]" value="${seciliOperasyon}" readonly>
                    <input type="number" class="form-control" name="operasyonlar[${operasyonSayisi}][sira_no]" value="${siraNo}" readonly>
                    <button type="button" class="btn btn-danger operasyon-sil">Sil</button>
                </div>
            </div>
        `;

        operasyonlarListesi.insertAdjacentHTML('beforeend', yeniOperasyon);
        operasyonSayisi++;

        // Silme butonlarına event listener ekle
        document.querySelectorAll('.operasyon-sil').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.operasyon-item').remove();
                // Sıra numaralarını güncelle
                document.querySelectorAll('.operasyon-item').forEach((item, index) => {
                    item.querySelector('input[name$="[sira_no]"]').value = index + 1;
                });
                operasyonSayisi--;
            });
        });

        // Seçimi sıfırla
        operasyonSecim.value = '';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>