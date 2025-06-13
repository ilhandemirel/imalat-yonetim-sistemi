<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Silme işlemi için onay
function silmeOnayi(url) {
    if (confirm('Bu kaydı silmek istediğinizden emin misiniz?')) {
        window.location.href = url;
    }
}

// Sayfa yüklendiğinde çalışacak kodlar
document.addEventListener('DOMContentLoaded', function() {
    // Başarı mesajı varsa göster
    <?php if (isset($_SESSION['success_message']) && isset($_SESSION['success_page']) && $_SESSION['success_page'] === basename($_SERVER['PHP_SELF'])): ?>
        alert('<?php echo $_SESSION['success_message']; ?>');
        <?php 
        unset($_SESSION['success_message']);
        unset($_SESSION['success_page']);
        ?>
    <?php endif; ?>

    // Hata mesajı varsa göster
    <?php if (isset($_SESSION['error_message']) && isset($_SESSION['error_page']) && $_SESSION['error_page'] === basename($_SERVER['PHP_SELF'])): ?>
        alert('<?php echo $_SESSION['error_message']; ?>');
        <?php 
        unset($_SESSION['error_message']);
        unset($_SESSION['error_page']);
        ?>
    <?php endif; ?>
});
</script> 