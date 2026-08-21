<?php
$pageTitle = 'Gestion des cycles';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare('DELETE FROM cycles WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        header('Location: cycles.php?msg=supprimer');
        exit;
    }
}

$cycles = $pdo->query('
    SELECT cy.*, COUNT(c.id) as nb_classes
    FROM cycles cy
    LEFT JOIN classes c ON c.cycle_id = cy.id
    GROUP BY cy.id
    ORDER BY cy.nom
')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Cycle ajouté avec succès.',
        'modifier' => 'Cycle modifié avec succès.',
        'supprimer' => 'Cycle supprimé avec succès.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Tous les cycles (<?= count($cycles) ?>)</h3>
        <a href="ajouter-cycle.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a>
    </div>
    <?php if (count($cycles) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Code</th>
                <th>Classes</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cycles as $cycle): ?>
            <tr>
                <td><?= $cycle['id'] ?></td>
                <td><strong><?= htmlspecialchars($cycle['nom']) ?></strong></td>
                <td><?= $cycle['code'] ? htmlspecialchars($cycle['code']) : '-' ?></td>
                <td><span class="badge badge-admin"><?= $cycle['nb_classes'] ?></span></td>
                <td><?= $cycle['description'] ? htmlspecialchars(substr($cycle['description'], 0, 60)) : '-' ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-cycle.php?id=<?= $cycle['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="?action=supprimer&id=<?= $cycle['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer ce cycle ? Les classes associées ne seront pas supprimées.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucun cycle enregistré.</p>
        <a href="ajouter-cycle.php" class="btn btn-primary btn-sm" style="margin-top:0.8rem;">Créer un cycle</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
