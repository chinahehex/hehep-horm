<?php
namespace horm\shard;

use horm\base\BaseTable;
use horm\shard\base\ShardDbTableTrait;

/**
 * 分库分表基类
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class ShardDbTable extends BaseTable
{
    use ShardDbTableTrait;
}