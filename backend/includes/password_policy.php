<?php
/**
 * Politique de mot de passe :
 * - 8 caractères minimum
 * - au moins une lettre majuscule
 * - au moins une lettre minuscule
 * - au moins un chiffre
 */

function validatePasswordPolicy($password)
{
    if (strlen($password) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Le mot de passe doit contenir au moins une lettre majuscule.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Le mot de passe doit contenir au moins une lettre minuscule.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Le mot de passe doit contenir au moins un chiffre.';
    }
    return true;
}

function passwordPolicyHint()
{
    return 'Minimum 8 caracteres, avec au moins une majuscule, une minuscule et un chiffre.';
}

/**
 * Génère un mot de passe aléatoire conforme à la politique :
 * longueur 12, avec au moins une majuscule, une minuscule et un chiffre.
 */
function generateRandomPassword($length = 12)
{
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghijkmnopqrstuvwxyz';
    $digits = '23456789';
    $all = $upper . $lower . $digits;

    $password = [
        $upper[random_int(0, strlen($upper) - 1)],
        $lower[random_int(0, strlen($lower) - 1)],
        $digits[random_int(0, strlen($digits) - 1)]
    ];

    for ($i = count($password); $i < $length; $i++) {
        $password[] = $all[random_int(0, strlen($all) - 1)];
    }

    shuffle($password);
    return implode('', $password);
}
