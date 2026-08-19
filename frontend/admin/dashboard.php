<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../backend/config/database.php';

$pdo = getConnection();

$totalComptes = $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();
$totalDirecteurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'directeur'")->fetchColumn();
$totalActifs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'")->fetchColumn();
$totalInactifs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'inactif'")->fetchColumn();
$derniersComptes = $pdo->query('SELECT nom, prenom, email, role, statut, date_creation FROM utilisateurs ORDER BY date_creation DESC LIMIT 5')->fetchAll();
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalAdmins ?></h3>
            <p>Administrateurs</p>
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
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Derniers comptes créés</h3>
        <a href="comptes.php" class="btn btn-primary btn-sm">Voir tout</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($derniersComptes as $compte): ?>
            <tr>
                <td><?= htmlspecialchars($compte['nom']) ?></td>
                <td><?= htmlspecialchars($compte['prenom']) ?></td>
                <td><?= htmlspecialchars($compte['email']) ?></td>
                <td><span class="badge badge-<?= $compte['role'] ?>"><?= ucfirst($compte['role']) ?></span></td>
                <td><span class="badge badge-<?= $compte['statut'] ?>"><?= ucfirst($compte['statut']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
