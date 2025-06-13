<?php
// register.php

// Oturumu başlat. Projenin her sayfasında oturum yönetimi için bu gerekli.
session_start();

// Hata mesajlarını tutmak için bir dizi oluşturalım.
$errors = [];

// Formun POST metodu ile gönderilip gönderilmediğini kontrol edelim.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Veritabanı bağlantısını dahil et.
    require_once 'config/database.php';

    // 2. Formdan gelen verileri al ve temizle.
    $ad_soyad = trim($_POST['ad_soyad']);
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $eposta = trim($_POST['eposta']);
    $sifre = $_POST['sifre']; // Şifreyi trim'leme, boşluk karakteri de şifrenin bir parçası olabilir.

    // 3. Sunucu tarafı doğrulama (Validation)
    if (empty($ad_soyad)) {
        $errors[] = "Ad Soyad alanı boş bırakılamaz.";
    }
    if (empty($kullanici_adi)) {
        $errors[] = "Kullanıcı Adı alanı boş bırakılamaz.";
    }
    if (empty($eposta)) {
        $errors[] = "E-posta alanı boş bırakılamaz.";
    } elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçersiz e-posta formatı.";
    }
    if (empty($sifre)) {
        $errors[] = "Şifre alanı boş bırakılamaz.";
    }

    // Eğer şu ana kadar bir hata yoksa, kullanıcı adı ve e-postanın veritabanında mevcut olup olmadığını kontrol et.
    if (empty($errors)) {
        // SQL Injection'a karşı KORUNMAK İÇİN Prepared Statements KULLAN!
        $stmt = $mysqli->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR eposta = ?");
        $stmt->bind_param("ss", $kullanici_adi, $eposta);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Bu kullanıcı adı veya e-posta adresi zaten kayıtlı.";
        }
        $stmt->close();
    }

    // 4. Hata yoksa, veritabanına kaydet.
    if (empty($errors)) {
        // ŞİFREYİ HASH'LE! Bu, proje kurallarındaki en önemli maddelerden biri.
        $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);
        
        // Kullanıcıyı veritabanına ekle.
        $stmt = $mysqli->prepare("INSERT INTO kullanicilar (ad_soyad, kullanici_adi, eposta, sifre, rol) VALUES (?, ?, ?, ?, 'operator')");
        // Varsayılan olarak her yeni kullanıcı 'operator' rolüyle başlasın.
        $stmt->bind_param("ssss", $ad_soyad, $kullanici_adi, $eposta, $hashed_sifre);

        if ($stmt->execute()) {
            // Kayıt başarılı olursa, kullanıcıyı bilgilendir ve login sayfasına yönlendir.
            $_SESSION['success_message'] = "Kayıt başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.";
            header("Location: login.php");
            exit(); // Yönlendirmeden sonra script'in çalışmasını durdurmak önemlidir.
        } else {
            $errors[] = "Kayıt sırasında bir hata oluştu: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Veritabanı bağlantısını kapat.
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Kaydı - İmalat Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }
        .card-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
            border-color: #2196F3;
        }
        .btn-primary {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }
        .card-footer {
            background: none;
            border-top: 1px solid #eee;
            padding: 20px;
            text-align: center;
        }
        .card-footer a {
            color: #2196F3;
            text-decoration: none;
            font-weight: 600;
        }
        .card-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-person-plus me-2"></i>Kullanıcı Kaydı</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-4">
                            <label for="ad_soyad" class="form-label">Ad Soyad</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="eposta" class="form-label">E-posta Adresi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="eposta" name="eposta" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="sifre" class="form-label">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="sifre" name="sifre" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>Kayıt Ol
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <p class="mb-0">Zaten bir hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>