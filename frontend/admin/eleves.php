<?php
$pageTitle = 'Gestion des élèves';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare('DELETE FROM eleves WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        header('Location: eleves.php?msg=supprimer');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare('
        SELECT e.*, c.nom as classe_nom
        FROM eleves e
        LEFT JOIN classes c ON e.classe_id = c.id
        WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.parent_nom LIKE ?
        ORDER BY e.nom, e.prenom
    ');
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('
        SELECT e.*, c.nom as classe_nom
        FROM eleves e
        LEFT JOIN classes c ON e.classe_id = c.id
        ORDER BY e.nom, e.prenom
    ');
}
$eleves = $stmt->fetchAll();

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
        <form method="GET" action="eleves.php" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="text" name="q" placeholder="Rechercher un élève..." value="<?= htmlspecialchars($search) ?>" style="padding:0.5rem 0.8rem;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;outline:none;width:250px;">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
            <?php if ($search): ?>
                <a href="eleves.php" class="btn btn-cancel btn-sm">Effacer</a>
            <?php endif; ?>
        </form>
        <a href="ajouter-eleve.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a>
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
    <div style="padding:2rem;text-align:center;color:#888;">
        <p><?= $search ? 'Aucun élève trouvé pour "'.$search.'"' : 'Aucun élève inscrit.' ?></p>
        <?php if (!$search): ?>
            <a href="ajouter-eleve.php" class="btn btn-primary btn-sm" style="margin-top:0.8rem;">Ajouter un élève</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
