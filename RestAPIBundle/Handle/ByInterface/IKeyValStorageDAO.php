<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IKeyValStorageDAO extends RestHandle
{
    public function projection()
    {
        return
        [ 'id' => G::S('getId')->t('int')
        , 'title' => G::S('getTitle')->t('string')
        , 'name' => G::S('getName')->t('string')
        , 'type' => G::S('getType')->t('string')
        , 'value' => G::S('getValue', 'setValue')
        ];
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

    public function updateStorage($accessor)
    {
        $this->restricted($accessor);
        return function ($dataIn) {
            $dao = $this->getDAO();
            $em = $this->getEntityManager();
            foreach ($dataIn as $id => $v) {
                $s = $dao->find($id);
                $s->setValue($v);
                $em->merge($s);
            }
            $em->flush();
            return true;
        };
    }

    public function getList($accessor)
    {
        $this->restricted($accessor);
        return function () {
            return $this->mkResult($this->getDAO()->getList());
        };
    }
}