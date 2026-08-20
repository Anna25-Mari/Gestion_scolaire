<?php
session_start();
require_once __DIR__ . '/../backend/includes/auth.php';
require_once __DIR__ . '/../backend/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['must_change_password'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $pdo = getConnection();
    $stmt = $pdo->prepare('SELECT password FROM utilisateurs WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        $error = 'Le mot de passe actuel est incorrect.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Le nouveau mot de passe doit contenir au moins 6 caracteres.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'Le nouveau mot de passe doit etre different de l\'actuel.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE utilisateurs SET password = ?, must_change_password = 0 WHERE id = ?');
        $update->execute([$hashed, $_SESSION['user_id']]);
        unset($_SESSION['must_change_password']);
        $success = 'Mot de passe modifie avec succes. Redirection...';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - Complexe Scolaire ANNA</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .change-wrapper {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            background: linear-gradient(135deg, #e8f0f8 0%, #f5f7fa 100%);
        }
        .change-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(26, 82, 118, 0.12);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 440px;
            animation: fadeInUp 0.5s ease-out;
        }
        .change-card h2 {
            font-size: 1.3rem;
            color: #1a5276;
            margin-bottom: 0.3rem;
        }
        .change-card .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1.8rem;
        }
        .change-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #d4efdf, #a9dfbf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 0.3rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.35);
        }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background-color: #fdecea;
            color: #c0392b;
            border: 1px solid #f5b7b1;
        }
        .alert-success {
            background-color: #d4efdf;
            color: #1e8449;
            border: 1px solid #a9dfbf;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Complexe Scolaire ANNA</h1>
        </div>
    </header>

    <main class="change-wrapper">
        <div class="change-card">
            <div class="change-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#1e8449" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <h2>Changement de mot de passe</h2>
            <p class="subtitle">Vous devez modifier votre mot de passe avant de continuer.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <script>
                    setTimeout(function() {
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            window.location.href = 'admin/dashboard.php';
                        <?php else: ?>
                            window.location.href = 'directeur/dashboard.php';
                        <?php endif; ?>
                    }, 1500);
                </script>
            <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Saisissez votre mot de passe actuel" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Minimum 6 caracteres" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Retapez le nouveau mot de passe" required minlength="6">
                </div>
                <button type="submit" class="btn-submit">Modifier le mot de passe</button>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
