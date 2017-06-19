<?php
defined('ABSPATH') or exit;

use SiteLoaded\Vendor\Milo\Github;
use SiteLoaded\Vendor\Michelf\Markdown;

add_filter('pre_set_site_transient_update_plugins', 'siteloaded_check_for_updates');
add_filter('plugins_api', 'siteloaded_fill_version_details_popup', 10, 3);

function siteloaded_check_for_updates($transient) {
    if (siteloaded_ratelimit_cando('siteloaded_check_for_updates', 2 * HOUR_IN_SECONDS) === false) {
        return $transient;
    };

    siteloaded_log('checking for updates');
    $rel = siteloaded_fetch_latest_release_infos();
    if ($rel === FALSE) {
        return $transient;
    }

    $ver = ltrim($rel->tag_name, 'v');
    if (version_compare(SITELOADED_VERSION, $ver, '>=')) {
        siteloaded_debug('already running version ' . $ver . ' or later (' . SITELOADED_VERSION . ')');
        return $transient;
    }

    $update = new stdClass();

    $update->slug = SITELOADED_SLUG . '.php';
    $update->new_version = $ver;
    $update->url = $rel->html_url;
    $update->package = $rel->assets[0]->browser_download_url;
    $update->upgrade_notice = $rel->name;

    siteloaded_log('update ' . $ver . ' found');
    $transient->response[SITELOADED_SLUG . '/' . SITELOADED_SLUG . '.php'] = $update;
    return $transient;
}

function siteloaded_fill_version_details_popup($result, $action, $args) {
    if (empty($args->slug) || $args->slug !== SITELOADED_SLUG . '.php') {
        return $result;
    }

    $rel = siteloaded_fetch_latest_release_infos();
    if ($rel === FALSE) {
        return $result;
    }

    $data = get_plugin_data(SITELOADED_DIR . SITELOADED_SLUG . '.php');
    $html_desc = Markdown::defaultTransform($rel->body);

    $plugin = array(
        'name'              => $data['Name'],
        'slug'              => SITELOADED_SLUG . '/' . SITELOADED_SLUG . '.php',
        'version'           => ltrim($rel->tag_name, 'v'),
        'author'            => $data['Author'],
        'author_profile'    => $data['AuthorURI'],
        'last_updated'      => $rel->created_at,
        'homepage'          => $data['PluginURI'],
        'short_description' => $data['Description'],
        'sections'          => array(
            __('Updates', 'siteloaded')     => $html_desc,
            __('Description', 'siteloaded') => $data['Description'],
        ),
        'download_link'     => $rel->assets[0]->browser_download_url
    );

    return (object)$plugin;
}

function siteloaded_ratelimit_cando($key, $interval) {
    $now = time();
    $last = get_transient($key);
    if ($last === FALSE) {
        $last = 0;
    }

    $elapsed = $now - (int)$last;
    if ($interval > $elapsed) {
        return FALSE;
    }

    set_transient($key, $now, $interval + 15);
    return TRUE;
}

function siteloaded_fetch_latest_release_infos() {
    static $rel = FALSE;

    if ($rel !== FALSE) {
        return $rel;
    }

    $github = new Github\Api;
    $response = $github->get("/repos/:org/:repo/releases/latest", array(
        'org' => SITELOADED_SLUG,
        'repo' => SITELOADED_GITHUB_REPO,
    ));

    try {
        $decoded = $github->decode($response);
    } catch (SiteLoaded\Vendor\Milo\Github\IException $e) {
        siteloaded_log('error fetching latest release: ' . get_class($e));
        return FALSE;
    }

    if (count($decoded->assets) !== 1) {
        siteloaded_log('invalid release, there must be exactly one attached zip file');
        return FALSE;
    }

    $rel = $decoded;
    return $rel;
}
