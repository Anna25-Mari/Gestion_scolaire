<?php
$pageTitle = 'Gestion des cycles';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare('DELETE FROM cycles WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        header('Location: cycles.php?msg=supprimer');
        exit;
    }
}

$perPage = 15;

$search = trim($_GET['q'] ?? '');
$where = [];
$args = [];
if ($search !== '') {
    $where[] = '(cy.nom LIKE ? OR cy.code LIKE ? OR cy.description LIKE ?)';
    $like = "%$search%";
    array_push($args, $like, $like, $like);
}
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM cycles cy $whereSql");
$stmt->execute($args);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min(paginationPage(), $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT cy.*, COUNT(c.id) AS nb_classes
    FROM cycles cy
    LEFT JOIN classes c ON c.cycle_id = cy.id
    $whereSql
    GROUP BY cy.id, cy.nom, cy.code, cy.description, cy.date_creation
    ORDER BY cy.id ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($args);
$cycles = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Cycle ajouté avec succès.',
        'modifier' => 'Cycle modifié avec succès.',
        'supprimer' => 'Cycle supprimé avec succès.'
    ];
    ?>
    <?php if (isset($messages[$_GET['msg']])): ?>
        <div class="alert alert-success"><?= $messages[$_GET['msg']] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Cycles <span class="count-chip"><?= $totalRows ?></span></h3>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <form method="GET" action="cycles.php" style="display:flex;gap:0.5rem;align-items:center;">
                <input type="text" name="q" placeholder="Rechercher un cycle..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                <?php if ($search): ?>
                    <a href="cycles.php" class="btn btn-cancel btn-sm">Effacer</a>
                <?php endif; ?>
            </form>
            <a href="ajouter-cycle.php" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter
            </a>
        </div>
    </div>
    <?php if (count($cycles) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Code</th>
                <th>Salles / Classes</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cycles as $cycle): ?>
            <tr>
                <td><?= $cycle['id'] ?></td>
                <td><strong><?= htmlspecialchars($cycle['nom']) ?></strong></td>
                <td><?= $cycle['code'] ? '<span class="badge badge-admin">' . htmlspecialchars($cycle['code']) . '</span>' : '-' ?></td>
                <td><span class="badge badge-directeur"><?= $cycle['nb_classes'] ?></span></td>
                <td><?= $cycle['description'] ? htmlspecialchars(mb_substr($cycle['description'], 0, 60)) . (mb_strlen($cycle['description']) > 60 ? '...' : '') : '-' ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-cycle.php?id=<?= $cycle['id'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="?action=supprimer&id=<?= $cycle['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer ce cycle ? Les salles associées ne seront pas supprimées.">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M16.2 7.8l-2 6.3-6.4 2.1 2-6.3z"/></svg>
        <p><?= $search ? 'Aucun cycle trouvé pour "' . htmlspecialchars($search) . '"' : 'Aucun cycle enregistré.' ?></p>
        <?php if (!$search): ?>
            <a href="ajouter-cycle.php" class="btn btn-primary btn-sm">Créer un cycle</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?= renderPagination($page, $totalPages, $search !== '' ? ['q' => $search] : []) ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
