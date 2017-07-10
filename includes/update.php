<?php
defined('ABSPATH') or exit;

add_filter('pre_set_site_transient_update_plugins', 'siteloaded_check_for_updates');
add_filter('plugins_api', 'siteloaded_fill_version_details_popup', 10, 3);

function siteloaded_check_for_updates($transient) {
    $rel = siteloaded_fetch_latest_release_infos();
    if ($rel === FALSE) {
        return $transient;
    }

    $current = get_plugin_data(SITELOADED_DIR . SITELOADED_SLUG . '.php')['Version'];
    $ver = ltrim($rel->tag_name, 'v');

    if (version_compare($current, $ver, '>=')) {
        siteloaded_debug('already running version ' . $ver . ' or later (' . $current . ')');
        return $transient;
    }

    $update = new stdClass();
    $update->slug = SITELOADED_SLUG;
    $update->plugin = SITELOADED_MAIN_PLUGIN_FILE;
    $update->new_version = $ver;
    $update->url = $rel->html_url;
    $update->package = $rel->assets[0]->browser_download_url;
    $update->upgrade_notice = $rel->name;

    siteloaded_log('update ' . $ver . ' found');
    $transient->response[SITELOADED_MAIN_PLUGIN_FILE] = $update;
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
    $html_desc = SiteLoaded\Vendor\Michelf\Markdown::defaultTransform($rel->body);

    $plugin = array(
        'name'              => $data['Name'],
        'slug'              => SITELOADED_MAIN_PLUGIN_FILE,
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

function siteloaded_fetch_latest_release_infos() {
    $rel = get_site_transient('siteloaded-latest-release-remote-infos');
    if ($rel !== FALSE) {
        return $rel;
    }

    siteloaded_log('fetching latest release infos');

    $github = new SiteLoaded\Vendor\Milo\Github\Api;
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
    set_site_transient('siteloaded-latest-release-remote-infos', $rel, 2 * HOUR_IN_SECONDS);
    return $rel;
}
