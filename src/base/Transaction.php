<?php
namespace  horm\base;

/**
 * db事务类
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class Transaction
{
    /**
     * 默认设置定义
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var \horm\base\DbConnection
     */
    protected $db = null;

    /**
     * 构造方法
     *<B>说明：</B>
     *<pre>
     * 参数赋值
     *</pre>
     * @param \horm\base\DbConnection $db 数据库连接
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * 启动事务
     *<B>说明：</B>
     *<pre>
     * 	略
     *</pre>
     * @throws Transaction fail
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * 提交事务
     *<B>说明：</B>
     *<pre>
     *  事务提交，清空当前事务相关数据，状态，事务db
     *</pre>
     * @return boolean false 只要其中一个事务提交失败,true 全部数据库事务提交成功
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * 回滚事务
     *<B>说明：</B>
     *<pre>
     *  事务回滚，清空当前事务相关数据，状态，事务db
     *</pre>
     * @return boolean
     */
    public function rollback()
    {
        return $this->db->rollback();
    }
}