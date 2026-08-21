<?php
$pageTitle = 'Importer un fichier Excel';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/ExcelImporter.php';

$pdo = getConnection();
$report = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier_excel'])) {
    $file = $_FILES['fichier_excel'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur lors du téléversement du fichier (code ' . $file['error'] . ').';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $error = 'Format invalide : seuls les fichiers .xlsx sont acceptés.';
    } else {
        $tmpPath = sys_get_temp_dir() . '/import_anna_' . uniqid() . '.xlsx';
        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $error = 'Impossible d\'enregistrer le fichier temporaire.';
        } else {
            try {
                $report = ExcelImporter::import($tmpPath, $pdo);
            } catch (Exception $e) {
                $error = $e->getMessage();
            } finally {
                @unlink($tmpPath);
            }
        }
    }
}

$totalInserted = $totalUpdated = $totalErrors = 0;
if ($report) {
    foreach ($report as $r) {
        $totalInserted += $r['inserted'];
        $totalUpdated += $r['updated'];
        $totalErrors += count($r['errors']);
    }
}
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'modele_ok'): ?>
    <div class="alert alert-success">Modèle téléchargé. Remplissez-le puis importez-le ici.</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($report): ?>
<div class="table-card" style="margin-bottom:1.5rem;">
    <div class="table-header">
        <h3>Résultat de l'import</h3>
        <a href="importer.php" class="btn btn-primary btn-sm">Nouvel import</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Ajoutés</th>
                <th>Mis à jour</th>
                <th>Ignorés (lignes vides)</th>
                <th>Erreurs</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $labels = [
                'cycles' => 'Cycles',
                'classes' => 'Classes',
                'eleves' => 'Élèves',
                'enseignants' => 'Enseignants'
            ];
            foreach ($labels as $key => $label): ?>
            <tr>
                <td><strong><?= $label ?></strong></td>
                <td><span class="badge badge-actif"><?= (int)$report[$key]['inserted'] ?></span></td>
                <td><span class="badge badge-admin"><?= (int)$report[$key]['updated'] ?></span></td>
                <td><?= (int)$report[$key]['ignored'] ?></td>
                <td><span class="badge <?= count($report[$key]['errors']) > 0 ? 'badge-inactif' : 'badge-actif' ?>"><?= count($report[$key]['errors']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="padding: 0.8rem 1.2rem; color:#888; font-size:0.85rem;">
        Total : <strong><?= $totalInserted ?></strong> ajout(s), <strong><?= $totalUpdated ?></strong> mise(s) à jour, <strong><?= $totalErrors ?></strong> erreur(s).
    </p>
</div>

<?php
$hasErrors = false;
foreach ($report as $key => $r) {
    if (count($r['errors']) > 0) { $hasErrors = true; break; }
}
?>
<?php if ($hasErrors): ?>
<div class="table-card" style="margin-bottom:1.5rem;">
    <div class="table-header"><h3>Détail des erreurs</h3></div>
    <table class="data-table">
        <thead>
            <tr><th>Catégorie</th><th>Ligne Excel</th><th>Problème</th></tr>
        </thead>
        <tbody>
            <?php foreach ($labels as $key => $label): ?>
                <?php foreach ($report[$key]['errors'] as $err): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td><?= $err['ligne'] ?></td>
                    <td style="color:#c0392b;"><?= htmlspecialchars($err['message']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-success">Import terminé sans erreur. Les données sont disponibles dans les menus Cycles, Classes, Élèves et Enseignants.</div>
<?php endif; ?>

<?php else: ?>

<div class="form-card" style="max-width:720px;">
    <h3>Importer cycles, classes, élèves et enseignants</h3>
    <p style="color:#666; font-size:0.9rem; margin-bottom:1.2rem;">
        Préparez votre fichier Excel sur votre PC en respectant le modèle officiel, puis importez-le ici.
        Les données sont enregistrées directement dans la base de l'école et consultables depuis le tableau de bord.
    </p>

    <div style="background:#f0f6fc; border:1px solid #d4e6f1; border-radius:10px; padding:1.2rem; margin-bottom:1.5rem;">
        <h4 style="margin-bottom:0.8rem; color:#1a5276;">Structure attendue (4 onglets)</h4>
        <table class="data-table" style="font-size:0.82rem;">
            <thead>
                <tr><th>Onglet</th><th>Colonnes (dans l'ordre)</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Cycles</strong></td><td>Nom | Code | Description</td></tr>
                <tr><td><strong>Classes</strong></td><td>Nom | Cycle | Niveau | Capacité | Description</td></tr>
                <tr><td><strong>Eleves</strong></td><td>Nom | Prenom | Date naissance | Sexe (M/F) | Classe | Parent nom | Parent tel | Adresse</td></tr>
                <tr><td><strong>Enseignants</strong></td><td>Nom | Prenom | Email | Telephone | Specialite | Date embauche | Classes (séparées par des virgules)</td></tr>
            </tbody>
        </table>
        <ul style="margin:0.8rem 0 0 1.2rem; color:#555; font-size:0.83rem;">
            <li>Les dates s'écrivent au format <strong>jj/mm/aaaa</strong>.</li>
            <li>Un cycle cité dans l'onglet Classes est créé automatiquement s'il n'existe pas.</li>
            <li>Une classe ou un cycle déjà existant est mis à jour (pas de doublon).</li>
            <li>Un élève dont la classe n'existe pas encore est signalé en erreur.</li>
        </ul>
        <a href="../../backend/actions/modele-import.php" class="btn btn-primary btn-sm" style="margin-top:1rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Télécharger le modèle Excel
        </a>
    </div>

    <form method="POST" action="importer.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="fichier_excel">Fichier Excel (.xlsx)</label>
            <input type="file" id="fichier_excel" name="fichier_excel" accept=".xlsx" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Lancer l'import</button>
        </div>
    </form>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
