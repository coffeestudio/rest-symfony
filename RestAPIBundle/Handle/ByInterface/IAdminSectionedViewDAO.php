<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IAdminSectionedViewDAO extends IAdminViewDAO
{

    public function schema($accessor)
    {
        $this->restricted($accessor);
        return function($section) {
            $ecn = $this->getEntityName();
            $newe = new $ecn;
            $newe->setSectionId($section);
            return $this->mkResult($newe, true);
        };
    }

    public function getListBySection($accessor)
    {
        $this->restricted($accessor);
        return function ($section) {
            return $this->mkResult($this->getDAO()->getListBySection($section));
        };
    }
}