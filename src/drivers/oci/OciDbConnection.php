<?php
namespace  horm\drivers\oci;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use horm\base\QueryCommand;


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
     * @return OciQueryBuilder
     */
    public function createQueryBuilder():OciQueryBuilder
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

    /**
     * 获取最近插入的ID
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return string|int
     */
    public function getLastInsertID($sequence = '')
    {
        if(!empty($sequence)) {
            $cmd = new QueryCommand(['command'=>'select "'.$sequence.'".currval as "id" from dual']);
            $pdoStatement = $this->executeCommand($cmd);
            $result = $this->getResult($pdoStatement);
            if (!empty($result)) {
                return $result[0]['id'];
            }
        }

        return null;
    }



}
