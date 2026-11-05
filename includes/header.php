<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$usernameDisplay = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengguna';
$pageTitles = [
    'index.php' => ['Dashboard', 'Ringkasan sistem dan peta lokasi alternatif'],
    'alternatif.php' => ['Data Alternatif', 'Kelola dan telusuri lokasi co-working space'],
    'kriteria.php' => ['Data Kriteria', 'Indikator penilaian dalam model keputusan'],
    'input_matrix.php' => ['Matriks AHP', 'Matriks perbandingan berpasangan'],
    'proses_ahp.php' => ['Hasil AHP', 'Bobot prioritas dan pemeriksaan konsistensi'],
    'nilai_alternatif.php' => ['Nilai Alternatif', 'Matriks keputusan alternatif'],
    'perhitungan_saw.php' => ['Perhitungan SAW', 'Normalisasi dan kontribusi nilai preferensi'],
    'hasil_ranking.php' => ['Hasil Ranking', 'Urutan rekomendasi berdasarkan skor akhir'],
    'profile.php' => ['Profil Pengguna', 'Akun dan riwayat aktivitas perhitungan']
];
$pageMeta = $pageTitles[$currentPage] ?? ['SPK AHP–SAW', 'Sistem Pendukung Keputusan Lokasi Co-working Space'];
$protectedPages = ['input_matrix.php', 'nilai_alternatif.php', 'perhitungan_saw.php', 'hasil_ranking.php', 'profile.php'];
$needsLogin = in_array($currentPage, $protectedPages, true) && !$isLoggedIn;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
      (function () {
        try {
          var savedTheme = localStorage.getItem('spk-theme') || 'light';
          document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
        } catch (e) {
          document.documentElement.setAttribute('data-theme', 'light');
        }
      })();
    </script>
    <title><?= htmlspecialchars($pageMeta[0]); ?> | SPK Co-working Space</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-app">
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="app-sidebar" id="appSidebar">
        <a href="index.php" class="sidebar-brand text-decoration-none">
            <span class="brand-symbol"><i class="bi bi-buildings-fill"></i></span>
            <span class="brand-copy"><strong>SPK Co-working</strong><small>AHP &amp; SAW Decision System</small></span>
        </a>

        <div class="sidebar-caption">WORKSPACE</div>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <a href="index.php" class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
            <a href="alternatif.php" class="sidebar-link <?= $currentPage === 'alternatif.php' ? 'active' : ''; ?>"><i class="bi bi-geo-alt-fill"></i><span>Data Alternatif</span></a>
            <a href="kriteria.php" class="sidebar-link <?= $currentPage === 'kriteria.php' ? 'active' : ''; ?>"><i class="bi bi-list-check"></i><span>Data Kriteria</span></a>
            <a href="input_matrix.php" class="sidebar-link <?= $currentPage === 'input_matrix.php' ? 'active' : ''; ?>"><i class="bi bi-grid-3x3-gap-fill"></i><span>Matriks AHP</span></a>
            <a href="proses_ahp.php" class="sidebar-link <?= $currentPage === 'proses_ahp.php' ? 'active' : ''; ?>"><i class="bi bi-diagram-3-fill"></i><span>Hasil AHP</span></a>
            <a href="nilai_alternatif.php" class="sidebar-link <?= $currentPage === 'nilai_alternatif.php' ? 'active' : ''; ?>"><i class="bi bi-sliders2"></i><span>Nilai Alternatif</span></a>
            <a href="perhitungan_saw.php" class="sidebar-link <?= $currentPage === 'perhitungan_saw.php' ? 'active' : ''; ?>"><i class="bi bi-calculator-fill"></i><span>Perhitungan SAW</span></a>
            <a href="hasil_ranking.php" class="sidebar-link <?= $currentPage === 'hasil_ranking.php' ? 'active' : ''; ?>"><i class="bi bi-trophy-fill"></i><span>Hasil Ranking</span></a>
            <a href="index.php#map-panel" class="sidebar-link"><i class="bi bi-map-fill"></i><span>Peta Lokasi</span></a>
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="sidebar-link <?= $currentPage === 'profile.php' ? 'active' : ''; ?>"><i class="bi bi-person-circle"></i><span>Profil &amp; Riwayat</span></a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-help-card">
                <span class="help-icon"><i class="bi bi-stars"></i></span>
                <strong>Keputusan lebih terukur</strong>
                <p>Bandingkan alternatif dengan metode AHP dan SAW.</p>
                <a href="proses_ahp.php">Jelajahi metode <i class="bi bi-arrow-up-right"></i></a>
            </div>
            <?php if ($isLoggedIn): ?>
                <div class="sidebar-user">
                    <span class="user-avatar"><i class="bi bi-person-fill"></i></span>
                    <span class="user-copy"><strong><?= htmlspecialchars($usernameDisplay); ?></strong><small>Pengguna sistem</small></span>
                    <a href="logout.php" title="Keluar" aria-label="Keluar" onclick="return confirm('Apakah Anda yakin ingin keluar?')"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            <?php else: ?>
                <div class="sidebar-auth-actions">
                    <a href="login.php" class="btn btn-primary w-100">Masuk ke sistem <i class="bi bi-arrow-right ms-1"></i></a>
                    <a href="register.php" class="sidebar-register">Belum punya akun? <strong>Daftar</strong></a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
                <div class="topbar-heading"><span class="topbar-eyebrow">SISTEM PENDUKUNG KEPUTUSAN</span><h1><?= htmlspecialchars($pageMeta[0]); ?></h1><p><?= htmlspecialchars($pageMeta[1]); ?></p></div>
            </div>
            <div class="topbar-right">
                <span class="system-status"><span></span> Sistem aktif</span>
                <div class="topbar-location"><i class="bi bi-geo-alt"></i><span>Yogyakarta</span></div>
                <div class="topbar-profile"><span class="profile-avatar"><i class="bi bi-person-fill"></i></span><span><strong><?= htmlspecialchars($isLoggedIn ? $usernameDisplay : 'Pengunjung'); ?></strong><small><?= $isLoggedIn ? 'Pengguna' : 'Mode publik'; ?></small></span></div>
            </div>
        </header>
        <main class="app-content">

<div class="modal fade" id="modalAuthWarning" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-warning-modal border-0">
            <div class="modal-header border-0 pb-0"><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body text-center px-4 pb-4 pt-2">
                <span class="auth-warning-icon"><i class="bi bi-shield-lock"></i></span>
                <h4 class="fw-bold mt-3">Login terlebih dahulu</h4>
                <p class="text-muted">Masuk ke akun untuk menggunakan input matriks, perhitungan SAW, dan hasil ranking.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap"><button class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button><a href="login.php" class="btn btn-primary px-4">Login sekarang</a></div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggle = document.getElementById('sidebarToggle');
    const closeSidebar = () => { sidebar?.classList.remove('sidebar-open'); backdrop?.classList.remove('show'); document.body.classList.remove('sidebar-visible'); };
    toggle?.addEventListener('click', () => { sidebar?.classList.toggle('sidebar-open'); backdrop?.classList.toggle('show'); document.body.classList.toggle('sidebar-visible'); });
    backdrop?.addEventListener('click', closeSidebar);
    sidebar?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { if (window.innerWidth < 992) closeSidebar(); }));

    document.querySelectorAll('.app-content .dashboard-welcome, .app-content .metric-card, .app-content .panel-card, .app-content .card-executive, .app-content .section-title-card').forEach((el, index) => {
        el.classList.add('reveal-on-scroll');
        if (index % 3 === 1) el.classList.add('motion-delay-1');
        if (index % 3 === 2) el.classList.add('motion-delay-2');
    });
    const revealObserver = new IntersectionObserver((entries) => entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); revealObserver.unobserve(entry.target); }
    }), { threshold: 0.06 });
    document.querySelectorAll('.reveal-on-scroll').forEach(el => revealObserver.observe(el));
});
function openAuthModal() {
    const modalEl = document.getElementById('modalAuthWarning');
    if (modalEl && window.bootstrap) new bootstrap.Modal(modalEl).show();
    else window.location.href = 'login.php';
}
<?php if ($needsLogin): ?>document.addEventListener('DOMContentLoaded', openAuthModal);<?php endif; ?>
</script>
