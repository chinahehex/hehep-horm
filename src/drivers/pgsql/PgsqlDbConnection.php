<?php
namespace  horm\drivers\pgsql;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;


/**
 * Pgsql 连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class PgsqlDbConnection extends DbConnection
{


    /**
     * Builder 实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var BaseQueryBuilder
     */
    private $builder = null;

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
        return new PgsqlQueryBuilder($this);
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
        $dsn = 'pgsql:dbname=' . $this->getConfig("database") . ';host=' . $this->getConfig("host");

        if (!empty($this->getConfig("port"))) {
            $dsn .= ';port=' . $this->getConfig("port");
        }

        if (!empty($this->config['charset'])) {
            $dsn  .= ";options='--client_encoding=".$this->config['charset']."'";
        }

        return $dsn;
    }



}
