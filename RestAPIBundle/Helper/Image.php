<?php
namespace CoffeeStudio\RestAPIBundle\Helper;
use CoffeeStudio\RestAPIBundle\DirConf;

class Image {
    use DirConf;

    private $width, $height; // $dwidth and $dheight from process()

    private $input_path, $input_http_path;
    private $output_path, $output_http_path;

    private $desired_size, $flags, $position; // fixme: need preprocess

    private $watermark_path, $watermark_position;

    private $suffix; // suffix to distinct cached files

    /* State */
    private $is_ready = false;
    private $processed = false;

    const FIT       = 0x01; // Fit image into frame, all source dimensions are visible
    const FILL      = 0x02; // Fill frame with image, no air in the frame
    const PAD       = 0x04; // Pad image with air if needed to fit into frame
    const CROP      = 0x08; // Crop image if needed to fit into frame
    const ENLARGE   = 0x10; // Enlarge image if needed (with quality drawback)
    const PRIVATED  = 0x20; // Use PRIVATE_ROOT as image source
    const WATERMARK = 0x40; // Attach watermark
    const BYSMALL   = 0x80; // Fit small side into frame
    const SCALABLE  = 0x100; // Don not set size attributes
    const FORCE_PUBLIC = 0x200; // Force public diregarding keep_private setting

    const CACHEDIR_PREFIX = '_img_';

    function __construct($src, $size=null, $flags=null, $position="0% 0%", $suffix=null) {
        $this->suffix = $suffix;
        $flags |= self::PRIVATED; // XXX
        if ($flags & self::FORCE_PUBLIC) $flags ^= self::PRIVATED;
        $this->input_http_path = $src;
        if(empty($size)) {
            $this->desired_size = null;
        } else {
            if(!is_array($size)) $size = preg_split('/x/i', $size, 2);
            if(!isset($size[1]) || $size[1] < 0) $size[1] = 0;
            if($size[0] < 0) $size[0] = 0;
            $size[0] = intval($size[0]);
            $size[1] = intval($size[1]);
            $this->desired_size = $size;
        }
        $this->flags = $flags;
        $this->position = $position;
    }

    function attachWatermark($path, $position='')
    {
        $this->watermark_path = $path;
        $this->watermark_position = $position;
        $this->flags |= self::WATERMARK;
    }

    function getSrc($html=false)
    {
        $this->requireProcess();
        return '/media' . ($html ? htmlspecialchars($this->output_http_path) : $this->output_http_path);
    }
    function __toString()
    {
        $src = $this->getSrc();
        return empty($src) ? '' : $src;
    }
    function getWidth()
    {
        $this->requireProcess();
        return $this->width;
    }
    function getHeight()
    {
        $this->requireProcess();
        return $this->height;
    }
    function getSize()
    {
        $this->requireProcess();
        return array($this->width, $this->height);
    }
    function isReady()
    {
        return $this->is_ready;
    }
    function printTag(array $attrs=array(), $to_string=false, $scalable=null)
    {
        $this->requireProcess();
        if(!$this->isReady()) return;

        $widthattr = $heightattr = '';
        if (is_null($scalable)) {
            $scalable = (bool)($this->flags & self::SCALABLE);
        }
        if (! $scalable) {
            if ($this->getWidth()) $widthattr = ' width="'.$this->getWidth().'"';
            if ($this->getHeight()) $heightattr =  ' height="'.$this->getHeight().'"';
        }
        $tag = '<img src="'.$this->getSrc(true).'"'.$widthattr.$heightattr;
        foreach($attrs as $k => $v) {
            //$tag .= ' '.htmlspecialchars($k).'="'.htmlspecialchars($v).'"';
            $tag .= ' '.$k.'="'.$v.'"';
        }
        $tag .= '/>';
        if($to_string) return $tag;
        else print $tag;
    }

    /* NOTE:
     * swidth, sheight - original size of the source image
     * width, height - size of the target image without pad or crop (designates scale factor)
     * dwidth, dheight - size of the destination frame where image is put into
     */
    private function process() // FIXME $src
    {
        $src = $this->input_http_path;
        $size = $this->desired_size;
        $flags = $this->flags;
        $position = $this->position;

        if(empty($src)) return false;
        if($flags & self::PRIVATED) {
            $this->input_path = $this->getPrivateRoot() . $src;
        } else {
            $this->input_path = $this->getPublicRoot() . $src;
        }
        if(!file_exists($this->input_path)) return false;

        @ $imageinfo = getimagesize($this->input_path);
        list($swidth, $sheight) = $imageinfo;
        if(!$swidth || !$sheight) return false;

        if($flags & self::BYSMALL) {
            if($swidth > $sheight) $size[0] = 0;
            else $size[1] = 0;
        }

        $sratio = $swidth / $sheight;
        if(empty($size)) {
            $width = $swidth;
            $height = $sheight;
        } else {
            list($width, $height) = $size;
        }
        $dwidth = $width;
        $dheight = $height;

        if($width && $height && $flags) {
            $ratio = $width / $height;
            if($flags & self::FILL) {
                if($sratio < $ratio) {
                    $height = 0; // 0 means auto ...
                } else {
                    $width = 0;
                }
            } elseif($flags & self::FIT) {
                if($sratio > $ratio) {
                    $height = 0;
                } else {
                    $width = 0;
                }
            }
        }

        $pos_x = $pos_y = 0;
        if($flags & self::CROP || $flags & self::PAD) { // FIXME: somewhat dirty fix, requires optimization
            $subdir = self::CACHEDIR_PREFIX . ($dwidth ? $dwidth : '') . 'x' . ($dheight ? $dheight : '') . '_' . sprintf('%02x', $flags);

            /* TMP TMP TMP */
            $w = $width; $h = $height;
            if(!$w) $w = intval($sratio * $h);
            elseif (!$h) $h = intval($w / $sratio);
            /* Let's count position */
            if(!is_array($position)) $position = preg_split('/\s+/', $position, 2);
            if(!isset($position[1])) $position[1] = 0;
            list($pos_x, $pos_y) = $position;
            if(preg_match('/%$/', $pos_x)) $pos_x = self::pct2px($pos_x, $w, $dwidth);
            else $pos_x = intval($pos_x);
            if(preg_match('/%$/', $pos_y)) $pos_y = self::pct2px($pos_y, $h, $dheight);
            else $pos_y = intval($pos_y);
            /* / */
            if($pos_x || $pos_y) $subdir .= '_'.$pos_x.'_'.$pos_y;
        } else {
            $subdir = self::CACHEDIR_PREFIX . ($width ? $width : '') . 'x' . ($height ? $height : '') . '_' . sprintf('%02x', $flags);
        }
        if (!empty ($this->suffix)) $subdir .= '_' . $this->suffix;
        $this->output_http_path = dirname($src) . "/$subdir/" . basename($src);
        $this->output_path = $this->getPublicRoot() . $this->output_http_path;
        if(file_exists($this->output_path)) // XXX: Debug
        {
            $this->setSize(getimagesize($this->output_path));
            return true;
        }

        if(! file_exists(dirname($this->output_path))) mkdir(dirname($this->output_path), 0777, true);

        if(!($flags & self::WATERMARK) && ($width == $swidth || $width == 0) && ($height == $sheight || $height == 0)) { // TODO: exclude PAD and CROP!
            $this->cloneOriginal();
            return true;
        }

        if(! $width) $width = intval($sratio * $height);
        elseif (! $height) $height = intval($width / $sratio);

        if(!($flags & self::ENLARGE | $flags & self::WATERMARK) && ($width > $swidth || $height > $sheight)) {
            $this->output_path = $this->getPublicRoot() . $src; // XXX XXX XXX FIXME <- insert here check for file presence (what if source is private?), make symlink/copy <- need method for this
            $this->output_http_path = $src; // FIXME
            if($flags & self::PRIVATED) $this->cloneOriginal();
            $this->setSize(getimagesize($this->output_path)); // empty attrs otherwise :(
            return true;
        }
        $img_t = preg_replace('/.*\/(\w+)/', '\1', $imageinfo['mime']);
        if (! $img_t) return null;
        if ($img_t == 'x-ms-bmp') $img_t = 'bmp';
        $create = 'imagecreatefrom' . $img_t;
        if(!function_exists($create)) return false;
        $save = 'image' . $img_t;
        $img_res = $create($this->input_path);
        if(! ($flags & self::CROP || $flags & self::PAD)) {
            $dwidth = $width;
            $dheight = $height;
        } else {
            if(!$dwidth) $dwidth = $width;
            if(!$dheight) $dheight = $height;
        }
        $this->width = $dwidth;
        $this->height = $dheight;
        $img_dest_res = imagecreatetruecolor($dwidth, $dheight);
        /* Making background */
        $bgcolor_d = imagecolorallocatealpha($img_dest_res, 255, 255, 255, 0);
        imagefill($img_dest_res, 0, 0, $bgcolor_d);

        if ( $img_t == 'png' ) {
	        //imagealphablending($img_dest_res, false);
	        imagealphablending($img_dest_res, true);
	        imagesavealpha($img_dest_res, true);
		}
		
		if ( $img_t == 'gif' ) {
			
			//Получаем прозрачный цвет
			$transparent_source_index = imagecolortransparent($img_res);
			
			if($transparent_source_index!==-1) {
				$transparent_color=imagecolorsforindex($img_res, $transparent_source_index);
				
				$transparent_destination_index=imagecolorallocate($img_dest_res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagecolortransparent($img_dest_res, $transparent_destination_index);
				
				imagefill($img_dest_res, 0, 0, $transparent_destination_index);
			}
		}
		
        imagecopyresampled($img_dest_res, $img_res, $pos_x, $pos_y, 0, 0, $width, $height, $swidth, $sheight);

        /* Watermark */
        if($flags & self::WATERMARK) {
            $src_wm = $this->watermark_path;
            $wm_imageinfo = getimagesize($src_wm);
            list ($wm_width, $wm_height) = $wm_imageinfo;
            $wm_t = preg_replace('/.*\/(\w+)/', '\1', $wm_imageinfo['mime']);
            if($wm_t) {
                $fProcent = strstr($this->watermark_position, "%") ? true : false;
                $wm_pos = explode(' ', $this->watermark_position);
                $wm_pos_x = 0;
                $wm_pos_y = 0;

                //position is % or PX(pixels)
                if($fProcent) {
                    $part_x = floatval($wm_pos[0])/100;
                    $part_y = floatval($wm_pos[1])/100;
                    $wm_pos_x = abs(intval($part_x*($dwidth - $wm_width)));
                    $wm_pos_y = abs(intval($part_y*($dheight - $wm_height)));
                } else {
                    $wm_pos_x = intval($wm_pos[0]);
                    $wm_pos_y = intval($wm_pos[1]);
                }

                $wm_create = 'imagecreatefrom' . $wm_t;
                $wm_res = $wm_create($src_wm);
                imagecopy($img_dest_res, $wm_res, $wm_pos_x, $wm_pos_y, 0, 0, $wm_width, $wm_height);
                imagedestroy($wm_res);
            }
        }
        /* /Watermark */

        $save($img_dest_res, $this->output_path, $img_t == 'png' ? 9 : 100);
        imagedestroy($img_res);
        imagedestroy($img_dest_res);
        return true;
    }

    private function setSize($w_size, $h=0)
    {
        if(!is_array($w_size)) $w_size = preg_split('/x/i', $w_size, 2);
        if(!isset($w_size[1])) $w_size[1] = $h;
        list($this->width, $this->height) = $w_size;
    }

    private function cloneOriginal()
    {
        if(file_exists($this->output_path)) return;
        if(OS == 'WIN') {
            copy($this->input_path, $this->output_path);
        } else {
            symlink($this->input_path, $this->output_path);
        }
    }

    private function requireProcess()
    {
        if($this->processed) return;
        $this->is_ready = $this->process();
        $this->processed = true;
    }

    private static function pct2px($pos, $dim, $dimcont) /* FIXME: If we keep dimensions as properties, this must not be static */
    {
        $pos = floatval($pos);
        return intval($dimcont * $pos / 100 - $dim * $pos / 100);
    }
}
