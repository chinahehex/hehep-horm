<?php
namespace horm;

use horm\base\BaseEntity;
use ArrayAccess;
use Iterator;

/**
 * 实体
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class Entity extends BaseEntity implements ArrayAccess,Iterator
{
    /** 以下方法为,对象作为数组操作的接口方法 **/
    public function offsetExists($offset)
    {
        if (isset($this->_value[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    public function offsetGet($offset)
    {

        return $this->_value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_value[$offset] = $value;

        return ;
    }

    public function offsetUnset($offset)
    {
        unset($this->_value[$offset]);
    }

    /** 以下方法为,遍历对象操作的接口方法 **/
    private $_position = 0;

    private $_posList = [];

    // 遍历对象之前 先重置指针
    public function rewind()
    {
        $this->_posList = array_keys($this->_value);
        $this->_position = 0;
    }

    // 获取当前指针
    public function current()
    {
        return $this->_value[$this->_posList[$this->_position]];
    }

    // 获取当前的键值
    public function key()
    {
        return $this->_posList[$this->_position];//返回当前键的值 为0
    }

    // 指针下移
    public function next()
    {
        $this->_position++;
    }

    //4、判断指针是否合法
    public function valid()
    {
        if (isset($this->_posList[$this->_position])) {
            return true;
        } else {
            return false;
        }
    }
}