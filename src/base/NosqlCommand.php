<?php
namespace horm\base;

use horm\base\QueryCommand;

class NosqlCommand extends QueryCommand
{


    public function buildCommand()
    {
        return json_encode($this->command);
    }


    /**
     * 获取方法
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
     * 获取sql 对应的绑定参数
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
