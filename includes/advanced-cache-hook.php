<?php

if (!siteloaded_current_request_cacheable()) {
    return;
}

ob_start(function($html) {
    return function_exists('siteloaded_optimize_page')
        ? siteloaded_optimize_page($html)
        : $html;
});

function siteloaded_current_request_cacheable() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return FALSE;
    }

    if (!empty($_COOKIE)) {
        foreach ($_COOKIE as $k => $v) {
            if (preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $k)) {
                return FALSE;
            }
        }
    }

    return TRUE;
}
