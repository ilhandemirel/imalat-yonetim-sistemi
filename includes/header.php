<?php
// header.php
// auth_check.php'nin zaten session_start() yaptığından emin olsak da,
// bu dosyanın tek başına çağırılma ihtimaline karşı kontrol eklemek iyidir.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmalat İş Emri Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff !important;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            background: linear-gradient(45deg, #2196F3, #1976D2);
            transition: all 0.3s ease;
        }
        .navbar-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-menu .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-menu .nav-link i {
            font-size: 1.1rem;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .dropdown-item i {
            margin-right: 0.5rem;
            color: #6c757d;
        }
        .user-menu .dropdown-toggle {
            background: rgba(33, 37, 41, 0.95) !important;
            color: #fff !important;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 0.5rem 1.2rem;
            transition: background 0.2s, color 0.2s;
        }
        .user-menu .dropdown-toggle:hover, .user-menu .dropdown-toggle:focus {
            background: #1976D2 !important;
            color: #fff !important;
        }
        .user-menu .dropdown-toggle .bi-person-circle {
            color: #fff !important;
        }
        .user-menu .dropdown-toggle span,
        .user-menu .dropdown-toggle small {
            color: #fff !important;
        }
        .user-menu .dropdown-toggle small {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house-door"></i> Ana Sayfa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="is_emri_ekle.php">
                        <i class="bi bi-plus-circle"></i> Yeni İş Emri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="malzemeler_liste.php">
                        <i class="bi bi-box-seam"></i> Malzemeler
                    </a>
                </li>
                <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="kullanicilar_liste.php">
                        <i class="bi bi-people"></i> Kullanıcı Yönetimi
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="user-menu ms-auto">
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </div>
                                <div>
                                    <span class="d-block"><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></span>
                                    <small class="text-muted d-block"><?php echo ucfirst(htmlspecialchars($_SESSION['user_rol'])); ?></small>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <i class="bi bi-person me-2"></i>
                                    <span>Profil</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <i class="bi bi-gear me-2"></i>
                                    <span>Ayarlar</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    <span>Çıkış Yap</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        <span>Giriş Yap</span>
                    </a>
                    <a class="nav-link d-flex align-items-center" href="register.php">
                        <i class="bi bi-person-plus me-2"></i>
                        <span>Kayıt Ol</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">