<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

class Result
{
    private $entities;
    private $viewMap;
    private $typeMap;

    public function __construct(\Iterator $ents, array $fMap)
    {
        $this->entities = $ents;
        list ($this->viewMap, $this->typeMap) = self::splitTypes($fMap);
    }

    private static function splitTypes($fMap)
    {
        $vs = [];
        $ts = [];
        foreach ($fMap as $k => $v) {
            if (preg_match('/^(\w+)\s*:\s*(\w+)$/', $v, $m)) {
                $vs[$k] = $m[1];
                $ts[$k] = $m[2];
            } else {
                $vs[$k] = $v;
            }
        }
        return [$vs, $ts];
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
            $a[] = array_map(function ($m) use ($row) { return $row->$m(); }, $fields);
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