<?php
namespace  horm\base;

/**
 * db 原始表达式类
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
