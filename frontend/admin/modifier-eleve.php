<?php
$pageTitle = 'Modifier un élève';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: eleves.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM eleves WHERE id = ?');
$stmt->execute([$id]);
$eleve = $stmt->fetch();

if (!$eleve) {
    header('Location: eleves.php');
    exit;
}

$classes = $pdo->query('SELECT id, nom FROM classes ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? null;
    $sexe = $_POST['sexe'] ?? 'M';
    $parent_nom = trim($_POST['parent_nom'] ?? '');
    $parent_tel = trim($_POST['parent_tel'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $classe_id = $_POST['classe_id'] ?: null;

    if (empty($nom) || empty($prenom) || empty($sexe)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } else {
        $update = $pdo->prepare('UPDATE eleves SET nom = ?, prenom = ?, date_naissance = ?, sexe = ?, parent_nom = ?, parent_tel = ?, adresse = ?, classe_id = ? WHERE id = ?');
        $update->execute([$nom, $prenom, $date_naissance ?: null, $sexe, $parent_nom, $parent_tel, $adresse, $classe_id, $id]);
        header('Location: eleves.php?msg=modifier');
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="form-card">
    <h3>Modifier l'élève : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $eleve['nom']) ?>">
        </div>
        <div class="form-group">
            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $eleve['prenom']) ?>">
        </div>
        <div class="form-group">
            <label for="date_naissance">Date de naissance</label>
            <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($_POST['date_naissance'] ?? $eleve['date_naissance']) ?>">
        </div>
        <div class="form-group">
            <label>Sexe *</label>
            <div style="display:flex;gap:1.5rem;margin-top:0.4rem;">
                <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-size:0.9rem;color:#555;">
                    <input type="radio" name="sexe" value="M" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'M' ? 'checked' : '' ?>> Garçon
                </label>
                <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-size:0.9rem;color:#555;">
                    <input type="radio" name="sexe" value="F" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'F' ? 'checked' : '' ?>> Fille
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="classe_id">Classe</label>
            <select id="classe_id" name="classe_id">
                <option value="">-- Non assigné --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_POST['classe_id'] ?? $eleve['classe_id']) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="parent_nom">Nom du parent / tuteur</label>
            <input type="text" id="parent_nom" name="parent_nom" value="<?= htmlspecialchars($_POST['parent_nom'] ?? $eleve['parent_nom']) ?>">
        </div>
        <div class="form-group">
            <label for="parent_tel">Téléphone du parent</label>
            <input type="text" id="parent_tel" name="parent_tel" value="<?= htmlspecialchars($_POST['parent_tel'] ?? $eleve['parent_tel']) ?>">
        </div>
        <div class="form-group">
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? $eleve['adresse']) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="eleves.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
