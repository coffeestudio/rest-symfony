<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

class Result
{
    private $entities;
    private $viewMap;
    private $typeMap;

    public function __construct(\Iterator $ents, array $projection)
    {
        $this->entities = $ents;
        list ($this->viewMap, $this->typeMap) = self::splitProjection($projection);
    }

    private static function splitProjection($p)
    {
        $viewMap = [];
        $typeMap = [];
        foreach ($p as $k => $pm) {
            $viewMap[$k] = $pm->getter;
            $typeMap[$k] = $pm->type;
        }
        return [$viewMap, $typeMap];
    }

    /**
     * @param array $fieldset Array of field names to return in output array.
     * @return array Representation of the model rows.
     */
    public function apply($fieldset = null)
    {
        $a = [];
        $fields = $fieldset ? array_intersect_key($this->viewMap, array_flip($fieldset)) : $this->viewMap;
        foreach ($this->entities as $row) {
            $a[] = array_map(function ($m) use ($row) {
                if ($m[0] == '!') {
                    $m = substr($m, 1);
                    return ! $row->$m();
                }
                return $row->$m();
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