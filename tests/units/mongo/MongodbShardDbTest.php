<?php
namespace horm\tests\units\mongo;

use horm\tests\common\mongo\AdminUserinfoShardDbNosqlEntity;
use horm\tests\TestCase;

// 分表
class MongodbShardDbTest extends TestCase
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

        $number = AdminUserinfoShardDbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $this->assertTrue($number == 1);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdminUserinfoShardDbNosqlEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testDelete()
    {

        AdminUserinfoShardDbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

        $delete_number = AdminUserinfoShardDbNosqlEntity::setWhere(['userId'=>1])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        AdminUserinfoShardDbNosqlEntity::addAll($datas);
        $admminUserinfos =  AdminUserinfoShardDbNosqlEntity::setWhere(['userId'=>[1,2]])->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }

    // 指定分区
    public function testSetShard()
    {
        $number = AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user = AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxx');

        AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);
        $user = AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxok');

        AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);
        $user = AdminUserinfoShardDbNosqlEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue(empty($user));
    }




}
