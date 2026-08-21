<?php
$pageTitle = 'Détails de la salle';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: classes.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT c.*, cy.nom AS cycle_nom
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    WHERE c.id = ?
');
$stmt->execute([$id]);
$classe = $stmt->fetch();

if (!$classe) {
    header('Location: classes.php');
    exit;
}

$perPage = 15;

/* ===== Liste des élèves de la salle ===== */
$qe = trim($_GET['qe'] ?? '');
$pageE = paginationPage();
$whereE = 'classe_id = ?';
$argsE = [$id];
if ($qe !== '') {
    $whereE .= ' AND (nom LIKE ? OR prenom LIKE ?)';
    $likeE = "%$qe%";
    array_push($argsE, $likeE, $likeE);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM eleves WHERE $whereE");
$stmt->execute($argsE);
$totalRowsE = (int)$stmt->fetchColumn();
$totalPagesE = max(1, ceil($totalRowsE / $perPage));
$pageE = min($pageE, $totalPagesE);
$offsetE = ($pageE - 1) * $perPage;

$stmt = $pdo->prepare("SELECT id, nom, prenom, sexe, date_naissance FROM eleves WHERE $whereE ORDER BY id ASC LIMIT $perPage OFFSET $offsetE");
$stmt->execute($argsE);
$eleves = $stmt->fetchAll();

/* ===== Liste des enseignants de la salle ===== */
$qn = trim($_GET['qn'] ?? '');
$pageN = 1;
if (isset($_GET['pn'])) {
    $pageN = intval($_GET['pn']) > 0 ? intval($_GET['pn']) : 1;
}
$whereN = 'ec.classe_id = ?';
$argsN = [$id];
if ($qn !== '') {
    $whereN .= ' AND (e.nom LIKE ? OR e.prenom LIKE ? OR e.specialite LIKE ?)';
    $likeN = "%$qn%";
    array_push($argsN, $likeN, $likeN, $likeN);
}

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.id) FROM enseignants_classes ec JOIN enseignants e ON ec.enseignant_id = e.id WHERE $whereN");
$stmt->execute($argsN);
$totalRowsN = (int)$stmt->fetchColumn();
$totalPagesN = max(1, ceil($totalRowsN / $perPage));
$pageN = min($pageN, $totalPagesN);
$offsetN = ($pageN - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT DISTINCT e.id, e.nom, e.prenom, e.email, e.telephone, e.specialite, e.statut
    FROM enseignants_classes ec
    JOIN enseignants e ON ec.enseignant_id = e.id
    WHERE $whereN
    ORDER BY e.id ASC
    LIMIT $perPage OFFSET $offsetN
");
$stmt->execute($argsN);
$enseignants = $stmt->fetchAll();

$paramsE = ['id' => $id];
if ($qn !== '') {
    $paramsE['qn'] = $qn;
}
if (isset($_GET['pn'])) {
    $paramsE['pn'] = $pageN;
}
$paramsN = ['id' => $id];
if ($qe !== '') {
    $paramsN['qe'] = $qe;
}
if (isset($_GET['pe'])) {
    $paramsN['pe'] = $pageE;
}

$effectifTotal = $pdo->prepare('SELECT COUNT(*) FROM eleves WHERE classe_id = ?');
$effectifTotal->execute([$id]);
$effectifTotal = (int)$effectifTotal->fetchColumn();

$pct = $classe['capacite'] > 0 ? round(($effectifTotal / $classe['capacite']) * 100) : 0;
?>

<div class="detail-card">
    <div class="detail-header">
        <div>
            <h3>Salle : <?= htmlspecialchars($classe['nom']) ?></h3>
            <div class="detail-sub">Cycle : <?= htmlspecialchars($classe['cycle_nom'] ?? 'Non défini') ?></div>
        </div>
        <a href="classes.php" class="btn btn-cancel btn-sm">&larr; Retour aux salles</a>
    </div>
    <div class="detail-grid">
        <div class="info-item">
            <div class="info-label">Cycle</div>
            <div class="info-value"><?= htmlspecialchars($classe['cycle_nom'] ?? '-') ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Niveau</div>
            <div class="info-value"><?= $classe['niveau'] ? htmlspecialchars($classe['niveau']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Effectif</div>
            <div class="info-value"><?= $effectifTotal ?> / <?= $classe['capacite'] ?> places (<?= $pct ?>%)</div>
        </div>
        <div class="info-item">
            <div class="info-label">Description</div>
            <div class="info-value"><?= $classe['description'] ? htmlspecialchars($classe['description']) : '-' ?></div>
        </div>
    </div>
</div>

<div class="table-card" style="margin-bottom:2rem;">
    <div class="table-header">
        <h3>Élèves de la salle (<?= $totalRowsE ?>)</h3>
        <form method="GET" action="details-classe.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($qn !== ''): ?><input type="hidden" name="qn" value="<?= htmlspecialchars($qn) ?>"><?php endif; ?>
            <?php if (isset($_GET['pn'])): ?><input type="hidden" name="pn" value="<?= $pageN ?>"><?php endif; ?>
            <input type="text" name="qe" placeholder="Rechercher un élève..." class="search-input" style="width:200px;" value="<?= htmlspecialchars($qe) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
            <?php if ($qe): ?>
                <a href="details-classe.php?id=<?= $id ?>" class="btn btn-cancel btn-sm">Effacer</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (count($eleves) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Sexe</th>
                <th>Date de naissance</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $eleve): ?>
            <tr>
                <td><?= $eleve['id'] ?></td>
                <td><strong><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong></td>
                <td><?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></td>
                <td><?= $eleve['date_naissance'] ? date('d/m/Y', strtotime($eleve['date_naissance'])) : '-' ?></td>
                <td><a href="details-eleve.php?id=<?= $eleve['id'] ?>" class="btn btn-primary btn-sm">Voir détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucun élève dans cette salle.</p>
    </div>
    <?php endif; ?>
    <?= renderPagination($pageE, $totalPagesE, $paramsE, 'pe') ?>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Enseignants de la salle (<?= $totalRowsN ?>)</h3>
        <form method="GET" action="details-classe.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($qe !== ''): ?><input type="hidden" name="qe" value="<?= htmlspecialchars($qe) ?>"><?php endif; ?>
            <?php if (isset($_GET['pe'])): ?><input type="hidden" name="pe" value="<?= $pageE ?>"><?php endif; ?>
            <input type="text" name="qn" placeholder="Rechercher un enseignant..." class="search-input" style="width:200px;" value="<?= htmlspecialchars($qn) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
            <?php if ($qn): ?>
                <a href="details-classe.php?id=<?= $id ?>" class="btn btn-cancel btn-sm">Effacer</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (count($enseignants) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Contact</th>
                <th>Spécialité</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignants as $ens): ?>
            <tr>
                <td><?= $ens['id'] ?></td>
                <td><strong><?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></strong></td>
                <td>
                    <?= $ens['telephone'] ? htmlspecialchars($ens['telephone']) : '-' ?>
                    <?= $ens['email'] ? '<br><small style="color:#888">' . htmlspecialchars($ens['email']) . '</small>' : '' ?>
                </td>
                <td><?= $ens['specialite'] ? htmlspecialchars($ens['specialite']) : '-' ?></td>
                <td>
                    <?php if (($ens['statut'] ?? 'actif') === 'suspendu'): ?>
                        <span class="badge badge-suspendu">Suspendu</span>
                    <?php else: ?>
                        <span class="badge badge-actif">Actif</span>
                    <?php endif; ?>
                </td>
                <td><a href="details-enseignant.php?id=<?= $ens['id'] ?>" class="btn btn-primary btn-sm">Voir détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucun enseignant assigné à cette salle.</p>
    </div>
    <?php endif; ?>
    <?= renderPagination($pageN, $totalPagesN, $paramsN, 'pn') ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
