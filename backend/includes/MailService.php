<?php
require_once __DIR__ . '/../config/mail_config.php';

/**
 * Service d'envoi d'e-mails via Gmail SMTP.
 * Client SMTP minimaliste natif (STARTTLS + AUTH LOGIN), sans Composer.
 *
 * - Si MAIL_ADDRESS / MAIL_PASSWORD sont configurés : envoi réel via Gmail.
 * - Sinon : mode simulation, les e-mails sont journalisés dans
 *   backend/logs/mail.log (aucun envoi réel).
 */
class MailService
{
    const SMTP_HOST = 'tcp://smtp.gmail.com';
    const SMTP_PORT = 587;

    public static function isConfigured()
    {
        return MAIL_ADDRESS !== '' && MAIL_PASSWORD !== '';
    }

    /**
     * Envoie un e-mail. Retourne ['success' => bool, 'mode' => 'gmail'|'simulation', 'error' => ?]
     */
    public static function send($to, $subject, $htmlBody)
    {
        if (!self::isConfigured()) {
            return self::simulate($to, $subject, $htmlBody);
        }

        try {
            self::smtpSend($to, $subject, $htmlBody);
            return ['success' => true, 'mode' => 'gmail', 'error' => null];
        } catch (Exception $e) {
            self::log('ERREUR', $to, $subject, $e->getMessage());
            return ['success' => false, 'mode' => 'gmail', 'error' => $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------
    // Simulation locale
    // ------------------------------------------------------------------

    private static function simulate($to, $subject, $htmlBody)
    {
        self::log('SIMULATION', $to, $subject, strip_tags($htmlBody));

        $_SESSION['last_mail'] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $htmlBody,
            'at' => date('d/m/Y H:i')
        ];

        return ['success' => true, 'mode' => 'simulation', 'error' => null];
    }

    private static function log($status, $to, $subject, $content)
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $entry = sprintf(
            "[%s] %s TO=%s | %s | %s\n",
            date('Y-m-d H:i:s'),
            $status,
            $to,
            $subject,
            str_replace(["\r", "\n"], ' ', $content)
        );
        @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);
    }

    // ------------------------------------------------------------------
    // Client SMTP
    // ------------------------------------------------------------------

    private static function smtpSend($to, $subject, $htmlBody)
    {
        if (!function_exists('stream_socket_client')) {
            throw new Exception('Les sockets PHP ne sont pas disponibles sur ce serveur.');
        }

        $fp = @stream_socket_client(self::SMTP_HOST . ':' . self::SMTP_PORT, $errno, $errstr, 15);
        if (!$fp) {
            throw new Exception("Connexion au serveur SMTP impossible ($errstr)");
        }
        stream_set_timeout($fp, 15);

        try {
            self::expect($fp, 220);
            self::command($fp, 'EHLO localhost', 250);
            self::command($fp, 'STARTTLS', 220);

            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Échec du chiffrement TLS avec le serveur SMTP.');
            }

            self::command($fp, 'EHLO localhost', 250);
            self::command($fp, 'AUTH LOGIN', 334);
            self::command($fp, base64_encode(MAIL_ADDRESS), 334);
            self::command($fp, base64_encode(MAIL_PASSWORD), 235);

            self::command($fp, 'MAIL FROM:<' . MAIL_ADDRESS . '>', 250);
            self::command($fp, 'RCPT TO:<' . $to . '>', 250);
            self::command($fp, 'DATA', 354);

            $headers = 'From: Complexe Scolaire ANNA <' . MAIL_ADDRESS . '>' . "\r\n"
                . 'To: <' . $to . '>' . "\r\n"
                . 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=' . "\r\n"
                . 'MIME-Version: 1.0' . "\r\n"
                . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
                . 'Content-Transfer-Encoding: base64' . "\r\n";

            $body = chunk_split(base64_encode($htmlBody));

            fwrite($fp, $headers . "\r\n" . $body . "\r\n.");
            self::expect($fp, 250);
            fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }

    private static function command($fp, $line, $expectedCode)
    {
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $expectedCode);
    }

    private static function expect($fp, $code)
    {
        $response = self::readResponse($fp);
        $received = (int)substr($response, 0, 3);
        if ($received !== $code) {
            throw new Exception('SMTP (' . $code . ' attendu, ' . $received . ' reçu) : ' . trim($response));
        }
    }

    private static function readResponse($fp)
    {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Fin de réponse multi-lignes : 4e caractère différent de '-'
            if (!isset($line[3]) || $line[3] !== '-') {
                break;
            }
        }
        return $data;
    }
}
