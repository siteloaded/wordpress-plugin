<?php
defined('ABSPATH') or exit;

if (!class_exists('siteloaded_lock_file')) {
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
}

if (!class_exists('siteloaded_file_access')) {
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
}

if (!function_exists('siteloaded_shared_read')) {
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
}
