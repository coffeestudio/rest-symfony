<?php
namespace CoffeeStudio\RestAPIBundle\Util;

class Test implements IRestUtil
{
    function someAction($accessor)
    {
       return function () {
           return 'SUCCESS';
       };
    }

    function __invoke($accessor)
    {
        return function () {
            return 'INVOKE TEST';
        };
    }
}