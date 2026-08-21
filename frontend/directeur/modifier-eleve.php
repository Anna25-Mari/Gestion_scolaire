<?php
$pageTitle = 'Modifier un élève';
require_once __DIR__ . '/header.php';
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
?>

<div class="form-card">
    <h3>Modifier l'élève : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
    <p class="form-legend">Les champs marqués d'un <span style="color:#e74c3c;font-weight:700;">*</span> sont obligatoires.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Identité de l'élève
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom" class="req">Nom</label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $eleve['nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="prenom" class="req">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $eleve['prenom']) ?>">
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($_POST['date_naissance'] ?? $eleve['date_naissance']) ?>">
                </div>
                <div class="form-group">
                    <label class="req">Sexe</label>
                    <div class="radio-group">
                        <label class="radio-pill">
                            <input type="radio" name="sexe" value="M" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'M' ? 'checked' : '' ?>>
                            <span>Garçon</span>
                        </label>
                        <label class="radio-pill">
                            <input type="radio" name="sexe" value="F" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'F' ? 'checked' : '' ?>>
                            <span>Fille</span>
                        </label>
                    </div>
                </div>
                <div class="form-group full">
                    <label for="classe_id">Salle / Classe</label>
                    <select id="classe_id" name="classe_id">
                        <option value="">-- Non assigné --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['classe_id'] ?? $eleve['classe_id']) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                Parent / Tuteur
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="parent_nom">Nom du parent / tuteur</label>
                    <input type="text" id="parent_nom" name="parent_nom" value="<?= htmlspecialchars($_POST['parent_nom'] ?? $eleve['parent_nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="parent_tel">Téléphone du parent</label>
                    <input type="tel" id="parent_tel" name="parent_tel" placeholder="Ex: 690 00 00 00" value="<?= htmlspecialchars($_POST['parent_tel'] ?? $eleve['parent_tel']) ?>">
                </div>
                <div class="form-group full">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? $eleve['adresse']) ?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="eleves.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
