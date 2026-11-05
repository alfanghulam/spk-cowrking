<?php
require_once 'config/database.php';
include 'includes/header.php';

// CEK STATUS LOGIN USER
$isLoggedIn = isset($_SESSION['user_id']);

// DATA FETCHING DARI DATABASE
$totalKriteria = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn() ?: 8;
$totalAlternatif = $pdo->query("SELECT COUNT(*) FROM alternatives")->fetchColumn() ?: 5;
$kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

// Jika belum login, riwayat & hasil ranking di-reset ke status N/A / 0
$totalRiwayat = $isLoggedIn ? ($pdo->query("SELECT COUNT(*) FROM matrix_history")->fetchColumn() ?: 0) : 0;

$topLokasi = null;
$ranking = [];

if ($isLoggedIn) {
    $ranking = $pdo->query("
        SELECT r.*, a.kode, a.nama, a.alamat 
        FROM saw_results r 
        JOIN alternatives a ON r.alternative_id = a.id 
        ORDER BY r.ranking ASC
    ")->fetchAll();
    $topLokasi = $ranking[0] ?? null;
}

// Data Chart
$chartLabels = [];
$chartScores = [];
if (!empty($ranking)) {
    foreach ($ranking as $rk) {
        $chartLabels[] = $rk['nama'];
        $chartScores[] = (float)$rk['nilai_akhir'];
    }
}
?>

<div class="container dashboard-container py-4">
    <section class="dashboard-welcome mb-4">
        <div>
            <div class="section-eyebrow"><i class="bi bi-stars me-1"></i> RINGKASAN SISTEM</div>
            <h2 class="dashboard-heading">Selamat datang di Dashboard</h2>
            <p class="dashboard-subtitle">Sistem Pendukung Keputusan Pemilihan Lokasi Co-working Space di Yogyakarta menggunakan metode AHP dan SAW.</p>
        </div>
        <div class="dashboard-date-pill"><i class="bi bi-geo-alt-fill"></i> Yogyakarta, Indonesia</div>
    </section>

    <section class="row g-3 mb-4 dashboard-stat-grid">
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="alternatif.php" class="metric-card metric-blue text-decoration-none">
                <span class="metric-icon"><i class="bi bi-geo-alt-fill"></i></span>
                <span class="metric-label">Alternatif Lokasi</span>
                <span class="metric-value"><?= (int)$totalAlternatif; ?></span>
                <span class="metric-foot">Tempat co-working dibandingkan <i class="bi bi-arrow-up-right"></i></span>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="kriteria.php" class="metric-card metric-violet text-decoration-none">
                <span class="metric-icon"><i class="bi bi-list-check"></i></span>
                <span class="metric-label">Kriteria Penilaian</span>
                <span class="metric-value"><?= (int)$totalKriteria; ?></span>
                <span class="metric-foot">Indikator pengambilan keputusan <i class="bi bi-arrow-up-right"></i></span>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="proses_ahp.php" class="metric-card metric-teal text-decoration-none">
                <span class="metric-icon"><i class="bi bi-diagram-3-fill"></i></span>
                <span class="metric-label">Metode Pembobotan</span>
                <span class="metric-value metric-text-value">AHP</span>
                <span class="metric-foot">Bobot prioritas dan konsistensi <i class="bi bi-arrow-up-right"></i></span>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="<?= $isLoggedIn ? 'hasil_ranking.php' : 'javascript:void(0)'; ?>" <?= !$isLoggedIn ? 'onclick="openAuthModal()"' : ''; ?> class="metric-card metric-orange text-decoration-none">
                <span class="metric-icon"><i class="bi bi-trophy-fill"></i></span>
                <span class="metric-label">Metode Peringkat</span>
                <span class="metric-value metric-text-value">SAW</span>
                <span class="metric-foot"><?= $isLoggedIn && $topLokasi ? 'Rekomendasi: '.htmlspecialchars($topLokasi['nama']) : 'Hitung skor dan ranking'; ?> <i class="bi bi-arrow-up-right"></i></span>
            </a>
        </div>
    </section>

    <section class="row g-4 mb-4 map-layout" id="map-panel">
        <div class="col-12 col-xl-8">
            <div class="panel-card map-panel h-100">
                <div class="panel-heading">
                    <div><h3>Peta Lokasi Alternatif</h3><p>Jelajahi persebaran lima lokasi co-working space di kawasan Yogyakarta.</p></div>
                    <span class="panel-heading-icon"><i class="bi bi-map-fill"></i></span>
                </div>
                <div id="locationMap" class="map-canvas" role="application" aria-label="Peta lokasi alternatif co-working space"></div>
                <div class="map-legend"><span class="map-legend-dot"></span> Titik lokasi indikatif · Peta © OpenStreetMap</div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="panel-card h-100">
                <div class="panel-heading"><div><h3>Lokasi yang dibandingkan</h3><p>Pilih lokasi untuk membuka petunjuk di peta.</p></div></div>
                <div class="map-place-list">
                    <a class="map-place-item" href="https://www.google.com/maps/search/?api=1&query=Excowork+Coworking+Space+Yogyakarta" target="_blank" rel="noopener noreferrer"><span class="map-place-code">A1</span><span class="map-place-copy"><strong>Excowork Coworking Space</strong><small>Nogotirto, Gamping, Sleman</small></span><i class="bi bi-box-arrow-up-right"></i></a>
                    <a class="map-place-item" href="https://www.google.com/maps/search/?api=1&query=Antologi+Collaboractive+Space+Yogyakarta" target="_blank" rel="noopener noreferrer"><span class="map-place-code">A2</span><span class="map-place-copy"><strong>Antologi Collaboractive Space</strong><small>Caturtunggal, Depok, Sleman</small></span><i class="bi bi-box-arrow-up-right"></i></a>
                    <a class="map-place-item" href="https://www.google.com/maps/search/?api=1&query=KLA+Co-Working+Space+Sagan+Yogyakarta" target="_blank" rel="noopener noreferrer"><span class="map-place-code">A3</span><span class="map-place-copy"><strong>KLA Co-Working Space</strong><small>Sagan, Yogyakarta</small></span><i class="bi bi-box-arrow-up-right"></i></a>
                    <a class="map-place-item" href="https://www.google.com/maps/search/?api=1&query=Cronica+Creative+Workspace+Eatery+Yogyakarta" target="_blank" rel="noopener noreferrer"><span class="map-place-code">A4</span><span class="map-place-copy"><strong>Crónica Creative Workspace &amp; Eatery</strong><small>Cokrodiningratan, Jetis</small></span><i class="bi bi-box-arrow-up-right"></i></a>
                    <a class="map-place-item" href="https://www.google.com/maps/search/?api=1&query=Relasi+Coworking+Space+Yogyakarta" target="_blank" rel="noopener noreferrer"><span class="map-place-code">A5</span><span class="map-place-copy"><strong>Relasi Coworking Space</strong><small>Pogung, Sleman</small></span><i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="panel-card h-100">
                <div class="panel-heading">
                    <div>
                        <h3>Visualisasi Perhitungan</h3>
                        <p><?= ($isLoggedIn && !empty($chartScores)) ? 'Perbandingan skor preferensi SAW setiap alternatif.' : 'Distribusi bobot prioritas kriteria berdasarkan matriks AHP.'; ?></p>
                    </div>
                    <span class="panel-heading-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                </div>
                <div class="chart-wrap chart-large"><canvas id="rankingChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="panel-card h-100">
                <div class="panel-heading">
                    <div><h3>Komposisi Kriteria</h3><p>Jenis kriteria dalam model penilaian.</p></div>
                    <span class="panel-heading-icon violet"><i class="bi bi-pie-chart-fill"></i></span>
                </div>
                <div class="chart-wrap chart-donut"><canvas id="criteriaChart"></canvas></div>
                <div class="criteria-legend">
                    <span><i class="legend-dot legend-benefit"></i> Benefit <strong>7</strong></span>
                    <span><i class="legend-dot legend-cost"></i> Cost <strong>1</strong></span>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="panel-card h-100">
                <div class="panel-heading">
                    <div><h3>Alur Pengambilan Keputusan</h3><p>Proses kerja metode AHP–SAW dalam aplikasi.</p></div>
                </div>
                <div class="process-steps">
                    <a href="proses_ahp.php" class="process-step"><span class="process-number">01</span><span class="process-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span><span class="process-copy"><strong>Matriks Perbandingan AHP</strong><small>Perbandingan berpasangan antarkriteria.</small></span><i class="bi bi-chevron-right process-arrow"></i></a>
                    <a href="proses_ahp.php" class="process-step"><span class="process-number">02</span><span class="process-icon"><i class="bi bi-sliders"></i></span><span class="process-copy"><strong>Bobot dan Konsistensi</strong><small>Bobot prioritas serta pemeriksaan CR.</small></span><i class="bi bi-chevron-right process-arrow"></i></a>
                    <a href="perhitungan_saw.php" class="process-step"><span class="process-number">03</span><span class="process-icon"><i class="bi bi-calculator-fill"></i></span><span class="process-copy"><strong>Normalisasi dan Skor SAW</strong><small>Kontribusi setiap kriteria pada alternatif.</small></span><i class="bi bi-chevron-right process-arrow"></i></a>
                    <a href="hasil_ranking.php" class="process-step"><span class="process-number">04</span><span class="process-icon"><i class="bi bi-trophy-fill"></i></span><span class="process-copy"><strong>Hasil Ranking</strong><small>Urutan alternatif berdasarkan skor akhir.</small></span><i class="bi bi-chevron-right process-arrow"></i></a>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="panel-card h-100 recommendation-panel">
                <div class="panel-heading"><div><h3>Rekomendasi Teratas</h3><p>Ringkasan hasil perhitungan terakhir.</p></div><span class="panel-heading-icon orange"><i class="bi bi-award-fill"></i></span></div>
                <?php if ($isLoggedIn && $topLokasi): ?>
                    <div class="recommendation-highlight"><span class="recommendation-crown"><i class="bi bi-trophy-fill"></i></span><span class="recommendation-label">PERINGKAT 1</span><h4><?= htmlspecialchars($topLokasi['nama']); ?></h4><p><?= htmlspecialchars($topLokasi['alamat'] ?? 'Yogyakarta'); ?></p><div class="recommendation-score"><span>Skor preferensi SAW</span><strong><?= number_format((float)$topLokasi['nilai_akhir'], 6, ',', '.'); ?></strong></div></div>
                    <a href="hasil_ranking.php" class="btn btn-dark w-100 mt-3">Lihat seluruh ranking <i class="bi bi-arrow-right ms-1"></i></a>
                <?php else: ?>
                    <div class="recommendation-empty"><span class="empty-state-icon"><i class="bi bi-bar-chart-steps fs-2"></i></span><h4>Ranking belum ditampilkan</h4><p>Masuk ke sistem untuk membuka input matriks, menjalankan perhitungan SAW, dan melihat rekomendasi akhir.</p><a href="<?= $isLoggedIn ? 'input_matrix.php' : 'login.php'; ?>" class="btn btn-dark w-100"><?= $isLoggedIn ? 'Mulai perhitungan' : 'Login untuk menghitung'; ?> <i class="bi bi-arrow-right ms-1"></i></a></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-heading">
            <div><h3>Alternatif Lokasi Co-working Space</h3><p>Daftar lokasi yang menjadi objek perbandingan dalam penelitian.</p></div>
            <a href="alternatif.php" class="btn btn-outline-dark btn-sm">Lihat semua <i class="bi bi-arrow-up-right ms-1"></i></a>
        </div>
        <div class="table-responsive dashboard-table-wrap">
            <table class="table dashboard-table align-middle">
                <thead><tr><th>Kode</th><th>Nama Alternatif</th><th>Wilayah</th><th>Status</th><th class="text-end">Detail</th></tr></thead>
                <tbody>
                <?php
                $locationDetails = [
                    'A1' => ['wilayah' => 'Nogotirto, Gamping, Sleman', 'nama' => 'Excowork Coworking Space'],
                    'A2' => ['wilayah' => 'Caturtunggal, Depok, Sleman', 'nama' => 'Antologi Collaboractive Space'],
                    'A3' => ['wilayah' => 'Sagan, Yogyakarta', 'nama' => 'KLA Co-Working Space'],
                    'A4' => ['wilayah' => 'Cokrodiningratan, Jetis, Yogyakarta', 'nama' => 'Crónica Creative Workspace & Eatery'],
                    'A5' => ['wilayah' => 'Pogung, Sleman', 'nama' => 'Relasi Coworking Space'],
                ];
                $locationRows = !empty($alternatif) ? $alternatif : [];
                if ($locationRows): foreach ($locationRows as $idx => $alt):
                    $code = $alt['kode'] ?? ('A'.($idx+1));
                    $detail = $locationDetails[$code] ?? ['wilayah' => ($alt['alamat'] ?? 'Yogyakarta'), 'nama' => ($alt['nama'] ?? 'Alternatif lokasi')];
                ?>
                    <tr><td><span class="table-code"><?= htmlspecialchars($code); ?></span></td><td><strong><?= htmlspecialchars($alt['nama'] ?? $detail['nama']); ?></strong></td><td><?= htmlspecialchars($alt['alamat'] ?? $detail['wilayah']); ?></td><td><span class="status-chip"><i class="bi bi-check-circle-fill"></i> Terdaftar</span></td><td class="text-end"><a class="table-action" href="alternatif.php" aria-label="Lihat alternatif"><i class="bi bi-arrow-up-right"></i></a></td></tr>
                <?php endforeach; else: ?>
                    <?php foreach ($locationDetails as $code => $detail): ?><tr><td><span class="table-code"><?= $code; ?></span></td><td><strong><?= htmlspecialchars($detail['nama']); ?></strong></td><td><?= htmlspecialchars($detail['wilayah']); ?></td><td><span class="status-chip"><i class="bi bi-check-circle-fill"></i> Terdaftar</span></td><td class="text-end"><a class="table-action" href="alternatif.php"><i class="bi bi-arrow-up-right"></i></a></td></tr><?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
window.addEventListener('load', function () {
    const mapElement = document.getElementById('locationMap');
    if (!mapElement || !window.L) return;

    const locations = [
        { code: 'A1', name: 'Excowork Coworking Space', address: 'Nogotirto, Gamping, Sleman', lat: -7.7818, lng: 110.3484, query: 'Excowork Coworking Space Yogyakarta' },
        { code: 'A2', name: 'Antologi Collaboractive Space', address: 'Caturtunggal, Depok, Sleman', lat: -7.7618, lng: 110.3891, query: 'Antologi Collaboractive Space Yogyakarta' },
        { code: 'A3', name: 'KLA Co-Working Space', address: 'Sagan, Yogyakarta', lat: -7.7764, lng: 110.3775, query: 'KLA Co-Working Space Sagan Yogyakarta' },
        { code: 'A4', name: 'Crónica Creative Workspace & Eatery', address: 'Cokrodiningratan, Jetis', lat: -7.7814, lng: 110.3671, query: 'Cronica Creative Workspace Eatery Yogyakarta' },
        { code: 'A5', name: 'Relasi Coworking Space', address: 'Pogung, Sleman', lat: -7.7584, lng: 110.3698, query: 'Relasi Coworking Space Yogyakarta' }
    ];

    const map = L.map(mapElement, { scrollWheelZoom: false }).setView([-7.7730, 110.3745], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
    }).addTo(map);

    const bounds = [];
    locations.forEach(function (place) {
        const marker = L.marker([place.lat, place.lng]).addTo(map);
        marker.bindPopup('<div><strong>' + place.code + ' · ' + place.name + '</strong><br><span style="color:#718096">' + place.address + '</span><br><a href="https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(place.query) + '" target="_blank" rel="noopener noreferrer">Buka petunjuk Google Maps ↗</a></div>');
        bounds.push([place.lat, place.lng]);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24] });
    setTimeout(function () { map.invalidateSize(); }, 150);
});

const ctxBar = document.getElementById('rankingChart');
if (ctxBar) {
    const chartContext = ctxBar.getContext('2d');
    const barGradient = chartContext.createLinearGradient(0, 0, 0, 300);
    barGradient.addColorStop(0, '#4778f5');
    barGradient.addColorStop(1, '#7c5ce7');
    const isLoggedIn = <?= json_encode($isLoggedIn); ?>;
    const hasData = <?= json_encode(!empty($chartScores)); ?>;
    let chartLabels = [], chartData = [], chartTitle = '';
    if (isLoggedIn && hasData) {
        chartLabels = <?= json_encode($chartLabels); ?>;
        chartData = <?= json_encode($chartScores); ?>;
        chartTitle = 'Skor Preferensi SAW (Vi)';
    } else {
        chartLabels = ['C1 Aksesibilitas', 'C2 Jarak', 'C3 Lingkungan', 'C4 Fasilitas', 'C5 Internet', 'C6 Keamanan', 'C7 Parkir', 'C8 Aktivitas'];
        chartData = [0.086860508, 0.086860508, 0.222119985, 0.222119985, 0.222119985, 0.086860508, 0.03652926, 0.03652926];
        chartTitle = 'Bobot Prioritas AHP (wj)';
    }
    new Chart(ctxBar, {type:'bar', data:{labels:chartLabels,datasets:[{label:chartTitle,data:chartData,backgroundColor:barGradient,borderRadius:7,borderSkipped:false,barThickness:'flex',maxBarThickness:36}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#13233f',padding:12}},scales:{y:{beginAtZero:true,grid:{color:'#edf1f7'},ticks:{color:'#718096'}},x:{grid:{display:false},ticks:{color:'#718096',maxRotation:35,minRotation:0}}}}});
}
const ctxDoughnut = document.getElementById('criteriaChart');
if (ctxDoughnut) new Chart(ctxDoughnut,{type:'doughnut',data:{labels:['Benefit','Cost'],datasets:[{data:[7,1],backgroundColor:['#4778f5','#ffb454'],borderWidth:0,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
</script>
<?php include 'includes/footer.php'; ?>
