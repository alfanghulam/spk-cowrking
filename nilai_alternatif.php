<?php
require_once 'config/database.php';
include 'includes/header.php';

$kriteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$alternatif = $pdo->query("SELECT * FROM alternatives ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    foreach ($_POST['nilai'] as $alt_id => $c_list) {
        foreach ($c_list as $crit_id => $val) {
            $stmt = $pdo->prepare("DELETE FROM alternative_values WHERE alternative_id = ? AND criteria_id = ?");
            $stmt->execute([$alt_id, $crit_id]);

            $stmt = $pdo->prepare("INSERT INTO alternative_values (alternative_id, criteria_id, nilai) VALUES (?, ?, ?)");
            $stmt->execute([$alt_id, $crit_id, $val]);
        }
    }
    $msg = "Nilai alternatif berhasil disimpan!";
}

// Fetch existing values
$existing = [];
$res = $pdo->query("SELECT * FROM alternative_values")->fetchAll();
foreach ($res as $r) {
    $existing[$r['alternative_id']][$r['criteria_id']] = $r['nilai'];
}
?>

<h2>Input Nilai Alternatif (Matrix X)</h2>
<?php if (isset($msg)): ?>
    <div class="alert alert-success"><?= $msg; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Alternatif</th>
                    <?php foreach ($kriteria as $c): ?>
                        <th><?= $c['kode']; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alternatif as $a): ?>
                <tr>
                    <td><strong><?= $a['kode']; ?></strong> (<?= htmlspecialchars($a['nama']); ?>)</td>
                    <?php foreach ($kriteria as $c): ?>
                        <td>
                            <input type="number" step="0.01" name="nilai[<?= $a['id']; ?>][<?= $c['id']; ?>]" 
                                   value="<?= $existing[$a['id']][$c['id']] ?? 0; ?>" 
                                   class="form-control form-control-sm" style="width: 80px;" required>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button type="submit" name="simpan_nilai" class="btn btn-success mt-2">Simpan Nilai Matrix</button>
</form>

<?php include 'includes/footer.php'; ?>