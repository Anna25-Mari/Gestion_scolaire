<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

$totalComptes = $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();
$totalDirecteurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'directeur'")->fetchColumn();
$totalInactifs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'inactif'")->fetchColumn();

$totalCycles = $pdo->query('SELECT COUNT(*) FROM cycles')->fetchColumn();
$totalClasses = $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$totalEleves = $pdo->query('SELECT COUNT(*) FROM eleves')->fetchColumn();
$totalEnseignants = $pdo->query('SELECT COUNT(*) FROM enseignants')->fetchColumn();

$derniersComptes = $pdo->query('SELECT id, nom, prenom, email, role, statut, date_creation FROM utilisateurs ORDER BY date_creation DESC LIMIT 5')->fetchAll();
$derniersEleves = $pdo->query('
    SELECT e.nom, e.prenom, e.sexe, c.nom as classe_nom, e.date_inscription
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.id
    ORDER BY e.date_inscription DESC, e.id DESC
    LIMIT 5
')->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalComptes ?></h3>
            <p>Total comptes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16.2 7.8l-2 6.3-6.4 2.1 2-6.3z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalCycles ?></h3>
            <p>Cycles</p>
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
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalEleves ?></h3>
            <p>Élèves</p>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalDirecteurs ?></h3>
            <p>Directeurs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalInactifs ?></h3>
            <p>Comptes inactifs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <div class="stat-info">
            <h3 style="font-size:1rem; line-height:2.2;">Importer</h3>
            <p>Données Excel</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="table-card">
        <div class="table-header">
            <h3>Derniers comptes créés</h3>
            <a href="comptes.php" class="btn btn-primary btn-sm">Voir tout</a>
        </div>
        <?php if (count($derniersComptes) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniersComptes as $compte): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <span class="avatar-sm"><?= htmlspecialchars(mb_substr($compte['prenom'], 0, 1) . mb_substr($compte['nom'], 0, 1)) ?></span>
                            <div>
                                <strong><?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?></strong><br>
                                <small style="color:#888;"><?= htmlspecialchars($compte['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $compte['role'] ?>"><?= ucfirst($compte['role']) ?></span></td>
                    <td><span class="badge badge-<?= $compte['statut'] ?>"><?= ucfirst($compte['statut']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($compte['date_creation'])) ?></td>
                    <td>
                        <div class="actions-cell">
                            <a href="modifier-compte.php?id=<?= $compte['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php if ($compte['id'] != $_SESSION['user_id']): ?>
                                <a href="comptes.php?action=supprimer&id=<?= $compte['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" data-confirm="Supprimer ce compte définitivement ?">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <p>Aucun compte créé.</p>
            <a href="ajouter-compte.php" class="btn btn-primary btn-sm">Créer un compte</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3>Derniers élèves inscrits</h3>
            <a href="eleves.php" class="btn btn-primary btn-sm">Voir tout</a>
        </div>
        <?php if (count($derniersEleves) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Sexe</th>
                    <th>Classe</th>
                    <th>Inscrit le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniersEleves as $eleve): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <span class="avatar-sm <?= $eleve['sexe'] === 'F' ? 'avatar-green' : '' ?>"><?= htmlspecialchars(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?></span>
                            <strong><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong>
                        </div>
                    </td>
                    <td><?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></td>
                    <td><?= $eleve['classe_nom'] ? htmlspecialchars($eleve['classe_nom']) : '<em style="color:#aaa">Non assigné</em>' ?></td>
                    <td><?= $eleve['date_inscription'] ? date('d/m/Y', strtotime($eleve['date_inscription'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <p>Aucun élève inscrit pour le moment.</p>
            <a href="importer.php" class="btn btn-primary btn-sm">Importer depuis Excel</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
