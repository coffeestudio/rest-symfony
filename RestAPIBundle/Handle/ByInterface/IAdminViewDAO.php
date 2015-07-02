<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IAdminViewDAO extends RestHandle
{
    public function fields()
    {
        return ['id' => 'getId', 'title' => 'getTitle'];
    }

    public function getList($accessor)
    {
        return function () {
            return $this->mkResult($this->getDAO()->getList());
        };
    }
}