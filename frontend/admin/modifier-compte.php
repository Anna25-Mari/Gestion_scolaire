<?php
$pageTitle = 'Modifier un compte';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role = $_POST['role'] ?? $compte['role'];

    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $update = $pdo->prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, telephone = ?, role = ? WHERE id = ?');
            $update->execute([$nom, $prenom, $email, $telephone ?: null, $role, $id]);
            header('Location: comptes.php?msg=modifier');
            exit;
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="form-card">
    <h3>Modifier le compte de <?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></h3>
    <p class="form-legend">Modifiez les informations du compte puis enregistrez.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Identité de l'utilisateur
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom" class="req">Nom</label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $compte['nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="prenom" class="req">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $compte['prenom']) ?>">
                </div>
                <div class="form-group full">
                    <label for="email" class="req">Adresse e-mail</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? $compte['email']) ?>">
                </div>
                <div class="form-group full">
                    <label for="telephone">Numéro de téléphone</label>
                    <input type="tel" id="telephone" name="telephone" placeholder="Ex: 690000000" value="<?= htmlspecialchars($_POST['telephone'] ?? $compte['telephone']) ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Rôle
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="role" class="req">Rôle</label>
                    <select id="role" name="role" required>
                        <option value="directeur" <?= ($compte['role'] === 'directeur') ? 'selected' : '' ?>>Directeur</option>
                        <option value="admin" <?= ($compte['role'] === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="comptes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
