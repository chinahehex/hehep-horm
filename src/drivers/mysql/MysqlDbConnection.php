<?php
namespace  horm\drivers\mysql;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;


/**
 * mysql 连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class MysqlDbConnection extends DbConnection
{


    /**
     * Builder 实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var BaseQueryBuilder
     */
    private $builder;

    /**
     * 获取sql生成对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return BaseQueryBuilder
     */
    public function getQueryBuilder()
    {
        if ($this->builder === null) {
            $this->builder = $this->createQueryBuilder();
        }

        return $this->builder;
    }

    /**
     * 创建生成sql类实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return BaseQueryBuilder
     */
    public function createQueryBuilder()
    {
        return new MysqlQueryBuilder($this);
    }

    /**
     * 解析pdo连接的dsn信息
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return string
     */
    protected function parseDsn()
    {
        $dsn  =   'mysql:dbname=' . $this->config['database'].';host=' . $this->config['host'];
        if (!empty($this->config['port'])) {
            $dsn  .= ';port=' . $this->config['port'];
        } elseif (!empty($this->config['socket'])){
            $dsn  .= ';unix_socket='.$this->config['socket'];
        }

        if (!empty($config['charset'])) {
            $dsn  .= ';charset='.$this->config['charset'];
        }

        return $dsn;
    }

	
}