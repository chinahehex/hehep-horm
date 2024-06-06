<?php
namespace horm\base;

use horm\base\QueryCommand;

/**
 * mongo 命令
 * Class NosqlCommand
 * @package horm\base
 */
class NosqlCommand extends QueryCommand
{

    protected function commandToString():string
    {
        return json_encode($this->command);
    }

    /**
     * 获取操作方法
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    public function getMethod():string
    {
        return isset($this->command['method']) ? $this->command['method'] : '';
    }

    /**
     * 获取操作参数
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    public function getOptions($name = '')
    {
        if ($name === '') {
            return $this->command['options'];
        }

        if (isset($this->command['options'][$name])) {
            return $this->command['options'][$name];
        } else {
            return null;
        }
    }

    /**
     * 获取命令对应的绑定参数
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return array
     */
    public function getParams():array
    {
        return [];
    }
}
