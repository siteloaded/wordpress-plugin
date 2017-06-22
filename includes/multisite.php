<?php
defined('ABSPATH') or exit;

function siteloaded_network_each_blog(callable $callback) {
    function plugin_is_active($blog_id) {
        $plugins = get_blog_option($blog_id, 'active_plugins');
        foreach($plugins as $p) {
            if ($p === SITELOADED_MAIN_PLUGIN_FILE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    if (!is_multisite()) {
        $blog_id = get_current_blog_id();
        $callback($blog_id, plugin_is_active($blog_id), TRUE);
        return;
    }

    $blog_id = is_network_admin() ? -1 : get_current_blog_id();
    foreach(siteloaded_network_get_blogs() as $domain) {
        foreach($domain as $b) {
            $callback($b['blog_id'], plugin_is_active($b['blog_id']), $b['blog_id'] === $blog_id);
        }
    }
}

function siteloaded_network_get_blogs($force = FALSE) {
    static $sites = NULL;

    if (!is_null($sites) && $force !== TRUE) {
        return $sites;
    }

    if (!is_multisite()) {
        $sites = array();
        return $sites;
    }

    if(function_exists('get_sites') && class_exists('WP_Site_Query')) {
        // WordPress >= 4.6
        $sites = array_map(function($s) { return $s->to_array(); }, get_sites(array('number' => PHP_INT_MAX)));
    } else {
        // WordPress < 4.6
        $sites = wp_get_sites(array('limit' => PHP_INT_MAX));
    }

    $sites = array_filter($sites, function($s) {
        return intval($s['deleted']) === 0;
    });

    $sites = array_map(function($s) {
        foreach (array('registered', 'last_updated', 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id') as $p) {
            unset($s[$p]);
        }
        $s['blog_id'] = intval($s['blog_id']);
        $s['site_id'] = intval($s['site_id']);
        return $s;
    }, $sites);

    $sites_dict = array();
    foreach ($sites as $s) {
        $domain = $s['domain'];
        unset($s['domain']);
        $sites_dict[$domain][] = $s;
    }

    $sites = $sites_dict;
    return $sites;
}
