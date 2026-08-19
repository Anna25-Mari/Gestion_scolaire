<?php
$pageTitle = 'Effectifs';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

$totalEleves = $pdo->query('SELECT COUNT(*) FROM eleves')->fetchColumn();
$totalEnseignants = $pdo->query('SELECT COUNT(*) FROM enseignants')->fetchColumn();
$totalClasses = $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$elevesFilles = $pdo->query("SELECT COUNT(*) FROM eleves WHERE sexe = 'F'")->fetchColumn();
$elevesGarcons = $pdo->query("SELECT COUNT(*) FROM eleves WHERE sexe = 'M'")->fetchColumn();
$elevesAffectes = $pdo->query('SELECT COUNT(*) FROM eleves WHERE classe_id IS NOT NULL')->fetchColumn();
$elevesNonAffectes = $totalEleves - $elevesAffectes;

$effectifsClasses = $pdo->query('
    SELECT c.id, c.nom, c.niveau, c.capacite, COUNT(e.id) as effectif
    FROM classes c
    LEFT JOIN eleves e ON e.classe_id = c.id
    GROUP BY c.id, c.nom, c.niveau, c.capacite
    ORDER BY c.nom
')->fetchAll();

$specialites = $pdo->query('
    SELECT specialite, COUNT(*) as total
    FROM enseignants
    WHERE specialite IS NOT NULL AND specialite != ""
    GROUP BY specialite
    ORDER BY total DESC
')->fetchAll();

$enseignantsParClasse = $pdo->query('
    SELECT ec.classe_id, e.nom, e.prenom, e.specialite
    FROM enseignants_classes ec
    JOIN enseignants e ON ec.enseignant_id = e.id
    ORDER BY e.nom
')->fetchAll();
$enseignantsByClasse = [];
foreach ($enseignantsParClasse as $row) {
    $enseignantsByClasse[$row['classe_id']][] = $row['prenom'] . ' ' . $row['nom'] . ($row['specialite'] ? ' (' . $row['specialite'] . ')' : '');
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalEleves ?></h3>
            <p>Total élèves</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalEnseignants ?></h3>
            <p>Enseignants</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalClasses ?></h3>
            <p>Classes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $elevesFilles ?> / <?= $elevesGarcons ?></h3>
            <p>Filles / Garçons</p>
        </div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $elevesAffectes ?></h3>
            <p>Élèves affectés</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $elevesNonAffectes ?></h3>
            <p>Élèves non affectés</p>
        </div>
    </div>
</div>

<?php if (count($effectifsClasses) > 0): ?>
<div class="table-card" style="margin-bottom:2rem;">
    <div class="table-header">
        <h3>Répartition des élèves par classe</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Classe</th>
                <th>Niveau</th>
                <th>Effectif</th>
                <th>Capacité</th>
                <th>Enseignants</th>
                <th>Occupation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($effectifsClasses as $classe): ?>
            <?php
                $pct = $classe['capacite'] > 0 ? round(($classe['effectif'] / $classe['capacite']) * 100) : 0;
                $color = $pct >= 90 ? '#e74c3c' : ($pct >= 70 ? '#f39c12' : '#27ae60');
                $cid = $classe['id'] ?? null;
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($classe['nom']) ?></strong></td>
                <td><?= htmlspecialchars($classe['niveau']) ?></td>
                <td><?= $classe['effectif'] ?></td>
                <td><?= $classe['capacite'] ?></td>
                <td>
                    <?php if (!empty($enseignantsByClasse[$classe['id']])): ?>
                        <?php foreach ($enseignantsByClasse[$classe['id']] as $ensName): ?>
                            <span class="badge badge-directeur" style="margin:1px;"><?= htmlspecialchars($ensName) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em style="color:#aaa;">Aucun</em>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <div style="flex:1;height:8px;background:#eee;border-radius:4px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div>
                        </div>
                        <span style="font-size:0.8rem;color:<?= $color ?>;font-weight:600;"><?= $pct ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (count($specialites) > 0): ?>
<div class="table-card">
    <div class="table-header">
        <h3>Enseignants par spécialité</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Spécialité</th>
                <th>Nombre</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($specialites as $sp): ?>
            <tr>
                <td><?= htmlspecialchars($sp['specialite']) ?></td>
                <td><?= $sp['total'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
