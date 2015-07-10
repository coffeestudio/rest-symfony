<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IUserDAO extends RestHandle
{
    public function projection()
    {
        return
        [ 'id' => G::S('getId')->t('int')
        , 'username' => G::S('getUsername')->t('string')
        ];
    }

    public function getUser($accessor)
    {
        $this->restricted($accessor);
        return function ($id) {
            return $this->mkResult($this->getDAO()->getUser($id));
        };
    }

    public function login($accessor)
    {
        return function ($dataIn) {
            return $this->mkResult($this->getDAO()->login($dataIn['login'], $dataIn['password']));
        };
    }
}