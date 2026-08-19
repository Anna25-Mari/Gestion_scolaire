<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /Gestion_scolaire/frontend/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /Gestion_scolaire/frontend/login.php');
        exit;
    }
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
