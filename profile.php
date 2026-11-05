<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'config/database.php';

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna SPK';

// Riwayat hanya menampilkan data milik akun yang sedang masuk.
$stmtHistories = $pdo->prepare("SELECT * FROM matrix_history WHERE user_id = ? ORDER BY created_at DESC");
$stmtHistories->execute([$userId]);
$histories = $stmtHistories->fetchAll();
$kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="container profile-page-container">
    <section class="profile-page-heading mb-4">
        <div>
            <span class="section-eyebrow"><i class="bi bi-person-gear"></i> PENGATURAN AKUN</span>
            <h2 class="profile-page-title">Profil &amp; Preferensi</h2>
            <p class="text-muted mb-0">Kelola sesi akun, tampilan aplikasi, dan riwayat perhitungan SPK kamu.</p>
        </div>
        <div class="profile-page-actions">
            <button type="button" class="btn btn-theme-toggle" id="themeToggleButton" aria-pressed="false">
                <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
                <span id="themeToggleLabel">Aktifkan mode gelap</span>
            </button>
            <a href="logout.php" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </a>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-lg-5">
            <section class="card card-executive profile-account-card h-100">
                <div class="profile-avatar-large"><i class="bi bi-person-fill"></i></div>
                <span class="profile-account-eyebrow">AKUN AKTIF</span>
                <h3 class="profile-account-name"><?= htmlspecialchars($namaLengkap); ?></h3>
                <p class="profile-account-username">@<?= htmlspecialchars($username); ?></p>
                <div class="profile-account-status"><span></span> Sesi sedang aktif</div>
                <div class="profile-account-divider"></div>
                <div class="profile-account-meta">
                    <span><i class="bi bi-shield-check"></i> Akun terautentikasi</span>
                    <span><i class="bi bi-clock-history"></i> Riwayat tersimpan</span>
                </div>
            </section>
        </div>
        <div class="col-xl-8 col-lg-7">
            <section class="card card-executive profile-preferences-card h-100">
                <div class="profile-section-heading">
                    <span class="profile-section-icon"><i class="bi bi-sliders2"></i></span>
                    <div><h3>Preferensi aplikasi</h3><p>Sesuaikan tampilan tanpa mengubah data maupun hasil perhitungan.</p></div>
                </div>
                <div class="profile-preference-row">
                    <span class="preference-icon preference-blue"><i class="bi bi-moon-stars"></i></span>
                    <div class="preference-copy"><strong>Mode tampilan</strong><small>Mode terang atau gelap akan diingat saat berpindah halaman.</small></div>
                    <span class="preference-tag" id="themeStatusTag">Mode terang</span>
                </div>
                <div class="profile-preference-row">
                    <span class="preference-icon preference-green"><i class="bi bi-clock-history"></i></span>
                    <div class="preference-copy"><strong>Riwayat perhitungan</strong><small>Riwayat menyimpan rekomendasi teratas dan matriks yang digunakan.</small></div>
                    <span class="preference-tag"><?= count($histories); ?> catatan</span>
                </div>
                
            </section>
        </div>
    </div>

    <section class="card card-executive profile-history-card">
        <div class="profile-history-heading">
            <div class="profile-section-heading mb-0">
                <span class="profile-section-icon history-icon"><i class="bi bi-journal-text"></i></span>
                <div><h3>Riwayat data &amp; perhitungan SPK</h3><p>Daftar hasil perhitungan yang pernah disimpan oleh akun ini.</p></div>
            </div>
            <div class="profile-history-count"><strong><?= count($histories); ?></strong><span>Total riwayat</span></div>
        </div>

        <?php if (empty($histories)): ?>
            <div class="profile-empty-history">
                <span class="empty-state-icon"><i class="bi bi-journal-x"></i></span>
                <h4>Belum ada riwayat perhitungan</h4>
                <p>Setelah menyimpan perhitungan melalui halaman Matriks AHP, hasilnya akan muncul di sini.</p>
                <a href="input_matrix.php" class="btn btn-primary"><i class="bi bi-grid-3x3-gap me-1"></i> Buka Matriks AHP</a>
            </div>
        <?php else: ?>
            <div class="table-responsive profile-history-table-wrap">
                <table class="table table-hover align-middle profile-history-table">
                    <thead>
                        <tr><th>Waktu perhitungan</th><th>Rekomendasi utama</th><th>Skor preferensi</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($histories as $h): ?>
                        <tr>
                            <td><span class="history-date"><i class="bi bi-calendar3"></i><?= date('d M Y, H:i', strtotime($h['created_at'])); ?> WIB</span></td>
                            <td><span class="history-winner"><i class="bi bi-trophy-fill"></i><?= htmlspecialchars($h['top_alternative']); ?></span></td>
                            <td><strong class="history-score"><?= number_format((float)$h['top_score'], 6); ?></strong></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalHist<?= (int)$h['id']; ?>"><i class="bi bi-eye me-1"></i> Detail</button>
                                <a href="input_matrix.php?action=delete_history&amp;id=<?= (int)$h['id']; ?>&amp;return=profile" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus riwayat perhitungan ini?')" aria-label="Hapus riwayat"><i class="bi bi-trash3"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="profile-history-footer"><span><i class="bi bi-shield-lock me-1"></i> Riwayat ditampilkan berdasarkan akun yang sedang masuk.</span><a href="input_matrix.php?action=clear_all_history&amp;return=profile" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus seluruh riwayat perhitungan akun ini? Tindakan ini tidak dapat dibatalkan.')"><i class="bi bi-trash3 me-1"></i> Hapus semua riwayat</a></div>
        <?php endif; ?>
    </section>
</div>

<?php if (!empty($histories)): ?>
    <?php foreach ($histories as $h): ?>
    <div class="modal fade" id="modalHist<?= (int)$h['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content history-detail-modal">
                <div class="modal-header">
                    <div><span class="section-eyebrow">ARSIP PERHITUNGAN</span><h5 class="modal-title fw-bold">Detail Matriks</h5><small class="text-muted"><?= date('d M Y, H:i', strtotime($h['created_at'])); ?> WIB</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="history-detail-summary"><span class="history-winner"><i class="bi bi-trophy-fill"></i><?= htmlspecialchars($h['top_alternative']); ?></span><span>Skor preferensi <strong><?= number_format((float)$h['top_score'], 6); ?></strong></span></div>
                    <div class="table-responsive"><table class="table table-sm table-bordered align-middle text-center history-matrix-table"><thead><tr><th>Alternatif</th><?php foreach ($kriteria as $c): ?><th><?= htmlspecialchars($c['kode']); ?></th><?php endforeach; ?></tr></thead><tbody>
                    <?php $mData = json_decode($h['matrix_data'], true) ?: []; foreach ($alternatif as $a): ?><tr><td class="text-start fw-bold"><?= htmlspecialchars($a['kode']); ?> · <?= htmlspecialchars($a['nama']); ?></td><?php foreach ($kriteria as $c): ?><td><?= htmlspecialchars((string)($mData[$a['id']][$c['id']] ?? 0)); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                    </tbody></table></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
