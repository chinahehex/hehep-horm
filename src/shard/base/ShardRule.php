<?php
namespace horm\shard\base;

use horm\base\BaseTable;
use horm\QueryTable;

/**
 * 实体模型类
 *<B>说明：</B>
 *<pre>
 * 通过对象的方式操作数据
 *</pre>
 */
abstract class ShardRule
{

    /**
     * 分区字段名称
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     */
    protected $fields = [];

    public function getFields()
    {
        return $this->fields;
    }

    public function __construct($fields = [])
    {
        if (!empty($fields)) {
            $this->fields = $fields;
        }
    }

    public static function make(){

    }


    /**
     * 计算分区序号
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param QueryTable $table
     * @param array $value
     */
    abstract public function getShardId(QueryTable $table,$value);

}
