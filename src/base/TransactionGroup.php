<?php
namespace  horm\base;

/**
 * 事务组
 *<B>说明：</B>
 *<pre>
 * 略
 *</pre>
 */
class TransactionGroup
{
    /**
     * 事务列表
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var Transaction[]
     */
    protected $transactions = [];

    /**
     * 构造方法
     *<B>说明：</B>
     *<pre>
     * 参数赋值
     *</pre>
     */
    public function __construct()
    {

    }

    /**
     * 是否已经存在事务
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $dbKey
     * @return boolean
     */
    public function hasTransaction($dbKey)
    {
        if (isset($this->transactions[$dbKey])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清空所有事务
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return boolean
     */
    public function cleanTransaction()
    {
        $this->transactions = [];
    }

    /**
     * 添加事务
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $dbKey
     * @param Transaction $transaction
     * @return boolean
     */
    public function addTransaction($dbKey,$transaction)
    {
        if (isset($this->transactions[$dbKey])) {
            return true;
        }

        $this->transactions[$dbKey] = $transaction;
        $transaction->beginTransaction();

        return true;
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
        $this->commit();

        return true;
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
        $result = true;
        foreach ($this->transactions as $transaction) {
            $result = $transaction->commit();
            if ($result == false) {
                break;
            }
        }

        // 清空事务db
        $this->cleanTransaction();

        return $result;
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
        $result = true;
        foreach ($this->transactions as $transaction) { // 事务提交失败，回滚事务
            $result = $transaction->rollback();
            if ($result == false) {
                break;
            }
        }

        // 清空事务db
        $this->cleanTransaction();

        return $result;
    }
}