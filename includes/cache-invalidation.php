<?php
defined('ABSPATH') or exit;

add_action('comment_post', '__siteloaded_cache_purge_post_of_comment', PHP_INT_MAX, 2);
add_action('transition_comment_status', '__siteloaded_cache_purge_post_after_comment_status_changed', PHP_INT_MAX, 3);
add_filter('widget_update_callback'	, '__siteloaded_cache_purge_after_widget_update_callback');
add_action('wp_trash_post', '__siteloaded_cache_purge_post', PHP_INT_MAX, 1);
add_action('delete_post', '__siteloaded_cache_purge_post', PHP_INT_MAX, 1);
add_action('clean_post_cache', '__siteloaded_cache_purge_post', PHP_INT_MAX, 1);
foreach (array('switch_theme', 'user_register', 'deleted_user', 'wp_update_nav_menu', 'update_option_sidebars_widgets', 'update_option_category_base', 'update_option_tag_base', 'permalink_structure_changed', 'create_term', 'edited_terms', 'delete_term', 'add_link', 'edit_link', 'delete_link', 'customize_save') as $action) {
    add_action($action, '__siteloaded_cache_safe_purge_current_blog');
}

function __siteloaded_cache_purge_after_widget_update_callback($instance) {
	__siteloaded_cache_safe_purge_current_blog();
	return $instance;
}

function __siteloaded_cache_purge_post_of_comment($comment_id, $approved) {
    if ($approved !== 1) {
        return;
    }

    $comment = get_comment($comment_id);
    if ($comment === null) {
        return;
    }

    $url = get_permalink($comment->comment_post_ID);
    if ($url === FALSE) {
        return;
    }

    siteloaded_cache_purge_filename(get_current_blog_id(), sha1($url) . '.html');
}

function __siteloaded_cache_purge_post_after_comment_status_changed($new_status, $old_status, $comment) {
    if ($old_status === $new_status || ($new_status !== 'approved' && $old_status !== 'approved')) {
        return;
    }

    $url = get_permalink($comment->comment_post_ID);
    if ($url === FALSE) {
        return;
    }

    siteloaded_cache_purge_filename(get_current_blog_id(), sha1($url) . '.html');
}

function __siteloaded_cache_purge_post($post_id) {
    if (is_preview() || defined('DOING_AUTOSAVE')) {
        return;
    }

	$post = get_post($post_id);
	if (!is_a($post, 'WP_Post') || $post->post_type === 'revision') {
    	return;
    }

    $url = get_permalink($post);
    if ($url === FALSE) {
        return;
    }

    siteloaded_debug('post purge detected for: ' . $url);

    $to_purge = array();
    $blog_id = get_current_blog_id();
    $to_purge[] = sha1($url) . '.html';

	if ($post->post_type === 'post') {
        $posts_page_id = (int)get_option('page_for_posts');
        if ($posts_page_id > 0) {
            $url = get_permalink($posts_page_id);
            if ($url !== FALSE) {
                siteloaded_debug('adding related post page to purge: ' . $url);
                $to_purge[] = sha1($url) . '.html';
            }
        }
	}

    $adj_post = get_adjacent_post(FALSE, '', FALSE);
	if (is_a($adj_post, 'WP_Post')) {
        $url = get_permalink($adj_post);
        if ($url !== FALSE) {
            siteloaded_debug('adding related post page to purge: ' . $url);
    		$to_purge[] = sha1($url) . '.html';
        }
	}

    $adj_post = get_adjacent_post(TRUE, '', FALSE);
	if (is_a($adj_post, 'WP_Post')) {
        $url = get_permalink($adj_post);
        if ($url !== FALSE) {
            siteloaded_debug('adding related post page to purge: ' . $url);
    		$to_purge[] = sha1($url) . '.html';
        }
	}

	$url = get_author_posts_url($post->post_author);
    siteloaded_debug('adding related post page to purge: ' . $url);
    $to_purge[] = sha1($url) . '.html';

    foreach(get_post_ancestors($post_id) as $parent_id) {
    	$url = get_permalink($parent_id);
        if ($url !== FALSE) {
            siteloaded_debug('adding related post page to purge: ' . $url);
            $to_purge[] = sha1($url) . '.html';
        }
    }

    $url = get_site_url($blog_id, '/');
    siteloaded_debug('adding related post page to purge: ' . $url);
    $to_purge[] = sha1($url) . '.html';

    foreach (array_unique($to_purge) as $filename) {
       siteloaded_cache_purge_filename($blog_id, $filename);
    }
}

function __siteloaded_cache_safe_purge_current_blog() {
    siteloaded_cache_safe_purge(get_current_blog_id());
}
