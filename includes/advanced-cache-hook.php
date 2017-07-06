<?php

if (!siteloaded_advc_current_request_cacheable()) {
    return;
}

if (siteloaded_advc_cache_hit() === TRUE) {
    die();
}

ob_start(function($html) {
    return function_exists('siteloaded_optimize_and_cache')
        ? siteloaded_optimize_and_cache($html)
        : $html;
});

function siteloaded_advc_current_request_cacheable() {
    if (!isset($_SERVER['REQUEST_METHOD']) || strtolower($_SERVER['REQUEST_METHOD']) !== 'get') {
        return FALSE;
    }

    if (preg_match('/^(?:\/[^\/]+)?\/wp-admin\//', $_SERVER['REQUEST_URI'])) {
        return FALSE;
    }

    // TODO, check and refine things here...
    // TODO, do not cache if ?preview=true
    // TODO, getting stuck out of cache after a comment (the cookie stays after the comment)
    if (!empty($_COOKIE)) {
        foreach ($_COOKIE as $k => $v) {
            if (preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $k)) {
                return FALSE;
            }
        }
    }

    return TRUE;
}

function siteloaded_advc_current_request_url() {
    $s = $_SERVER;

    // Adapted from http://stackoverflow.com/a/8891890/193982

    $tls = FALSE;
    $scheme = 'http';
    if (isset($s['REQUEST_SCHEME'])) {
        $scheme = strtolower($s['REQUEST_SCHEME']);
        $tls = $scheme === 'https';
    } else {
        $tls = (isset($s['HTTP_X_FORWARDED_PROTO']) && $s['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($s['HTTPS']) && $s['HTTPS'] !== 'off' && $s['HTTPS'] !== '');
        if ($tls) { $scheme = 'https'; }
    }
    $port = $s['SERVER_PORT'];
    $port = ((!$tls && $port == '80') || ($tls && $port == '443')) ? '' : ':' . $port;
    $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = (isset($host) ? $host : $s['SERVER_NAME']) . $port;

    return array('scheme' => $scheme, 'host' => $host, 'path' => $s['REQUEST_URI']);
}

function siteloaded_advc_request_url_to_str($r) {
    return $r['scheme'] . '://' . $r['host'] . $r['path'];
}

function siteloaded_advc_get_blog_id($request_url) {
    global $blog_id;
    $id = $blog_id;

    if (SITELOADED_ADVC_NETWORK_ENABLED !== TRUE) {
        return $id;
    }

    $host = $request_url['host'];
    if (empty(SITELOADED_ADVC_NETWORK_SITES[$host])) {
        return $id;
    }

    $path = siteloaded_advc_urlpath_to_blogid_path($request_url['path']);
    foreach (SITELOADED_ADVC_NETWORK_SITES[$host] as $s) {
        $p = $s['path'];

        if ($p === '/') {
            $id = $s['blog_id'];
        }

        if ($p === $path) {
            return $s['blog_id'];
        }
    }

    return $id;
}

function siteloaded_advc_urlpath_to_blogid_path($path) {
    $re = '/^\/(?:[^\?\/]+\/)?/';
    $count = preg_match($re, $path, $matches);
    if ($count !== 1) {
        return '/';
    }
    return $matches[0];
}

function siteloaded_advc_cache_hit() {
    $request = siteloaded_advc_current_request_url();
    $request_str = siteloaded_advc_request_url_to_str($request);
    $blog_id = siteloaded_advc_get_blog_id($request);
    $sha1_str = sha1($request_str);
    $cached_file = SITELOADED_ADVC_LOCAL_CACHE_DIR . $blog_id . '/' . $sha1_str . '.html';

    $f = new siteloaded_advc_file_access();
    $fp = $f->open_shared($cached_file, 'rb');
    if ($fp === FALSE) {
        $cached_file = SITELOADED_ADVC_LOCAL_CACHE_DIR . $blog_id . '/' . $sha1_str . '-404.html';
        $fp = $f->open_shared($cached_file, 'rb');
        if ($fp === FALSE) {
            return FALSE;
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404);
        @fpassthru($fp);
    } else {
        $fstat = @fstat($fp);
        $file_time = $fstat['mtime'];
        $http_if_modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';

        if ($http_if_modified_since && (strtotime($http_if_modified_since) === $file_time)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', TRUE, 304);
        } else {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $file_time) . ' GMT');
            @fpassthru($fp);
        }
    }

    $f->close();
    return TRUE;
}

class siteloaded_advc_file_access {
    private $fp = FALSE;

    function open_shared($path, $mode) {
        return $this->open($path, $mode, LOCK_SH);
    }

    function open_excl($path, $mode) {
        return $this->open($path, $mode, LOCK_EX);
    }

    function close() {
        if ($this->fp !== FALSE) {
            @flock($this->fp, LOCK_UN);
            @fclose($this->fp);
            $this->fp = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    private function open($path, $mode, $type) {
        if ($this->fp !== FALSE) {
            return FALSE;
        }

        if (strpos($mode, 'b') === FALSE) {
            $mode .= 'b';
        }

        $this->fp = @fopen($path, $mode);
        if ($this->fp === FALSE) {
            return FALSE;
        }

        if (!@flock($this->fp, $type)) {
            @fclose($this->fp);
            return FALSE;
        }

        return $this->fp;
    }

    function __destruct() {
        if ($this->fp !== FALSE) {
            @flock($this->fp, LOCK_UN);
            @fclose($this->fp);
        }
    }
}
