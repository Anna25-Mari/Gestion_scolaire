<?php
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function setSuccess($message) {
    $_SESSION['flash_success'] = $message;
}

function setError($message) {
    $_SESSION['flash_error'] = $message;
}

function getFlash($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}
