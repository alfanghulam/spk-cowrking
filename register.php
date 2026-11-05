<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetch()) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
            if ($stmtInsert->execute([$username, $hashedPassword, $nama_lengkap])) {
                $success = 'Pendaftaran berhasil! Silakan login.';
            } else {
                $error = 'Gagal mendaftar, coba lagi nanti.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SPK Co-Working Space</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-card card card-executive border-0 shadow-sm">
            <div class="text-center mb-4">
                <span class="auth-brand mb-3"><i class="bi bi-person-plus-fill fs-3"></i></span>
                <h3 class="fw-bold mb-1">Registrasi Akun</h3>
                <p class="text-muted mb-0">Buat akun baru untuk menggunakan sistem SPK.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-3 fs-7 mb-3"><?= $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-3 fs-7 mb-3"><?= $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                </div>
                <button type="submit" name="register" class="btn btn-dark w-100 fw-bold py-3 mb-3">
                    <i class="bi bi-check2-circle me-1"></i> Daftar Sekarang
                </button>
            </form>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                <a href="index.php" class="text-decoration-none text-muted fw-semibold">← Kembali ke beranda</a>
                <div class="fs-7">Sudah punya akun? <a href="login.php" class="fw-bold text-dark text-decoration-none">Login</a></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
