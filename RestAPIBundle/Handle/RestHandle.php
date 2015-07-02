<?php
namespace CoffeeStudio\RestAPIBundle\Handle;

abstract class RestHandle implements IRestHandle {
    private $dao;

    /**
     * @param object $dao Data access object for REST model.
     */
    public function __construct($dao)
    {
        $this->dao = $dao;
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
        return new Result($ent, $this->fields());
    }
}