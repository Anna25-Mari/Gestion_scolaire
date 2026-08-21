<?php
$pageTitle = 'Modifier un enseignant';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: enseignants.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM enseignants WHERE id = ?');
$stmt->execute([$id]);
$ens = $stmt->fetch();

if (!$ens) {
    header('Location: enseignants.php');
    exit;
}

$classes = $pdo->query('SELECT id, nom FROM classes ORDER BY nom')->fetchAll();

$stmtSelected = $pdo->prepare('SELECT classe_id FROM enseignants_classes WHERE enseignant_id = ?');
$stmtSelected->execute([$id]);
$selectedClasses = array_column($stmtSelected->fetchAll(), 'classe_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $date_embauche = $_POST['date_embauche'] ?? null;
    $classesIds = $_POST['classes'] ?? [];

    if (empty($nom) || empty($prenom)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        if ($email) {
            $check = $pdo->prepare('SELECT id FROM enseignants WHERE email = ? AND id != ?');
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $error = 'Cet email est déjà utilisé.';
            }
        }
        if (!isset($error)) {
            $update = $pdo->prepare('UPDATE enseignants SET nom = ?, prenom = ?, email = ?, telephone = ?, specialite = ?, date_embauche = ? WHERE id = ?');
            $update->execute([$nom, $prenom, $email ?: null, $telephone, $specialite, $date_embauche ?: null, $id]);

            $pdo->prepare('DELETE FROM enseignants_classes WHERE enseignant_id = ?')->execute([$id]);
            if (!empty($classesIds)) {
                $insertLink = $pdo->prepare('INSERT INTO enseignants_classes (enseignant_id, classe_id) VALUES (?, ?)');
                foreach ($classesIds as $classeId) {
                    $insertLink->execute([$id, intval($classeId)]);
                }
            }

            header('Location: enseignants.php?msg=modifier');
            exit;
        }
    }
}
?>

<div class="form-card">
    <h3>Modifier l'enseignant : <?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $ens['nom']) ?>">
        </div>
        <div class="form-group">
            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $ens['prenom']) ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $ens['email']) ?>">
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? $ens['telephone']) ?>">
        </div>
        <div class="form-group">
            <label for="specialite">Spécialité</label>
            <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($_POST['specialite'] ?? $ens['specialite']) ?>">
        </div>
        <div class="form-group">
            <label for="date_embauche">Date d'embauche</label>
            <input type="date" id="date_embauche" name="date_embauche" value="<?= htmlspecialchars($_POST['date_embauche'] ?? $ens['date_embauche']) ?>">
        </div>
        <div class="form-group">
            <label for="classes">Classes assignées</label>
            <select id="classes" name="classes[]" multiple style="height:150px;">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= in_array($c['id'], $_POST['classes'] ?? $selectedClasses) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <small style="color:#888;font-size:0.8rem;">Maintiens Ctrl (ou Cmd) pour sélectionner plusieurs classes.</small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="enseignants.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
