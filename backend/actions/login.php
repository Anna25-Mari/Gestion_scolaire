<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Gestion_scolaire/frontend/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
    header('Location: /Gestion_scolaire/frontend/login.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['flash_error'] = 'Email ou mot de passe incorrect.';
    header('Location: /Gestion_scolaire/frontend/login.php');
    exit;
}

if ($user['statut'] === 'inactif') {
    $_SESSION['flash_error'] = 'Votre compte est désactivé. Contactez l\'administrateur.';
    header('Location: /Gestion_scolaire/frontend/login.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nom'] = $user['nom'];
$_SESSION['prenom'] = $user['prenom'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'admin') {
    header('Location: /Gestion_scolaire/frontend/admin/dashboard.php');
} else {
    header('Location: /Gestion_scolaire/frontend/directeur/dashboard.php');
}
exit;
