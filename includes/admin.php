<?php
defined('ABSPATH') or exit;

add_action('admin_bar_menu', 'siteloaded_admin_bar_menu', 100, 1);
add_action('admin_enqueue_scripts', 'siteloaded_admin_bar_enqueue_scripts');
add_action('wp_enqueue_scripts', 'siteloaded_admin_bar_enqueue_scripts');
add_action('wp_ajax_siteloaded_admin_bar_purge_all', 'siteloaded_admin_bar_purge_all');
add_action('admin_init', 'siteloaded_ensure_valid_config');
// TODO: disabled during beta
// add_action('admin_menu', 'siteloaded_admin_menu', PHP_INT_MAX);
// add_action('admin_notices', 'siteloaded_admin_notices_no_subscription');
// add_action('admin_enqueue_scripts', 'siteloaded_admin_panel_enqueue_scripts');
// add_action('wp_ajax_siteloaded_admin_set_subscription', 'siteloaded_admin_set_subscription');

function siteloaded_admin_bar_menu($wp_admin_bar) {
    if (is_network_admin() || !current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_node(array(
        'id'    => SITELOADED_SLUG,
        'title' => '<span class="ab-icon">' . siteloaded_get_svg_logo('rgba(240,245,250,.6)') . '</span><span class="ab-label">' . htmlentities(SITELOADED_NAME) . '</span>'
        // TODO, disabled during beta
        // 'href'  => admin_url('admin.php?page=' . SITELOADED_SLUG)
    ));

    $wp_admin_bar->add_node(array(
        'parent' => SITELOADED_SLUG,
		'id' 	 => 'siteloaded-purge-all',
        'title'  => __('Empty Cache', 'siteloaded'),
        'href' 	 => '#',
        'meta'   => array('class' => 'siteloaded-admin-bar-purge-all')
    ));
}

function siteloaded_admin_bar_enqueue_scripts($hook) {
    if (!is_admin_bar_showing() || is_network_admin() || !current_user_can('manage_options')) {
        return;
    }

    wp_enqueue_script('siteloaded_vendor_featherlight_script', SITELOADED_URL . 'admin/js/featherlight.min.js', array('jquery'));
    wp_enqueue_style('siteloaded_vendor_featherlight_style', SITELOADED_URL . 'admin/css/featherlight.min.css');

    wp_enqueue_script('siteloaded_admin_bar_script', SITELOADED_URL . 'admin/js/admin-bar.js', array('jquery', 'siteloaded_vendor_featherlight_script'));
    wp_enqueue_style('siteloaded_admin_bar_style', SITELOADED_URL . 'admin/css/admin-bar.css', array('siteloaded_vendor_featherlight_style'));

    wp_localize_script('siteloaded_admin_bar_script', 'siteloaded_admin_bar_script', array(
        'purgeAllAction' => 'siteloaded_admin_bar_purge_all',
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'emptyingMessage' => __('Emptying cache...', 'siteloaded'),
        'failedMessage' => __('An error occured, please try again later', 'siteloaded'),
    ));
}

function siteloaded_admin_bar_purge_all() {
    $blog_id = get_current_blog_id();

    if (!current_user_can('manage_options')) {
        header( "Content-Type: application/json" );
        echo '{"code":401}';
        wp_die();
    }

    siteloaded_close_http_client_connection('application/json', '{"code":200}');
    siteloaded_cache_safe_purge($blog_id);
    siteloaded_cache_warmup($blog_id);
}

function siteloaded_admin_menu() {
    if (is_network_admin() || !current_user_can('manage_options')) {
        return;
    }

    $logo = 'data:image/svg+xml;base64,' . base64_encode(siteloaded_get_svg_logo('#82878c'));

    add_menu_page(
        SITELOADED_NAME,            // page name
        SITELOADED_NAME,            // button label
        'manage_options',           // capability required
        SITELOADED_SLUG,            // menu slug
        'siteloaded_cp_content',    // page content
        $logo,                      // logo
        null                        // position
    );
}

function siteloaded_cp_content() {
    $subscription = get_option('siteloaded_subscription_id');
    if ($subscription === false) {
        $subscription = "";
    }
    ?>
    <div class="wrap">
        <iframe class="siteloaded-control-panel" src="<?= SITELOADED_CONTROLPANEL_URL ?>?platform=wordpress&referrer=<?= urlencode(get_site_url()) ?>&locale=<?= urlencode(get_locale()) ?>&subscription=<?= urlencode($subscription) ?>"></iframe>
    </div>
    <?php
}

function siteloaded_admin_panel_enqueue_scripts($hook) {
    if (is_network_admin() || !current_user_can('manage_options')) {
        return;
    }

    if ($hook !== 'toplevel_page_' . SITELOADED_SLUG) {
        return;
    }

    wp_enqueue_script('siteloaded_admin_panel_script', SITELOADED_URL . 'admin/js/admin-panel.js', array('jquery'));
    wp_enqueue_style('siteloaded_admin_panel_style', SITELOADED_URL . 'admin/css/admin-panel.css');

    wp_localize_script('siteloaded_admin_panel_script', 'siteloaded_admin_panel_script', array(
        'validReferrer' => rtrim(SITELOADED_CONTROLPANEL_URL, "/"),
        'setSubscriptionAction' => 'siteloaded_admin_set_subscription'
    ));
}

function siteloaded_admin_set_subscription() {
    // TODO, respond with content-type json
    // and a proper response like in function `siteloaded_admin_bar_purge_all`

    // TODO, test multisite, what happens

    if (!current_user_can('manage_options')) {
        echo 401;
        wp_die();
    }

    if (!isset($_POST['subscription_id'])) {
        echo 422;
        wp_die();
    }

    $id = $_POST['subscription_id'];

    if (get_option('siteloaded_subscription_id') === $id || update_option('siteloaded_subscription_id', $id, true) === true) {
        echo 200;
        wp_die();
    }

    echo 500;
    wp_die();
}

function siteloaded_admin_notices_no_subscription() {
    global $pagenow;

    if (is_network_admin() || !current_user_can('manage_options')) {
        return;
    }

    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === SITELOADED_SLUG) {
        return;
    }

    if (get_option('siteloaded_subscription_id') !== false) {
        return;
    }

	$class = 'notice notice-info';
	$message = __('Thank you for using Site Loaded. You must now create an account or login to an existing one to link your subscription to your blog. Click <a href="' . admin_url('admin.php?page=' . SITELOADED_SLUG) . '">here</a> to do so.', 'siteloaded');

	printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
}

function siteloaded_ensure_valid_config() {
    if (defined('DOING_AJAX') || defined('DOING_AUTOSAVE')) {
        return;
    }

    $current = get_plugin_data(SITELOADED_DIR . SITELOADED_SLUG . '.php')['Version'];

    if (get_site_option('siteloaded_just_activated') !== FALSE) {
        update_site_option('siteloaded_version', $current);
        delete_site_option('siteloaded_just_activated');
        return;
    }

    if (!defined('SITELOADED_ADVC_HOOK_INSTALLED')) {
        siteloaded_debug('invalid advanced-cache.php file detected');
        siteloaded_ensure_advanced_cache_file();
    }

    if (!defined('WP_CACHE') || WP_CACHE !== TRUE) {
        siteloaded_debug('WP_CACHE not set');
        siteloaded_ensure_config('WP_CACHE', TRUE);
    }

    if (get_site_option('siteloaded_version') !== $current) {
        update_site_option('siteloaded_version', $current);
        siteloaded_debug('plugin update detected');
        siteloaded_ensure_advanced_cache_file();
        siteloaded_ensure_htaccess_file(TRUE);
    }
}
