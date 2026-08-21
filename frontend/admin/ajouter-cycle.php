<?php
$pageTitle = 'Ajouter un cycle';
require_once __DIR__ . '/../../backend/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../../backend/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    $nom = trim($_POST['nom'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $error = 'Le nom du cycle est obligatoire.';
    } else {
        $check = $pdo->prepare('SELECT id FROM cycles WHERE LOWER(nom) = LOWER(?)');
        $check->execute([$nom]);
        if ($check->fetch()) {
            $error = 'Ce cycle existe déjà.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO cycles (nom, code, description) VALUES (?, ?, ?)');
            $stmt->execute([$nom, $code ?: null, $description ?: null]);
            header('Location: cycles.php?msg=ajouter');
            exit;
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="form-card">
    <h3>Ajouter un cycle</h3>
    <p class="form-legend">Les cycles permettent de classer les salles (Maternelle, Primaire, Secondaire...).</p>

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
                    <input type="text" id="nom" name="nom" placeholder="Ex: Maternelle, Primaire, Secondaire" required value="<?= htmlspecialchars($nom ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" id="code" name="code" placeholder="Ex: MAT, PRI, SEC" maxlength="20" value="<?= htmlspecialchars($code ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" placeholder="Optionnel" value="<?= htmlspecialchars($description ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter le cycle</button>
            <a href="cycles.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
