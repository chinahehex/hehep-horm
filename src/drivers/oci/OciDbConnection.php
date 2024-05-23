<?php
namespace  horm\drivers\oci;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;


/**
 * oci 连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class OciDbConnection extends DbConnection
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
        return new OciQueryBuilder($this);
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
        $dsn = sprintf("oci:dbname=//%s:%s/%s;charset=%s",
            $this->getConfig("host"),
            $this->getConfig("port"),
            $this->getConfig("database"),
            $this->getConfig('charset')
        );

        return $dsn;
    }


	
}