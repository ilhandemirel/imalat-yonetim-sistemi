<?php
// login.php

// Oturumu başlat
session_start();

// Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya yönlendir.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Hata mesajları için dizi
$errors = [];

// Formun POST metodu ile gönderilip gönderilmediğini kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Veritabanı bağlantısını dahil et
    require_once 'config/database.php';

    // Formdan gelen verileri al
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];

    // Basit doğrulama
    if (empty($kullanici_adi) || empty($sifre)) {
        $errors[] = "Kullanıcı adı ve şifre alanları boş bırakılamaz.";
    } else {
        // Kullanıcıyı veritabanında ara
        // SQL Injection'a karşı Prepared Statements KULLAN!
        $stmt = $mysqli->prepare("SELECT id, kullanici_adi, sifre, rol FROM kullanicilar WHERE kullanici_adi = ?");
        $stmt->bind_param("s", $kullanici_adi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Kullanıcı bulundu, şimdi şifreyi kontrol et
            $user = $result->fetch_assoc();
            
            // password_verify() fonksiyonu, girilen şifre ile veritabanındaki hash'lenmiş şifreyi karşılaştırır.
            if (password_verify($sifre, $user['sifre'])) {
                // Şifre doğru! Oturumu başlat.
                
                // Güvenlik için oturum kimliğini yeniden oluştur (Session Fixation saldırılarını önler)
                session_regenerate_id(true);

                // Oturum değişkenlerini ayarla
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['user_rol'] = $user['rol']; // Kullanıcı rolünü de oturuma kaydedelim, yetkilendirme için lazım olacak.

                // Kullanıcıyı ana sayfaya (index.php) yönlendir
                header("Location: index.php");
                exit;
            } else {
                // Şifre yanlış
                $errors[] = "Kullanıcı adı veya şifre hatalı.";
            }
        } else {
            // Kullanıcı bulunamadı
            $errors[] = "Kullanıcı adı veya şifre hatalı.";
        }
        $stmt->close();
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - İmalat Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap</h3>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>' . $_SESSION['success_message'] . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                        unset($_SESSION['success_message']);
                    }

                    if (!empty($errors)) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>';
                        foreach ($errors as $error) {
                            echo '<p class="mb-0">' . $error . '</p>';
                        }
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    }
                    ?>
                    <form action="login.php" method="POST">
                        <div class="mb-4">
                            <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
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
                                <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <p class="mb-0">Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>