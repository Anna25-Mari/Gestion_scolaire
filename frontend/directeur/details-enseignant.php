<?php
$pageTitle = 'Détails de l\'enseignant';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

$pdo = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: enseignants.php');
    exit;
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'suspendre') {
        $pdo->prepare("UPDATE enseignants SET statut = 'suspendu', date_suspension = NOW() WHERE id = ?")->execute([$id]);
        header('Location: details-enseignant.php?id=' . $id . '&msg=suspendre');
        exit;
    }
    if ($_GET['action'] === 'reactiver') {
        $pdo->prepare("UPDATE enseignants SET statut = 'actif', date_suspension = NULL WHERE id = ?")->execute([$id]);
        header('Location: details-enseignant.php?id=' . $id . '&msg=reactiver');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM enseignants WHERE id = ?');
$stmt->execute([$id]);
$ens = $stmt->fetch();

if (!$ens) {
    header('Location: enseignants.php');
    exit;
}

$stmtClasses = $pdo->prepare('
    SELECT c.id, c.nom, cy.nom AS cycle_nom
    FROM enseignants_classes ec
    JOIN classes c ON ec.classe_id = c.id
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    WHERE ec.enseignant_id = ?
    ORDER BY c.nom
');
$stmtClasses->execute([$id]);
$classesEns = $stmtClasses->fetchAll();

$statut = $ens['statut'] ?? 'actif';
$dateEmbauche = $ens['date_embauche'] ? strtotime($ens['date_embauche']) : null;
$ancienneteAnnees = $dateEmbauche ? floor((time() - $dateEmbauche) / (365.25 * 86400)) : null;
$ancienneteMois = $dateEmbauche ? floor(((time() - $dateEmbauche) / (30.44 * 86400)) % 12) : null;
?>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $messages = [
        'suspendre' => 'Enseignant suspendu.',
        'reactiver' => 'Enseignant réactivé.'
    ];
    ?>
    <?php if (isset($messages[$_GET['msg']])): ?>
        <div class="alert alert-success"><?= $messages[$_GET['msg']] ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="detail-card">
    <div class="detail-header">
        <div>
            <h3><?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></h3>
            <div class="detail-sub">
                Enseignant #<?= $ens['id'] ?>
                <?php if ($statut === 'suspendu'): ?>
                    — <span class="badge badge-suspendu">Suspendu</span>
                <?php else: ?>
                    — <span class="badge badge-actif">Actif</span>
                <?php endif; ?>
            </div>
            <div class="detail-sub">
                Salles attribuées :
                <?php if (count($classesEns) > 0): ?>
                    <?php foreach ($classesEns as $classe): ?>
                        <span class="badge badge-admin"><?= htmlspecialchars($classe['nom']) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <em style="color:#aaa;">aucune pour le moment</em>
                <?php endif; ?>
            </div>
        </div>
        <div class="actions-cell">
            <a href="modifier-enseignant.php?id=<?= $ens['id'] ?>" class="btn btn-warning btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Modifier
            </a>
            <?php if ($statut === 'actif'): ?>
            <a href="?action=suspendre&id=<?= $ens['id'] ?>" class="btn btn-cancel btn-sm" data-confirm="Suspendre cet enseignant ?">Suspendre</a>
            <?php else: ?>
            <a href="?action=reactiver&id=<?= $ens['id'] ?>" class="btn btn-success btn-sm" data-confirm="Réactiver cet enseignant ?">Réactiver</a>
            <?php endif; ?>
            <a href="enseignants.php" class="btn btn-cancel btn-sm">&larr; Retour aux enseignants</a>
        </div>
    </div>
    <div class="detail-grid">
        <div class="info-item">
            <div class="info-label">Nom complet</div>
            <div class="info-value"><?= htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><?= $ens['email'] ? htmlspecialchars($ens['email']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Téléphone</div>
            <div class="info-value"><?= $ens['telephone'] ? htmlspecialchars($ens['telephone']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Spécialité</div>
            <div class="info-value"><?= $ens['specialite'] ? htmlspecialchars($ens['specialite']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Dernier diplôme</div>
            <div class="info-value"><?= !empty($ens['dernier_diplome']) ? htmlspecialchars($ens['dernier_diplome']) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Date d'embauche</div>
            <div class="info-value"><?= $dateEmbauche ? date('d/m/Y', $dateEmbauche) : '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Dans l'établissement depuis</div>
            <div class="info-value">
                <?php if ($dateEmbauche): ?>
                    <?= date('d/m/Y', $dateEmbauche) ?>
                    (<?= trim(($ancienneteAnnees > 0 ? $ancienneteAnnees . ' an' . ($ancienneteAnnees > 1 ? 's' : '') : '') . ($ancienneteMois > 0 ? ($ancienneteAnnees > 0 ? ' et ' : '') . $ancienneteMois . ' mois' : '')) ?: 'Moins d\'un mois' ?>)
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>
        <?php if ($statut === 'suspendu'): ?>
        <div class="info-item">
            <div class="info-label">Suspendu depuis le</div>
            <div class="info-value"><?= !empty($ens['date_suspension']) ? date('d/m/Y à H:i', strtotime($ens['date_suspension'])) : '-' ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Salles / Classes assignées (<?= count($classesEns) ?>)</h3>
    </div>
    <?php if (count($classesEns) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Salle</th>
                <th>Cycle</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classesEns as $classe): ?>
            <tr>
                <td><?= $classe['id'] ?></td>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= $classe['cycle_nom'] ? htmlspecialchars($classe['cycle_nom']) : '-' ?></td>
                <td><a href="details-classe.php?id=<?= $classe['id'] ?>" class="btn btn-primary btn-sm">Voir détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:2rem;text-align:center;color:#888;">
        <p>Aucune salle assignée à cet enseignant.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
