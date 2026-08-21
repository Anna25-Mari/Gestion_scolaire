<?php
/**
 * Service d'envoi de SMS via l'API Twilio.
 *
 * CONFIGURATION REELLE (backend/config/mail_config.php) :
 *   TWILIO_SID    -> Account SID (commence par "AC")
 *   TWILIO_TOKEN  -> Auth Token
 *   TWILIO_FROM   -> numero Twilio achete (ex : +15551234567)
 *
 * TANT QUE CES CONSTANTES SONT VIDES, LE SYSTEME FONCTIONNE EN MODE
 * SIMULATION : les SMS sont journalises dans backend/logs/sms.log.
 */
class SmsService
{
    const API_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    public static function isConfigured()
    {
        return defined('TWILIO_SID') && defined('TWILIO_TOKEN') && defined('TWILIO_FROM')
            && TWILIO_SID !== '' && TWILIO_TOKEN !== '' && TWILIO_FROM !== '';
    }

    public static function send($telephone, $message)
    {
        $telephone = self::normalize($telephone);
        if ($telephone === '') {
            return ['success' => false, 'error' => 'Numéro de téléphone manquant.'];
        }

        if (!self::isConfigured()) {
            return self::simulate($telephone, $message);
        }

        return self::sendViaTwilio($telephone, $message);
    }

    /**
     * Normalise un numero vers le format international E.164 (+229...).
     */
    public static function normalize($telephone)
    {
        $tel = preg_replace('/[\s\.\-\(\)]/', '', trim((string)$telephone));
        if ($tel === '') {
            return '';
        }
        if (strpos($tel, '+') === 0) {
            return '+' . preg_replace('/[^0-9]/', '', substr($tel, 1));
        }
        if (strpos($tel, '00') === 0) {
            return '+' . substr($tel, 2);
        }
        // Numero local beninois (8 ou 10 chiffres) : on suppose l'indicatif +229
        $digits = preg_replace('/[^0-9]/', '', $tel);
        if (strlen($digits) === 8 || strlen($digits) === 10) {
            return '+229' . $digits;
        }
        return '+' . $digits;
    }

    private static function sendViaTwilio($telephone, $message)
    {
        $url = sprintf(self::API_URL, rawurlencode(TWILIO_SID));

        $post = http_build_query([
            'To'   => $telephone,
            'From' => TWILIO_FROM,
            'Body' => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_USERPWD        => TWILIO_SID . ':' . TWILIO_TOKEN,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            self::log('ERREUR_CURL', $telephone, $curlErr);
            return ['success' => false, 'error' => 'Connexion impossible à Twilio : ' . $curlErr];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($data['sid'])) {
            self::log('ENVOYE', $telephone, 'sid=' . $data['sid']);
            return ['success' => true];
        }

        $error = isset($data['message'])
            ? 'Twilio (' . ($data['code'] ?? $httpCode) . ') : ' . $data['message']
            : 'Erreur Twilio HTTP ' . $httpCode;
        self::log('REFUSE', $telephone, $error);
        return ['success' => false, 'error' => $error];
    }

    private static function simulate($telephone, $message)
    {
        self::log('SIMULATION', $telephone, str_replace(["\r", "\n"], ' ', $message));

        $_SESSION['last_sms'] = [
            'to'      => $telephone,
            'message' => $message,
            'at'      => date('d/m/Y H:i'),
        ];

        return ['success' => true];
    }

    private static function log($status, $telephone, $detail)
    {
        $entry = sprintf(
            "[%s] %s TO=%s | %s\n",
            date('Y-m-d H:i:s'),
            $status,
            $telephone,
            $detail
        );

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @file_put_contents($logDir . '/sms.log', $entry, FILE_APPEND);
    }
}
