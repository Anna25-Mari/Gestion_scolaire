<?php
require_once __DIR__ . '/../../backend/includes/auth.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Directeur' ?> - Complexe Scolaire ANNA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script>
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                window.location.reload();
            }
        });
    </script>
</head>
<body class="admin-body">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Complexe Scolaire ANNA</h2>
            <div class="sidebar-role">Directeur</div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Tableau de bord
            </a>
            <a href="classes.php" class="<?= in_array($currentPage, ['classes','ajouter-classe','modifier-classe']) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Gestion des classes
            </a>
            <a href="eleves.php" class="<?= in_array($currentPage, ['eleves','ajouter-eleve','modifier-eleve']) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Gestion des élèves
            </a>
            <a href="enseignants.php" class="<?= in_array($currentPage, ['enseignants','ajouter-enseignant','modifier-enseignant']) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Gestion des enseignants
            </a>
            <a href="effectifs.php" class="<?= $currentPage === 'effectifs' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Effectifs
            </a>
            <hr class="sidebar-divider">
            <a href="../deconnexion.php" class="logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Déconnexion
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h2><?= $pageTitle ?? 'Tableau de bord' ?></h2>
            <div class="topbar-user">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?></div>
                <span><?= htmlspecialchars(getUserName()) ?></span>
            </div>
        </div>

        <div class="page-content">
