<?php
namespace horm\base;

/**
 * db 原始表达式
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class RawExpression
{
    public $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}