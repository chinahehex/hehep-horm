<?php
namespace horm\pools;

use horm\base\DbConnection;
use horm\Dbsession;

/**
 * 数据库连接池基类
 *<B>说明：</B>
 *<pre>
 *	略
 *</pre>
 */
abstract class ConnectionPool
{
    /**
     * db key
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var string
     */
    protected $dbKey = '';

    /**
     * db 配置
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var array
     */
    protected $dbconf = null;

    /**
     * 创建db 链接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var Dbsession
     */
    protected $dbsession = null;

    public function __construct(array $attrs = [])
    {
        if (!empty($attrs)) {
            foreach ($attrs as $attr=>$value) {
                $this->$attr = $value;
            }
        }
    }

    /**
     * 创建db 链接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return DbConnection
     */
    public function createConnection():DbConnection
    {
        $dbconn = $this->dbsession->makeDbConnection($this->dbKey,$this->dbconf);
        $dbconn->setConnectionPool($this);

        return $dbconn;
    }

    /**
     * 获取一个可用数据库连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return \horm\base\DbConnection
     */
    abstract public function getConnection();

    /**
     * 释放连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return \horm\base\DbConnection
     */
    abstract public function releaseConnection(DbConnection $dbConnection);

}
