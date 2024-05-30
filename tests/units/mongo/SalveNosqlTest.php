<?php
namespace horm\tests\units\mongo;

use horm\tests\common\mongo\AdminUserForceSlaveNosqlEntity;
use horm\tests\common\mongo\AdminUserSlaveNosqlEntity;
use horm\tests\TestCase;

// 主从数据库
class SalveNosqlTest extends TestCase
{
    protected static $db_driver = 'mongo';

    protected function setUp()
    {
        static::makeDb('hehe_master','hehe_master','mongo.php',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017',
                'onSlave'=>true,'slaves'=>['hehe_slave1','hehe_slave2'],
                'username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);

        static::makeDb('hehe_slave1','hehe_slave1','mongo.php',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017',
                'username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);

        static::makeDb('hehe_slave2','hehe_slave2','mongo.php',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017',
                'username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);

    }

    protected function tearDown()
    {
       static::clearDb();
    }


    public function testAdd()
    {

        $number = AdminUserSlaveNosqlEntity::addOne(['username'=>"hehe5",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($number == 1);
        // 批量添加
        $datas = [
            ['username'=>"hehe6",'password'=>'123123','tel'=>'135xxxxxxxb','realName'=>'hehex'],
            ['username'=>"hehe7",'password'=>'123123','tel'=>'135xxxxxxxc','realName'=>'hehex'],
        ];

        $result = AdminUserSlaveNosqlEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserSlaveNosqlEntity::addOne(['username'=>"hehe8",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);

        $number = AdminUserSlaveNosqlEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['username'=>'hehe8']);

        $this->assertTrue($number == 1 );

    }

    public function testDelete()
    {

        AdminUserSlaveNosqlEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);

        $delete_number = AdminUserSlaveNosqlEntity::setWhere(['username'=>"hehe9"])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {

        $user =  AdminUserSlaveNosqlEntity::setWhere(['username'=>'hehe1'])->fetchOne();
        $this->assertTrue(!empty($user));

        $users =  AdminUserSlaveNosqlEntity::setWhere(['id'=>['>=',2] ])->fetchAll();
        $this->assertTrue(!empty($users));
    }

    public function testEntitySlave()
    {
        AdminUserForceSlaveNosqlEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $user =  AdminUserForceSlaveNosqlEntity::setWhere(['username'=>"hehe9"])->fetchOne();
        $this->assertTrue(empty($user));

        $user =  AdminUserForceSlaveNosqlEntity::setWhere(['username'=>"hehe9"])->asMaster()->fetchOne();
        $this->assertTrue(!empty($user));
    }

//    public function testTran()
//    {
//        AdminUserSlaveNosqlEntity::beginTransaction();
//        AdminUserSlaveNosqlEntity::addOne(['username'=>"hehe9",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
//        $user =  AdminUserSlaveNosqlEntity::setWhere(['username'=>"hehe9"])->fetchOne();
//        $this->assertTrue(empty($user));
//
//        $user =  AdminUserSlaveNosqlEntity::setWhere(['username'=>"hehe9"])->asMaster()->fetchOne();
//        $this->assertTrue(!empty($user));
//
//        AdminUserSlaveNosqlEntity::commitTransaction();
//    }



}
