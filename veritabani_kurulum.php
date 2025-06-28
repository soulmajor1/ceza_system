<?php
// Bu script, kullanıcıları veritabanına hatasız bir şekilde eklemek için sadece BİR KEZ çalıştırılacaktır.
// Çalıştırdıktan sonra bu dosyayı silmeniz ÖNEMLİDİR.

echo "<h1>Divan Jüri Heyeti Kurulum Scripti Başlatıldı...</h1>";

require_once 'db.php';

try {
    // 1. ADIM: Önceki tüm hatalı kayıtları temizle
    echo "<p>Eski kayıtlar siliniyor...</p>";
    $pdo->exec("DELETE FROM katilimcilar");
    echo "<p style='color:green;'>Eski kayıtlar başarıyla silindi.</p><hr>";

    // 2. ADIM: Eklenecek kullanıcılar ve şifreleri
    $kullanicilar = [
        'Huseyin Gelir' => 'hus1234',
        'Erhan Buyuk' => 'erh1234',
        'Gultekin Yamak' => 'gul1234',
        'Atakan Gurkan' => 'ata1234',
        'Bircan Burak Uslu' => 'bir1234',
    ];

    echo "<p>Yeni kullanıcılar ekleniyor...</p>";

    // 3. ADIM: Her kullanıcıyı döngüye al, şifresini hash'le ve veritabanına ekle
    $stmt = $pdo->prepare("INSERT INTO katilimcilar (isim, sifre) VALUES (?, ?)");

    foreach ($kullanicilar as $isim => $sifre) {
        // Şifreyi PHP'nin kendisiyle, hatasız bir şekilde hash'le
        $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

        // Veritabanına ekle
        $stmt->execute([$isim, $hashed_sifre]);

        echo "Kullanıcı: '<b>" . htmlspecialchars($isim) . "</b>' eklendi. (Hash uzunluğu: " . strlen($hashed_sifre) . ")<br>";
    }

    echo "<hr><h2 style='color:green;'>Kurulum tamamlandı! Tüm jüri üyeleri veritabanına başarıyla ve hatasız bir şekilde eklendi.</h2>";
    echo "<h3 style='color:red;'>GÜVENLİK UYARISI: Lütfen şimdi bu `veritabani_kurulum.php` dosyasını sunucudan silin!</h3>";
    echo "<a href='index.php'>Ana Sayfaya Dön ve Giriş Yapmayı Dene</a>";

} catch (PDOException $e) {
    die("<h2 style='color:red;'>Bir hata oluştu: " . $e->getMessage() . "</h2>");
}

?>