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

    public function schema($accessor)
    {
        $this->restricted($accessor);
        return function() {
            $ecn = $this->getEntityName();
            $newe = new $ecn;
            return $this->mkResult($newe, true);
        };
    }

    public function add($accessor)
    {
        $this->restricted($accessor);
        return function($dataIn) {
            if (empty($dataIn)) return null;
            $ecn = $this->getEntityName();
            $newe = new $ecn;
            $this->applyData($newe, $dataIn);
            $this->getEntityManager()->persist($newe);
            $this->getEntityManager()->flush();
            $dproj = $this->projection();
            $this->extendProjection('id', $dproj['id']);
            return $this->mkResult($newe);
        };
    }

    public function edit($accessor)
    {
        $this->restricted($accessor);
        return function($id, $dataIn) {
            $s = $this->getDAO()->find($id);
            $this->applyData($s, $dataIn);
            $this->getEntityManager()->merge($s);
            $this->getEntityManager()->flush();
            return $this->mkResult($s);
        };
    }

    public function delete($accessor)
    {
        $this->restricted($accessor);
        return function($id) {
            $s = $this->getDAO()->find($id);
            $this->getEntityManager()->remove($s);
            $this->getEntityManager()->flush();
            return $this->mkResult($s);
        };
    }

    public function get($accessor)
    {
        $this->restricted($accessor);
        return function ($id) {
            return $this->mkResult($this->getDAO()->get($id));
        };
    }

    public function getList($accessor)
    {
        $this->restricted($accessor);
        return function () {
            return $this->mkResult($this->getDAO()->getList());
        };
    }

    public function listViewFields($accessor)
    {
        $this->restricted($accessor);
        return function () {
            return $this->getDAO()->listViewFields();
        };
    }

    public function editViewFields($accessor)
    {
        $this->restricted($accessor);
        return function () {
            return $this->getDAO()->editViewFields();
        };
    }
}