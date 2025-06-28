<?php
session_start();
require_once 'db.php';

// Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
if (isset($_SESSION['katilimci_id'])) {
    header('Location: index.php');
    exit;
}

$hata = '';
// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dropdown'dan gelen isim artık $_POST['isim'] içinde olacak
    $isim = $_POST['isim'];
    $sifre = $_POST['sifre'];

    if (empty($isim) || empty($sifre)) {
        $hata = "Lütfen listeden isminizi seçin ve şifrenizi girin.";
    } else {
        // Kullanıcıyı veritabanında ara
        $stmt = $pdo->prepare("SELECT id, sifre FROM katilimcilar WHERE isim = ?");
        $stmt->execute([$isim]);
        $kullanici = $stmt->fetch(PDO::FETCH_ASSOC); // Sadece isim-değer çiftlerini al

        // Kullanıcı bulunduysa ve şifre doğruysa (password_verify ile kontrol)
        if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            // Session (Oturum) bilgilerini ayarla
            $_SESSION['katilimci_id'] = $kullanici['id'];
            $_SESSION['katilimci_isim'] = $isim;
            
            // Ana sayfaya yönlendir
            header('Location: index.php');
            exit;
        } else {
            $hata = "Geçersiz şifre! Lütfen tekrar deneyin.";
        }
    }
}

// Dropdown menüsünü doldurmak için tüm kullanıcıları veritabanından çek.
try {
    $kullanicilar = $pdo->query("SELECT isim FROM katilimcilar ORDER BY isim ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Kullanıcı listesi alınamadı. Veritabanı bağlantısını kontrol edin. Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - Dota 2 Ceza Divanı</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Divan'a Giriş</h1>
        <form action="login.php" method="post" class="login-form">
            <h2>Jüri Üyesi Girişi</h2>
            
            <?php if ($hata): ?>
                <p class="hata-mesaji"><?= $hata ?></p>
            <?php endif; ?>

            <label for="isim">Kullanıcı Adı:</label>
            <select id="isim" name="isim" required>
                <option value="" disabled selected>-- Lütfen isminizi seçin --</option>
                <?php
                foreach ($kullanicilar as $kullanici_secim) {
                    echo '<option value="' . htmlspecialchars($kullanici_secim['isim']) . '">' . htmlspecialchars($kullanici_secim['isim']) . '</option>';
                }
                ?>
            </select>

            <label for="sifre">Şifre:</label>
            <input type="password" id="sifre" name="sifre" required>

            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</body>
</html>