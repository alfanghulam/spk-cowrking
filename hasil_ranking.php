<?php
require_once 'config/database.php';
include 'includes/header.php';

$ranking = $pdo->query("
    SELECT r.*, a.kode, a.nama, a.alamat 
    FROM saw_results r 
    JOIN alternatives a ON r.alternative_id = a.id 
    ORDER BY r.ranking ASC
")->fetchAll();

$topLokasi = $ranking[0] ?? null;
?>

<div class="container py-5" style="margin-top: 5rem;">
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Hasil Rekomendasi Ranking</h3>
        <p class="text-muted">Urutan kelayakan lokasi co-working space berdasarkan skor preferensi V<sub>i</sub>.</p>
    </div>

    <?php if (empty($ranking)): ?>
        <div class="alert alert-secondary border-0 shadow-sm p-4">
            Belum ada data hasil perhitungan. Silakan isi nilai matriks pada <a href="input_matrix.php#section-matrix" class="fw-bold text-dark">Halaman Input data</a> terlebih dahulu.
        </div>
    <?php else: ?>
        <div class="card card-executive p-4 mb-4 border-start border-4 border-dark shadow-sm">
            <div class="d-flex align-items-center mb-2">
                <span class="badge bg-dark text-white me-2 fs-6">LOKASI TERBAIK (#1)</span>
                <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($topLokasi['kode']); ?> - <?= htmlspecialchars($topLokasi['nama']); ?></h4>
            </div>
            <p class="text-muted mb-2"><strong>Dasar Pemilihan:</strong> <?= htmlspecialchars($topLokasi['alamat']); ?></p>
            <p class="mb-0">⭐ <strong>Skor Preferensi Akhir (V<sub>1</sub>):</strong> <span class="badge bg-dark fs-6"><?= number_format($topLokasi['nilai_akhir'], 6); ?></span></p>
        </div>

        <div class="card card-executive p-3 shadow-sm">
            <h6 class="fw-bold mb-3 p-2">Tabel Urutan Peringkat Seluruh Lokasi</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Ranking</th>
                            <th>Kode</th>
                            <th>Alternatif Lokasi</th>
                            <th>Karakteristik Wilayah</th>
                            <th>Nilai Preferensi (V<sub>i</sub>)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $row): ?>
                        <tr class="<?= $row['ranking'] == 1 ? 'fw-bold table-light' : ''; ?>">
                            <td class="text-center">
                                <span class="badge bg-<?= $row['ranking'] == 1 ? 'dark' : 'secondary'; ?> px-3 py-2">
                                    #<?= $row['ranking']; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['kode']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                            <td><strong><?= number_format($row['nilai_akhir'], 6); ?></strong></td>
                            <td>
                                <?php if ($row['ranking'] == 1): ?>
                                    <span class="badge bg-dark">Prioritas Utama</span>
                                <?php elseif ($row['ranking'] == 2): ?>
                                    <span class="badge bg-secondary">Cadangan Utama</span>
                                <?php else: ?>
                                    <span class="badge bg-outline-secondary text-dark border">Alternatif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>