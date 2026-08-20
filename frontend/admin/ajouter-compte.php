<?php
$pageTitle = 'Ajouter un compte';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$defaultPassword = 'Anna@2024';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'directeur';
    $passwordChoice = $_POST['password_choice'] ?? 'defaut';
    $customPassword = $_POST['custom_password'] ?? '';

    if ($passwordChoice === 'defaut') {
        $password = $defaultPassword;
    } else {
        $password = $customPassword;
    }

    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($passwordChoice === 'personnalise' && strlen($customPassword) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
            $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $mustChange = ($passwordChoice === 'defaut') ? 1 : 0;
                $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, password, role, statut, must_change_password) VALUES (?, ?, ?, ?, ?, "actif", ?)');
                $stmt->execute([$nom, $prenom, $email, $hashedPassword, $role, $mustChange]);
                $createdPassword = $password;
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
        <p style="margin-bottom: 0.5rem;">Mot de passe attribué à <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong> :</p>
        <div style="background: #f0f2f5; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 1.2rem; text-align: center; margin-bottom: 1.5rem; letter-spacing: 2px;">
            <?= htmlspecialchars($createdPassword) ?>
        </div>
        <p style="color: #888; font-size: 0.85rem; margin-bottom: 1.5rem;">Communiquez ce mot de passe à l'utilisateur.</p>
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
            <input type="password" id="custom_password" name="custom_password" placeholder="Minimum 6 caractères">
        </div>
        <div class="form-group">
            <label for="role">Rôle</label>
            <select id="role" name="role" required>
                <option value="directeur" <?= ($role ?? '') === 'directeur' ? 'selected' : '' ?>>Directeur</option>
                <option value="admin" <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le compte</button>
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
