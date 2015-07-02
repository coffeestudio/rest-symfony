<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IAdminViewDAO extends RestHandle
{
    public function fields()
    {
        return ['id' => 'getId', 'title' => 'getTitle'];
    }

    public function get($accessor)
    {
        return function ($id) {
            return $this->mkResult($this->getDAO()->get($id));
        };
    }

    public function getList($accessor)
    {
        return function () {
            return $this->mkResult($this->getDAO()->getList());
        };
    }

    public function listViewFields($accessor)
    {
        return function () {
            return $this->getDAO()->listViewFields();
        };
    }

    public function editViewFields($accessor)
    {
        return function () {
            return $this->getDAO()->editViewFields();
        };
    }
}