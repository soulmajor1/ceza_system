<?php
session_start(); // Oturumu başlat

session_unset(); // Tüm oturum değişkenlerini temizle

session_destroy(); // Oturumu yok et

// Kullanıcıyı ana sayfaya yönlendir
header('Location: index.php');
exit;
?>