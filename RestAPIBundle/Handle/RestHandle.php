<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

abstract class RestHandle implements IRestHandle {
    private $dao;
    private $customProjection;
    private $entityName;

    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct($dao, $entityName, $projection=null)
    {
        $this->dao = $dao;
        $this->customProjection = $projection;
        $this->entityName = $entityName;

    }

    /**
     * @return string entity name
     */
    public function getEntityName()
    {
        return $this->entityName;
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
        return new Result($ent, $this->getProjection());
    }

    public function getProjection()
    {
       return $this->customProjection ? $this->customProjection : $this->projection();
    }
}