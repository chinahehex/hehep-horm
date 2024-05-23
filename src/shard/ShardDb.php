<?php
namespace horm\shard;
use horm\base\BaseTable;
use horm\shard\base\ShardDbTrait;

/**
 * 分库基类
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class ShardDb extends BaseTable
{
    use ShardDbTrait;
}