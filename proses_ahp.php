<?php
require_once 'config/database.php';
include 'includes/header.php';

// Matriks Perbandingan Berpasangan
$ahpMatrix = [
    'C1' => ['C1' => 1,     'C2' => 1,     'C3' => 1/3, 'C4' => 1/3, 'C5' => 1/3, 'C6' => 1,     'C7' => 3, 'C8' => 3],
    'C2' => ['C1' => 1,     'C2' => 1,     'C3' => 1/3, 'C4' => 1/3, 'C5' => 1/3, 'C6' => 1,     'C7' => 3, 'C8' => 3],
    'C3' => ['C1' => 3,     'C2' => 3,     'C3' => 1,   'C4' => 1,   'C5' => 1,   'C6' => 3,     'C7' => 5, 'C8' => 5],
    'C4' => ['C1' => 3,     'C2' => 3,     'C3' => 1,   'C4' => 1,   'C5' => 1,   'C6' => 3,     'C7' => 5, 'C8' => 5],
    'C5' => ['C1' => 3,     'C2' => 3,     'C3' => 1,   'C4' => 1,   'C5' => 1,   'C6' => 3,     'C7' => 5, 'C8' => 5],
    'C6' => ['C1' => 1,     'C2' => 1,     'C3' => 1/3, 'C4' => 1/3, 'C5' => 1/3, 'C6' => 1,     'C7' => 3, 'C8' => 3],
    'C7' => ['C1' => 1/3,   'C2' => 1/3,   'C3' => 1/5, 'C4' => 1/5, 'C5' => 1/5, 'C6' => 1/3,   'C7' => 1, 'C8' => 1],
    'C8' => ['C1' => 1/3,   'C2' => 1/3,   'C3' => 1/5, 'C4' => 1/5, 'C5' => 1/5, 'C6' => 1/3,   'C7' => 1, 'C8' => 1],
];

// Label Kriteria Baru
$kriteriaList = [
    'C1' => 'Aksesibilitas lokasi',
    'C2' => 'Jarak pusat aktivitas',
    'C3' => 'Kualitas lingkungan ruang',
    'C4' => 'Fasilitas coworking space',
    'C5' => 'Kualitas jaringan internet',
    'C6' => 'Keamanan lingkungan',
    'C7' => 'Ketersediaan parkir',
    'C8' => 'Aktivitas kawasan'
];

// Total Kolom
$totalKolomFormat = [
    'C1' => 12.67, 'C2' => 12.67, 'C3' => 4.40, 'C4' => 4.40,
    'C5' => 4.40,  'C6' => 12.67, 'C7' => 26.00, 'C8' => 26.00
];

// Bobot Prioritas Desimal Presisi
$bobotAHP = [
    'C1' => 0.086860508,
    'C2' => 0.086860508,
    'C3' => 0.222119985,
    'C4' => 0.222119985,
    'C5' => 0.222119985,
    'C6' => 0.086860508,
    'C7' => 0.03652926,
    'C8' => 0.03652926
];
?>

<div class="container py-5" style="margin-top: 5rem;">
    <!-- HEADER JUDUL -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Proses Pembobotan AHP</h3>
        <p class="text-muted mb-0">Matriks Perbandingan Berpasangan (Pairwise Comparison Matrix) dan Hasil Prioritas Kriteria.</p>
    </div>

    <!-- 1. MATRIKS PERBANDINGAN BERPASANGAN -->
    <div class="card card-executive p-4 mb-4 shadow-sm">
        <h5 class="fw-bold text-dark mb-3">2. Matriks Perbandingan Berpasangan</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width: 160px;" class="text-start">Kriteria</th>
                        <?php foreach (array_keys($kriteriaList) as $k): ?>
                            <th><?= $k; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteriaList as $rowKey => $rowName): ?>
                    <tr>
                        <td class="text-start fw-bold"><?= $rowKey; ?></td>
                        <?php foreach (array_keys($kriteriaList) as $colKey): 
                            $val = $ahpMatrix[$rowKey][$colKey];
                            if ($val == 1) {
                                $disp = "1";
                            } elseif ($val < 1) {
                                $disp = "1/" . round(1/$val);
                            } else {
                                $disp = round($val);
                            }
                        ?>
                            <td><?= $disp; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold bg-light">
                        <td class="text-start text-dark">Total kolom</td>
                        <?php foreach ($totalKolomFormat as $colKey => $sumVal): ?>
                            <td class="text-dark"><?= number_format($sumVal, 2, ',', '.'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. HASIL BOBOT PRIORITAS KRITERIA -->
    <div class="card card-executive p-4 shadow-sm">
        <h5 class="fw-bold text-dark mb-3">Bobot Prioritas (rata-rata baris)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Kode</th>
                        <th class="text-start">Nama Kriteria</th>
                        <th style="width: 240px;">Bobot Prioritas (rata-rata baris)</th>
                        <th style="width: 160px;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalBobot = 0;
                    foreach ($kriteriaList as $kKode => $kNama): 
                        $w = $bobotAHP[$kKode];
                        $totalBobot += $w;
                    ?>
                    <tr>
                        <td class="fw-bold"><?= $kKode; ?></td>
                        <td class="text-start fw-semibold"><?= $kNama; ?></td>
                        <td class="fw-bold text-dark"><?= number_format($w, 8, ',', '.'); ?></td>
                        <td class="fw-bold text-success"><?= number_format($w * 100, 2, ',', '.'); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="2" class="text-end text-dark">Total:</td>
                        <td class="text-dark"><?= round($totalBobot); ?></td>
                        <td class="text-success"><?= round($totalBobot * 100); ?>%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>