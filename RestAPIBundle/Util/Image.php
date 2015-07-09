<?php
namespace CoffeeStudio\RestAPIBundle\Util;
use CoffeeStudio\RestAPIBundle\AccessControl;
use CoffeeStudio\RestAPIBundle\Helper\Image as Img;

class Image implements IRestUtil {
    use AccessControl;

    const IMAGE_HELPER_CLASS = 'CoffeeStudio\RestAPIBundle\Helper\Image';

    public function __invoke($accessor=null) {
        // TODO: Fixme
//        if ($this->restricted($accessor));
        return function ($src, $size=null, $flags=null, $pos="0% 0%") {
            if ($flags) {
                $flags = array_reduce(preg_split('/[|,]/', $flags, -1, PREG_SPLIT_NO_EMPTY),
                    function ($acc, $v) { return $acc |= constant(self::IMAGE_HELPER_CLASS.'::'.$v); }, 0);
            }
            $img = new Img($src, $size, $flags, $pos);
            return array ('src' => $img->getSrc());
        };
    }
}
