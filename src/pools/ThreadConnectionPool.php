<?php
namespace horm\pools;

use horm\base\BaseConnection;
use horm\base\DbConnection;

/**
 * 进程数据库连接池
 *<B>说明：</B>
 *<pre>
 * 多个线程共享一个连接池
 * 如果当前线程正在使用,改链接,则直接从当前线程使用的链接
 *</pre>
 * @property DbConnection $threadConnection 当前线程使用的连接
 */
class ThreadConnectionPool extends ConnectionPool
{
    /**
     * 最大连接数
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var int
     */
    protected $maxSize = 1;

    /**
     * 缓存池最大数量
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var int
     */
    protected $maxcachedSize = 1;

    /**
     * 预加载连接数量
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var int
     */
    protected $preload = 0;

    /**
     * 正在使用的连接数量
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var int
     */
    protected $_useingNum = 0;

    /**
     * 空闲连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var DbConnection[]
     */
    protected $_freeConnections = [];


    protected $_saleLocal = null;

    public function __construct(array $attrs = [])
    {
        parent::__construct($attrs);

        $this->_saleLocal = $this->dbsession->createSaleLocal();

        $this->preloadConnection();
    }

    /**
     * 预加载连接
     */
    protected function preloadConnection()
    {
        if ($this->preload <= 0) {
            return ;
        }

        for ($i = 0;$i < $this->preload;$i++) {
            $this->_freeConnections[] = $this->createConnection();
        }
    }

    /**
     * 获取一个可用数据库连接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return \horm\base\DbConnection
     */
    public function getConnection()
    {
        // 加锁
        $freenum = count($this->_freeConnections);
        if ($freenum <= 0) {
            // 如果报错异常，忘记提交事务,如何回收资源
            $conn = $this->threadConnection;
            // 直接获取当前线程连接
            if (!empty($conn)) {
                return $conn;
            }

            // 判断是否超过最大数量
            if ($freenum > $this->maxSize) {
                // 等待或抛出异常
                return null;
            }

            // 创建连接
            $conn = $this->createConnection();
        } else {
            $conn = array_pop ($this->_freeConnections);
        }

        $this->threadConnection = $conn;
        $this->_useingNum++;

        return $conn;
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
        $this->_useingNum--;

        // 判断空闲连接
        if (count($this->_freeConnections) < $this->maxcachedSize) {
            array_push($this->_freeConnections,$dbConnection);
        } else {
            // 直接销毁连接
            $dbConnection->close();
            unset($dbConnection);
        }

        $this->threadConnection = null;
    }

    public function __get($name)
    {
        return $this->_saleLocal->getAttribute($name);
    }

    public function __set($name, $value)
    {
        $this->_saleLocal->setAttribute($name,$value);
    }

}
