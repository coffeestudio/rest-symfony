<?php
namespace CoffeeStudio\RestAPIBundle\Handle\ByInterface;

use CoffeeStudio\RestAPIBundle\Handle\G;
use CoffeeStudio\RestAPIBundle\Handle\RestHandle;

class IWithSpecAttachmentsDAO extends IWithAttachmentsDAO
{
    /* Projection of IAttachmentWithSpec */
    public function projection()
    {
        return
        [ 'id' => G::S('getId')
        , 'title' => G::S('getTitle')
        , 'comment' => G::S('getComment')
        , 'sort' => G::S('getSort')
        , 'isMain' => G::S('isMain')
        , 'isSpec' => G::S('isSpec')
        , 'type' => G::S('getFileType')
        , 'path' => G::S('getFilePath')
        ];
    }

    public function addAttachment($accessor)
    {
        $this->restricted($accessor);
        return function ($entityId, $dataIn) {
            $defaults = ['path' => null, 'title' => '', 'comment' => '', 'sort' => 50, 'isMain' => false, 'isSpec' => false];
            list ($path, $title, $comment, $sort, $isMain, $isSpec) = array_values (
                array_merge($defaults, array_intersect_key($dataIn, $defaults))
            );
            return $this->mkResult($this->getDAO()->addAttachment($entityId, $path, $title, $comment, $sort, $isMain, $isSpec));
        };
    }
    public function editAttachment($accessor)
    {
        $this->restricted($accessor);
        return function ($entityId, $attId, $dataIn) {
            $defaults = array_fill_keys(['path', 'title', 'comment', 'sort', 'isMain', 'isSpec'], null);
            list ($path, $title, $comment, $sort, $isMain, $isSpec) = array_values (
                array_merge($defaults, array_intersect_key($dataIn, $defaults))
            );
            return $this->mkResult($this->getDAO()->editAttachment($entityId, $attId, $path, $title, $comment, $sort, $isMain, $isSpec));
        };
    }
}