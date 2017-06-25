<?php
defined('ABSPATH') or exit;

add_action('comment_post', 'siteloaded_comment_posted', PHP_INT_MAX, 2);
add_action('transition_comment_status', 'siteloaded_comment_status_changed', PHP_INT_MAX, 3);

function siteloaded_cache_safe_purge($blog_id) {
    $base = siteloaded_cache_dir($blog_id);

    siteloaded_debug('safetly purging cache for blog ' . $blog_id . ' at ' . $base);

    $more_recent = PHP_INT_MAX;

    foreach (glob("$base*.html", GLOB_NOSORT) as $filename) {
        $f = new siteloaded_file_access();
        $fp = $f->open_excl($filename, 'rb');

        if ($fp === FALSE) {
            siteloaded_log('could not open file for purge: ' . $filename);
            continue;
        }

        $stat = fstat($fp);
        $mtime = $stat['mtime'];
        if ($mtime < $more_recent) {
            $more_recent = $mtime;
        }

        if (SITELOADED_PLATFORM_WINDOWS) {
            $f->close();
            if (!@unlink($filename)) {
                siteloaded_log('could not delete file for purge: ' . $filename);
            }
            continue;
        }

        if (!@unlink($filename)) {
            siteloaded_log('could not delete file for purge: ' . $filename);
        }

        $f->close();
    }

    if ($more_recent !== PHP_INT_MAX) {
        foreach (glob("$base*.{css,js}", GLOB_NOSORT | GLOB_BRACE) as $filename) {
            $f = new siteloaded_file_access();
            $fp = $f->open_excl($filename, 'rb');

            if ($fp === FALSE) {
                siteloaded_log('could not open file for purge: ' . $filename);
                continue;
            }

            $stat = fstat($fp);
            $mtime = $stat['mtime'];
            if ($mtime < $more_recent) {
                // don't delete static resources that have been created
                // after we started the purge...

                if (SITELOADED_PLATFORM_WINDOWS) {
                    $f->close();
                    if (!@unlink($filename)) {
                        siteloaded_log('could not delete file for purge: ' . $filename);
                    }
                    continue;
                }

                if (!@unlink($filename)) {
                    siteloaded_log('could not delete file for purge: ' . $filename);
                }
            }

            $f->close();
        }
    }
}

function siteloaded_cache_destroy($blog_id) {
    $base = siteloaded_cache_dir($blog_id);

    siteloaded_debug('destroying cache for blog ' . $blog_id . ' at ' . $base);

    if (!is_dir($base)) {
        return;
    }

    foreach (glob("$base*.{css,js,html}", GLOB_NOSORT | GLOB_BRACE) as $filename) {
        $f = new siteloaded_file_access();
        $fp = $f->open_excl($filename, 'rb');

        if ($fp === FALSE) {
            siteloaded_log('could not open file for purge: ' . $filename);
            continue;
        }

        if (SITELOADED_PLATFORM_WINDOWS) {
            $f->close();
            if (!@unlink($filename)) {
                siteloaded_log('could not delete file for purge: ' . $filename);
            }
            continue;
        }

        if (!@unlink($filename)) {
            siteloaded_log('could not delete file for purge: ' . $filename);
        }

        $f->close();
    }

    @rmdir($base);
}

function siteloaded_cache_purge_post($blog_id, $post_id) {
    $url = get_permalink($post_id);
    if ($url === FALSE) {
        return;
    }

    siteloaded_debug('purging page due to comment: ' . $url);

    $filename = siteloaded_cache_dir($blog_id) . sha1($url) . '.html';
    $f = new siteloaded_file_access();
    $fp = $f->open_excl($filename, 'rb');

    if ($fp === FALSE) {
        // might not be in cache, ignore error
        return;
    }

    if (SITELOADED_PLATFORM_WINDOWS) {
        $f->close();
        if (!@unlink($filename)) {
            siteloaded_log('could not delete file for purge: ' . $filename);
        }
        return;
    }

    if (!@unlink($filename)) {
        siteloaded_log('could not delete file for purge: ' . $filename);
    }

    $f->close();
}

function siteloaded_cache_warmup($blog_id) {
    $max_visits = 5000;                 // safety net, stop after 5k visits
    $inbetween_sleep = 500000;          // 500ms between pages
    $cookie = (string)mt_rand();        // this warmer's cookie
    $tmp_path = siteloaded_temp_dir();
    $cookie_path = $tmp_path . ".siteloaded-warmer.$blog_id.lock";
    if (@file_put_contents($cookie_path, $cookie, LOCK_EX) === FALSE) {
        $cookie_path = FALSE;
    }

    $root = SiteLoaded\Vendor\phpUri::parse(get_home_url($blog_id));
    if ($root->path === '') {
        $root->path = '/';
    }

    function page_links($url, $root) {
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
        foreach (page_links($url, $root) as $found) {
            array_push($queue, $found);
        }
        if ($loop_count++ >= $max_visits) {
            siteloaded_debug('cache warmer maximum page visit count reached, exiting...');
            break; // safety net
        }
        usleep($inbetween_sleep);
    }
}

function siteloaded_cache_dir($blog_id) {
    return SITELOADED_CACHE_DIR . $blog_id . '/';
}

function siteloaded_ensure_htaccess_file($force = FALSE) {
    $path =  SITELOADED_CACHE_DIR . '.htaccess';

    if (!is_file($path) || $force) {
        $htaccess = <<<EOT
<IfModule headers_module>
Header unset Pragma
FileETag None
Header unset ETag
</IfModule>

<IfModule mod_expires.c>
ExpiresActive On
<FilesMatch "\.(js|css)$">
ExpiresDefault "access plus 1 year"
</FilesMatch>
</IfModule>

<IfModule mod_deflate.c>
<FilesMatch "\.(js|css)$">
SetOutputFilter DEFLATE
</FilesMatch>
</IfModule>
EOT;

        if (!is_dir(SITELOADED_CACHE_DIR)) {
            $recursive = TRUE;
            mkdir(SITELOADED_CACHE_DIR, 0755, $recursive);
        }

        siteloaded_debug('writing .htaccess file');
        if (file_put_contents($path, $htaccess, LOCK_EX) === FALSE) {
            siteloaded_log('could not write asset ' . $path);
        }
    }
}

function siteloaded_cache_ensure_valid($blog_id) {
    $base = siteloaded_cache_dir($blog_id);

    if (!is_dir($base)) {
        $recursive = TRUE;
        @mkdir($base, 0755, $recursive);
    }

    siteloaded_ensure_htaccess_file();
}

function siteloaded_comment_posted($id, $approved) {
    if ($approved !== 1) {
        return;
    }
    $comment = get_comment($id);
    if ($comment === null) {
        return;
    }
    siteloaded_cache_purge_post(get_current_blog_id(), $comment->comment_post_ID);
}

function siteloaded_comment_status_changed($new_status, $old_status, $comment) {
    if ($old_status === $new_status || ($new_status !== 'approved' && $old_status !== 'approved')) {
        return;
    }

    siteloaded_cache_purge_post(get_current_blog_id(), $comment->comment_post_ID);
}
