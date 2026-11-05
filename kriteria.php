<?php
require_once 'config/database.php';
include 'includes/header.php';

// Data Baku Kriteria Baru Sesuai Jurnal Gambar
$kriteriaBaku = [
    ['kode' => 'C1', 'nama' => 'Aksesibilitas lokasi',     'tipe' => 'Benefit', 'jurnal' => 4, 'bobot' => 0.086860508],
    ['kode' => 'C2', 'nama' => 'Jarak pusat aktivitas',    'tipe' => 'Cost',    'jurnal' => 4, 'bobot' => 0.086860508],
    ['kode' => 'C3', 'nama' => 'Kualitas lingkungan ruang','tipe' => 'Benefit', 'jurnal' => 5, 'bobot' => 0.222119985],
    ['kode' => 'C4', 'nama' => 'Fasilitas coworking space','tipe' => 'Benefit', 'jurnal' => 5, 'bobot' => 0.222119985],
    ['kode' => 'C5', 'nama' => 'Kualitas jaringan internet','tipe' => 'Benefit','jurnal' => 5, 'bobot' => 0.222119985],
    ['kode' => 'C6', 'nama' => 'Keamanan lingkungan',     'tipe' => 'Benefit', 'jurnal' => 4, 'bobot' => 0.086860508],
    ['kode' => 'C7', 'nama' => 'Ketersediaan parkir',      'tipe' => 'Benefit', 'jurnal' => 3, 'bobot' => 0.03652926],
    ['kode' => 'C8', 'nama' => 'Aktivitas kawasan',        'tipe' => 'Benefit', 'jurnal' => 3, 'bobot' => 0.03652926],
];
?>

<div class="container py-5" style="margin-top: 5rem;">
    <!-- HEADER JUDUL -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Daftar Kriteria Penilaian AHP</h3>
        <p class="text-muted mb-0">Indikator baku yang digunakan dalam pembobotan Analytical Hierarchy Process (AHP).</p>
    </div>

    <!-- TABEL KRITERIA -->
    <div class="card card-executive p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle text-center border">
                <thead style="background-color: #1e293b; color: #ffffff;">
                    <tr>
                        <th style="width: 80px;">Kode</th>
                        <th class="text-start">Nama Kriteria</th>
                        <th style="width: 140px;">Tipe</th>
                        <th style="width: 120px;">Jurnal</th>
                        <th style="width: 200px;">Bobot Prioritas ($w_j$)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalBobot = 0;
                    foreach ($kriteriaBaku as $k): 
                        $totalBobot += $k['bobot'];
                    ?>
                    <tr>
                        <td class="fw-bold text-dark"><?= $k['kode']; ?></td>
                        <td class="text-start">
                            <strong class="text-dark fs-6"><?= htmlspecialchars($k['nama']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?= strtolower($k['tipe']) == 'benefit' ? 'dark' : 'secondary'; ?>">
                                <?= $k['tipe']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?= $k['jurnal']; ?></span>
                        </td>
                        <td class="fw-bold text-dark">
                            <?= number_format($k['bobot'], 8, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="4" class="text-end text-dark">Total Bobot:</td>
                        <td class="text-dark"><?= round($totalBobot); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>