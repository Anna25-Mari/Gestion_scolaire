<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getBasePath() {
    $docroot = $_SERVER['DOCUMENT_ROOT'];
    $scriptdir = dirname(dirname(dirname($_DIR__)));
    return rtrim(str_replace('\\', '/', str_replace($docroot, '', $scriptdir)), '/');
}

function noCache() {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . getBasePath() . '/frontend/login.php');
        exit;
    }
    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
        header('Location: ' . getBasePath() . '/frontend/changer-mot-de-passe.php');
        exit;
    }
    noCache();
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . getBasePath() . '/frontend/login.php');
        exit;
    }
    noCache();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

function getUserName() {
    return ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');
}
