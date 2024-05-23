<?php
namespace horm\shard;

use horm\base\BaseTable;
use horm\shard\base\ShardTableTrait;

/**
 * 分表table 基类
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class ShardTable extends BaseTable
{
    use ShardTableTrait;
}