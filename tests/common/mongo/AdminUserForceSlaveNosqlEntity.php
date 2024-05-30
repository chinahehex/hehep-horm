<?php
namespace horm\tests\common\mongo;

use horm\Entity;
use horm\QueryTable;
use horm\tests\TestCase;

/**
 * 管理人员数据实体
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 * @method static QueryTable effective(QueryTable $queryTable)
 *
 * hehex
 * @property  $id
 * @property  $username
 * @property  $password
 * @property  $tel
 * @property  $realName
 * @property  $headPortrait
 * @property  $safeCode
 * @property  $status
 * @property  $age
 * @property  $ctime
 * @property  $roleId
 * hehex
 *
 */
class AdminUserForceSlaveNosqlEntity extends Entity
{

    public static function dbSession()
    {
        return TestCase::getDbsession();
    }

    /**
     * 定义数据库标识
     * @return string
     */
    public static function dbKey()
    {
        return 'hehe_master';
    }

    // 获取从库
    public static function dbSlave()
    {
        // 随机从从库列表读取一个从库
        $slaves = ['hehe_slave1','hehe_slave2'];
        return $slaves[mt_rand(0,count($slaves) -1)];;
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        // '{{%users_:shard}}',:shard 分区号
        return '{{%admin_users}}';
    }

    /**
     * 是否自增定义
     * @return string
     */
    public static function autoIncrement()
    {
        return false;
    }

    /**
     * 定义表主键字段
     * @return string
     */
    public static function pk()
    {
        return 'id';
    }



}
