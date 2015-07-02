<?php
namespace CoffeeStudio\RestAPIBundle\Entity;

use CoffeeStudio\Harness\ITest;
use CoffeeStudio\Harness\ITestDAO;

class TestDAO implements ITestDAO
{
    /**
     * @return ITest
     */
    public function getTest()
    {
        return new Test;
    }
}