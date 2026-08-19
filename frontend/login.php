<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Complexe Scolaire ANNA</title>
    <link rel="stylesheet" href="css/style.css">
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

    <main class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#1a5276" viewBox="0 0 24 24">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                </div>
                <h2>Connexion</h2>
                <p>Accédez à votre espace de gestion</p>
            </div>

            <?php
            session_start();
            if (isset($_SESSION['flash_error'])):
            ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    <?php unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" action="../backend/actions/login.php" method="POST">
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" placeholder="exemple@ecole.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Se souvenir de moi
                    </label>
                </div>
                <button type="submit" class="btn-login">Se connecter</button>
            </form>
        </div>
    </main>
</body>
</html>
