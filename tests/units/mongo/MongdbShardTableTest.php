<?php
namespace horm\tests\units\mongo;

use horm\tests\common\mongo\AdminUserinfoShardTbNosqlEntity;
use horm\tests\TestCase;

// 分表
class MongdbShardTableTest extends TestCase
{
    protected static $db_driver = 'mongodb';

    protected function setUp()
    {

        static::makeDb('hehe1','hehe_test','mongo.php',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017','username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);

    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testAdd()
    {

        $number = AdminUserinfoShardTbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $table_name = 'web_admin_users_info_' . AdminUserinfoShardTbNosqlEntity::tbShardRule()->getShardId(null,1);
        $this->assertTrue($number == 1 && strpos(AdminUserinfoShardTbNosqlEntity::getLastCmd(),$table_name) !== false);

        // 批量添加
        $datas = [
            ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
        ];

        $result = AdminUserinfoShardTbNosqlEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {

        AdminUserinfoShardTbNosqlEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
        $number = AdminUserinfoShardTbNosqlEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>1]);
        $table_name = 'web_admin_users_info_' . AdminUserinfoShardTbNosqlEntity::tbShardRule()->getShardId(null,1);
        $this->assertTrue($number == 1 && strpos(AdminUserinfoShardTbNosqlEntity::getLastCmd(),$table_name) !== false);

    }

    public function testDelete()
    {

        AdminUserinfoShardTbNosqlEntity::addOne(['userId'=>2,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

        $delete_number = AdminUserinfoShardTbNosqlEntity::setWhere(['userId'=>2])->deleteOne();

        $table_name = 'web_admin_users_info_' . AdminUserinfoShardTbNosqlEntity::tbShardRule()->getShardId(null,2);
        $this->assertTrue($delete_number == 1 && strpos(AdminUserinfoShardTbNosqlEntity::getLastCmd(),$table_name) !== false);

    }

    public function testQuery()
    {
        // 批量添加
        $datas = [
            ['userId'=>1,'tel'=>'135xxxxxxxb','realName'=>'hehex1','sex'=>'男','education'=>'高中'],
            ['userId'=>2,'tel'=>'135xxxxxxxc','realName'=>'hehex2','sex'=>'男','education'=>'高中'],
            ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex3','sex'=>'男','education'=>'高中'],
            ['userId'=>4,'tel'=>'135xxxxxxxc','realName'=>'hehex4','sex'=>'男','education'=>'高中'],
            ['userId'=>5,'tel'=>'135xxxxxxxc','realName'=>'hehex5','sex'=>'男','education'=>'高中'],
            ['userId'=>6,'tel'=>'135xxxxxxxc','realName'=>'hehex6','sex'=>'男','education'=>'高中'],
            ['userId'=>7,'tel'=>'135xxxxxxxc','realName'=>'hehex7','sex'=>'男','education'=>'高中'],
        ];

        AdminUserinfoShardTbNosqlEntity::addAll($datas);
        $admminUserinfos =  AdminUserinfoShardTbNosqlEntity::setWhere(['userId'=>['in',[1,7]]])->setLimit(7)->fetchAll();
        $this->assertTrue(count($admminUserinfos) == 2
            && $admminUserinfos[0]['tel'] == '135xxxxxxxb'
            && $admminUserinfos[1]['tel'] == '135xxxxxxxc'
        );

    }





}
