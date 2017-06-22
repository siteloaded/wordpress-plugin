<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_site_transient('siteloaded_check_for_updates');
delete_site_option('siteloaded_version');
delete_site_option('siteloaded_just_activated');
