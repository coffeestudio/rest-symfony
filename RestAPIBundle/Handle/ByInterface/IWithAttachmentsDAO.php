<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IWithAttachmentsDAO extends RestHandle
{
    /* Projection of IAttachment */
    public function projection()
    {
        return
        [ 'id' => G::S('getId')
        , 'title' => G::S('getTitle')
        , 'comment' => G::S('getComment')
        , 'sort' => G::S('getSort')
        , 'is_main' => G::S('isMain')
        , 'type' => G::S('getFileType')
        , 'path' => G::S('getFilePath')
        ];
    }

    public function getAttachmentsById($accessor)
    {
        return function ($entityId) {
            return $this->mkResult($this->getDAO()->getAttachmentsById($entityId));
        };
    }
    public function addAttachment($accessor)
    {
        return function ($entityId, $dataIn) {
            $defaults = ['path' => null, 'title' => '', 'comment' => '', 'sort' => 50, 'isMain' => false];
            list ($path, $title, $comment, $sort, $isMain) = array_values (
                array_merge($defaults, array_intersect_key($dataIn, $defaults))
            );
            return $this->mkResult($this->getDAO()->addAttachment($entityId, $path, $title, $comment, $sort, $isMain));
        };
    }
    public function editAttachment($accessor)
    {
        return function ($entityId, $attId, $dataIn) {
            $defaults = array_fill_keys(['path', 'title', 'comment', 'sort', 'isMain'], null);
            list ($path, $title, $comment, $sort, $isMain) = array_values (
                array_merge($defaults, array_intersect_key($dataIn, $defaults))
            );
            return $this->mkResult($this->getDAO()->editAttachment($entityId, $attId, $path, $title, $comment, $sort, $isMain));
        };
    }
    public function delAttachment($accessor)
    {
        return function ($entityId, $attId, $dataIn) {
            return $this->mkResult($this->getDAO()->delAttachment($entityId, $attId));
        };
    }
}