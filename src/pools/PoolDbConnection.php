<?php
namespace horm\pools;


trait PoolDbConnection
{
    /**
     * 连接池
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var ConnectionPool
     */
    protected $connectionPool = null;

    protected $haspool = true;

    public function setConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function free()
    {
        if ($this->haspool) {
            $this->connectionPool->releaseConnection($this);
        }
    }

    public function reconnect()
    {

    }

    public function isActive()
    {

    }
}