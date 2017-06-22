<?php
defined('ABSPATH') or exit;

function siteloaded_cache_dir($blog_id) {
    return SITELOADED_CACHE_DIR . $blog_id . '/';
}

function siteloaded_cache_safe_purge($blog_id) {
    $base = siteloaded_cache_dir($blog_id);

    siteloaded_debug('safetly purging cache for blog ' . $blog_id . ' at ' . $base);

    $oldest = PHP_INT_MAX;

    foreach (glob("$base*.html", GLOB_NOSORT) as $filename) {
        $f = new siteloaded_file_access();
        $fp = $f->open_excl($filename, 'rb');

        if ($fp === FALSE) {
            sl_log('could not open file for purge: ' . $filename);
            continue;
        }

        $stat = fstat($fp);
        $mtime = $stat['mtime'];
        if ($mtime < $oldest) {
            $oldest = $mtime;
        }

        if (!@unlink($filename)) {
            sl_log('could not delete file for purge: ' . $filename);
        }

        $f->close();
    }

    if ($oldest != PHP_INT_MAX) {
        foreach (glob("$base*.{css,js}", GLOB_NOSORT | GLOB_BRACE) as $filename) {
            $f = new siteloaded_file_access();
            $fp = $f->open_excl($filename, 'rb');

            if ($fp === FALSE) {
                sl_log('could not open file for purge: ' . $filename);
                continue;
            }

            $stat = fstat($fp);
            $mtime = $stat['mtime'];
            if ($mtime < $oldest) {
                if (!@unlink($filename)) {
                    sl_log('could not delete file for purge: ' . $filename);
                }
            }

            $f->close();
        }
    }
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
