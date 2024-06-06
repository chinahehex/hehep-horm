<?php
namespace  horm\drivers\sqlsrv;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;


/**
 * mysql 连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class SqlsrvDbConnection extends DbConnection
{


    /**
     * Builder 实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var SqlsrvQueryBuilder
     */
    private $builder;

    /**
     * 获取sql生成对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return SqlsrvQueryBuilder
     */
    public function getQueryBuilder():SqlsrvQueryBuilder
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
     * @return SqlsrvQueryBuilder
     */
    public function createQueryBuilder()
    {
        return new SqlsrvQueryBuilder($this);
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

        $dsn = 'sqlsrv:Database=' . $this->config['database'] . ';Server=' . $this->config['host'];

        if (!empty($this->config['port'])) {
            $dsn .= ',' . $this->config['port'];
        }

        return $dsn;
    }


}
