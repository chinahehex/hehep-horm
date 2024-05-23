<?php
namespace  horm\base;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use Exception;
use horm\Dbsession;
use horm\Entity;

/**
 * db 表达式类
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class RawComparison
{
    public $comparison;

    public function __construct($comparison)
    {
        $this->comparison = $comparison;
    }

    public function getComparison()
    {
        return $this->comparison;
    }
}

?>
