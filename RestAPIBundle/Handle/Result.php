<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

class Result
{
    private $entities;
    private $viewMap;

    public function __construct(\Iterator $ents, array $viewMap)
    {
        $this->entities = $ents;
        $this->viewMap = $viewMap;
    }

    /**
     * @param array $fieldset Array of field names to return in output array.
     * @return array Representation of the model rows.
     */
    function apply($fieldset = null)
    {
        $a = [];
        $fields = $fieldset ? array_intersect_key($this->viewMap, array_flip($fieldset)) : $this->viewMap;
        foreach ($this->entities as $row) {
            $a[] = array_map(function ($m) use ($row) { return $row->$m(); }, $fields);
        }
        return $a;
    }

    function __invoke($fieldset = null)
    {
        return $this->apply($fieldset);
    }
}