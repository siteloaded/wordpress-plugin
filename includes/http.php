<?php
defined('ABSPATH') or exit;

function siteloaded_close_http_client_connection($code, $content_type, $response) {
    ignore_user_abort(TRUE);
    ob_end_clean();
    http_response_code($code);
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . mb_strlen($response, '8bit'));
    header('Connection: close');
    echo $response;
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    session_write_close();
}

function siteloaded_current_request_url() {
    $s = $_SERVER;

    // Adapted from http://stackoverflow.com/a/8891890/193982

    $tls = FALSE;
    $scheme = 'http';
    if (isset($s['REQUEST_SCHEME'])) {
        $scheme = strtolower($s['REQUEST_SCHEME']);
        $tls = $scheme === 'https';
    } else {
        $tls = (isset($s['HTTP_X_FORWARDED_PROTO']) && $s['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($s['HTTPS']) && $s['HTTPS'] !== 'off' && $s['HTTPS'] !== '');
        if ($tls) { $scheme = 'https'; }
    }
    $port = $s['SERVER_PORT'];
    $port = ((!$tls && $port == '80') || ($tls && $port == '443')) ? '' : ':' . $port;
    $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = (isset($host) ? $host : $s['SERVER_NAME']) . $port;

    return array('scheme' => $scheme, 'host' => $host, 'path' => $s['REQUEST_URI']);
}

function siteloaded_request_url_to_str($r) {
    return $r['scheme'] . '://' . $r['host'] . $r['path'];
}
