<?php
$pageTitle = 'Gestion des élèves';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare('DELETE FROM eleves WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        header('Location: eleves.php?msg=supprimer');
        exit;
    }
}

$perPage = 10;

$search = trim($_GET['q'] ?? '');
$classeFilter = isset($_GET['classe']) && $_GET['classe'] !== '' ? intval($_GET['classe']) : null;

$where = [];
$args = [];
if ($search !== '') {
    $where[] = '(e.nom LIKE ? OR e.prenom LIKE ? OR e.parent_nom LIKE ? OR e.parent_tel LIKE ? OR c.nom LIKE ?)';
    $like = "%$search%";
    $args = array_merge($args, [$like, $like, $like, $like, $like]);
}
if ($classeFilter) {
    $where[] = 'e.classe_id = ?';
    $args[] = $classeFilter;
}
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM eleves e LEFT JOIN classes c ON e.classe_id = c.id $whereSql");
$stmt->execute($args);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min(paginationPage(), $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT e.id, e.nom, e.prenom, e.sexe, e.date_naissance, e.parent_nom, e.parent_tel, c.nom AS classe_nom
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.id
    $whereSql
    ORDER BY e.id ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($args);
$eleves = $stmt->fetchAll();

$classes = $pdo->query('SELECT id, nom FROM classes ORDER BY nom')->fetchAll();

$paginationParams = [];
if ($search !== '') {
    $paginationParams['q'] = $search;
}
if ($classeFilter) {
    $paginationParams['classe'] = $classeFilter;
}

require_once __DIR__ . '/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Élève ajouté avec succès.',
        'modifier' => 'Élève modifié avec succès.',
        'supprimer' => 'Élève supprimé avec succès.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Liste des élèves <span class="count-chip"><?= $totalRows ?></span></h3>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" action="eleves.php" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                <input type="text" name="q" placeholder="Rechercher un élève..." class="search-input" style="width:190px;" value="<?= htmlspecialchars($search) ?>">
                <select name="classe" class="select-filter" onchange="this.form.submit()">
                    <option value="">Toutes les salles</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classeFilter === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                <?php if ($search || $classeFilter): ?>
                    <a href="eleves.php" class="btn btn-cancel btn-sm">Effacer</a>
                <?php endif; ?>
            </form>
            <a href="ajouter-eleve.php" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter
            </a>
        </div>
    </div>
    <?php if (count($eleves) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Sexe</th>
                <th>Date naissance</th>
                <th>Classe</th>
                <th>Parent</th>
                <th>Téléphone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $eleve): ?>
            <tr>
                <td><?= $eleve['id'] ?></td>
                <td><strong><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong></td>
                <td><?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></td>
                <td><?= $eleve['date_naissance'] ? date('d/m/Y', strtotime($eleve['date_naissance'])) : '-' ?></td>
                <td><?= $eleve['classe_nom'] ? htmlspecialchars($eleve['classe_nom']) : '<em style="color:#aaa">Non assigné</em>' ?></td>
                <td><?= $eleve['parent_nom'] ? htmlspecialchars($eleve['parent_nom']) : '-' ?></td>
                <td><?= $eleve['parent_tel'] ? htmlspecialchars($eleve['parent_tel']) : '-' ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-eleve.php?id=<?= $eleve['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="?action=supprimer&id=<?= $eleve['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer cet élève définitivement ?">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p><?= $search || $classeFilter ? 'Aucun élève trouvé.' : 'Aucun élève inscrit.' ?></p>
        <?php if (!$search && !$classeFilter): ?>
            <a href="ajouter-eleve.php" class="btn btn-primary btn-sm">Ajouter un élève</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?= renderPagination($page, $totalPages, $paginationParams) ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
