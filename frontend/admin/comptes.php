<?php
$pageTitle = 'Gestion des comptes';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;

    if ($action === 'activer' && $id) {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'actif' WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: comptes.php?msg=activer');
        exit;
    }

    if ($action === 'desactiver' && $id) {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'inactif' WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: comptes.php?msg=desactiver');
        exit;
    }

    if ($action === 'supprimer' && $id) {
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND id != ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        header('Location: comptes.php?msg=supprimer');
        exit;
    }
}

$comptes = $pdo->query('SELECT * FROM utilisateurs ORDER BY date_creation DESC')->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'activer' => 'Compte activé avec succès.',
        'desactiver' => 'Compte désactivé avec succès.',
        'supprimer' => 'Compte supprimé avec succès.',
        'ajouter' => 'Compte créé avec succès.',
        'modifier' => 'Compte modifié avec succès.',
        'reset' => 'Mot de passe réinitialisé avec succès.'
    ];
    $msg = $_GET['msg'];
    ?>
    <?php if (isset($messages[$msg])): ?>
        <div class="alert alert-success"><?= $messages[$msg] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3>Tous les comptes (<?= count($comptes) ?>)</h3>
        <a href="ajouter-compte.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Date création</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comptes as $compte): ?>
            <tr>
                <td><?= $compte['id'] ?></td>
                <td><?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></td>
                <td><?= htmlspecialchars($compte['email']) ?></td>
                <td><span class="badge badge-<?= $compte['role'] ?>"><?= ucfirst($compte['role']) ?></span></td>
                <td><span class="badge badge-<?= $compte['statut'] ?>"><?= ucfirst($compte['statut']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($compte['date_creation'])) ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="modifier-compte.php?id=<?= $compte['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="reinitialiser-mdp.php?id=<?= $compte['id'] ?>" class="btn btn-warning btn-sm" title="Réinitialiser MDP" data-confirm="Réinitialiser le mot de passe de ce compte ?">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </a>
                        <?php if ($compte['id'] != $_SESSION['user_id']): ?>
                            <?php if ($compte['statut'] === 'actif'): ?>
                                <a href="?action=desactiver&id=<?= $compte['id'] ?>" class="btn btn-danger btn-sm" title="Désactiver" data-confirm="Désactiver ce compte ?">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                </a>
                            <?php else: ?>
                                <a href="?action=activer&id=<?= $compte['id'] ?>" class="btn btn-success btn-sm" title="Activer" data-confirm="Activer ce compte ?">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </a>
                            <?php endif; ?>
                            <a href="?action=supprimer&id=<?= $compte['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer ce compte définitivement ?">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
