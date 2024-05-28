<?php
namespace horm\tests\units\mysql;

use horm\tests\common\AdminUserEntity;
use horm\tests\common\AdmminUserinfoEntity;
use horm\tests\TestCase;

// 分表
class ShardTableTest extends TestCase
{
    protected static $db_driver = 'mysql';

    protected function setUp()
    {
        static::makeDb('hehe1','hehe_test','hehe.sql');
        static::createMysqlShardTable('hehe_test','shard_table.sql',"0");
        static::createMysqlShardTable('hehe_test','shard_table.sql',"1");
        static::createMysqlShardTable('hehe_test','shard_table.sql',"2");
    }

    protected function tearDown()
    {
        static::clearDb();
    }


    public function testAdd()
    {

        $number = AdmminUserinfoEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $table_name = 'web_admin_users_info_' . AdmminUserinfoEntity::tbShardRule()->getSequence(null,1);
        $this->assertTrue($number == 1 && strpos(AdmminUserinfoEntity::getLastSql(),$table_name) !== false);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdmminUserinfoEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {
        AdminUserEntity::addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $userId = AdminUserEntity::getLastId();
        AdmminUserinfoEntity::addOne(['userId'=>$userId,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdmminUserinfoEntity::getLastId();

        $number = AdmminUserinfoEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>$user_info_id,'userId'=>$userId]);

        $table_name = 'web_admin_users_info_' . AdmminUserinfoEntity::tbShardRule()->getSequence(null,$userId);
        $this->assertTrue($number == 1 && strpos(AdmminUserinfoEntity::getLastSql(),$table_name) !== false);

    }

    public function testDelete()
    {
        AdminUserEntity::addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $userId = AdminUserEntity::getLastId();
        AdmminUserinfoEntity::addOne(['userId'=>$userId,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user_info_id = AdmminUserinfoEntity::getLastId();

        $delete_number = AdmminUserinfoEntity::setWhere(['id'=>$user_info_id,'userId'=>$userId])->deleteOne();

        $table_name = 'web_admin_users_info_' . AdmminUserinfoEntity::tbShardRule()->getSequence(null,$userId);
        $this->assertTrue($delete_number == 1 && strpos(AdmminUserinfoEntity::getLastSql(),$table_name) !== false);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        AdmminUserinfoEntity::addAll($datas);
        $admminUserinfos =  AdmminUserinfoEntity::setWhere(['userId'=>[1,2]])->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }

    // 指定分区
    public function testSetShard()
    {
        $number = AdmminUserinfoEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $user = AdmminUserinfoEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxx');

        AdmminUserinfoEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);
        $user = AdmminUserinfoEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue($user['tel'] == '135xxxxxxxok');

        AdmminUserinfoEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);
        $user = AdmminUserinfoEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();
        $this->assertTrue(empty($user));
    }



}
