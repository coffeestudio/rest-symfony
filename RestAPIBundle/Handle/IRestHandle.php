<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

interface IRestHandle {
    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct(EntityRepository $dao, $entityName, EntityManager $em, $viewMap=null);

    /**
     * @return array Map JSON fields to interface getters/setters (default projection).
     */
    public function projection();

    /**
     * @return array Map JSON fields to interface getters. Returned custom projection if supplied, otherwise projection().
     */
    public function getProjection();

    /**
     * @return string entity name
     */
    public function getEntityName();

    /**
     * @return EntityManager
     */
    public function getEntityManager();

    /**
     * Set data on object using setters from projection
     * @param object $obj
     * @param array $data
     * @return object entity
     */
    public function applyData($obj, array $data);
}
