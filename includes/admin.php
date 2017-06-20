<?php
defined('ABSPATH') or exit;

add_action('admin_bar_menu', 'siteloaded_admin_bar_menu', 100, 1);
add_action('admin_enqueue_scripts', 'siteloaded_admin_bar_enqueue_scripts');
add_action('wp_enqueue_scripts', 'siteloaded_admin_bar_enqueue_scripts');
add_action('wp_ajax_siteloaded_admin_bar_purge_all', 'siteloaded_admin_bar_purge_all');
// TODO: disabled during beta
// add_action('admin_menu', 'siteloaded_admin_menu', PHP_INT_MAX);
// add_action('admin_notices', 'siteloaded_admin_notices_no_subscription');
// add_action('admin_enqueue_scripts', 'siteloaded_admin_panel_enqueue_scripts');
// add_action('wp_ajax_siteloaded_set_subscription', 'siteloaded_set_subscription');

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
        'succeededMessage' => __('Emptying cache...', 'siteloaded'),
        'failedMessage' => __('An error occured, please try again later', 'siteloaded'),
    ));
}

function siteloaded_admin_bar_purge_all() {
    if (!current_user_can('manage_options')) {
        header( "Content-Type: application/json" );
        echo '{"status":"permission denied"}';
        wp_die();
    }

    register_shutdown_function(function($blog_id) {
        siteloaded_debug('warming up cache for blog ' . $blog_id);
        // TODO, purge here...
        siteloaded_warmup_cache($blog_id);
        siteloaded_debug('cache warming done');
    }, get_current_blog_id());

    siteloaded_close_client_connection('application/json', '{"status":"ok"}');
    wp_die();
}

function siteloaded_close_client_connection($content_type, $response) {
    ignore_user_abort(TRUE);
    ob_end_clean();
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . mb_strlen($response, '8bit'));
    header('Content-Encoding: identity');
    header('Connection: close');
    echo $response;
    flush();
    session_write_close();
}

// function siteloaded_admin_menu() {
//     if (is_network_admin()) {
//         return;
//     }

//     $logo = 'data:image/svg+xml;base64,' . base64_encode(siteloaded_get_svg_logo('#82878c'));

//     add_menu_page(
//         SITELOADED_NAME,            // page name
//         SITELOADED_NAME,            // button label
//         'manage_options',           // capability required
//         SITELOADED_SLUG,            // menu slug
//         'siteloaded_cp_content',    // page content
//         $logo,                      // logo
//         null                        // position
//     );
// }

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

// function siteloaded_admin_panel_enqueue_scripts($hook) {
//     if ($hook !== 'toplevel_page_' . SITELOADED_SLUG) {
//         return;
//     }

//     wp_enqueue_script('siteloaded_admin_panel_script', SITELOADED_URL . 'admin/js/admin-panel.js', array('jquery'));
//     wp_enqueue_style('siteloaded_admin_panel_style', SITELOADED_URL . 'admin/css/admin-panel.css');

//     wp_localize_script('siteloaded_admin_panel_script', 'siteloaded_admin_panel_script', array(
//         'validReferrer' => rtrim(SITELOADED_CONTROLPANEL_URL, "/"),
//         'setSubscriptionAction' => 'siteloaded_set_subscription'
//     ));
// }

// function siteloaded_set_subscription() {
//     if (!current_user_can('administrator')) {
//         echo 401;
//         wp_die();
//     }

//     if (!isset($_POST['subscription_id'])) {
//         echo 422;
//         wp_die();
//     }

//     $id = $_POST['subscription_id'];

//     if (get_option('siteloaded_subscription_id') === $id || update_option('siteloaded_subscription_id', $id, true) === true) {
//         echo 200;
//         wp_die();
//     }

//     echo 500;
//     wp_die();
// }

// function siteloaded_admin_notices_no_subscription() {
//     if (get_option('siteloaded_subscription_id') !== false) {
//         return;
//     }

// 	$class = 'notice notice-info';
// 	$message = __('Thank you for using Site Loaded. You must now create an account or login to an existing one to link your subscription to your blog. Click <a href="' . admin_url('admin.php?page=' . SITELOADED_SLUG) . '">here</a> to do so.', 'siteloaded');

// 	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
// }

function siteloaded_get_svg_logo($fill = '#fff') {
    $logo = <<<EOD
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 371.4 325.8" style="enable-background:new 0 0 371.4 325.8;" xml:space="preserve">
    <style type="text/css">
            .siteloaded-svg-logo-fill{fill:${fill};}
    </style>
    <title>logo-white</title>
    <path class="siteloaded-svg-logo-fill" d="M248.8,119.4l-90.4,82.7l-41.2-39.8l-61.1,56.1l0,0l0,0L21,250.8c44,73.1,138.9,96.8,212.1,52.8 c54.7-32.9,83.5-96,72.6-158.8l-46,41.9v-0.1c-8.7,58.9-63.6,99.7-122.5,90.9c-19.1-2.8-37.1-10.8-52.2-22.9l31.5-29l41,39.7 l99.8-91.3l45.7-41.9l51.5-46.4l16.9,15.8V6.8L248.8,119.4z"/>
    <path class="siteloaded-svg-logo-fill" d="M262.5,0l17.3,14.9l-66,59.5l-53.1,48.4l-41.2-40l-70.7,64.7c12.9-58.2,70.6-94.9,128.9-82 c7.5,1.7,14.9,4.2,21.9,7.4l0,0l36.3-33.1C163.1-5,67.9,17.5,23,90.1C8,114.5,0,142.6,0,171.2c0,29.1,16.5,68.5,16.5,68.5l35.8-33 l0,0l0,0l66.2-61l41.4,40l84.9-77.6l33.7-29.4L365.8,0H262.5z"/>
</svg>
EOD;

    return $logo;
}

function siteloaded_warmup_cache($blog_id) {
    $max_visits = 5000;                 // safety net, stop after 5k visits
    $inbetween_sleep = 500000;          // 500ms between pages
    $cookie = (string)mt_rand();        // this warmer's cookie
    $tmp_path = siteloaded_temp_dir();
    $cookie_path = $tmp_path . '.siteloaded-warmer.lock';
    if (@file_put_contents($cookie_path, $cookie, LOCK_EX) === FALSE) {
        $cookie_path = FALSE;
    }

    $root = SiteLoaded\Vendor\phpUri::parse(get_home_url($blog_id));
    if ($root->path === '') {
        $root->path = '/';
    }

    $visited = array();
    $queue = array($root);
    $loop_count = 0;
    while (count($queue) > 0) {
        set_time_limit(30);

        if ($cookie_path !== FALSE) {
            $current_cookie = siteloaded_shared_read($cookie_path);
            if ($current_cookie !== FALSE && $cookie !== $current_cookie) {
                // yielding since another cache warmer was started
                siteloaded_debug('cache warmer yielding to a newer one...');
                break;
            }
        }

        $url = array_shift($queue);
        $cache_key = trailingslashit($url->to_str());
        if (array_key_exists($cache_key, $visited)) {
            continue;
        }
        $visited[$cache_key] = TRUE;

        siteloaded_debug('warming up ' . $url->to_str());
        foreach (siteloaded_get_page_links($url, $root) as $found) {
            array_push($queue, $found);
        }
        if ($loop_count++ >= $max_visits) {
            siteloaded_debug('cache warmer maximum page visit count reached, exiting...');
            break; // safety net
        }
        usleep($inbetween_sleep);
    }
}

function siteloaded_temp_dir() {
    if (function_exists('sys_get_temp_dir')) {
        $temp = sys_get_temp_dir();
        if (@is_dir($temp) && @is_writable($temp)) {
            return trailingslashit($temp);
        }
    }

    $temp = ini_get('upload_tmp_dir');
    if (@is_dir($temp) && @is_writable($temp)) {
        return trailingslashit($temp);
    }

    return SITELOADED_CACHE_DIR;
}

function siteloaded_get_page_links($url, $root) {
    $found = array();

    $body = @file_get_contents($url->to_str());
    if($body === FALSE) {
        return $found;
    }

    $dom = (new SiteLoaded\Vendor\Masterminds\HTML5())->loadHTML($body);
    if (!$dom) {
        return $found;
    }

    foreach ($dom->getElementsByTagName('a') as $node) {
        $resolved = SiteLoaded\Vendor\phpUri::parse($url->join($node->getAttribute('href')));
        if ($resolved->path === '') {
            $resolved->path = '/';
        }
        if ($resolved->scheme === $url->scheme
                && $resolved->authority === $url->authority
                && strpos($resolved->path, 'wp-login.php') === false
                && strpos($resolved->path, '/wp-admin/') === false
                && strpos($resolved->path, '.') === false
                && strpos($resolved->path, $root->path) === 0) {
            $resolved->fragment = '';
            $resolved->query = '';
            array_push($found, $resolved);
        }
    }

    return $found;
}
