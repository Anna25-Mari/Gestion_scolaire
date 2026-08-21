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

define('MAIL_ADDRESS', 'projetdeux2@gmail.com');   // Compte Gmail de l'ecole
define('MAIL_PASSWORD', 'gphfgdosccwdwpsy');  // Mot de passe d'application Gmail (16 caracteres)

/**
 * CONFIGURATION SMS (Twilio) :
 * 1. Cree un compte sur https://www.twilio.com/try-twilio (essai gratuit)
 * 2. Achete un numero avec capacite SMS : Phone Numbers -> Manage -> Buy a number
 * 3. Recupere Account SID (commence par "AC") et Auth Token sur le tableau de bord
 * 4. En mode essai, verifie aussi les numeros destinataires :
 *    Phone Numbers -> Verified Caller IDs
 *
 * TANT QUE CES CONSTANTES SONT VIDES, LES SMS FONCTIONNENT EN MODE
 * SIMULATION : journalises dans backend/logs/sms.log.
 */

define('TWILIO_SID', '');     // Ex : ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
define('TWILIO_TOKEN', '');   // Auth Token Twilio
define('TWILIO_FROM', '');    // Numero Twilio achete, ex : +15551234567
