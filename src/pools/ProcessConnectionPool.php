<?php
namespace horm\pools;

use horm\base\BaseConnection;
use horm\base\DbConnection;

/**
 * 进程数据库连接池
 *<B>说明：</B>
 *<pre>
 * 一个进程对应一个连接
 *</pre>
 */
class ProcessConnectionPool extends ConnectionPool
{
    /**
     * dbconn
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var DbConnection
     */
    protected $dbconn = null;

    /**
     * 从连接池中获取一个可用数据库连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return \horm\base\DbConnection
     */
    public function getConnection()
    {
        if (!empty($this->dbconn)) {
            return $this->dbconn;
        }

        $this->dbconn = $this->createConnection();

        return $this->dbconn;
    }

    /**
     * 释放连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return \horm\base\DbConnection
     */
    public function releaseConnection(BaseConnection $dbConnection)
    {

    }

}
