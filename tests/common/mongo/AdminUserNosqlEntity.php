<?php
namespace horm\tests\common;

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
class AdminUserNosqlEntity extends Entity
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
        return 'hehe1';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        return '[[%admin_users]]';
        //return '{{%admin_users}}ooo';
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

    public static function getRole()
    {
        return static::hasOne(AdminUserRoleEntity::class,['id'=>'roleId']);
    }

    public static function scopeEffective(QueryTable $queryTable,$status=2)
    {
        $queryTable->setWhere(['status'=>1]);
    }

    public static function scopeAdmin(QueryTable $queryTable)
    {
        $queryTable->setWhere(['roleId'=>['>=',0]]);
    }

}
