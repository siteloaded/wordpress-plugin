<?php
defined('ABSPATH') or exit;

add_action('clean_post_cache', '__siteloaded_clean_attachment_post_cache', PHP_INT_MAX, 1);
add_action('delete_attachment', '__siteloaded_clean_attachment_post_cache', PHP_INT_MAX, 1);
add_action('transition_post_status', '__siteloaded_purge_post_permalink', PHP_INT_MAX, 3);
add_filter('widget_update_callback'    , '__siteloaded_cache_purge_after_widget_update_callback');
add_action('comment_post', '__siteloaded_cache_purge_post_of_comment', PHP_INT_MAX, 2);
add_action('transition_comment_status', '__siteloaded_cache_purge_post_after_comment_status_changed', PHP_INT_MAX, 3);
foreach (array('switch_theme', 'user_register', 'deleted_user', 'wp_update_nav_menu', 'update_option_sidebars_widgets', 'update_option_category_base', 'update_option_tag_base', 'permalink_structure_changed', 'create_term', 'edited_terms', 'delete_term', 'add_link', 'edit_link', 'delete_link', 'customize_save') as $action) {
    add_action($action, 'siteloaded_cache_safe_purge_current_blog');
}

function __siteloaded_clean_attachment_post_cache($post_id) {
    if (get_post_type($post_id) !== 'attachment') {
        return;
    }

    $url = get_permalink($post_id);
    if ($url === FALSE) {
        return;
    }

    siteloaded_debug('purging cache for media id ' . $post_id);

    $blog_id = get_current_blog_id();
    $sha = sha1($url);
    siteloaded_cache_purge_filename($blog_id, $sha . '.html');
    siteloaded_cache_purge_filename($blog_id, $sha . '-404.html');
}

function __siteloaded_purge_post_permalink($new_status, $old_status, $post) {
    if ($post->post_type === 'custom_css') {
        if ($new_status === 'publish') {
            siteloaded_cache_safe_purge_current_blog();
        }
        return;
    }

    if ($post->post_type === 'attachment'
        || $post->post_type === 'revision'
        || $post->post_type === 'nav_menu_item'
        || $post->post_type === 'customize_changeset') {
        return;
    }

    if ($old_status !== 'publish' && $new_status !== 'publish') {
        return;
    }

    $to_purge = array();
    $blog_id = get_current_blog_id();

    $purge = function($url) use (&$to_purge) {
        if ($url === FALSE) {
            return;
        }
        siteloaded_debug('adding post page to purge (or related): ' . $url);
        $sha = sha1($url);
        $to_purge[] = $sha . '.html';
        $to_purge[] = $sha . '-404.html';
    };

    // current post
    $purge($post->post_status === 'publish' ? get_permalink($post) : siteloaded_get_safe_permalink($post));

    // author
    $purge(get_author_posts_url($post->post_author));

	// prev/next posts
    foreach (array(array(FALSE, FALSE), array(TRUE, FALSE), array(FALSE, TRUE), array(TRUE, TRUE)) as $flags) {
    	$adj_post = get_adjacent_post($flags[0], '', $flags[1]);
        if (is_a($adj_post, 'WP_Post')) {
            $purge(get_permalink($adj_post));
        }
    }

    // ancestors
    foreach(get_post_ancestors($post->ID) as $parent_id) {
        $purge(get_permalink($parent_id));
    }

    // home page
    $purge(get_site_url($blog_id, '/'));

    // posts page, see
    // https://wordpress.stackexchange.com/questions/32620/get-page-id-of-page-that-is-set-as-the-posts-page
    if ($post->post_type === 'post') {
        $page_for_posts = get_option('page_for_posts');
        if ($page_for_posts !== FALSE && (int)$page_for_posts > 0) {
            $purge(get_permalink($page_for_posts));
        }
    }

    // terms
	foreach(get_object_taxonomies($post, 'objects') as $taxonomy ) {
		$terms = get_the_terms($post, $taxonomy->name);
		if (is_array($terms)) {
            foreach ($terms as $term) {
                $url = get_term_link($term->slug, $taxonomy->name);
                if (!is_wp_error($url)) {
                    $purge($url);
                }
            }
        }
	}

    // archives (of current post type)
	if ($post->post_type !== 'post') {
        $purge(get_post_type_archive_link($post->post_type));
    }

    // archives /2017/, /2017/07/, /2017/07/09/ (only for posts or custom posts)
    if ($post->post_type !== 'page') {
        $post_date = explode('-', get_the_time('Y-m-d', $post));
        $purge(get_year_link($post_date[0]));
        $purge(get_month_link($post_date[0], $post_date[1]));
        $purge(get_day_link($post_date[0], $post_date[1], $post_date[2]));
    }

    // actual purge
    foreach (array_unique($to_purge) as $filename) {
       siteloaded_cache_purge_filename($blog_id, $filename);
    }
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

function __siteloaded_cache_purge_after_widget_update_callback($instance) {
    siteloaded_cache_safe_purge_current_blog();
    return $instance;
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

function siteloaded_get_safe_permalink($post) {
    list($permalink, $postname) = get_sample_permalink($post);
    $url = preg_replace('/\%[a-zA-Z_-]+\%/', $postname, $permalink);
    $url = str_replace('%postname%', $postname, str_replace('__trashed', '', $url));
    return $url;
}
