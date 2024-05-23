<?php
namespace horm\base;

/**
 * db 命令对象
 *<B>说明：</B>
 *<pre>
 * 主要用于装载sql
 *</pre>
 */
class QueryCommand
{
    /**
     * sql 语句
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var string
     */
    protected $command;

    /**
     * 预处理参数
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var string
     */
    protected $params = [];

    public function __construct($attrs = [])
    {
        foreach ($attrs as $attr => $value) {
            $this->$attr = $value;
        }
    }

    /**
     * 获取原始命令
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    public function buildCommand()
    {
        return $this->command;
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
        return $this->params;
    }
}