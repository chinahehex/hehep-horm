<?php
namespace horm\tests\units\mysql;

use horm\tests\common\AdminUserinfoShardDbEntity;
use horm\tests\TestCase;

// 分表
class ShardDbTest extends TestCase
{
    protected static $db_driver = 'mysql';

    protected function setUp()
    {
        static::makeDb('hehe','hehe_test','hehe.sql',['driver'=>'mysql',]);
        static::makeDb('hehe_0','hehe_test0','',['driver'=>'mysql',]);
        static::makeDb('hehe_1','hehe_test1','',['driver'=>'mysql',]);
        static::makeDb('hehe_2','hehe_test2','',['driver'=>'mysql',]);

        static::createMysqlShardTable('hehe_test0','shard_db.sql',"0");
        static::createMysqlShardTable('hehe_test1','shard_db.sql',"1");
        static::createMysqlShardTable('hehe_test2','shard_db.sql',"2");
    }

    protected function tearDown()
    {
        static::clearDb();
    }


    public function testAdd()
    {

        $number = AdminUserinfoShardDbEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $db_name = 'hehe_' . AdminUserinfoShardDbEntity::dbShardRule()->getShardId(null,1);
        $this->assertTrue($number == 1);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdminUserinfoShardDbEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserinfoShardDbEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdminUserinfoShardDbEntity::getLastId();

        $number = AdminUserinfoShardDbEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>$user_info_id,'userId'=>1]);

        $this->assertTrue($number == 1 );

    }

    public function testDelete()
    {

        AdminUserinfoShardDbEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdminUserinfoShardDbEntity::getLastId();

        $delete_number = AdminUserinfoShardDbEntity::setWhere(['id'=>$user_info_id,'userId'=>1])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        AdminUserinfoShardDbEntity::addAll($datas);
        $admminUserinfos =  AdminUserinfoShardDbEntity::setWhere(['userId'=>[1,2]])->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }

    // 指定分区
    public function testSetShard()
    {
        $number = AdminUserinfoShardDbEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user = AdminUserinfoShardDbEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxx');

        AdminUserinfoShardDbEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);
        $user = AdminUserinfoShardDbEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxok');

        AdminUserinfoShardDbEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);
        $user = AdminUserinfoShardDbEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue(empty($user));
    }



}
