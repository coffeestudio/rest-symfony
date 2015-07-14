<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

class Result
{
    private $entities;
    private $projection;
    private $viewMap;
    private $typeMap;
    private $defaultMap;
    private $setDefaults;

    public function __construct(\Iterator $ents, array $projection, $setDefaults = false)
    {
        $this->entities = $ents;
        $this->projection = $projection;
        $this->setDefaults = $setDefaults;
        list ($this->viewMap, $this->typeMap, $this->defaultMap) = self::splitProjection($projection);
    }

    private static function splitProjection($p)
    {
        $viewMap = [];
        $typeMap = [];
        $defaultMap = [];
        foreach ($p as $k => $pm) {
            $viewMap[$k] = $pm->getter;
            $typeMap[$k] = $pm->type;
            $defaultMap[$k] = $pm->default;
        }
        return [$viewMap, $typeMap, $defaultMap];
    }

    public function flatten()
    {
        /* TODO: implement */
    }

    /**
     * @param array $fieldset Array of field names to return in output array.
     * @return array Representation of the model rows.
     */
    public function apply($fieldset = null)
    {
        $a = [];
        $fields = $fieldset ? array_intersect_key($this->projection, array_flip($fieldset)) : $this->projection;
        foreach ($this->entities as $row) {
            $a[] = array_map(function ($pm) use ($row) {
                $m = $pm->getter;
                if ($m[0] == '!') {
                    $m = substr($m, 1);
                    $value = ! $row->$m();
                }
                $value = $row->$m();
                return $this->setDefaults && is_null($value) ? $pm->default : $value;
            }, $fields);
        }
        return $a;
    }

    public function getTypes()
    {
        return $this->typeMap;
    }

    public function __invoke($fieldset = null)
    {
        return $this->apply($fieldset);
    }
}