<?php
namespace horm\shard\rule;

use horm\base\BaseTable;
use horm\shard\base\ShardRule;

/**
 * 取摸分区域
 *<B>说明：</B>
 *<pre>
 * 优点:
 *</pre>
 */
class ModShardRule extends ShardRule
{

    protected $mod = 0;

    public function __construct($mod = null,$fields = [])
    {
        parent::__construct($fields);

        if (!empty($mod)) {
            $this->mod = $mod;
        }
    }


    public function getSequence(BaseTable $table = null, $value)
    {
        return $value %  $this->mod;

    }
}
