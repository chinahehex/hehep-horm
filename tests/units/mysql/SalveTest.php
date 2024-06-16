<?php
namespace horm\tests\units\mysql;

use horm\tests\common\AdminUserinfoForceSlaveEntity;
use horm\tests\common\AdminUserinfoSlaveEntity;
use horm\tests\TestCase;

// 主从数据库
class SalveTest extends TestCase
{
    protected static $db_driver = 'mysql';

    protected function setUp()
    {
        parent::setUp();
        static::makeDb('hehe_master','hehe_master','hehe.sql',['driver'=>'mysql','onSlave'=>true,'slaves'=>['hehe_slave1','hehe_slave2']]);
        static::makeDb('hehe_slave1','hehe_slave1','hehe.sql',['driver'=>'mysql',]);
        static::makeDb('hehe_slave2','hehe_slave2','hehe.sql',['driver'=>'mysql',]);

    }

    protected function tearDown()
    {
        static::clearDb();
    }


    public function testAdd()
    {

        $number = AdminUserinfoSlaveEntity::addOne(['username'=>"hehe5",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($number == 1);
        // 批量添加
        $datas = [
            ['username'=>"hehe6",'password'=>'123123','tel'=>'135xxxxxxxb','realName'=>'hehex'],
            ['username'=>"hehe7",'password'=>'123123','tel'=>'135xxxxxxxc','realName'=>'hehex'],
        ];

        $result = AdminUserinfoSlaveEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserinfoSlaveEntity::addOne(['username'=>"hehe8",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $id = AdminUserinfoSlaveEntity::getLastId();
        $number = AdminUserinfoSlaveEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>$id]);

        $this->assertTrue($number == 1 );

    }

    public function testDelete()
    {

        AdminUserinfoSlaveEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $id = AdminUserinfoSlaveEntity::getLastId();

        $delete_number = AdminUserinfoSlaveEntity::setWhere(['id'=>$id])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {

        $user =  AdminUserinfoSlaveEntity::setWhere(['username'=>'hehe1'])->fetchOne();
        $this->assertTrue(!empty($user));

        $users =  AdminUserinfoSlaveEntity::setWhere(['id'=>['>=',2] ])->fetchAll();
        $this->assertTrue(!empty($users));
    }

    public function testTran()
    {
        $this->hdbsession->beginTransaction();
        AdminUserinfoSlaveEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $id = AdminUserinfoSlaveEntity::getLastId();
        $user =  AdminUserinfoSlaveEntity::setWhere(['id'=>$id])->fetchOne();
        $this->assertTrue(empty($user));

        $user =  AdminUserinfoSlaveEntity::setWhere(['id'=>$id])->asMaster()->fetchOne();
        $this->assertTrue(!empty($user));

        $this->hdbsession->commitTransaction();
    }

    public function testEntitySlave()
    {
        AdminUserinfoForceSlaveEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $id = AdminUserinfoForceSlaveEntity::getLastId();
        $user =  AdminUserinfoForceSlaveEntity::setWhere(['id'=>$id])->fetchOne();
        $this->assertTrue(empty($user));

        $user =  AdminUserinfoForceSlaveEntity::setWhere(['id'=>$id])->asMaster()->fetchOne();
        $this->assertTrue(!empty($user));
    }

}
