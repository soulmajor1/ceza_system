<?php
session_start();
require_once 'db.php';

// --- GÜVENLİK VE KONTROL ADIMLARI ---

// 1. Kullanıcı Giriş Yapmış mı?
// Oy kullanmak için mutlaka giriş yapmış olmak gerekir.
if (!isset($_SESSION['katilimci_id'])) {
    die("HATA: Oy kullanabilmek için giriş yapmanız gerekmektedir. <a href='login.php'>Giriş Yap</a>");
}

// 2. Veri POST metodu ile mi geldi?
// Tarayıcıdan direkt bu sayfaya gelinmesini engeller.
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // POST değilse, ana sayfaya yönlendir.
    header('Location: index.php');
    exit;
}

// 3. Gerekli veriler gönderilmiş mi ve geçerli mi?
$ceza_id = $_POST['ceza_id'] ?? null;
$oy = $_POST['oy'] ?? null;
$katilimci_id = $_SESSION['katilimci_id'];

if (!$ceza_id || !$oy || !is_numeric($ceza_id) || !is_numeric($oy)) {
    die("HATA: Geçersiz veya eksik veri gönderildi.");
}

// 4. Oy değeri beklenen aralıkta mı? (-5 ile +5 arası)
if ($oy < -5 || $oy > 5 || $oy == 0) {
    die("HATA: Geçersiz oy değeri.");
}

// --- VERİTABANI İŞLEMİ ---

try {
    // Veritabanına yeni oy'u ekle.
    // juri_oylari tablosundaki UNIQUE KEY (ceza_id, katilimci_id) sayesinde
    // bir kullanıcının aynı davaya ikinci kez oy vermesi veritabanı seviyesinde engellenir.
    $sql = "INSERT INTO juri_oylari (ceza_id, katilimci_id, oy) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ceza_id, $katilimci_id, $oy]);

    // Her şey başarılıysa, kullanıcıyı ana sayfaya geri yönlendir.
    // Böylece verdiği oyun skoru nasıl etkilediğini görür.
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // Hata oluşursa, hatanın ne olduğunu kontrol et.
    // Hata kodu '23000' ise bu, UNIQUE KEY ihlali demektir, yani daha önce oy vermiş.
    if ($e->getCode() == '23000') {
        die("HATA: Bu davaya zaten daha önce oy vermişsiniz. Her jüri üyesi bir davaya sadece bir kez oy verebilir. <a href='index.php'>Geri Dön</a>");
    } else {
        // Farklı bir veritabanı hatası varsa onu göster.
        die("Veritabanı Hatası: " . $e->getMessage());
    }
}
?>