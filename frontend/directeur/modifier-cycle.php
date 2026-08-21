<?php
$pageTitle = 'Modifier un cycle';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: cycles.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cycles WHERE id = ?');
$stmt->execute([$id]);
$cycle = $stmt->fetch();

if (!$cycle) {
    header('Location: cycles.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $error = 'Le nom du cycle est obligatoire.';
    } else {
        $check = $pdo->prepare('SELECT id FROM cycles WHERE LOWER(nom) = LOWER(?) AND id != ?');
        $check->execute([$nom, $id]);
        if ($check->fetch()) {
            $error = 'Ce cycle existe déjà.';
        } else {
            $update = $pdo->prepare('UPDATE cycles SET nom = ?, code = ?, description = ? WHERE id = ?');
            $update->execute([$nom, $code ?: null, $description ?: null, $id]);
            header('Location: cycles.php?msg=modifier');
            exit;
        }
    }
}
?>

<div class="form-card">
    <h3>Modifier le cycle : <?= htmlspecialchars($cycle['nom']) ?></h3>
    <p class="form-legend">Modifiez les informations du cycle puis enregistrez.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16.2 7.8l-2 6.3-6.4 2.1 2-6.3z"/></svg>
                Informations du cycle
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom" class="req">Nom du cycle</label>
                    <input type="text" id="nom" name="nom" placeholder="Ex: Maternelle, Primaire, Secondaire" required value="<?= htmlspecialchars($_POST['nom'] ?? $cycle['nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" id="code" name="code" placeholder="Ex: MAT, PRI, SEC" maxlength="20" value="<?= htmlspecialchars($_POST['code'] ?? $cycle['code']) ?>">
                </div>
                <div class="form-group full">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="<?= htmlspecialchars($_POST['description'] ?? $cycle['description']) ?>">
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="cycles.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
