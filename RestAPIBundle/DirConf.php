<?php
namespace CoffeeStudio\RestAPIBundle;

trait DirConf
{
    private $_root = null;
    private $_pubroot = null;
    private $_privroot = null;

    public function getRoot()
    {
        if (is_null($this->_root)) {
            $this->_root = realpath($this->getPublicRoot() . '/../..');
        }
        return $this->_root;
    }

    public function getPublicRoot()
    {
        if (is_null($this->_pubroot)) {
            $this->_pubroot = empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['PWD'] : $_SERVER['DOCUMENT_ROOT'] . '/media';
        }
        return $this->_pubroot;
    }

    public function getPrivateRoot()
    {
        if (is_null($this->_privroot)) {
            $this->_privroot = $this->getRoot() . '/media';
        }
        return $this->_privroot;
    }
}