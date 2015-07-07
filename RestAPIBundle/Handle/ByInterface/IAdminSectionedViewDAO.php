<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IAdminSectionedViewDAO extends IAdminViewDAO
{
    public function getListBySection($accessor)
    {
        return function ($section) {
            return $this->mkResult($this->getDAO()->getListBySection($section));
        };
    }
}