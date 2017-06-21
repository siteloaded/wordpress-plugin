<?php
defined('ABSPATH') or exit;

function siteloaded_close_http_client_connection($content_type, $response) {
    ignore_user_abort(TRUE);
    ob_end_clean();
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . mb_strlen($response, '8bit'));
    header('Connection: close');
    echo $response;
    flush();
    session_write_close();
}
