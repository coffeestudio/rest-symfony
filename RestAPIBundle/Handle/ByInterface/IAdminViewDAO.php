<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IAdminViewDAO extends RestHandle
{
    public function projection()
    {
        return
        [ 'id' => G::S('getId')->t('int')
        , 'title' => G::S('getTitle')->t('string')
        ];
    }

    public function add($accessor)
    {
        return function($dataIn) {
            $meta = $this->getDAO()->persist();
            print $meta;
        };
    }

    public function edit($accessor)
    {
        return function($id, $dataIn) {

        };
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