<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// MAP BOBOT AHP DESIMAL PRESISI BARU UNTUK 8 KRITERIA (C1 - C8)
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

// PROSES SIMPAN NILAI OBSERVASI + KALKULASI OTOMATIS AHP & SAW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_spk'])) {
    try {
        $pdo->beginTransaction();

        $kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
        $alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

        // 1. Reset Nilai Matriks Lama Khusus User Ini & Simpan Input Baru
        $stmtDelVal = $pdo->prepare("DELETE FROM alternative_values WHERE user_id = ?");
        $stmtDelVal->execute([$userId]);

        if (isset($_POST['nilai']) && is_array($_POST['nilai'])) {
            $stmtInsert = $pdo->prepare("INSERT INTO alternative_values (alternative_id, criteria_id, user_id, nilai) VALUES (?, ?, ?, ?)");
            foreach ($_POST['nilai'] as $alt_id => $c_list) {
                foreach ($c_list as $crit_id => $val) {
                    $stmtInsert->execute([$alt_id, $crit_id, $userId, (float)$val]);
                }
            }
        }

        // 2. Set & Simpan Bobot AHP
        $pdo->exec("DELETE FROM ahp_weights");
        $weights = [];
        $stmtAHP = $pdo->prepare("INSERT INTO ahp_weights (criteria_id, bobot, cr) VALUES (?, ?, 0)");
        
        foreach ($kriteria as $c) {
            $kode = $c['kode'];
            $w = $bobotAHP[$kode] ?? 0.1;
            $weights[$c['id']] = $w;
            $stmtAHP->execute([$c['id'], $w]);
        }

        // 3. Fetch Matrix Nilai Terbaru Milik User Ini untuk Normalisasi SAW
        $matrix = [];
        $stmtRaw = $pdo->prepare("SELECT * FROM alternative_values WHERE user_id = ?");
        $stmtRaw->execute([$userId]);
        $raw = $stmtRaw->fetchAll();

        foreach ($raw as $r) {
            $matrix[$r['alternative_id']][$r['criteria_id']] = (float)$r['nilai'];
        }

        // 4. Hitung Min & Max per Kriteria
        $minMax = [];
        foreach ($kriteria as $c) {
            $col = [];
            foreach ($alternatif as $a) {
                $v = $matrix[$a['id']][$c['id']] ?? 0;
                if ($v > 0) $col[] = $v;
            }
            $minMax[$c['id']] = [
                'min' => !empty($col) ? min($col) : 1,
                'max' => !empty($col) ? max($col) : 1,
            ];
        }

        // 5. Normalisasi SAW & Perhitungan Skor Preferensi Akhir (Vi)
        $vResults = [];
        foreach ($alternatif as $a) {
            $vi = 0;
            foreach ($kriteria as $c) {
                $x = $matrix[$a['id']][$c['id']] ?? 0;
                $max = $minMax[$c['id']]['max'];
                $min = $minMax[$c['id']]['min'];

                $r = ($c['tipe'] == 'benefit') ? (($max > 0) ? ($x / $max) : 0) : (($x > 0) ? ($min / $x) : 0);
                $vi += $r * ($weights[$c['id']] ?? 0);
            }
            $vResults[$a['id']] = round($vi, 6);
        }

        // 6. Simpan Hasil Perengkingan & Histori Khusus User Ini
        arsort($vResults);

        $stmtDelSaw = $pdo->prepare("DELETE FROM saw_results WHERE user_id = ?");
        $stmtDelSaw->execute([$userId]);

        $rank = 1;
        $stmtSAW = $pdo->prepare("INSERT INTO saw_results (user_id, alternative_id, nilai_akhir, ranking) VALUES (?, ?, ?, ?)");
        
        $topAltName = '';
        $topScore = 0;

        foreach ($vResults as $alt_id => $val) {
            $stmtSAW->execute([$userId, $alt_id, $val, $rank]);
            if ($rank == 1) {
                $stmtAlt = $pdo->prepare("SELECT nama FROM alternatives WHERE id = ?");
                $stmtAlt->execute([$alt_id]);
                $altName = $stmtAlt->fetchColumn();
                $topAltName = $altName ?: 'Alternatif #' . $alt_id;
                $topScore = $val;
            }
            $rank++;
        }

        $stmtHist = $pdo->prepare("INSERT INTO matrix_history (user_id, top_alternative, top_score, matrix_data) VALUES (?, ?, ?, ?)");
        $stmtHist->execute([$userId, $topAltName, $topScore, json_encode($_POST['nilai'])]);

        $pdo->commit();
        
        header("Location: perhitungan_saw.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Terjadi kesalahan saat menyimpan: " . $e->getMessage();
    }
}

// PROSES HAPUS HISTORI KHUSUS USER
if (isset($_GET['action']) && $_GET['action'] == 'clear_all_history') {
    $stmtClearHist = $pdo->prepare("DELETE FROM matrix_history WHERE user_id = ?");
    $stmtClearHist->execute([$userId]);
    $historyReturn = (($_GET['return'] ?? '') === 'profile') ? 'profile.php' : 'input_matrix.php';
    header("Location: " . $historyReturn);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_history' && isset($_GET['id'])) {
    $stmtDelHist = $pdo->prepare("DELETE FROM matrix_history WHERE id = ? AND user_id = ?");
    $stmtDelHist->execute([(int)$_GET['id'], $userId]);
    $historyReturn = (($_GET['return'] ?? '') === 'profile') ? 'profile.php' : 'input_matrix.php';
    header("Location: " . $historyReturn);
    exit;
}

include 'includes/header.php';

$kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

// Ambil riwayat khusus user ini
$stmtHistories = $pdo->prepare("SELECT * FROM matrix_history WHERE user_id = ? ORDER BY created_at DESC");
$stmtHistories->execute([$userId]);
$histories = $stmtHistories->fetchAll();

// Fetch data nilai eksisting milik user ini saja
$existingValues = [];
$stmtEx = $pdo->prepare("SELECT * FROM alternative_values WHERE user_id = ?");
$stmtEx->execute([$userId]);
$rawEx = $stmtEx->fetchAll();

foreach ($rawEx as $rx) {
    $existingValues[$rx['alternative_id']][$rx['criteria_id']] = $rx['nilai'];
}
?>

<div class="container py-5" style="margin-top: 5rem;">
    <!-- HEAD TITLE -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Input Matrix Observasi</h3>
        <p class="text-muted">Masukkan angka hasil pengamatan riil untuk setiap kriteria lokasi.</p>
    </div>

    <!-- PANDUAN PENGISIAN -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <h6 class="fw-bold mb-2">Panduan Pengisian Matriks:</h6>
        <ul class="mb-0 fs-7 ps-3">
            <li><strong>Nilai Input Riil:</strong> Masukkan angka riil hasil pengamatan lapangan (Gunakan titik `.` untuk nilai desimal, contoh: `2.5`).</li>
            <li><strong>BENEFIT:</strong> Nilai lebih besar = Lebih bagus.</li>
            <li><strong>COST:</strong> Nilai lebih kecil = Lebih bagus (Jarak & Biaya Sewa).</li>
        </ul>
    </div>

    <!-- FORM MATRIKS OBSERVASI -->
    <div class="card card-executive p-4 mb-5 shadow-sm">
        <form method="POST" id="formMatrix">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 180px;" class="text-start">Alternatif Lokasi</th>
                            <?php foreach ($kriteria as $c): ?>
                                <th style="min-width: 120px;">
                                    <?= htmlspecialchars($c['nama']); ?>
                                    <small class="d-block text-muted">(<?= htmlspecialchars($c['kode']); ?> - <?= strtoupper($c['tipe']); ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $a): ?>
                        <tr>
                            <td class="text-start">
                                <strong class="text-dark"><?= htmlspecialchars($a['kode']); ?> - <?= htmlspecialchars($a['nama']); ?></strong>
                                <small class="text-muted d-block fs-7"><?= htmlspecialchars($a['alamat']); ?></small>
                            </td>
                            <?php foreach ($kriteria as $c): 
                                // Jika belum pernah mengisi input data, tampilkan string kosong ""
                                $valNow = $existingValues[$a['id']][$c['id']] ?? '';
                            ?>
                                <td>
                                    <input type="number" step="any" name="nilai[<?= $a['id']; ?>][<?= $c['id']; ?>]" 
                                           value="<?= htmlspecialchars($valNow); ?>" 
                                           placeholder="0"
                                           class="form-control form-control-sm text-center matrix-input" required>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" onclick="clearInputMatrix()" class="btn btn-outline-danger px-3 py-2.5 fw-semibold">
                    Clear Input
                </button>
                <button type="submit" name="proses_spk" class="btn btn-dark px-4 py-2.5 fw-bold">
                    Hitung SPK & Lanjut Perhitungan SAW
                </button>
            </div>
        </form>
    </div>

    <!-- RIWAYAT HISTORI MATRIKS -->
    <div class="card card-executive p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Riwayat Perhitungan Matriks</h5>
            <?php if (!empty($histories)): ?>
                <a href="input_matrix.php?action=clear_all_history" 
                   class="btn btn-sm btn-danger px-3 py-1.5 fw-semibold"
                   onclick="return confirm('Apakah Anda yakin ingin MENGHAPUS SELURUH riwayat perhitungan?')">
                    Hapus Semua Riwayat
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($histories)): ?>
            <p class="text-muted mb-0 fs-7">Belum ada riwayat perhitungan tersimpan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu Perhitungan</th>
                            <th>Rekomendasi Utama (#1)</th>
                            <th>Skor Preferensi (V<sub>1</sub>)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($histories as $h): ?>
                        <tr>
                            <td><span class="fw-semibold text-dark"><?= date('d M Y, H:i', strtotime($h['created_at'])); ?> WIB</span></td>
                            <td><span class="badge bg-dark text-white px-2 py-1 fs-7">🏆 <?= htmlspecialchars($h['top_alternative']); ?></span></td>
                            <td><strong><?= number_format($h['top_score'], 6); ?></strong></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-dark me-1" data-bs-toggle="modal" data-bs-target="#modalHist<?= $h['id']; ?>">
                                    Detail
                                </button>
                                <a href="input_matrix.php?action=delete_history&id=<?= $h['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus riwayat ini?')">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DETAIL HISTORI -->
<?php if (!empty($histories)): ?>
    <?php foreach ($histories as $h): ?>
    <div class="modal fade" id="modalHist<?= $h['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detail Matriks (<?= date('d M Y, H:i', strtotime($h['created_at'])); ?> WIB)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong>Lokasi Terpilih:</strong> <span class="badge bg-dark fs-6"><?= htmlspecialchars($h['top_alternative']); ?></span> (Nilai: <?= number_format($h['top_score'], 6); ?>)</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Alternatif</th>
                                    <?php foreach ($kriteria as $c): ?>
                                        <th><?= htmlspecialchars($c['kode']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $mData = json_decode($h['matrix_data'], true);
                                foreach ($alternatif as $a): 
                                ?>
                                <tr>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($a['kode']); ?> - <?= htmlspecialchars($a['nama']); ?></td>
                                    <?php foreach ($kriteria as $c): ?>
                                        <td><?= $mData[$a['id']][$c['id']] ?? 0; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function clearInputMatrix() {
    if (confirm('Apakah Anda yakin ingin mengosongkan seluruh nilai input di tabel ini?')) {
        document.querySelectorAll('.matrix-input').forEach(input => input.value = '');
    }
}
</script>

<?php include 'includes/footer.php'; ?>