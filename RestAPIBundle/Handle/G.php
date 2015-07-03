<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

class G
{
    public static function S($getter, $setter=null)
    {
        return new ProjectionMap($getter, $setter);
    }
}
