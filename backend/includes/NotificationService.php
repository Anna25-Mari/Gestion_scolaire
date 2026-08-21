<?php
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/SmsService.php';

/**
 * Envoi des identifiants à un utilisateur.
 * Canal prioritaire : Gmail. SMS en secours si l'e-mail échoue
 * ou si l'utilisateur n'a pas d'adresse e-mail.
 */
class NotificationService
{
    /**
     * Retourne ['mail' => resultat|null, 'sms' => resultat|null]
     * Succès global : au moins un canal a abouti.
     */
    public static function sendCredentials($email, $telephone, $prenom, $nom, $password, $context = 'cree')
    {
        $results = ['mail' => null, 'sms' => null];

        $messageText = self::buildMessage($prenom, $nom, $email, $password, $context);
        $html = self::buildHtml($prenom, $nom, $email, $password, $context);

        // 1. Gmail (prioritaire)
        if ($email !== '') {
            $results['mail'] = MailService::send(
                $email,
                'Vos identifiants - Complexe Scolaire ANNA',
                $html
            );
        }

        // 2. SMS en secours
        if (($results['mail'] === null || !$results['mail']['success']) && trim((string)$telephone) !== '') {
            $results['sms'] = SmsService::send($telephone, $messageText);
        }

        return $results;
    }

    public static function isDelivered(array $results)
    {
        return ($results['mail'] !== null && $results['mail']['success'])
            || ($results['sms'] !== null && $results['sms']['success']);
    }

    private static function buildMessage($prenom, $nom, $email, $password, $context)
    {
        if ($context === 'reinitialise') {
            $intro = "Votre mot de passe a ete reinitialise par l'administrateur.";
        } else {
            $intro = "Votre compte vient d'etre cree.";
        }

        $lines = [
            "Bonjour " . $prenom . " " . $nom . ",",
            $intro,
            "Identifiant : " . $email,
            "Mot de passe : " . $password,
            "Pour votre securite, vous devrez changer ce mot de passe a votre premiere connexion."
        ];

        return implode("\n", $lines);
    }

    private static function buildHtml($prenom, $nom, $email, $password, $context)
    {
        $message = htmlspecialchars(self::buildMessage($prenom, $nom, $email, $password, $context), ENT_QUOTES, 'UTF-8');
        return '<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;border:1px solid #ddd;border-radius:10px;overflow:hidden;">'
            . '<div style="background:#1a5276;color:#fff;padding:16px 24px;font-size:18px;font-weight:bold;">Complexe Scolaire ANNA</div>'
            . '<div style="padding:24px;white-space:pre-wrap;color:#333;font-size:14px;line-height:1.7;">' . $message . '</div>'
            . '</div>';
    }
}
