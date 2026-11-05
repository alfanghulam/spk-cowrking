<?php
require_once 'config/database.php';
include 'includes/header.php';

// Ambil data alternatif lokasi terbaru dari database
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();
?>

<div class="container py-5" style="margin-top: 5rem;">
    <!-- HEADER JUDUL -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Alternatif Lokasi Co-working Space</h3>
        <p class="text-muted mb-0">Daftar lokasi strategis co-working space di wilayah Yogyakarta.</p>
    </div>

    <!-- TABEL ALTERNATIF LOKASI -->
    <div class="card card-executive p-4 shadow-sm mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;" class="text-center">Kode</th>
                        <th style="min-width: 250px;">Nama Tempat</th>
                        <th style="min-width: 300px;">Alamat / Lokasi</th>
                        <th style="width: 180px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($alternatif)): ?>
                        <?php foreach ($alternatif as $a): ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-dark px-2.5 py-1.5 fs-7 fw-bold"><?= htmlspecialchars($a['kode']); ?></span>
                            </td>
                            <td>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($a['nama']); ?></strong>
                            </td>
                            <td class="text-muted">
                                <?= htmlspecialchars($a['alamat']); ?>
                            </td>
                            <td class="text-center">
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($a['nama'] . ' ' . $a['alamat']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-semibold">
                                    📍 Lihat di Maps
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada data alternatif lokasi tersimpan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>