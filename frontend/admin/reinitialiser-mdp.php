<?php
$pageTitle = 'Réinitialiser le mot de passe';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/password_policy.php';
require_once __DIR__ . '/../../backend/includes/NotificationService.php';

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

$status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Génération d'un mot de passe aléatoire (jamais visible par l'admin)
    $newPassword = generateRandomPassword(12);

    // 2. Envoi direct à l'utilisateur (Gmail prioritaire, SMS en secours)
    $results = NotificationService::sendCredentials(
        $compte['email'],
        $compte['telephone'],
        $compte['prenom'],
        $compte['nom'],
        $newPassword,
        'reinitialise'
    );

    // 3. Le mot de passe n'est changé que si l'envoi a abouti
    if (NotificationService::isDelivered($results)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE utilisateurs SET password = ?, must_change_password = 1 WHERE id = ?');
        $update->execute([$hashedPassword, $id]);
        $status = 'envoye';
    } else {
        $status = 'echec';
    }
}
?>

<div class="form-card">
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <h3>Réinitialiser le mot de passe</h3>

    <p style="margin-bottom: 1rem;">
        Vous êtes sur le point de réinitialiser le mot de passe de
        <strong><?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></strong>
        (<?= htmlspecialchars($compte['email']) ?>).
    </p>

    <div style="background:#fef9e7; border:1px solid #f7dc6f; border-radius:10px; padding:1rem; margin-bottom:1.2rem; font-size:0.88rem; color:#7d6608;">
        Un <strong>nouveau mot de passe aléatoire</strong> sera généré et envoyé <strong>directement</strong> à l'utilisateur :
        <ul style="margin:0.5rem 0 0 1.2rem;">
            <li>par e-mail Gmail sur <strong><?= htmlspecialchars($compte['email']) ?></strong></li>
            <?php if ($compte['telephone']): ?>
                <li>par SMS au <strong><?= htmlspecialchars($compte['telephone']) ?></strong> si l'e-mail échoue</li>
            <?php endif; ?>
        </ul>
        Pour des raisons de sécurité, <strong>ce mot de passe ne vous sera pas affiché</strong>.
        L'utilisateur devra le changer à sa prochaine connexion.
    </div>

    <form method="POST" action="">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Confirmer et envoyer le mot de passe</button>
            <a href="comptes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>

    <?php elseif ($status === 'envoye'): ?>
    <h3>Mot de passe réinitialisé</h3>

    <div class="alert alert-success">
        Un nouveau mot de passe a été généré et envoyé directement à
        <strong><?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></strong>.
    </div>

    <?php $mail = $results['mail']; ?>
    <?php if ($mail !== null && $mail['success']): ?>
        <div style="background:#f0f6fc; border:1px solid #d4e6f1; border-radius:10px; padding:1rem; margin-bottom:1rem; font-size:0.88rem;">
            E-mail envoyé à <strong><?= htmlspecialchars($compte['email']) ?></strong>
            <?= MailService::isConfigured() ? '' : '<em>(mode simulation locale — consultez backend/logs/mail.log)</em>' ?>
        </div>
    <?php elseif (isset($results['sms']) && $results['sms']['success']): ?>
        <div style="background:#f0f6fc; border:1px solid #d4e6f1; border-radius:10px; padding:1rem; margin-bottom:1rem; font-size:0.88rem;">
            L'e-mail n'a pas pu partir ; un <strong>SMS</strong> a été envoyé au
            <strong><?= htmlspecialchars($compte['telephone']) ?></strong>.
        </div>
    <?php endif; ?>

    <p style="color:#888; font-size:0.85rem; margin-bottom:1.5rem;">
        Le mot de passe est connu uniquement de l'utilisateur. Il devra obligatoirement le changer à sa première connexion.
    </p>

    <div class="form-actions">
        <a href="comptes.php" class="btn btn-primary">Retour à la liste</a>
        <a href="reinitialiser-mdp.php?id=<?= $id ?>" class="btn btn-cancel">Réinitialiser à nouveau</a>
    </div>

    <?php else: ?>
    <h3>Échec de l'envoi</h3>

    <div class="alert alert-error">
        Le mot de passe n'a pas pu être envoyé (e-mail injoignable et aucun numéro de téléphone valide).
        <strong>Rien n'a été modifié</strong> : l'ancien mot de passe reste actif.
    </div>

    <div class="form-actions">
        <a href="reinitialiser-mdp.php?id=<?= $id ?>" class="btn btn-primary">Réessayer</a>
        <a href="comptes.php" class="btn btn-cancel">Retour à la liste</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
