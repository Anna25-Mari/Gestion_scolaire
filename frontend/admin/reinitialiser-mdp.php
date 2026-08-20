<?php
$pageTitle = 'Réinitialiser le mot de passe';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: comptes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$compte = $stmt->fetch();

if (!$compte) {
    header('Location: comptes.php');
    exit;
}

$defaultPassword = 'Anna@2024';
$hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

$update = $pdo->prepare('UPDATE utilisateurs SET password = ?, must_change_password = 1 WHERE id = ?');
$update->execute([$hashedPassword, $id]);
?>

<div class="form-card">
    <h3>Mot de passe réinitialisé</h3>

    <div class="alert alert-success">Le mot de passe a été réinitialisé avec succès.</div>

    <p style="margin-bottom: 1rem;">Nouveau mot de passe pour <strong><?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></strong> :</p>

    <div style="background: #f0f2f5; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 1.2rem; text-align: center; margin-bottom: 1.5rem; letter-spacing: 2px;">
        <?= htmlspecialchars($defaultPassword) ?>
    </div>

    <p style="color: #888; font-size: 0.85rem; margin-bottom: 1.5rem;">Communiquez ce mot de passe à l'utilisateur. Il devra le changer après sa première connexion.</p>

    <div class="form-actions">
        <a href="comptes.php" class="btn btn-primary">Retour à la liste</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
