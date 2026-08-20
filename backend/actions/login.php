<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$base = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . '/frontend/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
    header('Location: ' . $base . '/frontend/login.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['flash_error'] = 'Email ou mot de passe incorrect.';
    header('Location: ' . $base . '/frontend/login.php');
    exit;
}

if ($user['statut'] === 'inactif') {
    $_SESSION['flash_error'] = 'Votre compte est desactive. Contactez l\'administrateur.';
    header('Location: ' . $base . '/frontend/login.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nom'] = $user['nom'];
$_SESSION['prenom'] = $user['prenom'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

if ($user['must_change_password'] == 1) {
    $_SESSION['must_change_password'] = 1;
    header('Location: ' . $base . '/frontend/changer-mot-de-passe.php');
    exit;
}

unset($_SESSION['must_change_password']);

if ($user['role'] === 'admin') {
    header('Location: ' . $base . '/frontend/admin/dashboard.php');
} else {
    header('Location: ' . $base . '/frontend/directeur/dashboard.php');
}
exit;
