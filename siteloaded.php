<?php
/*
Plugin Name: Site Loaded
Plugin URI: https://www.siteloaded.com/
Description: Site Loaded Official Plugin
Version: 0.0.2
Author: Site Loaded
Author URI: https://www.siteloaded.com/
Licence: GPLv2
Text Domain: siteloaded
Domain Path: languages

Copyright 2017 10152528 Canada Inc.
*/
defined('ABSPATH') or exit;

define('SITELOADED_SLUG',                       'siteloaded');
define('SITELOADED_VERSION',                    '0.0.2');
define('SITELOADED_DIR',                        trailingslashit(plugin_dir_path(__FILE__)));
define('SITELOADED_DEBUG',                      WP_CONTENT_DIR === '/vagrant/wordpress/wp-content');
define('SITELOADED_GITHUB_REPO',                'wordpress-plugin');

require_once(SITELOADED_DIR . 'vendor/milo/github-api/src/github-api.php');
require_once(SITELOADED_DIR . 'vendor/michelf/php-markdown/Michelf/Markdown.inc.php');
require_once(SITELOADED_DIR . 'includes/log.php');
require_once(SITELOADED_DIR . 'includes/update.php');
