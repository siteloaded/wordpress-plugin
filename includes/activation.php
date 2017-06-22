<?php
defined('ABSPATH') or exit;

register_activation_hook(SITELOADED_MAIN_PLUGIN_FILE, 'siteloaded_activated');
register_deactivation_hook(SITELOADED_MAIN_PLUGIN_FILE, 'siteloaded_deactivated');

function siteloaded_activated() {
    siteloaded_network_each_blog(function($blog_id, $active, $me) {
        siteloaded_debug(sprintf("(activated) blog id %d is active %b and this is the current blog %b", $blog_id, $active, $me));
        if (!$active || $me) {
            siteloaded_cache_destroy($blog_id);
        }
    });
    siteloaded_ensure_advanced_cache_file();
    siteloaded_ensure_config('WP_CACHE', TRUE);
    add_site_option('siteloaded_just_activated', 'TRUE');
    siteloaded_log('activated');
}

function siteloaded_deactivated() {
    $no_instance_remaining = TRUE;
    siteloaded_network_each_blog(function($blog_id, $active, $me) use (&$no_instance_remaining) {
        siteloaded_debug(sprintf("(deactivated) blog id %d is active %b and this is the current blog %b", $blog_id, $active, $me));
        $no_instance_remaining = $no_instance_remaining && (!$active || $me);
        if (!$active || $me) {
            siteloaded_cache_destroy($blog_id);
        }
    });
    if ($no_instance_remaining === TRUE) {
        siteloaded_ensure_config('WP_CACHE', FALSE);
        siteloaded_remove_advanced_cache_file();
    } else {
        siteloaded_ensure_config('WP_CACHE', TRUE);
        siteloaded_ensure_advanced_cache_file();
    }
    siteloaded_log('deactivated');
}
