<?php
// session_status() kontrolü, session_start()'ın birden fazla kez çağrılmasını engelleyerek
// olası hataların önüne geçer.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısını bir kez dahil et. require_once kullanmak en güvenlisidir.
require_once 'db.php';

// Giriş yapmış kullanıcının ID ve ismini al
$giris_yapan_id = $_SESSION['katilimci_id'] ?? null;
$giris_yapan_isim = '';

if ($giris_yapan_id) {
    $stmt = $pdo->prepare("SELECT isim FROM katilimcilar WHERE id = ?");
    $stmt->execute([$giris_yapan_id]);
    $giris_yapan_isim = $stmt->fetchColumn();
}
?>
<header class="site-header">
    <div class="logo">
        <a href="index.php">D2 Yüksek Ceza Divanı</a>
    </div>
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Ana Sayfa (Divan)</a></li>
            <?php if ($giris_yapan_id): ?>
                <li><a href="ceza_ekle.php">Yeni Dava Ekle</a></li>
                <li><a href="katilimcilar.php">Üyeler</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="user-info">
        <?php if ($giris_yapan_id): ?>
            <span>Hoş geldin, <strong><?= htmlspecialchars($giris_yapan_isim) ?></strong></span>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        <?php else: ?>
            <a href="login.php" class="login-button">Giriş Yap</a>
        <?php endif; ?>
    </div>
</header>