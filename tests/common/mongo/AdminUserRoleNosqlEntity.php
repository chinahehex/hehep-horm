<?php
namespace horm\tests\common\mongo;

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
class AdminUserRoleNosqlEntity extends Entity
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
