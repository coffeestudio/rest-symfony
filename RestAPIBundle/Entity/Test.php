<?php
namespace CoffeeStudio\RestAPIBundle\Entity;

use CoffeeStudio\Harness\ITest;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CoffeeStudio\RestAPIBundle\Entity\TestDAO")
 */
class Test implements ITest
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @return string
     */
    public function getTestMessage()
    {
        return 'Test message (' . time() . ')';
    }
}