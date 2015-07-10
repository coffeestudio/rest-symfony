<?php
namespace CoffeeStudio\RestAPIBundle\Util;

use CoffeeStudio\RestAPIBundle\AccessControl;
use CoffeeStudio\RestAPIBundle\DirConf;
use CoffeeStudio\RestAPIBundle\Helper\Upload;
use CoffeeStudio\RestAPIBundle\Helper\Image;

/* Adapted legacy ajax file manager from coffee framework */
class FileManager implements IRestUtil {
    use DirConf;
    use AccessControl;

    private $cwn, $rcwn;
    private $privated = true;
    private $root;

    public function __construct()
    {
        $this->root = $this->getPrivateRoot();
    }

    private function setCWN($path)
    {
        $path = explode('/', $path);
        $this->rcwn = '/' . implode('/', array_slice(array_filter($path, function ($x) { return $x != '..'; }), 1));
        $this->cwn = $this->root . $this->rcwn;
    }

    public function ls($accessor)
    {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
            $dh = @ opendir($this->cwn);
            if (!$dh) {
                return;
            }
            $result = array('dirs' => array(), 'files' => array());
            while ($entry = readdir($dh)) {
                if ($entry == '.' || $entry == '..' || preg_match('/^_img_.*/', $entry)) {
                    continue;
                }
                if (is_dir($this->cwn.'/'.$entry)) {
                    $mtime = filemtime($this->cwn.'/'.$entry);
                    $result['dirs'][] = array('dn' => $entry, 'mtime' => $mtime);
                } else {
                    $mtime = filemtime($this->cwn.'/'.$entry);
                    $type = null;
                    $mime = self::getMime($this->cwn.'/'.$entry);
                    $type = $mime[0];
                    $fstruct = array('fn' => $entry, 'resid' => null, 'mtime' => $mtime);
                    if ($type == 'image') {
                        $flags = Image::FILL | Image::CROP;
                        if ($this->privated) {
                            $flags |= Image::PRIVATED;
                        } // Not really needed
                        $thumb = new Image($this->rcwn.'/'.$entry, '18x18', $flags);
                        $fstruct['thumb'] = $thumb->getSrc();
                    }
                    $result['files'][] = $fstruct;
                }
            }

            $sortfunc = function ($e1, $e2) {
                $r1 = intval($e1['mtime']);
                $r2 = intval($e2['mtime']);
                if ($r1 > $r2) {
                    return -1;
                }
                if ($r1 < $r2) {
                    return 1;
                }

                return 0;
            };
            usort($result['dirs'], $sortfunc);
            usort($result['files'], $sortfunc);
            return $result;
        };
    }

    function rm($accessor) {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
//            $res = new Res;
//            $res->getByPath($this->rcwn);
//            $res->del();
            unlink($this->cwn);
        };
    }

    function mkdir($accessor) {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
            $json = array('status' => 0, 'message' => '');
            $ok = mkdir($this->cwn, 0777, true);
            if ($ok) {
                $json['status'] = 1;
            } else {
                $json['message'] = 'Failed to create dir '.$this->rcwn;
            }

            return $json;
        };
    }

    function previewIcon($accessor) {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
            return $this->preview('18x18');
        };
    }

    function previewBig($accessor) {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
            return $this->preview('1024x');
        };
    }

    function preview($accessor) {
        $this->restricted($accessor);
        return function ($path, $size='250x250') { $this->setCWN($path);
            $mime = self::getMime($this->cwn);
            $result = array('type' => $mime[0]);
            switch ($mime[0]) {
                case 'image':
                    $flags = Image::FIT;
                    if ($this->privated) {
                        $flags |= Image::PRIVATED;
                    } // Not really needed
                    $img = new Image($this->rcwn, $size, $flags);
                    $result['src'] = $img->getSrc();
                    break;
            }

            return $result;
        };
    }

    function upload($accessor) {
        $this->restricted($accessor);
        return function ($path) { $this->setCWN($path);
            $test = trim($this->rcwn, '/');
            if (empty($test)) {
                return;
            } // root upload prohibited

            $upl = new Upload('file', '*/*');
            $upl->setRenameCallback(
                function ($name) {
                    if ($ext_pos = strrpos($name, '.')) {
                        $filename = FileManager::translit(substr($name, 0, $ext_pos));
                        $ext = strtolower(substr($name, $ext_pos));
                        if (substr($ext, 0, 3) == 'php' || $ext == 'phtml') {
                            return ['status' => 0];
                        }

                        return $filename.'.'.substr(uniqid(), 9, 4).$ext;
                    } else {
                        return FileManager::translit($name).'.'.substr(uniqid(), 9, 4);
                    }
                }
            );
            $upl->handle();
            $upl->save($this->cwn);
//            $res = new Res;
            $uploaded_fns = array();
            foreach ($upl->getUploadedFiles() as $fs) {
                $type = explode('/', $fs['type']);
                $resfn = $this->rcwn.'/'.$fs['name'];
                $uploaded_fns[] = $resfn;
                if (!$type) {
                    continue;
                }
//                $res->add($resfn, $type[0], $this->admin->getName(), $this->admin->id, $this->privated);
            }

            return $uploaded_fns;
        };
    }

    static function getMime($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = explode('/', finfo_file($finfo, $file), 2);
        finfo_close($finfo);
        return $mime;
    }

    static function translit($str) {
        $str = mb_strtolower($str);
        $rules = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => 'y', 'ы' => 'yi', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        );
        return preg_replace('/[^a-z0-9]/s', '_', strtr($str, $rules));
    }
}
