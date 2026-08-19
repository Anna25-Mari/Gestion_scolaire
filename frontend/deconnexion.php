<?php
require_once __DIR__ . '/../backend/includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Complexe Scolaire ANNA</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .logout-wrapper {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            background: linear-gradient(135deg, #e8f0f8 0%, #f5f7fa 100%);
        }
        .logout-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(26, 82, 118, 0.12);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            text-align: center;
            animation: fadeInUp 0.5s ease-out;
        }
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fce4e4, #f5b7b1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .logout-card h2 {
            font-size: 1.4rem;
            color: #1a5276;
            margin-bottom: 0.5rem;
        }
        .logout-card p {
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .logout-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-logout {
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
        }
        .btn-confirm-logout {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: #fff;
        }
        .btn-confirm-logout:hover {
            box-shadow: 0 6px 20px rgba(192, 57, 43, 0.35);
        }
        .btn-cancel {
            background-color: #eee;
            color: #555;
        }
        .btn-cancel:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Complexe Scolaire ANNA</h1>
            <nav>
                <a href="index.html">Accueil</a>
            </nav>
        </div>
    </header>

    <main class="logout-wrapper">
        <div class="logout-card">
            <div class="logout-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#c0392b" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7" fill="none" stroke="#c0392b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="21" y1="12" x2="9" y2="12" fill="none" stroke="#c0392b" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h2>Déconnexion</h2>
            <p>Voulez-vous vraiment vous déconnecter ?</p>
            <div class="logout-actions">
                <a href="admin/dashboard.php" class="btn-logout btn-cancel">Annuler</a>
                <a href="../backend/actions/logout.php" class="btn-logout btn-confirm-logout">Oui, me déconnecter</a>
            </div>
        </div>
    </main>
</body>
</html>
