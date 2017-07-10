<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_site_transient('siteloaded-latest-release-remote-infos');
delete_site_option('siteloaded_version');
delete_site_option('siteloaded_just_activated');
