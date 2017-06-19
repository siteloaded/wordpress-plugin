<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_transient('siteloaded_check_for_updates');
