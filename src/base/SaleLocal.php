<?php
namespace horm\base;

/**
 * 线程安全类
 * Class SaleLocal
 * @package horm\base
 */
class SaleLocal
{
    protected $_attributes = [];

    public function setAttribute(string $name,$value)
    {
        $ident = $this->getIdent();

        if (is_null($value)) {
            unset($this->_attributes[$name][$ident]);
        } else {
            $this->_attributes[$name][$ident] = $value;
        }
    }

    public function getAttribute(string $name)
    {

        if (!isset($this->_attributes[$name])) {
            return null;
        }

        $ident = $this->getIdent();

        if (!isset($this->_attributes[$name][$ident])) {
            return null;
        }

        return $this->_attributes[$name][$ident];
    }

    protected function getIdent()
    {
        return 0;
        //return posix_getpid();
    }

}
