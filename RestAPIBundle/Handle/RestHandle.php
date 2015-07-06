<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class RestHandle implements IRestHandle {
    private $dao;
    private $customProjection;
    private $entityName;
    private $entityManager;

    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct(EntityRepository $dao, $entityName, EntityManager $em, $projection=null)
    {
        $this->dao = $dao;
        $this->customProjection = $projection;
        $this->entityName = $entityName;
        $this->entityManager = $em;
    }

    /**
     * @return string entity name
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
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

    /**
     * Set data on object using setters from projection
     * @param object $obj
     * @param array $data
     * @return object entity
     */
    public function applyData($obj, array $data)
    {
        $proj = $this->getProjection();
        $chain = array_filter(array_map(function ($x) { return $x->setter; }, array_intersect_key($proj, $data)));
        foreach ($chain as $k => $call) {
            $obj->$call($data[$k]);
        }
        return $obj;
    }
}