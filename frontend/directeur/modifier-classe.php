<?php
$pageTitle = 'Modifier une classe';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: classes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
$stmt->execute([$id]);
$classe = $stmt->fetch();

if (!$classe) {
    header('Location: classes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacite = intval($_POST['capacite'] ?? 40);

    if (empty($nom) || empty($niveau)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } else {
        $update = $pdo->prepare('UPDATE classes SET nom = ?, niveau = ?, description = ?, capacite = ? WHERE id = ?');
        $update->execute([$nom, $niveau, $description, $capacite, $id]);
        header('Location: classes.php?msg=modifier');
        exit;
    }
}
?>

<div class="form-card">
    <h3>Modifier la classe : <?= htmlspecialchars($classe['nom']) ?></h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom de la classe</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $classe['nom']) ?>">
        </div>
        <div class="form-group">
            <label for="niveau">Niveau</label>
            <select id="niveau" name="niveau" required>
                <?php
                $niveaux = ['Maternelle', 'Primaire', '6ème', '5ème', '4ème', '3ème', 'Seconde', 'Première', 'Terminale'];
                $current = $_POST['niveau'] ?? $classe['niveau'];
                foreach ($niveaux as $n):
                ?>
                    <option value="<?= $n ?>" <?= $current === $n ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" value="<?= htmlspecialchars($_POST['description'] ?? $classe['description']) ?>">
        </div>
        <div class="form-group">
            <label for="capacite">Capacité maximale</label>
            <input type="number" id="capacite" name="capacite" min="1" max="100" value="<?= $_POST['capacite'] ?? $classe['capacite'] ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="classes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
