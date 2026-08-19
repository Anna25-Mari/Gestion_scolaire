<?php
$pageTitle = 'Ajouter une classe';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    $nom = trim($_POST['nom'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacite = intval($_POST['capacite'] ?? 40);

    if (empty($nom) || empty($niveau)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO classes (nom, niveau, description, capacite) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nom, $niveau, $description, $capacite]);
        header('Location: classes.php?msg=ajouter');
        exit;
    }
}
?>

<div class="form-card">
    <h3>Ajouter une classe</h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom de la classe</label>
            <input type="text" id="nom" name="nom" placeholder="Ex: 6ème A" required value="<?= htmlspecialchars($nom ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="niveau">Niveau</label>
            <select id="niveau" name="niveau" required>
                <option value="">-- Choisir --</option>
                <option value="Maternelle">Maternelle</option>
                <option value="Primaire">Primaire</option>
                <option value="6ème">6ème</option>
                <option value="5ème">5ème</option>
                <option value="4ème">4ème</option>
                <option value="3ème">3ème</option>
                <option value="Seconde">Seconde</option>
                <option value="Première">Première</option>
                <option value="Terminale">Terminale</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" placeholder="Optionnel" value="<?= htmlspecialchars($description ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="capacite">Capacité maximale</label>
            <input type="number" id="capacite" name="capacite" min="1" max="100" value="<?= $capacite ?? 40 ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="classes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
