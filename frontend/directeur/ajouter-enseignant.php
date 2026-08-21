<?php
$pageTitle = 'Ajouter un enseignant';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();
$classes = $pdo->query('SELECT id, nom FROM classes ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $dernierDiplome = trim($_POST['dernier_diplome'] ?? '');
    $date_embauche = $_POST['date_embauche'] ?? null;
    $classesIds = $_POST['classes'] ?? [];

    if (empty($nom) || empty($prenom)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        if ($email) {
            $check = $pdo->prepare('SELECT id FROM enseignants WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Cet email est déjà utilisé.';
            }
        }
        if (!isset($error)) {
            $stmt = $pdo->prepare('INSERT INTO enseignants (nom, prenom, email, telephone, specialite, dernier_diplome, date_embauche) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nom, $prenom, $email ?: null, $telephone, $specialite, $dernierDiplome ?: null, $date_embauche ?: null]);
            $ensId = $pdo->lastInsertId();

            if (!empty($classesIds)) {
                $insertLink = $pdo->prepare('INSERT INTO enseignants_classes (enseignant_id, classe_id) VALUES (?, ?)');
                foreach ($classesIds as $classeId) {
                    $insertLink->execute([$ensId, intval($classeId)]);
                }
            }

            header('Location: enseignants.php?msg=ajouter');
            exit;
        }
    }
}
?>

<div class="form-card">
    <h3>Ajouter un enseignant</h3>
    <p class="form-legend">Les champs marqués d'un <span style="color:#e74c3c;font-weight:700;">*</span> sont obligatoires.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Identité
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom" class="req">Nom</label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($nom ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="prenom" class="req">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($prenom ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone / Contact</label>
                    <input type="tel" id="telephone" name="telephone" placeholder="Ex: 677 00 00 00" value="<?= htmlspecialchars($telephone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Ex: p.kamga@anna.com" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                Profil professionnel
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="specialite">Spécialité</label>
                    <input type="text" id="specialite" name="specialite" placeholder="Ex: Mathématiques, Français..." value="<?= htmlspecialchars($specialite ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="dernier_diplome">Dernier diplôme</label>
                    <input type="text" id="dernier_diplome" name="dernier_diplome" placeholder="Ex: Master de Mathématiques..." value="<?= htmlspecialchars($dernierDiplome ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label for="date_embauche">Date d'embauche</label>
                    <input type="date" id="date_embauche" name="date_embauche" value="<?= htmlspecialchars($date_embauche ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Affectation
            </div>
            <div class="form-group full">
                <label>Salles / Classes assignées</label>
                <?php if (count($classes) > 0): ?>
                <div class="checkbox-grid">
                    <?php foreach ($classes as $c): ?>
                    <label class="checkbox-pill">
                        <input type="checkbox" name="classes[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], array_map('intval', $_POST['classes'] ?? [])) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($c['nom']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small>Cochez les salles dans lesquelles cet enseignant intervient.</small>
                <?php else: ?>
                <small>Aucune salle n'existe encore. <a href="ajouter-classe.php">Créer une salle</a>.</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter l'enseignant</button>
            <a href="enseignants.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
