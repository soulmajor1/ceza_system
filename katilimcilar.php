<?php
// Bu sayfanın başında header.php çağrılacağı için session_start() ve db.php zaten dahil edilmiş olacak.
// Ancak güvenlik için biz yine de session kontrolünü yapalım.
require_once 'header.php'; // header.php session ve db bağlantısını zaten yapıyor.

// Sadece giriş yapmış kullanıcılar bu sayfayı görebilsin
if (!$giris_yapan_id) {
    // Yönlendirme header.php'den önce yapılmalı, bu yüzden burada die() kullanmak daha mantıklı.
    die("Bu sayfayı görüntülemek için giriş yapmalısınız. <a href='login.php'>Giriş Yap</a>");
}

$hata = '';
$basari = '';
// Yeni üye ekleme formu gönderilmişse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['yeni_isim'])) {
    $yeni_isim = trim($_POST['yeni_isim']);
    $yeni_sifre = $_POST['yeni_sifre'];

    if (empty($yeni_isim) || empty($yeni_sifre)) {
        $hata = "Yeni üye için isim ve şifre boş bırakılamaz.";
    } else {
        // Şifreyi hash'le
        $hashed_sifre = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO katilimcilar (isim, sifre) VALUES (?, ?)");
        try {
            $stmt->execute([$yeni_isim, $hashed_sifre]);
            $basari = htmlspecialchars($yeni_isim) . " başarıyla Divan'a kaydedildi!";
        } catch (PDOException $e) {
            // Eğer isim benzersiz (UNIQUE) ise ve aynı isim tekrar eklenmeye çalışılırsa hata verir.
            if ($e->errorInfo[1] == 1062) {
                $hata = "Bu isimde bir katılımcı zaten mevcut!";
            } else {
                $hata = "Bir veritabanı hatası oluştu: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Divan Üyeleri - Dota 2 Ceza Divanı</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Divan Jüri Heyeti</h1>
        
        <div class="uye-listesi">
            <h2>Mevcut Üyeler</h2>
            <ul>
                <?php
                $stmt = $pdo->query("SELECT isim FROM katilimcilar ORDER BY isim ASC");
                while ($row = $stmt->fetch()) {
                    echo "<li>" . htmlspecialchars($row['isim']) . "</li>";
                }
                ?>
            </ul>
        </div>
        
        <hr>

        <div class="uye-ekleme">
            <h2>Yeni Üye Kaydı</h2>
            <form action="katilimcilar.php" method="POST" class="login-form">
                <?php if ($hata): ?><p class="hata-mesaji"><?= $hata ?></p><?php endif; ?>
                <?php if ($basari): ?><p class="basari-mesaji"><?= $basari ?></p><?php endif; ?>

                <label for="yeni_isim">Yeni Üyenin Adı:</label>
                <input type="text" name="yeni_isim" required>
                
                <label for="yeni_sifre">Şifresi:</label>
                <input type="password" name="yeni_sifre" required>
                
                <button type="submit">Yeni Üyeyi Ekle</button>
            </form>
        </div>
    </div>
</body>
</html>