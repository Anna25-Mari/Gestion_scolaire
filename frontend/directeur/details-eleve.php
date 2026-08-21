<?php
$pageTitle = 'Détails de l\'élève';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: eleves.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau, cy.nom AS cycle_nom
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.id
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    WHERE e.id = ?
');
$stmt->execute([$id]);
$eleve = $stmt->fetch();

if (!$eleve) {
    header('Location: eleves.php');
    exit;
}

$dateNaissance = $eleve['date_naissance'] ? strtotime($eleve['date_naissance']) : null;
$age = $dateNaissance ? date('Y') - date('Y', $dateNaissance) - (date('md', $dateNaissance) > date('md') ? 1 : 0) : null;

$dateInscription = strtotime($eleve['date_inscription']);
$ancienneteAnnees = floor((time() - $dateInscription) / (365.25 * 86400));
$ancienneteMois = floor(((time() - $dateInscription) / (30.44 * 86400)) % 12);
?>

<div class="detail-card">
    <div class="detail-header">
        <div>
            <h3><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
            <div class="detail-sub">Élève #<?= $eleve['id'] ?> — Inscrit(e) le <?= date('d/m/Y', $dateInscription) ?></div>
        </div>
        <div class="actions-cell">
            <a href="telecharger-parcours.php?id=<?= $eleve['id'] ?>" class="btn btn-success btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Télécharger le parcours
            </a>
            <a href="eleves.php" class="btn btn-cancel btn-sm">&larr; Retour aux élèves</a>
        </div>
    </div>
    <div class="detail-grid">
        <div class="info-item">
            <div class="info-label">Nom complet</div>
            <div class="info-value"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Sexe</div>
            <div class="info-value"><?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Date de naissance</div>
            <div class="info-value"><?= $eleve['date_naissance'] ? date('d/m/Y', $dateNaissance) . ' (' . $age . ' ans)' : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Salle / Classe</div>
            <div class="info-value"><?= $eleve['classe_nom'] ? htmlspecialchars($eleve['classe_nom']) : 'Non assigné' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Cycle</div>
            <div class="info-value"><?= $eleve['cycle_nom'] ? htmlspecialchars($eleve['cycle_nom']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Niveau</div>
            <div class="info-value"><?= $eleve['classe_niveau'] ? htmlspecialchars($eleve['classe_niveau']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Tuteur / Parent</div>
            <div class="info-value"><?= $eleve['parent_nom'] ? htmlspecialchars($eleve['parent_nom']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Téléphone tuteur</div>
            <div class="info-value"><?= $eleve['parent_tel'] ? htmlspecialchars($eleve['parent_tel']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Adresse</div>
            <div class="info-value"><?= $eleve['adresse'] ? htmlspecialchars($eleve['adresse']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Date d'inscription</div>
            <div class="info-value"><?= date('d/m/Y', $dateInscription) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Ancienneté dans l'établissement</div>
            <div class="info-value">
                <?= $ancienneteAnnees > 0 ? $ancienneteAnnees . ' an' . ($ancienneteAnnees > 1 ? 's' : '') : '' ?>
                <?= $ancienneteMois > 0 ? ($ancienneteAnnees > 0 ? ' et ' : '') . $ancienneteMois . ' mois' : (!$ancienneteAnnees && !$ancienneteMois ? 'Moins d\'un mois' : '') ?>
            </div>
        </div>
    </div>
</div>

<?php if ($eleve['classe_nom']): ?>
<div class="table-card">
    <div class="table-header">
        <h3>Salle actuelle : <?= htmlspecialchars($eleve['classe_nom']) ?></h3>
        <a href="details-classe.php?id=<?= $eleve['classe_id'] ?>" class="btn btn-primary btn-sm">Voir la salle</a>
    </div>
    <div style="padding:1rem 1.5rem;color:#666;font-size:0.9rem;">
        L'élève évolue dans le cycle <strong><?= htmlspecialchars($eleve['cycle_nom'] ?? '-') ?></strong>,
        niveau <strong><?= htmlspecialchars($eleve['classe_niveau'] ?? '-') ?></strong>,
        depuis le <?= date('d/m/Y', $dateInscription) ?>.
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
