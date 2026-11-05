<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Hapus data perhitungan aktif milik user yang sedang logout jika tabel memiliki user_id
    // $stmt = $pdo->prepare("DELETE FROM alternative_values WHERE user_id = ?");
    // $stmt->execute([$userId]);
    
    // Kosongkan tabel aktif sementara
    $pdo->exec("DELETE FROM alternative_values");
    $pdo->exec("DELETE FROM saw_results");
}

session_unset();
session_destroy();

header("Location: index.php");
exit;