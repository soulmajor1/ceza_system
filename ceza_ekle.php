<?php
session_start();
require 'db.php';

// Kullanıcı giriş yapmamışsa, login sayfasına yönlendir.
if (!isset($_SESSION['katilimci_id'])) {
    header('Location: login.php');
    exit;
}

// ---- API FONKSİYONU ----
function getAIResponseFromAPI($bahane, $apiKey) {
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;
    $prompt = "Sen, 'Dota 2 Yüksek Ceza Divanı' isimli bir arkadaş grubu mahkemesinin Baş Yargıcısın. Görevin, her akşam saat 19:00-23:00 arasındaki Dota 2 oyunlarına katılmayan bir oyuncunun sunduğu bahaneyi analiz etmek ve 1-100 arasında bir ceza puanı atamaktır. Değerlendirme Kriterlerin: - Mücbir Sebep: Olay kişinin kontrolü dışında mı? (Elektrik/internet kesintisi, ani hastalık, ailevi acil durumlar en düşük puanı alır: 1-15 puan). - Öngörülebilirlik ve Bildirim: Mazeret ne kadar önceden bildirildi? Son dakika bildirimleri cezayı artırır. - Bahanenin Ciddiyeti: Bahanenin geçerliliği ve ağırlığı nedir? ('Canım istemedi' veya 'Uykum geldi' gibi keyfi bahaneler en yüksek puanı alır: 75-100 puan). - İhmal ve Saygı: Bahanede takıma karşı bir ihmal veya saygısızlık var mı? Senden beklenen çıktı, bir JSON formatında olmalıdır. JSON objesi iki anahtar içermelidir: 'puan' (1-100 arası bir integer) ve 'aciklama' (yaptığın muhakemeyi detaylı ve esprili bir dille anlatan metin). Şimdi aşağıdaki bahaneyi bu kurallara göre değerlendir ve sadece JSON çıktısını üret: Bahane: \"{$bahane}\"";
    $data = ['contents' => [['parts' => [['text' => $prompt]]]]];
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $jsonString = $result['candidates'][0]['content']['parts'][0]['text'];
        $jsonString = str_replace(['```json', '```'], '', $jsonString);
        $ai_data = json_decode(trim($jsonString), true);
        if (json_last_error() === JSON_ERROR_NONE && isset($ai_data['puan']) && isset($ai_data['aciklama'])) {
            return $ai_data;
        }
    }
    
    // === DEĞİŞİKLİK BURADA YAPILDI ===
    // API'den geçerli cevap gelmezse varsayılan değerler
    return ['puan' => 10, 'aciklama' => 'Yapay Zeka Divanı ile bağlantı kurulamadı veya geçersiz bir yanıt alındı. Standart ceza uygulandı.'];
}
// ---- API FONKSİYONU BİTİŞİ ----


// Form gönderildiğinde çalışacak kısım
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // KENDİ GOOGLE AI STUDIO API ANAHTARINIZI BURAYA GİRİN
    $apiKey = 'YOUR_GEMINI_API_KEY_HERE';

    $isim = $_POST['isim'];
    $bahane = $_POST['bahane'];
    $bahane_tarihi = $_POST['bahane_tarihi'];

    if (empty($apiKey) || $apiKey == 'YOUR_GEMINI_API_KEY_HERE') {
        die("HATA: Lütfen ceza_ekle.php dosyasında API anahtarınızı belirtin.");
    }

    // API'den yapay zeka değerlendirmesini al
    $ai_response = getAIResponseFromAPI($bahane, $apiKey);
    $ai_ceza_puani = $ai_response['puan'];
    $ai_aciklama = $ai_response['aciklama'];

    // Veritabanına kaydet
    $sql = "INSERT INTO cezalar (isim, bahane, bahane_tarihi, ai_ceza_puani, ai_aciklama) VALUES (?, ?, ?, ?, ?)";
    $stmt= $pdo->prepare($sql);
    $stmt->execute([$isim, $bahane, $bahane_tarihi, $ai_ceza_puani, $ai_aciklama]);

    // Kayıt sonrası ana sayfaya yönlendir
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Ceza Ekle - Dota 2 Ceza Divanı</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Yeni Suç Duyurusu</h1>
        <p>Divan, adaletin tecellisi için yeni davaları bekliyor.</p>
        
        <form action="ceza_ekle.php" method="post" class="ceza-formu">
            <label for="isim">Sanık:</label>
            <select name="isim" id="isim" required>
                <option value="" disabled selected>Sanığı Seçin</option>
                <?php
                    $stmt = $pdo->query("SELECT isim FROM katilimcilar ORDER BY isim ASC");
                    while ($katilimci = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value=\"" . htmlspecialchars($katilimci['isim']) . "\">" . htmlspecialchars($katilimci['isim']) . "</option>";
                    }
                ?>
            </select>

            <label for="bahane_tarihi">Olay Tarihi:</label>
            <input type="date" id="bahane_tarihi" name="bahane_tarihi" value="<?= date('Y-m-d') ?>" required>

            <label for="bahane">Bahaneyi Beyan Et:</label>
            <textarea id="bahane" name="bahane" rows="5" placeholder="Sanığın suçu örtbas etme çabalarını buraya yazın..." required></textarea>

            <button type="submit">Davayı Divana Sun</button>
        </form>
    </div>
</body>
</html>