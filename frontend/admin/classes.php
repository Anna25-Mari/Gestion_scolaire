<?php
$pageTitle = 'Gestion des classes';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare('UPDATE eleves SET classe_id = NULL WHERE classe_id = ?');
        $stmt->execute([$id]);
        $stmt = $pdo->prepare('DELETE FROM enseignants_classes WHERE classe_id = ?');
        $stmt->execute([$id]);
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: classes.php?msg=supprimer');
        exit;
    }
}

$perPage = 15;

$search = trim($_GET['q'] ?? '');
$cycleFilter = isset($_GET['cycle']) && $_GET['cycle'] !== '' ? intval($_GET['cycle']) : null;

$where = [];
$args = [];
if ($search !== '') {
    $where[] = '(c.nom LIKE ? OR c.description LIKE ? OR cy.nom LIKE ?)';
    $like = "%$search%";
    $args = array_merge($args, [$like, $like, $like]);
}
if ($cycleFilter) {
    $where[] = 'c.cycle_id = ?';
    $args[] = $cycleFilter;
}
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM classes c LEFT JOIN cycles cy ON c.cycle_id = cy.id $whereSql");
$stmt->execute($args);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min(paginationPage(), $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.capacite, c.description, cy.nom AS cycle_nom, COUNT(e.id) AS effectif
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    LEFT JOIN eleves e ON e.classe_id = c.id
    $whereSql
    GROUP BY c.id
    ORDER BY c.id ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($args);
$classes = $stmt->fetchAll();

$cycles = $pdo->query('SELECT id, nom FROM cycles ORDER BY nom')->fetchAll();

$paginationParams = [];
if ($search !== '') {
    $paginationParams['q'] = $search;
}
if ($cycleFilter) {
    $paginationParams['cycle'] = $cycleFilter;
}

require_once __DIR__ . '/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Classe ajoutée avec succès.',
        'modifier' => 'Classe modifiée avec succès.',
        'supprimer' => 'Classe supprimée avec succès.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Toutes les classes <span class="count-chip"><?= $totalRows ?></span></h3>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" action="classes.php" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                <input type="text" name="q" placeholder="Rechercher une classe..." class="search-input" style="width:190px;" value="<?= htmlspecialchars($search) ?>">
                <select name="cycle" class="select-filter" onchange="this.form.submit()">
                    <option value="">Tous les cycles</option>
                    <?php foreach ($cycles as $cy): ?>
                        <option value="<?= $cy['id'] ?>" <?= $cycleFilter === (int)$cy['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cy['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                <?php if ($search || $cycleFilter): ?>
                    <a href="classes.php" class="btn btn-cancel btn-sm">Effacer</a>
                <?php endif; ?>
            </form>
            <a href="ajouter-classe.php" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter
            </a>
        </div>
    </div>
    <?php if (count($classes) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Cycle</th>
                <th>Effectif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $classe): ?>
            <tr>
                <td><?= $classe['id'] ?></td>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['cycle_nom'] ? htmlspecialchars($classe['cycle_nom']) : '<em style="color:#aaa">Aucun</em>' ?></td>
                <td><?= $classe['effectif'] ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-classe.php?id=<?= $classe['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="?action=supprimer&id=<?= $classe['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer cette classe ? Les élèves ne seront pas supprimés.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <p><?= $search || $cycleFilter ? 'Aucune classe trouvée.' : 'Aucune classe créée.' ?></p>
        <?php if (!$search && !$cycleFilter): ?>
            <a href="ajouter-classe.php" class="btn btn-primary btn-sm">Créer une classe</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?= renderPagination($page, $totalPages, $paginationParams) ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
