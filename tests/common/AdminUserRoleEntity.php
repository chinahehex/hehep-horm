<?php
namespace horm\tests\common;

use horm\Entity;
use horm\tests\TestCase;

/**
 * 管理人员角色数据实体
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 * hehex
 * @property  $id
 * @property  $roleName
 * @property  $ctime
 * @property  $status
 * @property  $webAdminUserRolecol
 * hehex
 */
class AdminUserRoleEntity extends Entity
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
        return '{{%admin_user_role}}';
    }

    /**
     * 是否自增定义
     * @return string
     */
    public static function autoIncrement()
    {
        return 'web_admin_user_role_seq';
    }

    /**
     * 定义表主键字段
     * @return string
     */
    public static function pk()
    {
        return 'id';
    }

    public static function getPrmission()
    {
        return static::hasMany(AdminRolePrmissionEntity::class,['roleId'=>'id'])->setField('id,roleId');
    }
}
