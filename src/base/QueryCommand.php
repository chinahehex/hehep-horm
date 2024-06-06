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

    /**
     * 命令转字符串
     * @return string
     */
    protected function commandToString():string
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

    public function toRawCommand()
    {
        return $this->replaceSqlValue($this->commandToString(),$this->getParams());
    }

    /**
     * 替换sql 参数
     *<B>说明：</B>
     *<pre>
     *  	1、替换sql 语句中预处理标识
     *</pre>
     * @param string $query sql 指令
     * @param array $params 绑定参数
     * @return string sql 语句
     */
    protected  function replaceSqlValue(string $query = '', array $params = []):string
    {
        $keys = array();
        $values = array();

        # build a regular expression for each parameter
        foreach ($params as $key=>$value)
        {
            if (is_string($key)) {
                if (0 !== strpos($key,':')) {
                    $keys[] = '/:'.$key.'/';
                } else {
                    $keys[] = '/'.$key.'/';
                }

            } else {
                $keys[] = '/[?]/';
            }

            if (is_numeric($value)) {
                $values[] = intval($value);
            } else if (is_array($value)) {
                if ($value[1] == \PDO::PARAM_INT) {
                    $values[] = $value[0] ;
                } else if ($value[1] == \PDO::PARAM_STR) {
                    $values[] = '"'.$value[0] .'"';
                } else {
                    $values[] = '"'.$value[0] .'"';
                }
            } else {
                $values[] = '"'.$value .'"';
            }
        }

        $sql2 = preg_replace($keys, $values, $query, 1, $count);

        return $query . "\n" . $sql2;
    }
}
