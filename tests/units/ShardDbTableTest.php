<?php
namespace horm\tests\units;

use horm\tests\common\AdminUserinfoShardDbTableEntity;
use horm\tests\TestCase;

// 分表
class ShardDbTableTest extends TestCase
{
    protected function setUp()
    {

        static::makeDb('hehe','hehe_test','hehe.sql');
        static::makeDb('hehe_0','hehe_test0','');
        static::makeDb('hehe_1','hehe_test1','');
        static::makeDb('hehe_2','hehe_test2','');

        static::createShardTable('hehe_test0',0,'shard_table.sql');
        static::createShardTable('hehe_test1',1,'shard_table.sql');
        static::createShardTable('hehe_test2',2,'shard_table.sql');
    }

    protected function tearDown()
    {
        static::clearDb();
    }


    public function testAdd()
    {

        $number = AdminUserinfoShardDbTableEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $db_name = 'hehe_' . AdminUserinfoShardDbTableEntity::dbShardRule()->getSequence(null,1);
        $this->assertTrue($number == 1);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdminUserinfoShardDbTableEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserinfoShardDbTableEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdminUserinfoShardDbTableEntity::getLastId();

        $number = AdminUserinfoShardDbTableEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>$user_info_id,'userId'=>1]);

        $this->assertTrue($number == 1 );

    }

    public function testDelete()
    {

        AdminUserinfoShardDbTableEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdminUserinfoShardDbTableEntity::getLastId();

        $delete_number = AdminUserinfoShardDbTableEntity::setWhere(['id'=>$user_info_id,'userId'=>1])->deleteOne();

        $this->assertTrue($delete_number == 1);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        AdminUserinfoShardDbTableEntity::addAll($datas);
        $admminUserinfos =  AdminUserinfoShardDbTableEntity::setWhere(['userId'=>[1,2]])->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }

    // 指定分区
    public function testSetShard()
    {
        $number = AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user = AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxx');

        AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);
        $user = AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxok');

        AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);
        $user = AdminUserinfoShardDbTableEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue(empty($user));
    }



}
