<?php
require_once 'config/database.php';
include 'includes/header.php';

// CEK STATUS LOGIN USER
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Data Kriteria & Alternatif dari Database
$kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

// Map Bobot AHP Desimal Presisi Baru
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

// Ambil data nilai matriks observasi dari database
$rawValues = $pdo->query("SELECT * FROM alternative_values")->fetchAll();

$matrix = [];
$hasData = false;

if (!empty($rawValues)) {
    foreach ($rawValues as $r) {
        $val = (float)$r['nilai'];
        $matrix[$r['alternative_id']][$r['criteria_id']] = $val;
        if ($val > 0) {
            $hasData = true;
        }
    }
}

// Inisialisasi variabel kalkulasi SAW
$minMax = [];
$matrixR = [];
$matrixV = [];
$vectorV = [];

if ($hasData) {
    // 1. Hitung Min/Max per Kriteria
    foreach ($kriteria as $c) {
        $col = [];
        foreach ($alternatif as $a) {
            $val = $matrix[$a['id']][$c['id']] ?? 0;
            if ($val > 0) {
                $col[] = $val;
            }
        }
        $minMax[$c['id']] = [
            'min' => !empty($col) ? min($col) : 1,
            'max' => !empty($col) ? max($col) : 1
        ];
    }

    // 2. Normalisasi R & Matriks Terbobot V
    foreach ($alternatif as $a) {
        $totalV = 0;
        foreach ($kriteria as $c) {
            $x = $matrix[$a['id']][$c['id']] ?? 0;
            $max = $minMax[$c['id']]['max'];
            $min = $minMax[$c['id']]['min'];

            $r = 0;
            if ($c['tipe'] == 'benefit') {
                $r = ($max > 0) ? ($x / $max) : 0;
            } else { // Cost
                $r = ($x > 0) ? ($min / $x) : 0;
            }

            $w = $bobotAHP[$c['kode']] ?? 0.1;
            $v = $r * $w;

            $matrixR[$a['id']][$c['id']] = $r;
            $matrixV[$a['id']][$c['id']] = $v;
            $totalV += $v;
        }
        $vectorV[$a['id']] = round($totalV, 6);
    }
}
?>

<div class="container py-5" style="margin-top: 5rem;">
    <!-- HEADER JUDUL -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Perhitungan Simple Additive Weighting (SAW)</h3>
        <p class="text-muted mb-0">Tahapan normalisasi matriks observasi R dan perkalian dengan bobot prioritas AHP V.</p>
    </div>

    <?php if (!$hasData): ?>
        <!-- PEMBERITAHUAN JIKA DATA KOSONG -->
        <div class="card card-executive p-5 text-center shadow-sm">
            <div class="py-4">
                <span class="fs-1 d-block mb-3"></span>
                <h4 class="fw-bold text-dark mb-2">Data Observasi Masih Kosong</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
                    Belum ada nilai observasi yang dimasukkan. Silakan isi matriks nilai terlebih dahulu untuk melihat kalkulasi SAW.
                </p>
                <a href="input_matrix.php" class="btn btn-dark px-4 py-2.5 fw-bold rounded-pill">
                    Ke Halaman Input Data
                </a>
            </div>
        </div>
    <?php else: ?>
<!-- 1. TABEL MATRIKS TERNORMALISASI R -->
<div class="card card-executive p-4 mb-4 shadow-sm reveal-on-scroll">
    <h5 class="fw-bold text-dark mb-3">1. Matriks Ternormalisasi (R)</h5>
    
    <!-- KETERANGAN ATURAN NORMALISASI TEKS BIASA -->
    <div class="alert alert-secondary border-0 py-2.5 px-3 mb-3 fs-7 text-dark">
        <strong class="d-block mb-1">Aturan Normalisasi:</strong>
        <span class="me-3">• <strong>Benefit Criteria:</strong> Nilai Input / Nilai Maksimum</span>
        <span>• <strong>Cost Criteria:</strong> Nilai Minimum / Nilai Input</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th style="min-width: 160px;" class="text-start">Alternatif</th>
                    <?php foreach ($kriteria as $c): ?>
                        <th>
                            <div class="fw-bold"><?= htmlspecialchars($c['kode']); ?></div>
                            <small class="fw-normal text-light d-block opacity-75 fs-7"><?= strtoupper($c['tipe']); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alternatif as $a): ?>
                <tr>
                    <td class="text-start fw-bold"><?= htmlspecialchars($a['kode']); ?> - <?= htmlspecialchars($a['nama']); ?></td>
                    <?php foreach ($kriteria as $c): ?>
                        <td><?= number_format($matrixR[$a['id']][$c['id']] ?? 0, 4, ',', '.'); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <tr class="fw-bold bg-light">
                    <td class="text-start text-dark">Bobot Prioritas (wj)</td>
                    <?php foreach ($kriteria as $c): ?>
                        <td class="text-dark"><?= number_format($bobotAHP[$c['kode']] ?? 0, 4, ',', '.'); ?></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>
        <!-- 2. TABEL MATRIKS TERBOBOT DAN SKOR PREFERENSI V -->
        <div class="card card-executive p-4 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3">2. Matriks Terbobot V dan Skor Preferensi Akhir (V<sub>i</sub>)</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 160px;" class="text-start">Alternatif</th>
                            <?php foreach ($kriteria as $c): ?>
                                <th><?= htmlspecialchars($c['kode']); ?></th>
                            <?php endforeach; ?>
                            <th style="background-color: #1e293b; color: #ffffff;" class="fw-bold">Skor (V<sub>i</sub>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $a): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= htmlspecialchars($a['kode']); ?> - <?= htmlspecialchars($a['nama']); ?></td>
                            <?php foreach ($kriteria as $c): ?>
                                <td><?= number_format($matrixV[$a['id']][$c['id']] ?? 0, 4, ',', '.'); ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold bg-light text-dark fs-6"><?= number_format($vectorV[$a['id']] ?? 0, 6, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <a href="hasil_ranking.php" class="btn btn-dark px-4 py-2.5 fw-bold rounded-3">
                    Tampilkan Hasil Ranking ►
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>