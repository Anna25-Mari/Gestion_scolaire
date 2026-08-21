<?php
$pageTitle = 'Ajouter un compte';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/password_policy.php';
require_once __DIR__ . '/../../backend/includes/NotificationService.php';

$defaultPassword = 'Anna@2024';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role = $_POST['role'] ?? 'directeur';
    $passwordChoice = $_POST['password_choice'] ?? 'defaut';
    $customPassword = $_POST['custom_password'] ?? '';

    if ($passwordChoice === 'defaut') {
        $password = $defaultPassword;
    } else {
        $password = $customPassword;
    }

    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($passwordChoice === 'personnalise' && validatePasswordPolicy($customPassword) !== true) {
        $error = validatePasswordPolicy($customPassword);
    } else {
        $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Le changement de mot de passe est toujours forcé à la première connexion
            $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, statut, must_change_password) VALUES (?, ?, ?, ?, ?, ?, "actif", 1)');
            $stmt->execute([$nom, $prenom, $email, $telephone, $hashedPassword, $role]);

            // Envoi des identifiants directement à l'utilisateur (Gmail prioritaire, SMS en secours)
            $results = NotificationService::sendCredentials($email, $telephone, $prenom, $nom, $password, 'cree');

            $createdPassword = $password;
            $delivered = NotificationService::isDelivered($results);
            $viaMail = ($results['mail'] !== null && $results['mail']['success']);
        }
    }
}
?>

<div class="form-card">
    <h3>Créer un nouveau compte</h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($createdPassword)): ?>
        <div class="alert alert-success">Compte créé avec succès.</div>

        <?php if ($delivered): ?>
        <div style="background:#f0f6fc; border:1px solid #d4e6f1; border-radius:10px; padding:1rem; margin-bottom:1.2rem; font-size:0.88rem;">
            Les identifiants ont été envoyés directement à l'utilisateur par
            <strong><?= $viaMail ? 'e-mail Gmail à ' . htmlspecialchars($email) : 'SMS au ' . htmlspecialchars($telephone) ?></strong>
            <?= (!$viaMail && MailService::isConfigured()) ? '' : '' ?>
            <?= ($viaMail && !MailService::isConfigured()) ? '<em>(mode simulation locale — consultez backend/logs/mail.log)</em>' : '' ?>.
        </div>
        <?php else: ?>
        <div class="alert alert-error">Les identifiants n'ont pas pu être envoyés à l'utilisateur.</div>
        <?php endif; ?>

        <p style="margin-bottom: 0.5rem;">Mot de passe attribué à <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong> :</p>
        <div style="background: #f0f2f5; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 1.2rem; text-align: center; margin-bottom: 1rem; letter-spacing: 2px;">
            <?= htmlspecialchars($createdPassword) ?>
        </div>
        <p style="color: #888; font-size: 0.85rem; margin-bottom: 1.5rem;">L'utilisateur devra obligatoirement changer ce mot de passe à sa première connexion.</p>
        <div class="form-actions">
            <a href="comptes.php" class="btn btn-primary">Retour à la liste</a>
            <a href="ajouter-compte.php" class="btn btn-cancel">Créer un autre compte</a>
        </div>
    <?php else: ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" placeholder="Nom de famille" required value="<?= htmlspecialchars($nom ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" placeholder="Prénom" required value="<?= htmlspecialchars($prenom ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" placeholder="exemple@anna.com" required value="<?= htmlspecialchars($email ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="telephone">Numéro de téléphone *</label>
            <input type="tel" id="telephone" name="telephone" placeholder="Ex: 690000000" required value="<?= htmlspecialchars($telephone ?? '') ?>">
            <small style="color:#888;font-size:0.8rem;">Le mot de passe sera envoyé par SMS à ce numéro.</small>
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <div style="display: flex; gap: 1rem; margin-top: 0.4rem;">
                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem; color: #555;">
                    <input type="radio" name="password_choice" value="defaut" checked onchange="togglePasswordFields()"> Mot par défaut (<?= $defaultPassword ?>)
                </label>
                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem; color: #555;">
                    <input type="radio" name="password_choice" value="personnalise" onchange="togglePasswordFields()"> Personnaliser
                </label>
            </div>
        </div>
        <div class="form-group" id="customPasswordGroup" style="display: none;">
            <label for="custom_password">Nouveau mot de passe</label>
            <input type="password" id="custom_password" name="custom_password" placeholder="Minimum 8 caracteres, majuscule, minuscule et chiffre">
            <small style="color:#888;font-size:0.8rem;">Minimum 8 caractères, avec au moins une majuscule, une minuscule et un chiffre.</small>
        </div>
        <div class="form-group">
            <label for="role">Rôle</label>
            <select id="role" name="role" required>
                <option value="directeur" <?= ($role ?? '') === 'directeur' ? 'selected' : '' ?>>Directeur</option>
                <option value="admin" <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le compte et envoyer le SMS</button>
            <a href="comptes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function togglePasswordFields() {
    var choice = document.querySelector('input[name="password_choice"]:checked').value;
    var group = document.getElementById('customPasswordGroup');
    var input = document.getElementById('custom_password');
    if (choice === 'personnalise') {
        group.style.display = 'block';
        input.required = true;
    } else {
        group.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
