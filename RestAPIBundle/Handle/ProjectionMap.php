<?php
namespace CoffeeStudio\RestAPIBundle\Handle;


class ProjectionMap
{
    public $getter;
    public $setter;
    public $type;
    public $default;

    public function __construct($getter, $setter=null)
    {
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function t($type, $default=null)
    {
        $this->type = $type;
        $this->default = $default;
        return $this;
    }
}
