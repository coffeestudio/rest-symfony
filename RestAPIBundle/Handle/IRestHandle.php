<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

interface IRestHandle {
    /**
     * @param object $dao Data access object for REST model.
     */
    public function __construct($dao);

    /**
     * @return array Map JSON fields to interface getters.
     */
    public function fields();
}
