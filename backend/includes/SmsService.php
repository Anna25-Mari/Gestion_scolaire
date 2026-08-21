<?php
/**
 * Service d'envoi de SMS.
 * MODE = 'simulation' : le SMS est journalisé dans backend/logs/sms.log
 * et conservé en session pour affichage dans l'interface.
 *
 * Pour brancher une vraie passerelle plus tard (Twilio, Orange SMS, ...),
 * il suffira de remplacer le corps de send() — le reste du code ne change pas.
 */
class SmsService
{
    const MODE = 'simulation';

    public static function send($telephone, $message)
    {
        $telephone = trim((string)$telephone);
        if ($telephone === '') {
            return ['success' => false, 'error' => 'Numéro de téléphone manquant.'];
        }

        $entry = sprintf(
            "[%s] MODE=%s TO=%s | %s\n",
            date('Y-m-d H:i:s'),
            self::MODE,
            $telephone,
            str_replace(["\r", "\n"], ' ', $message)
        );

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @file_put_contents($logDir . '/sms.log', $entry, FILE_APPEND);

        $_SESSION['last_sms'] = [
            'to' => $telephone,
            'message' => $message,
            'at' => date('d/m/Y H:i')
        ];

        return ['success' => true];
    }
}
