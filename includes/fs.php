<?php
defined('ABSPATH') or exit;

class siteloaded_lock_file
{
    private $fp = FALSE;
    private $path = "";

    public function acquire($id) {
        if ($this->fp !== FALSE) {
            return FALSE;
        }

        $this->path = SL_TEMP_DIR . $id . '.lock';
        $this->fp = @fopen($this->path, 'wb');
        if ($this->fp === FALSE) {
            $this->path = "";
            return FALSE;
        }

        if (!@flock($this->fp, LOCK_EX)) {
            @fclose($this->fp);
            $this->path = "";
            $this->fp = FALSE;
            return FALSE;
        }

        return TRUE;
    }

    public function release() {
        if ($this->fp === FALSE) {
            return FALSE;
        }

        @unlink($this->path);
        @flock($this->fp, LOCK_UN);
        @fclose($this->fp);

        $this->path = "";
        $this->fp = FALSE;
        return TRUE;
    }

    function __destruct() {
        if ($this->fp !== FALSE) {
            $this->release();
        };
    }
}

class siteloaded_file_access {
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

function siteloaded_shared_read($path) {
    $f = new siteloaded_file_access();
    $fp = $f->open_shared($path, 'rb');
    if ($fp === FALSE) {
        return FALSE;
    }
    $content = @stream_get_contents($fp);
    $f->close();
    return $content;
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

function siteloaded_get_svg_logo($fill = '#fff') {
    $logo = <<<EOD
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 371.4 325.8" style="enable-background:new 0 0 371.4 325.8;" xml:space="preserve">
    <style type="text/css">
            .siteloaded-svg-logo-fill{fill:${fill};}
    </style>
    <title>siteloaded-logo</title>
    <path class="siteloaded-svg-logo-fill" d="M248.8,119.4l-90.4,82.7l-41.2-39.8l-61.1,56.1l0,0l0,0L21,250.8c44,73.1,138.9,96.8,212.1,52.8 c54.7-32.9,83.5-96,72.6-158.8l-46,41.9v-0.1c-8.7,58.9-63.6,99.7-122.5,90.9c-19.1-2.8-37.1-10.8-52.2-22.9l31.5-29l41,39.7 l99.8-91.3l45.7-41.9l51.5-46.4l16.9,15.8V6.8L248.8,119.4z"/>
    <path class="siteloaded-svg-logo-fill" d="M262.5,0l17.3,14.9l-66,59.5l-53.1,48.4l-41.2-40l-70.7,64.7c12.9-58.2,70.6-94.9,128.9-82 c7.5,1.7,14.9,4.2,21.9,7.4l0,0l36.3-33.1C163.1-5,67.9,17.5,23,90.1C8,114.5,0,142.6,0,171.2c0,29.1,16.5,68.5,16.5,68.5l35.8-33 l0,0l0,0l66.2-61l41.4,40l84.9-77.6l33.7-29.4L365.8,0H262.5z"/>
</svg>
EOD;

    return $logo;
}
