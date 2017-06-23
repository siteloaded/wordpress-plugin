<?php
/*
Plugin Name: Site Loaded
Plugin URI: https://www.siteloaded.com/
Description: Site Loaded Official Plugin
Version: 0.1.0
Author: Site Loaded
Author URI: https://www.siteloaded.com/
Licence: GPLv2
Text Domain: siteloaded
Domain Path: languages

Copyright 2017 10152528 Canada Inc.
*/
defined('ABSPATH') or exit;

define('SITELOADED_NAME',                       'Site Loaded');
define('SITELOADED_SLUG',                       'siteloaded');
define('SITELOADED_DIR',                        trailingslashit(plugin_dir_path(__FILE__)));
define('SITELOADED_CONTENT_DIR',                trailingslashit(WP_CONTENT_DIR));
define('SITELOADED_CACHE_DIR',                  trailingslashit(SITELOADED_CONTENT_DIR . 'cache/' . SITELOADED_SLUG));
define('SITELOADED_CACHE_DIR_URL',              trailingslashit(content_url() . '/cache/' . SITELOADED_SLUG));
define('SITELOADED_URL',                        trailingslashit(plugins_url(SITELOADED_SLUG)));
define('SITELOADED_ENV',                        WP_CONTENT_DIR === '/vagrant/wordpress/wp-content' ? 'DEV' : 'PROD');
define('SITELOADED_GITHUB_REPO',                'wordpress-plugin');
define('SITELOADED_CONTROLPANEL_URL',           SITELOADED_ENV === 'DEV' ? 'http://cp.siteloaded.dev/' : 'https://cp.siteloaded.com/');
define('SITELOADED_MAIN_PLUGIN_FILE',           plugin_basename(__FILE__));
define('SITELOADED_CACHE_HOOK',                 SITELOADED_DIR . 'includes/advanced-cache-hook.php');
define('SITELOADED_ADV_CACHE_FILE',             SITELOADED_CONTENT_DIR . 'advanced-cache.php');
define('SITELOADED_WP_DIR',                     trailingslashit(ABSPATH));
define('SITELOADED_OPTIMIZE_URL',               SITELOADED_ENV === 'DEV' ? 'http://optimizer.siteloaded.dev/optimize' : 'https://optimizer.siteloaded.com/optimize');

require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Elements.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Entities.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Exception.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/InstructionProcessor.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/CharacterReference.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/EventHandler.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/DOMTreeBuilder.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/InputStream.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/StringInputStream.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/FileInputStream.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/ParseError.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/Scanner.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/Tokenizer.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/TreeBuildingRules.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Parser/UTF8Utils.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Serializer/HTML5Entities.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Serializer/RulesInterface.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Serializer/OutputRules.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5/Serializer/Traverser.php');
require_once(SITELOADED_DIR . 'vendor/masterminds/html5/src/HTML5.php');
require_once(SITELOADED_DIR . 'vendor/pguardiario/phpuri/phpuri.php');
require_once(SITELOADED_DIR . 'vendor/milo/github-api/src/github-api.php');
require_once(SITELOADED_DIR . 'vendor/michelf/php-markdown/Michelf/Markdown.inc.php');

require_once(SITELOADED_DIR . 'includes/log.php');
require_once(SITELOADED_DIR . 'includes/http.php');
require_once(SITELOADED_DIR . 'includes/update.php');
require_once(SITELOADED_DIR . 'includes/fs.php');
require_once(SITELOADED_DIR . 'includes/cache.php');
require_once(SITELOADED_DIR . 'includes/admin.php');
require_once(SITELOADED_DIR . 'includes/activation.php');
require_once(SITELOADED_DIR . 'includes/multisite.php');
require_once(SITELOADED_DIR . 'includes/optimize.php');
