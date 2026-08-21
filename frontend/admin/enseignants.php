<?php
$pageTitle = 'Gestion des enseignants';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare('DELETE FROM enseignants_classes WHERE enseignant_id = ?');
        $stmt->execute([$_GET['id']]);
        $stmt = $pdo->prepare('DELETE FROM enseignants WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        header('Location: enseignants.php?msg=supprimer');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare('
        SELECT DISTINCT e.*
        FROM enseignants e
        LEFT JOIN enseignants_classes ec ON ec.enseignant_id = e.id
        LEFT JOIN classes c ON ec.classe_id = c.id
        WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.specialite LIKE ? OR c.nom LIKE ?
        ORDER BY e.nom, e.prenom
    ');
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM enseignants ORDER BY nom, prenom');
}
$enseignants = $stmt->fetchAll();

$classesMap = [];
if (count($enseignants) > 0) {
    $ids = array_column($enseignants, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtClasses = $pdo->prepare("
        SELECT ec.enseignant_id, c.nom
        FROM enseignants_classes ec
        JOIN classes c ON ec.classe_id = c.id
        WHERE ec.enseignant_id IN ($placeholders)
        ORDER BY c.nom
    ");
    $stmtClasses->execute($ids);
    foreach ($stmtClasses->fetchAll() as $row) {
        $classesMap[$row['enseignant_id']][] = $row['nom'];
    }
}

require_once __DIR__ . '/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'ajouter' => 'Enseignant ajouté avec succès.',
        'modifier' => 'Enseignant modifié avec succès.',
        'supprimer' => 'Enseignant supprimé avec succès.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <form method="GET" action="enseignants.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="text" name="q" placeholder="Rechercher un enseignant..." value="<?= htmlspecialchars($search) ?>" style="padding:0.5rem 0.8rem;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;outline:none;width:250px;">
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
    <?php if (count($enseignants) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Spécialité</th>
                <th>Classes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignants as $ens): ?>
            <tr>
                <td><?= $ens['id'] ?></td>
                <td><strong><?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></strong></td>
                <td><?= $ens['email'] ? htmlspecialchars($ens['email']) : '-' ?></td>
                <td><?= $ens['telephone'] ? htmlspecialchars($ens['telephone']) : '-' ?></td>
                <td><?= $ens['specialite'] ? htmlspecialchars($ens['specialite']) : '-' ?></td>
                <td>
                    <?php if (!empty($classesMap[$ens['id']])): ?>
                        <?php foreach ($classesMap[$ens['id']] as $cn): ?>
                            <span class="badge badge-admin" style="margin:1px;"><?= htmlspecialchars($cn) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em style="color:#aaa;">Aucune</em>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-enseignant.php?id=<?= $ens['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="?action=supprimer&id=<?= $ens['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer cet enseignant définitivement ?">
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
        <p><?= $search ? 'Aucun enseignant trouvé pour "'.$search.'"' : 'Aucun enseignant enregistré.' ?></p>
        <?php if (!$search): ?>
            <a href="ajouter-enseignant.php" class="btn btn-primary btn-sm" style="margin-top:0.8rem;">Ajouter un enseignant</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
