<?php
session_start();
require 'db.php';

// Giriş yapmış kullanıcının ID'sini al, yapmamışsa null ata.
$giris_yapan_id = $_SESSION['katilimci_id'] ?? null;

// === LİDERLİK TABLOSU VERİSİNİ ÇEKME ===
// Bu sorgu, her bir sanığın (isim) toplam ceza puanını hesaplar.
// Toplam Puan = Kendi davalarındaki tüm AI Puanlarının toplamı + Kendi davalarındaki tüm Jüri Oylarının toplamı.
$liderlik_sql = "
    SELECT 
        c.isim,
        SUM(
            c.ai_ceza_puani + IFNULL((SELECT SUM(jo.oy) FROM juri_oylari jo WHERE jo.ceza_id = c.id), 0)
        ) AS toplam_puan
    FROM 
        cezalar c
    GROUP BY 
        c.isim
    HAVING
        toplam_puan > 0
    ORDER BY 
        toplam_puan DESC";

$liderlik_stmt = $pdo->query($liderlik_sql);
$ceza_liderleri = $liderlik_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dota 2 Yüksek Ceza Divanı</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="site-wrapper">
        <div class="container">
            <h1>Dota 2 Yüksek Ceza Divanı</h1>

            <?php if ($giris_yapan_id): ?>
                <a href="ceza_ekle.php" class="yeni-ceza-butonu">Yeni Suç Duyurusu Yap</a>
            <?php else: ?>
                <p><strong>Yeni bir dava eklemek veya jüri olarak oy kullanmak için lütfen <a href="login.php">giriş yapın</a>.</strong></p>
            <?php endif; ?>

            <div class="liderlik-wrapper">
                <h2>Ceza Puanı Liderlik Tablosu</h2>
                <table class="liderlik-tablosu">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>İsim</th>
                            <th>Toplam Ceza Puanı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ceza_liderleri)): ?>
                            <tr><td colspan="3">Henüz ceza alan kimse yok. Herkes masum!</td></tr>
                        <?php else: ?>
                            <?php $sira = 1; ?>
                            <?php foreach ($ceza_liderleri as $lider): ?>
                                <tr>
                                    <td><?= $sira++ ?>.</td>
                                    <td><?= htmlspecialchars($lider['isim']) ?></td>
                                    <td class="toplam-puan-liderlik"><?= $lider['toplam_puan'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <hr class="divider">

            <h2>Dava Dosyaları</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sanık</th>
                            <th>Olay Tarihi</th>
                            <th>Bahane</th>
                            <th>AI Puanı</th>
                            <th class="aciklama-hucre">Yargıç Mütalaası</th>
                            <th>Jüri Puanı (Detaylı)</th>
                            <th>Nihai Puan</th>
                            <th>Jüri Oyu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ana Sorgu: cezalar tablosunu katilimcilar tablosu ile birleştirerek sanığın ID'sini de alır.
                        $stmt = $pdo->query(
                            "SELECT c.*, k.id AS sanik_id 
                             FROM cezalar c 
                             JOIN katilimcilar k ON c.isim = k.isim 
                             ORDER BY c.bahane_tarihi DESC, c.id DESC"
                        );

                        if ($stmt->rowCount() == 0): ?>
                            <tr><td colspan="9">Henüz divana intikal etmiş bir dava dosyası bulunmamaktadır.</td></tr>
                        <?php else: 
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                // Jüri notu toplamını hesapla
                                $juriStmt = $pdo->prepare("SELECT SUM(oy) AS toplam_oy FROM juri_oylari WHERE ceza_id = ?");
                                $juriStmt->execute([$row['id']]);
                                $juri_notu_toplami = $juriStmt->fetchColumn() ?: 0;
                                
                                // Oylama detaylarını çek
                                $detayStmt = $pdo->prepare(
                                    "SELECT k.isim, jo.oy FROM juri_oylari jo JOIN katilimcilar k ON jo.katilimci_id = k.id WHERE jo.ceza_id = ? ORDER BY k.isim ASC"
                                );
                                $detayStmt->execute([$row['id']]);
                                $oy_detaylari = $detayStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Nihai puanı hesapla
                                $nihai_puan = $row['ai_ceza_puani'] + $juri_notu_toplami;
                        ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['isim']) ?></td>
                                <td><?= date('d.m.Y', strtotime($row['bahane_tarihi'])) ?></td>
                                <td><?= htmlspecialchars($row['bahane']) ?></td>
                                <td class="puan-hucre"><?= $row['ai_ceza_puani'] ?></td>
                                <td class="aciklama-hucre"><?= htmlspecialchars($row['ai_aciklama']) ?></td>
                                <td class="juri-detay-hucre">
                                    <span class="toplam-puan"><?= ($juri_notu_toplami >= 0 ? '+' : '') . $juri_notu_toplami ?></span>
                                    <div class="oy-detaylari">
                                    <?php if (empty($oy_detaylari)): ?>
                                        <small>Henüz oy kullanılmadı.</small>
                                    <?php else: ?>
                                        <?php foreach ($oy_detaylari as $detay): ?>
                                            <small><?= htmlspecialchars($detay['isim']) ?>: <strong><?= ($detay['oy'] > 0 ? '+' : '') . $detay['oy'] ?></strong></small>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </div>
                                </td>
                                <td class="puan-hucre nihai-puan"><strong><?= $nihai_puan ?></strong></td>
                                <td class="oylama-hucre">
                                    <?php if ($giris_yapan_id):
                                        // Sanık kendi davasında oy kullanamaz kuralı
                                        if ($giris_yapan_id == $row['sanik_id']): ?>
                                            <span class="oy-verilemez">Sanık kendi davasında oy kullanamaz.</span>
                                        <?php else:
                                            // Normal oylama mantığı
                                            $oyStmt = $pdo->prepare("SELECT oy FROM juri_oylari WHERE ceza_id = ? AND katilimci_id = ?");
                                            $oyStmt->execute([$row['id'], $giris_yapan_id]);
                                            $mevcut_oy = $oyStmt->fetchColumn();
                                            if ($mevcut_oy !== false): ?>
                                                <span class="oy-verildi">Oy verdin: <?= ($mevcut_oy > 0 ? '+' : '') . $mevcut_oy ?></span>
                                            <?php else: ?>
                                                <form action="juri_oyla.php" method="post">
                                                    <input type="hidden" name="ceza_id" value="<?= $row['id'] ?>">
                                                    <select name="oy">
                                                        <?php for ($i = 5; $i >= -5; $i--): 
                                                            if ($i == 0) continue; ?>
                                                            <option value="<?= $i ?>"><?= ($i > 0 ? '+' : '') . $i ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <button type="submit">Oy Ver</button>
                                                </form>
                                            <?php endif; 
                                        endif;
                                    else: ?>
                                        <a href="login.php" class="login-link">Oy ver</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div> </div> </body>
</html>