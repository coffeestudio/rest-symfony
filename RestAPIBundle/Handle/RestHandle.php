<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

abstract class RestHandle implements IRestHandle {
    private $dao;
    private $customViewMap;

    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct($dao, $viewMap=null)
    {
        $this->dao = $dao;
        $this->customViewMap = $viewMap;
    }

    /**
     * @return object Data access object for REST model.
     */
    protected function getDAO()
    {
        return $this->dao;
    }

    /**
     * @param $ent
     * @return Result
     */
    protected function mkResult($ent)
    {
        if (is_array($ent)) {
            $ent = (new \ArrayObject($ent))->getIterator();
        } elseif (! $ent instanceof \Iterator) {
            $ent = (new \ArrayObject([$ent]))->getIterator();
        }
        return new Result($ent, $this->viewMap());
    }

    public function viewMap()
    {
       return $this->customViewMap ? $this->customViewMap : $this->fields();
    }
}