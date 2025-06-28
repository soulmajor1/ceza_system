<?php
$host = 'localhost';
$dbname = 'ceza_system';
$user = 'root'; // Veritabanı kullanıcı adınız
$pass = '';     // Veritabanı şifreniz

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}
?>