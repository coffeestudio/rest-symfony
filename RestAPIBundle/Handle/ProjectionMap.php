<?php
namespace CoffeeStudio\RestAPIBundle\Handle;


class ProjectionMap
{
    public $getter;
    public $setter;
    public $type;

    public function __construct($getter, $setter=null)
    {
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function t($type)
    {
        $this->type = $type;
        return $this;
    }
}
