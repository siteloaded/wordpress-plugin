<?php
defined('ABSPATH') or exit;

function siteloaded_optimize_and_cache($content) {
    if (!siteloaded_current_response_cacheable()) {
        return $content;
    }

    $request = siteloaded_current_request_url();
    $request_str = siteloaded_request_url_to_str($request);
    $sha_str = sha1($request_str);
    $blog_id = get_current_blog_id();
    $base = siteloaded_cache_dir($blog_id);
    $code = http_response_code();
    // TODO: we disabled credentials during beta
    // $key = get_option('siteloaded_subscription_id');
    $key = '';
    // if ($key === FALSE) {
    //     return $content;
    // }

    $once = new siteloaded_lock_file();
    $acquired = $once->acquire($sha_str);
    if ($acquired) {
        $cached_html_path = $base . $sha_str . ($code === 404 ? '.404' : '') . '.html';

        $f = new siteloaded_file_access();
        $fp = $f->open_shared($cached_html_path, 'rb');
        if ($fp !== FALSE) {
            $content = @stream_get_contents($fp);
            $f->close();
            if ($content !== FALSE) {
                $once->release();
                siteloaded_debug('piggybacking on another worker for ' . $request_str);
                return $content;
            }
        }
    } else {
        siteloaded_debug('could not acquire lock for ' . $request_str);
    }

    $content = siteloaded_remote_optimize_and_save($key, $blog_id, $request_str, SITELOADED_CACHE_DIR_URL . $blog_id . '/', $content);
    if ($acquired) {
        $once->release();
    }
    return $content;
}

function siteloaded_current_response_cacheable() {
    global $pagenow;
    $code = http_response_code();

    function is_html() {
        foreach (headers_list() as $header) {
            if (preg_match('/content-type.*text\/html/i', $header)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    return ($code === 200 || $code === 404) // it's ok or not found
        && $pagenow === 'index.php'         // not in any of wp-*.php, especially wp-login.php
        && !is_user_logged_in()             // not a logged-in user (includes seeing previews, customizing theme or being in admin section)
        && is_html();                       // it's content-type: text/html
}

function siteloaded_remote_optimize_and_save($key, $blog_id, $page_url, $cache_url, $html) {
    siteloaded_cache_ensure_valid($blog_id);
    $base = siteloaded_cache_dir($blog_id);
    $code = http_response_code();
    $response = siteloaded_get_optimization_package($key, $page_url, $cache_url, $html);
    if ($response === FALSE) {
        return $html;
    }

    $parts = siteloaded_parse_multipart_response($response['body'], $response['content-type']);
    if ($parts === FALSE) {
        siteloaded_log('could not parse optimization package');
        return $html;
    }

    foreach ($parts as $part) {
        if ($part['name'] !== 'asset') {
            continue;
        }

        $is_html = strpos($part['filename'], '.html') !== FALSE;
        $file_path = $is_html && $code === 404
            ? $base . str_replace('.html', '-404.html', $part['filename'])
            : $base . $part['filename'];

        if (@file_put_contents($file_path, $part['value'], LOCK_EX) === FALSE) {
            siteloaded_log('could not write asset: ' . $file_path);
            continue;
        }

        if ($is_html) {
            $html = $part['value'];
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_path)) . ' GMT');
        }
    }

    return $html;
}

function siteloaded_get_optimization_package($key, $page_url, $cache_url, $page_html) {
    siteloaded_debug('getting optimization package for ' . $page_url);
    $gzip = function_exists('gzencode');
    $mime_boundary = md5(time());
    $payload = siteloaded_get_optimization_multipart_payload($page_url, $cache_url, $page_html, $mime_boundary);
    $headers = array(
        'Content-Type' => 'multipart/form-data; boundary=' . $mime_boundary,
        'Authorization' => $key
    );

    if ($gzip) {
        $payload = gzencode($payload);
        $headers["Content-Encoding"] = "gzip";
    }

    $response = wp_remote_post(SITELOADED_OPTIMIZE_URL, array(
        'timeout' => 15,
	    'body' => $payload,
        'headers' => $headers,
        'decompress' => $gzip
    ));

    if (is_array($response)) {
        if (wp_remote_retrieve_response_code($response) === 200) {
            return array(
                'body' => wp_remote_retrieve_body($response),
                'content-type' => wp_remote_retrieve_header($response, 'content-type')
            );
        } else {
            siteloaded_log('could not get optimization package: ' . wp_remote_retrieve_response_code($response));
        }
    } else if (is_wp_error($response)) {
        siteloaded_log('could not get optimization package, ' . $response->get_error_message());
    }

    return FALSE;
}

function siteloaded_get_optimization_multipart_payload($page_url, $cache_url, $html, $mime_boundary) {
    $payload = '';

    $payload .= '--' . $mime_boundary . PHP_EOL;
    $payload .= 'Content-Disposition: form-data; name="page_html"; filename="' . $page_url . '"' . PHP_EOL;
    $payload .= 'Content-Type: application/octet-stream' . PHP_EOL . PHP_EOL;
    $payload .= $html . PHP_EOL;

    $payload .= '--' . $mime_boundary . PHP_EOL;
    $payload .= 'Content-Disposition: form-data; name="cache_url"' . PHP_EOL . PHP_EOL;
    $payload .= $cache_url . PHP_EOL;

    $payload .= "--" . $mime_boundary . "--" . PHP_EOL . PHP_EOL;
    return $payload;
}

function siteloaded_parse_multipart_response($input, $content_type)
{
    $params = array();
    $input = preg_replace('/(\r\n|\r|\n)/ms', "\n", $input); // normalize line breaks

    if (strpos($content_type, 'multipart/form-data') === FALSE) {
        return FALSE;
    }

    if (preg_match('/boundary=([^ ;]+)/', $content_type, $matches) !== 1) {
        return FALSE;
    }

    $boundary = $matches[1];
    $parts = preg_split("/-*$boundary(\R|--.*)/s", $input, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $part) {
        if (preg_match('/([^\R]+)\R\R(.*)\R/s', $part, $headers_and_body) !== 1) {
            return FALSE;
        }

        $name = '';
        $headers = preg_split('/\R/', $headers_and_body[1], -1, PREG_SPLIT_NO_EMPTY);
        $param = array('value' => $headers_and_body[2]);

        foreach ($headers as $header) {
            if (strpos(strtolower($header), 'content-disposition') !== FALSE) {
                if (preg_match('/[^a-zA-Z]name="([^"]*)"/', $header, $matches) === 1) {
                    $name = $matches[1];
                }
                if (preg_match('/filename="([^"]*)"/', $header, $matches) === 1) {
                    $param['filename'] = $matches[1];
                }
            } else if (preg_match('/content-type\s*:\s*([-\w\/]+)/i', $header, $matches) === 1) {
                $param['content-type'] = $matches[1];
            }
        }

        if (!$name) {
            continue;
        }

        $param['name'] = $name;
        array_push($params, $param);
    }

    return $params;
}
