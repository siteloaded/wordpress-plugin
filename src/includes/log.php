<?php
defined('ABSPATH') or exit;

function siteloaded_log($msg) {
    do_action('siteloaded_log', $msg);
}

function siteloaded_debug($msg) {
    do_action('siteloaded_log', $msg, TRUE);
}

add_action('siteloaded_log', '__siteloaded_debug_log', PHP_INT_MAX, 2);

function __siteloaded_debug_log($msg, $debug = FALSE) {
    if (!defined('WP_DEBUG') || !WP_DEBUG || !defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
        return;
    }

    if ($debug === TRUE && SITELOADED_ENV !== "DEV") {
        return;
    }

    error_log(SITELOADED_SLUG . ($debug === TRUE ? ' (debug)' : '') . ': ' . $msg);
}
