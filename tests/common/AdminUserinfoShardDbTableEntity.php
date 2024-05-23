<?php
namespace horm\tests\common;

use horm\Entity;
use horm\QueryTable;
use horm\shard\rule\ModShardRule;
use horm\shard\ShardDb;
use horm\shard\ShardDbTable;
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
class AdminUserinfoShardDbTableEntity extends Entity
{

    public static function dbSession()
    {
        return TestCase::getDbsession();
    }

    /**
     * ShardDbTable 分库分表
     * @return string
     */
    public static function queryTable()
    {
        return ShardDbTable::class;
    }

    /**
     * 分库规则规则定义
     * @return ModShardRule
     */
    public static function dbShardRule()
    {
        return new ModShardRule(3,'userId');
    }

    /**
     * 分表规则规则定义
     * @return ModShardRule
     */
    public static function tbShardRule()
    {
        return new ModShardRule(3,'userId');
    }

    /**
     * 定义数据库标识
     * @return string
     */
    public static function dbKey()
    {
        // '{{%hehe_:shard}}',:shard 分区号
        return 'hehe_';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        // '{{%users_:shard}}',:shard 分区号
        return '{{%admin_users_info_}}';

    }

    /**
     * 是否自增定义
     * @return string
     */
    public static function autoIncrement()
    {
        return true;
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
