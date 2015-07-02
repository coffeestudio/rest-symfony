<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

interface IRestHandle {
    /**
     * @param object $dao Data access object for REST model.
     * @param array $viewMap Optional custom view map.
     */
    public function __construct($dao, $viewMap=null);

    /**
     * @return array Map JSON fields to interface getters.
     */
    public function fields();

    /**
     * @return array Map JSON fields to interface getters. Returned custom view map if supplied, otherwise fields().
     */
    public function viewMap();
}
