<?php
namespace horm\tests\common\mongo;

use horm\Entity;
use horm\QueryTable;
use horm\shard\rule\ModShardRule;
use horm\shard\ShardDb;
use horm\shard\ShardTable;
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
class AdminUserinfoShardDbNosqlEntity extends Entity
{

    public static function dbSession()
    {
        return TestCase::getDbsession();
    }

    /**
     * ShardTable 分表
     * @return string
     */
    public static function queryTable()
    {
        return ShardDb::class;
    }

    /**
     * 分表规则定义
     * @return ModShardRule
     */
    public static function dbShardRule()
    {
        return new ModShardRule(3,'userId');
    }

    /**
     * 定义数据库标识
     * @return string
     */
    public static function dbKey()
    {
        return 'hehe_';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        // '{{%users_:shard}}',:shard 分区号
        return '{{%admin_users_info}}';

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
