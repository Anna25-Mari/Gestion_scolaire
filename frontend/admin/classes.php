<?php
$pageTitle = 'Gestion des classes';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

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

$classes = $pdo->query('
    SELECT c.*, cy.nom as cycle_nom, COUNT(e.id) as effectif
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    LEFT JOIN eleves e ON e.classe_id = c.id
    GROUP BY c.id
    ORDER BY c.nom
')->fetchAll();

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
        <h3>Toutes les classes (<?= count($classes) ?>)</h3>
        <a href="ajouter-classe.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a>
    </div>
    <?php if (count($classes) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Cycle</th>
                <th>Effectif</th>
                <th>Capacité</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $classe): ?>
            <tr>
                <td><?= $classe['id'] ?></td>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['cycle_nom'] ? htmlspecialchars($classe['cycle_nom']) : '<em style="color:#aaa">Aucun</em>' ?></td>
                <td><?= $classe['effectif'] ?> / <?= $classe['capacite'] ?></td>
                <td><?= $classe['description'] ? htmlspecialchars(substr($classe['description'], 0, 50)) . '...' : '-' ?></td>
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
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucune classe créée.</p>
        <a href="ajouter-classe.php" class="btn btn-primary btn-sm" style="margin-top:0.8rem;">Créer une classe</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
