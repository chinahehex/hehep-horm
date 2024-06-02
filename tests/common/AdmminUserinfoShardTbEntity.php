<?php
namespace horm\tests\common;

use horm\Entity;
use horm\QueryTable;
use horm\shard\rule\ModShardRule;
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
class AdmminUserinfoShardTbEntity extends Entity
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
        return ShardTable::class;
    }

    /**
     * 分表规则定义
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
        return 'hehe1';
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
        return 'web_admin_users_info_seq';
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
