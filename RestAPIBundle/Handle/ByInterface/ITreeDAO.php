<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;
use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class ITreeDAO extends RestHandle {
    public function projection()
    {
        return
            [ 'id' => G::S('getId')->t('int')
            , 'title' => G::S('getTitle')->t('string')
            , 'path' => G::S('getPath')->t('string')
            , 'fullpath' => G::S('getFullPath')->t('string')
            , 'leaf' => G::S('!hasChildren')->t('boolean')
            , 'root' => G::S('!hasParent')->t('boolean')
            ];
    }

    public function get($accessor = null) {
        $this->restricted($accessor);
        return function ($id) {
            if (empty($id)) return null;
            $s = $this->getDAO()->get($id);
            return $this->mkResult($s);
        };
    }

    public function getByPath($accessor = null) {
        $this->restricted($accessor);
        return function ($path) {
            if (empty($path)) return null;
            $s = $this->getDAO()->getByPath($path);
            return $this->mkResult($s);
        };
    }

    public function getTopLevel($accessor = null) {
        $this->restricted($accessor);
        return function () {
            $s = $this->getDAO()->getTopLevel();
            return $this->mkResult($s);
        };
    }

    public function getChildrenOf($accessor = null) {
        $this->restricted($accessor);
        return function ($id) {
            if (empty($id)) return null;
            $s = $this->getDAO()->get($id);
            $children = $s->getChildren();
            return $this->mkResult($children);
        };
    }

    public function getParentOf($accessor = null) {
        $this->restricted($accessor);
        return function ($id) {
            if (empty($id)) return null;
            $s = $this->getDAO()->getParent($id);
            return $this->mkResult($s);
        };
    }
}
