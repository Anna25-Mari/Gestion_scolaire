<?php
$pageTitle = 'Ajouter une classe';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$cycles = $pdo->query('SELECT id, nom FROM cycles ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $cycle_id = $_POST['cycle_id'] ?: null;
    $niveau = trim($_POST['niveau'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacite = intval($_POST['capacite'] ?? 40);

    if (empty($nom)) {
        $error = 'Le nom de la classe est obligatoire.';
    } else {
        $check = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom) = LOWER(?)');
        $check->execute([$nom]);
        if ($check->fetch()) {
            $error = 'Cette classe existe déjà.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO classes (nom, niveau, cycle_id, description, capacite) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nom, $niveau ?: null, $cycle_id, $description ?: null, $capacite > 0 ? $capacite : 40]);
            header('Location: classes.php?msg=ajouter');
            exit;
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="form-card">
    <h3>Ajouter une classe</h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom de la classe *</label>
            <input type="text" id="nom" name="nom" placeholder="Ex: 6ème A" required value="<?= htmlspecialchars($nom ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="cycle_id">Cycle</label>
            <select id="cycle_id" name="cycle_id">
                <option value="">-- Aucun cycle --</option>
                <?php foreach ($cycles as $cy): ?>
                    <option value="<?= $cy['id'] ?>" <?= ($cycle_id ?? '') == $cy['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cy['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="niveau">Niveau</label>
            <input type="text" id="niveau" name="niveau" placeholder="Ex: 6ème, CM2..." value="<?= htmlspecialchars($niveau ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" placeholder="Optionnel" value="<?= htmlspecialchars($description ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="capacite">Capacité maximale</label>
            <input type="number" id="capacite" name="capacite" min="1" max="200" value="<?= $capacite ?? 40 ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="classes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
