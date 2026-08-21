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

function renderPagination($page, $totalPages, array $params = [], $pageParam = 'page') {
    if ($totalPages <= 1) {
        return '';
    }

    $page = max(1, min($page, $totalPages));

    $buildUrl = function ($p) use ($params, $pageParam) {
        $params[$pageParam] = $p;
        return '?' . http_build_query($params);
    };

    $html = '<div class="pagination">';

    if ($page > 1) {
        $html .= '<a href="' . htmlspecialchars($buildUrl($page - 1)) . '" class="page-link">&laquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">&laquo;</span>';
    }

    $start = max(1, $page - 2);
    $end = min($totalPages, $start + 4);
    if ($end - $start < 4) {
        $start = max(1, $end - 4);
    }

    if ($start > 1) {
        $html .= '<a href="' . htmlspecialchars($buildUrl(1)) . '" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-link disabled">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            $html .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($buildUrl($i)) . '" class="page-link">' . $i . '</a>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-link disabled">...</span>';
        }
        $html .= '<a href="' . htmlspecialchars($buildUrl($totalPages)) . '" class="page-link">' . $totalPages . '</a>';
    }

    if ($page < $totalPages) {
        $html .= '<a href="' . htmlspecialchars($buildUrl($page + 1)) . '" class="page-link">&raquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">&raquo;</span>';
    }

    $html .= '</div>';

    return $html;
}

function paginationPage($default = 1) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : $default;
    return $page > 0 ? $page : $default;
}
