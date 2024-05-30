<?php
namespace horm\tests\units\pgsql;

use horm\tests\common\mongo\AdminUserinfoShardDbTbNosqlEntity;
use horm\tests\TestCase;

// 分表
class MongodbShardDbTbTest extends TestCase
{
    protected static $db_driver = 'mongodb';

    protected function setUp()
    {

        static::makeDb('hehe_0','hehe_test0','',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017','username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);
        static::makeDb('hehe_1','hehe_test1','',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017','username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);
        static::makeDb('hehe_2','hehe_test2','',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017','username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);

    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testAdd()
    {

        $number = AdminUserinfoShardDbTbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $db_name = 'hehe_' . AdminUserinfoShardDbTbNosqlEntity::dbShardRule()->getSequence(null,1);
        $this->assertTrue($number == 1);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdminUserinfoShardDbTbNosqlEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserinfoShardDbTbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

        $number = AdminUserinfoShardDbTbNosqlEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>1]);

        $this->assertTrue($number == 1 );

    }

    public function testDelete()
    {

        AdminUserinfoShardDbTbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

        $delete_number = AdminUserinfoShardDbTbNosqlEntity::setWhere(['userId'=>1])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        AdminUserinfoShardDbTbNosqlEntity::addAll($datas);
        $admminUserinfos =  AdminUserinfoShardDbTbNosqlEntity::setWhere(['userId'=>[1,2]])->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }

    // 指定分区
    public function testSetShard()
    {
        $number = AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user = AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxx');

        AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);
        $user = AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxok');

        AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);
        $user = AdminUserinfoShardDbTbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue(empty($user));
    }




}
