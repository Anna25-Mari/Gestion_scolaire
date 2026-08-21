<?php
$pageTitle = 'Consultation des classes';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

$classes = $pdo->query('
    SELECT c.*, cy.nom as cycle_nom, COUNT(e.id) as effectif
    FROM classes c
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    LEFT JOIN eleves e ON e.classe_id = c.id
    GROUP BY c.id
    ORDER BY c.nom
')->fetchAll();
?>

<div class="alert alert-success" style="margin-bottom:1rem;">Mode consultation : la gestion des classes est réservée à l'administrateur.</div>

<div class="table-card">
    <div class="table-header">
        <h3>Toutes les classes (<?= count($classes) ?>)</h3>
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
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $classe): ?>
            <tr>
                <td><?= $classe['id'] ?></td>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['cycle_nom'] ? htmlspecialchars($classe['cycle_nom']) : '-' ?></td>
                <td><?= $classe['effectif'] ?> / <?= $classe['capacite'] ?></td>
                <td><?= $classe['description'] ? htmlspecialchars(substr($classe['description'], 0, 50)) . '...' : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucune classe enregistrée.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
