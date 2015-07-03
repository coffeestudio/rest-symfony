<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

interface IRestHandle {
    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct($dao, $entityName, $viewMap=null);

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
}
