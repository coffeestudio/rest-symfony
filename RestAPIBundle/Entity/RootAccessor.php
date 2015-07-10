<?php
namespace CoffeeStudio\RestAPIBundle\Entity;

use CoffeeStudio\Harness\IUser;

class RootAccessor implements IUser
{
    public function getId()
    {
        return 0;
    }

    public function getUsername()
    {
        return 'root';
    }
}