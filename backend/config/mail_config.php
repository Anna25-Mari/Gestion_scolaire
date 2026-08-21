<?php
/**
 * Configuration de l'envoi d'e-mails via Gmail SMTP.
 *
 * POUR ACTIVER L'ENVOI REEL :
 * 1. Connecte-toi sur le compte Gmail de l'ecole
 * 2. Active la validation en 2 etapes :
 *    https://myaccount.google.com/security
 * 3. Cree un mot de passe d'application (16 caracteres) :
 *    https://myaccount.google.com/apppasswords
 *    (choisis "Application" = Autre, nomme-la "Gestion Scolaire")
 * 4. Colle ci-dessous l'adresse Gmail et le mot de passe d'application
 *    (sans espaces).
 *
 * TANT QUE CES CONSTANTES SONT VIDES, LE SYSTEME FONCTIONNE EN MODE
 * SIMULATION : les e-mails sont journalises dans backend/logs/mail.log
 * et ne partent nulle part. L'admin ne voit jamais les mots de passe.
 */

define('MAIL_ADDRESS', '');   // Ex : complexe.scolaire.anna@gmail.com
define('MAIL_PASSWORD', '');  // Mot de passe d'application Gmail (16 caracteres)
