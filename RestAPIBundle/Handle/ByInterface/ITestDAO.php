<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class ITestDAO extends RestHandle
{
    public function projection()
    {
        return ['testMessage' => G::S('getTestMessage')];
    }

    public function getTest($accessor)
    {
        return function () {
            return $this->mkResult($this->getDAO()->getTest());
        };
    }

    public function getValue($accessor)
    {
        return function ($n) {
            return $n * 10;
        };
    }
}