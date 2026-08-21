<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

$totalEleves = $pdo->query('SELECT COUNT(*) FROM eleves')->fetchColumn();
$totalEnseignants = $pdo->query('SELECT COUNT(*) FROM enseignants')->fetchColumn();
$totalClasses = $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$elevesFilles = $pdo->query("SELECT COUNT(*) FROM eleves WHERE sexe = 'F'")->fetchColumn();
$elevesGarcons = $pdo->query("SELECT COUNT(*) FROM eleves WHERE sexe = 'M'")->fetchColumn();

$perPage = 15;

/* ===== Tableau : élèves par salle ===== */
$q1 = trim($_GET['q1'] ?? '');
$page1 = paginationPage();
$where1 = '';
$args1 = [];
if ($q1 !== '') {
    $where1 = 'WHERE c.nom LIKE ? OR cy.nom LIKE ? OR c.niveau LIKE ?';
    $like1 = "%$q1%";
    $args1 = [$like1, $like1, $like1];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM classes c LEFT JOIN cycles cy ON c.cycle_id = cy.id $where1");
$stmt->execute($args1);
$totalRows1 = (int)$stmt->fetchColumn();
$totalPages1 = max(1, ceil($totalRows1 / $perPage));
$page1 = min($page1, $totalPages1);
$offset1 = ($page1 - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.niveau, c.capacite, cy.nom AS cycle_nom, COUNT(e.id) AS effectif
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    LEFT JOIN eleves e ON e.classe_id = c.id
    $where1
    GROUP BY c.id, c.nom, c.niveau, c.capacite, cy.nom
    ORDER BY cy.id ASC, c.id ASC
    LIMIT $perPage OFFSET $offset1
");
$stmt->execute($args1);
$elevesParSalle = $stmt->fetchAll();

/* ===== Tableau : enseignants par salle ===== */
$q2 = trim($_GET['q2'] ?? '');
$page2 = paginationPage(1);
$pageParam2 = 'ppage';
if (isset($_GET[$pageParam2])) {
    $page2 = intval($_GET[$pageParam2]) > 0 ? intval($_GET[$pageParam2]) : 1;
}
$where2 = '';
$args2 = [];
if ($q2 !== '') {
    $where2 = 'WHERE c.nom LIKE ? OR cy.nom LIKE ? OR c.niveau LIKE ?';
    $like2 = "%$q2%";
    $args2 = [$like2, $like2, $like2];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM classes c LEFT JOIN cycles cy ON c.cycle_id = cy.id $where2");
$stmt->execute($args2);
$totalRows2 = (int)$stmt->fetchColumn();
$totalPages2 = max(1, ceil($totalRows2 / $perPage));
$page2 = min($page2, $totalPages2);
$offset2 = ($page2 - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.niveau, cy.nom AS cycle_nom,
           COUNT(DISTINCT ec.enseignant_id) AS nb_enseignants
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    LEFT JOIN enseignants_classes ec ON ec.classe_id = c.id
    $where2
    GROUP BY c.id, c.nom, c.niveau, cy.nom
    ORDER BY cy.id ASC, c.id ASC
    LIMIT $perPage OFFSET $offset2
");
$stmt->execute($args2);
$enseignantsParSalle = $stmt->fetchAll();

// Noms des enseignants pour les salles affichées
$ensByClasse = [];
if (count($enseignantsParSalle) > 0) {
    $ids = array_column($enseignantsParSalle, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtEns = $pdo->prepare("
        SELECT ec.classe_id, e.prenom, e.nom
        FROM enseignants_classes ec
        JOIN enseignants e ON ec.enseignant_id = e.id
        WHERE ec.classe_id IN ($placeholders)
        ORDER BY e.nom ASC, e.prenom ASC
    ");
    $stmtEns->execute($ids);
    foreach ($stmtEns->fetchAll() as $row) {
        $ensByClasse[$row['classe_id']][] = $row['prenom'] . ' ' . $row['nom'];
    }
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalEleves ?></h3>
            <p>Total élèves</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalClasses ?></h3>
            <p>Salles / Classes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalEnseignants ?></h3>
            <p>Enseignants</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $elevesFilles ?> / <?= $elevesGarcons ?></h3>
            <p>Filles / Garçons</p>
        </div>
    </div>
</div>

<div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header">
        <h3>Nombre d'élèves par salle <span class="count-chip"><?= $totalRows1 ?></span></h3>
        <form method="GET" action="dashboard.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="hidden" name="q2" value="<?= htmlspecialchars($q2) ?>">
            <input type="hidden" name="<?= $pageParam2 ?>" value="<?= $page2 ?>">
            <input type="text" name="q1" placeholder="Rechercher une salle..." class="search-input" style="width:220px;" value="<?= htmlspecialchars($q1) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
            <?php if ($q1): ?>
                <a href="dashboard.php#ens-salles" class="btn btn-cancel btn-sm">Effacer</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (count($elevesParSalle) > 0): ?>
    <?php $lastCycle1 = null; ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Salle</th>
                <th>Niveau</th>
                <th>Effectif</th>
                <th>Capacité</th>
                <th>Occupation</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($elevesParSalle as $classe): ?>
            <?php if ($classe['cycle_nom'] !== $lastCycle1): ?>
            <tr class="cycle-row">
                <td colspan="6"><?= htmlspecialchars($classe['cycle_nom'] ?? 'Sans cycle') ?></td>
            </tr>
            <?php $lastCycle1 = $classe['cycle_nom']; ?>
            <?php endif; ?>
            <?php
                $pct = $classe['capacite'] > 0 ? round(($classe['effectif'] / $classe['capacite']) * 100) : 0;
                $color = $pct >= 90 ? '#e74c3c' : ($pct >= 70 ? '#f39c12' : '#27ae60');
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['niveau'] ? htmlspecialchars($classe['niveau']) : '-' ?></td>
                <td><?= $classe['effectif'] ?> élève<?= $classe['effectif'] > 1 ? 's' : '' ?></td>
                <td><?= $classe['capacite'] ?> places</td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <div style="flex:1;height:8px;background:#eee;border-radius:4px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div>
                        </div>
                        <span style="font-size:0.8rem;color:<?= $color ?>;font-weight:600;"><?= $pct ?>%</span>
                    </div>
                </td>
                <td><a href="details-classe.php?id=<?= $classe['id'] ?>" class="btn btn-primary btn-sm">Détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <p>Aucune salle enregistrée.</p>
    </div>
    <?php endif; ?>
    <?= renderPagination($page1, $totalPages1, array_filter(['q1' => $q1, 'q2' => $q2], function ($v) { return $v !== ''; })) ?>
</div>

<div class="table-card" id="ens-salles">
    <div class="table-header">
        <h3>Nombre d'enseignants par salle <span class="count-chip"><?= $totalRows2 ?></span></h3>
        <form method="GET" action="dashboard.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="hidden" name="q1" value="<?= htmlspecialchars($q1) ?>">
            <input type="hidden" name="page" value="<?= $page1 ?>">
            <input type="text" name="q2" placeholder="Rechercher une salle..." class="search-input" style="width:220px;" value="<?= htmlspecialchars($q2) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
            <?php if ($q2): ?>
                <a href="dashboard.php" class="btn btn-cancel btn-sm">Effacer</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (count($enseignantsParSalle) > 0): ?>
    <?php $lastCycle2 = null; ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Salle</th>
                <th>Niveau</th>
                <th>Enseignants</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignantsParSalle as $classe): ?>
            <?php if ($classe['cycle_nom'] !== $lastCycle2): ?>
            <tr class="cycle-row">
                <td colspan="4"><?= htmlspecialchars($classe['cycle_nom'] ?? 'Sans cycle') ?></td>
            </tr>
            <?php $lastCycle2 = $classe['cycle_nom']; ?>
            <?php endif; ?>
            <tr>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['niveau'] ? htmlspecialchars($classe['niveau']) : '-' ?></td>
                <td>
                    <span class="badge badge-admin" style="margin-right:4px;"><?= $classe['nb_enseignants'] ?> enseignant<?= $classe['nb_enseignants'] > 1 ? 's' : '' ?></span>
                    <?php if (!empty($ensByClasse[$classe['id']])): ?>
                        <?php foreach ($ensByClasse[$classe['id']] as $nomEns): ?>
                            <span class="badge badge-directeur" style="margin:1px;"><?= htmlspecialchars($nomEns) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td><a href="details-classe.php?id=<?= $classe['id'] ?>" class="btn btn-primary btn-sm">Détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <p>Aucune salle enregistrée.</p>
    </div>
    <?php endif; ?>
    <?= renderPagination($page2, $totalPages2, array_filter(['q1' => $q1, 'q2' => $q2], function ($v) { return $v !== ''; }), $pageParam2) ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
