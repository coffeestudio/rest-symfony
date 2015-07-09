<?php
namespace CoffeeStudio\RestAPIBundle\Helper;

/* Upload class from coffee framework */
class Upload {
    /* For unlimited size or quantity */
    const UNLIM     = 0;
    const UNLIMITED = 0;

    /* Upload handler state */
    const ERROR = -1;
    const NULL  = 0;
    const READY = 1;
    const DONE  = 2;
    private $state;

    /* File processing results */
    private $files = array();
    private $denied = array(); // index map to $files
    private $saved = array(); // index map to $files
    private $on_hold = array(); // index map to $files
    private $file_count = 0;

    /* Utils */
    private $rename_callback; // renamer

    private $slot, $allowed_mime, $max_files, $max_file_size;
    function __construct($slot, $allowed_mime, $max_files=self::UNLIM, $max_file_size=self::UNLIM)
    {
        $this->slot = $slot;
        $this->allowed_mime = is_array($allowed_mime) ? $allowed_mime : array($allowed_mime);
        foreach($this->allowed_mime as &$mime) {
            $mm = explode('/', $mime, 2);
            if(count($mm) < 2) unset($mime);
            else $mime = $mm;
        }
        $this->max_files = intval($max_files);
        $this->max_file_size = self::size2bytes($max_file_size);
    }

    private function checkType($mime)
    {
        $m1 = explode('/', $mime, 2);
        foreach($this->allowed_mime as $m0) {
            if( ($m0[0] == '*' || $m0[0] == $m1[0]) && ($m0[1] == '*' || $m0[1] == $m1[1]) ) return true;
        }
        return false;
    }

    private function addFile($orig_name, $type, $size, $tmp_name, $error)
    {
        $i = $this->file_count;
        $this->file_count++;
        $file_s = array(
              'orig_name' => $orig_name
            , 'type' => $type
            , 'size' => $size
            , 'tmp_name' => $tmp_name
            , 'error' => $error
        );
        $ok = false;

        if( ($this->max_files != self::UNLIM && $this->file_count > $this->max_files) || ($this->max_file_size != self::UNLIM && $size > $this->max_file_size) || (!$this->checkType($type)) ) {
            $this->denied[$i] = 1;
        } else {
            $this->on_hold[$i] = 1;
            $ok = true;
        }

        if($ok && $this->rename_callback) {
            $file_s['name'] = call_user_func($this->rename_callback, $file_s['orig_name']);
        } else {
            $file_s['name'] = $file_s['orig_name'];
        }

        $this->files[$i] = $file_s;

        return $ok;
    }

    function setRenameCallback($callback)
    {
        $this->rename_callback = $callback;
    }

    function handle()
    {
        if(!isset($_FILES[$this->slot])) {
            return false;
        }

        $fs = &$_FILES[$this->slot];
        if(is_array($fs['name'])) {
            foreach($fs['name'] as $i => $name) {
                $this->addFile($name, $fs['type'][$i], $fs['size'][$i], $fs['tmp_name'][$i], $fs['error'][$i]);
            }
        } else {
            $this->addFile($fs['name'], $fs['type'], $fs['size'], $fs['tmp_name'], $fs['error']);
        }

        $this->state = self::READY;

        return true;
    }

    function save($upload_dir)
    {
        if(!$this->isReady()) return false;
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach(array_intersect_key($this->files, $this->on_hold) as $i => $fs) {
            if(move_uploaded_file($fs['tmp_name'], $upload_dir . '/' . $fs['name'])) {
                unset($this->on_hold[$i]);
                $this->saved[$i] = 1;
            }
        }
        $this->state = self::DONE;
        return true;
    }

    function isReady()  { return $this->state == self::READY; }
    function isDone()   { return $this->state == self::DONE;  }
    function isFailed() { return $this->state == self::ERROR; }

    /**
     * (like dd manual says:)
     * Size may be followed by the following multiplicative suffixes:
     * kB = 1000, K = 1024, MB = 1000*1000, M = 1024*1024, GB = 1000*1000*1000, G = 1024*1024*1024
     */
    private static function size2bytes($s)
    {
        if(is_int($s)) return $s;
        if(preg_match('/^(.*\d+)\s*([kmgb]+)\s*$/i', $s, $m)) {
            $value = doubleval($m[1]);
            $suff = strtoupper($m[2]);
            $muxmap = array('KB' => 1000, 'K' => 1024, 'MB' => 1000000, 'M' => 1048576, 'GB' => 1000000000, 'G' => 1073741824);
            if(isset($muxmap[$suff])) {
                return intval($value * $muxmap[$suff]);
            }
        }

        return intval($s);
    }

    /* Getters to handle our result */
    function getUploadedFiles()
    {
        $fns = array();
        foreach(array_intersect_key($this->files, $this->saved) as $fs) {
            $fns[] = array_intersect_key($fs, array_flip(array('name', 'type', 'size', 'error')));
        }
        return $fns;
    }
}
