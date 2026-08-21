<?php
$pageTitle = 'Gestion des enseignants';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'supprimer') {
        $pdo->prepare('DELETE FROM enseignants_classes WHERE enseignant_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM enseignants WHERE id = ?')->execute([$id]);
        header('Location: enseignants.php?msg=supprimer');
        exit;
    }
    if ($_GET['action'] === 'suspendre') {
        $pdo->prepare("UPDATE enseignants SET statut = 'suspendu', date_suspension = NOW() WHERE id = ?")->execute([$id]);
        header('Location: enseignants.php?msg=suspendre');
        exit;
    }
    if ($_GET['action'] === 'reactiver') {
        $pdo->prepare("UPDATE enseignants SET statut = 'actif', date_suspension = NULL WHERE id = ?")->execute([$id]);
        header('Location: enseignants.php?msg=reactiver');
        exit;
    }
}

$perPage = 10;

$search = trim($_GET['q'] ?? '');

$where = [];
$args = [];
if ($search !== '') {
    $where[] = '(e.nom LIKE ? OR e.prenom LIKE ? OR e.specialite LIKE ? OR e.telephone LIKE ? OR e.email LIKE ? OR c.nom LIKE ?)';
    $like = "%$search%";
    $args = array_merge($args, [$like, $like, $like, $like, $like, $like]);
}
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.id)
    FROM enseignants e
    LEFT JOIN enseignants_classes ec ON ec.enseignant_id = e.id
    LEFT JOIN classes c ON ec.classe_id = c.id
    $whereSql
");
$stmt->execute($args);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min(paginationPage(), $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT DISTINCT e.*
    FROM enseignants e
    LEFT JOIN enseignants_classes ec ON ec.enseignant_id = e.id
    LEFT JOIN classes c ON ec.classe_id = c.id
    $whereSql
    ORDER BY e.id ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($args);
$enseignants = $stmt->fetchAll();

$paginationParams = [];
if ($search !== '') {
    $paginationParams['q'] = $search;
}

require_once __DIR__ . '/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Enseignant ajouté avec succès.',
        'modifier' => 'Enseignant modifié avec succès.',
        'supprimer' => 'Enseignant supprimé avec succès.',
        'suspendre' => 'Enseignant suspendu.',
        'reactiver' => 'Enseignant réactivé.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Liste des enseignants <span class="count-chip"><?= $totalRows ?></span></h3>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" action="enseignants.php" style="display:flex;gap:0.5rem;align-items:center;">
                <input type="text" name="q" placeholder="Rechercher un enseignant..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                <?php if ($search): ?>
                    <a href="enseignants.php" class="btn btn-cancel btn-sm">Effacer</a>
                <?php endif; ?>
            </form>
            <a href="ajouter-enseignant.php" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter
            </a>
        </div>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignants as $ens): ?>
            <?php $statut = $ens['statut'] ?? 'actif'; ?>
            <tr>
                <td><?= $ens['id'] ?></td>
                <td><strong><?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></strong></td>
                <td>
                    <?= $ens['telephone'] ? htmlspecialchars($ens['telephone']) : '-' ?>
                    <?= $ens['email'] ? '<br><small style="color:#888">' . htmlspecialchars($ens['email']) . '</small>' : '' ?>
                </td>
                <td><?= $ens['specialite'] ? htmlspecialchars($ens['specialite']) : '-' ?></td>
                <td>
                    <?php if ($statut === 'suspendu'): ?>
                        <span class="badge badge-suspendu">Suspendu</span>
                    <?php else: ?>
                        <span class="badge badge-actif">Actif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions-cell">
                        <a href="details-enseignant.php?id=<?= $ens['id'] ?>" class="btn btn-primary btn-sm" title="Voir détails">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="modifier-enseignant.php?id=<?= $ens['id'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <?php if ($statut === 'actif'): ?>
                        <a href="?action=suspendre&id=<?= $ens['id'] ?>" class="btn btn-cancel btn-sm" title="Suspendre" data-confirm="Suspendre cet enseignant ? Il ne sera plus considéré comme actif dans l'établissement.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="10" y1="9" x2="10" y2="15"/><line x1="14" y1="9" x2="14" y2="15"/></svg>
                        </a>
                        <?php else: ?>
                        <a href="?action=reactiver&id=<?= $ens['id'] ?>" class="btn btn-success btn-sm" title="Réactiver" data-confirm="Réactiver cet enseignant ?">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </a>
                        <?php endif; ?>
                        <a href="?action=supprimer&id=<?= $ens['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer cet enseignant définitivement ?">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2 2v2"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <p><?= $search ? 'Aucun enseignant trouvé pour "' . htmlspecialchars($search) . '"' : 'Aucun enseignant enregistré.' ?></p>
        <?php if (!$search): ?>
            <a href="ajouter-enseignant.php" class="btn btn-primary btn-sm">Ajouter un enseignant</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?= renderPagination($page, $totalPages, $paginationParams) ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
