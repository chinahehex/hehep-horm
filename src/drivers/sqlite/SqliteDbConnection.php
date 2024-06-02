<?php
namespace  horm\drivers\sqlite;

use horm\base\DbConnection;


/**
 * sqlite 连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class SqliteDbConnection extends DbConnection
{


    /**
     * Builder 实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var SqliteQueryBuilder
     */
    private $builder = null;

    /**
     * 获取sql生成对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return SqliteQueryBuilder
     */
    public function getQueryBuilder():SqliteQueryBuilder
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
     * @return SqliteQueryBuilder
     */
    public function createQueryBuilder():SqliteQueryBuilder
    {
        return new SqliteQueryBuilder($this);
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
        $dsn  = 'sqlite:' . $this->getConfig("database");

        return $dsn;
    }

}
