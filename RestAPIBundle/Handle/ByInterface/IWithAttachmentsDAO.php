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
            $defaults = ['type' => null, 'path' => null, 'title' => '', 'comment' => '', 'sort' => 50, 'isMain' => false];
            list ($type, $path, $title, $comment, $sort, $isMain) = $defaults + array_intersect_key($dataIn, $defaults);
            return $this->mkResult($this->getDAO()->addAttachment($entityId, $type, $path, $title, $comment, $sort, $isMain));
        };
    }
    public function editAttachment($accessor)
    {
        return function ($entityId, $attId, $dataIn) {
            $defaults = array_fill_keys(['type', 'path', 'title', 'comment', 'sort', 'isMain'], null);
            list ($type, $path, $title, $comment, $sort, $isMain) = $defaults + array_intersect_key($dataIn, $defaults);
            return $this->mkResult($this->getDAO()->editAttachment($entityId, $attId, $type, $path, $title, $comment, $sort, $isMain));
        };
    }
    public function delAttachment($accessor)
    {
        return function ($entityId, $attId, $dataIn) {
            return $this->mkResult($this->getDAO()->delAttachment($entityId, $attId));
        };
    }
}