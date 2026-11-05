<?php
$host = 'sql210.byethost4.com';
$db   = 'b4_43262963_db_spk';
$user = 'b4_43262963';
$pass = '$kCh2y7DL$!qDCp';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}