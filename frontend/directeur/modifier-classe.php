<?php
$pageTitle = 'Modifier une salle';
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

$cycles = $pdo->query('SELECT id, nom FROM cycles ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $cycle_id = $_POST['cycle_id'] ?: null;
    $niveau = trim($_POST['niveau'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacite = intval($_POST['capacite'] ?? 40);

    if (empty($nom)) {
        $error = 'Le nom de la salle est obligatoire.';
    } else {
        $check = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom) = LOWER(?) AND id != ?');
        $check->execute([$nom, $id]);
        if ($check->fetch()) {
            $error = 'Cette salle existe déjà.';
        } else {
            $update = $pdo->prepare('UPDATE classes SET nom = ?, niveau = ?, cycle_id = ?, description = ?, capacite = ? WHERE id = ?');
            $update->execute([$nom, $niveau ?: null, $cycle_id, $description ?: null, $capacite > 0 ? $capacite : 40, $id]);
            header('Location: classes.php?msg=modifier');
            exit;
        }
    }
}
?>

<div class="form-card">
    <h3>Modifier la salle : <?= htmlspecialchars($classe['nom']) ?></h3>
    <p class="form-legend">Modifiez les informations de la salle puis enregistrez.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Identification
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom" class="req">Nom de la salle</label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $classe['nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="cycle_id">Cycle</label>
                    <select id="cycle_id" name="cycle_id">
                        <option value="">-- Aucun cycle --</option>
                        <?php foreach ($cycles as $cy): ?>
                            <option value="<?= $cy['id'] ?>" <?= ($_POST['cycle_id'] ?? $classe['cycle_id']) == $cy['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cy['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="niveau">Niveau</label>
                    <input type="text" id="niveau" name="niveau" placeholder="Ex: 6ème, CM2..." value="<?= htmlspecialchars($_POST['niveau'] ?? $classe['niveau']) ?>">
                </div>
                <div class="form-group">
                    <label for="capacite">Capacité maximale</label>
                    <input type="number" id="capacite" name="capacite" min="1" max="200" value="<?= $_POST['capacite'] ?? $classe['capacite'] ?>">
                </div>
                <div class="form-group full">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="<?= htmlspecialchars($_POST['description'] ?? $classe['description']) ?>">
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="classes.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
