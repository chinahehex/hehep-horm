<?php
namespace horm\tests\units\oci;

use horm\tests\common\AdminUserEntity;
use horm\tests\units\mysql\CurdTest;

class OciCurdTest extends CurdTest
{
    protected static $db_driver = 'oci';
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_case','oci_hehe.sql',['driver'=>'oci','port'=>'1521',
            'username'=>static::$db_config['oci_user'],'password'=>static::$db_config['oci_pwd']]);
    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testAdd()
    {
        $number = AdminUserEntity::addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $id = AdminUserEntity::getLastId();
        $this->assertTrue($number == 1 && $id > 0);

        $adminUserEntity = AdminUserEntity::setWhere(['username'=>"hehe3"])
            ->asArray()
            ->fetchOne();

        $this->assertTrue($adminUserEntity['username'] == "hehe3");

        $adminUserEntity = new AdminUserEntity();
        $adminUserEntity->username = 'hehe4';
        $adminUserEntity->password = '123123';
        $adminUserEntity->tel = '135xxxx' . rand(10000,99999);
        $adminUserEntity->realName = 'hehex';
        $adminUserEntity->add();
        $userId = $adminUserEntity->id;

        $this->assertTrue($userId > 0);
        $adminUserEntity = AdminUserEntity::setWhere(['username'=>"hehe4"])
            ->asArray()
            ->fetchOne();

        $this->assertEquals("hehe4",$adminUserEntity['username']);

//        $datas = [
//            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
//            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
//        ];
//
//        $result = AdminUserEntity::addAll($datas);
//        $this->assertTrue($result == 2);
    }

    public function testDelete()
    {
        $delete_number = AdminUserEntity::setWhere(['id'=>1])->deleteOne();
        $this->assertEquals(1,$delete_number);

        // 删除实体
        $userEntity = AdminUserEntity::get(2);
        $delete_number = $userEntity->delete();
        $this->assertEquals(1,$delete_number);

//        $datas = [
//            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
//            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
//        ];
//
//        AdminUserEntity::addAll($datas);

        $number = AdminUserEntity::addOne(['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]);
        $number = AdminUserEntity::addOne(['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]);

        $delete_number = AdminUserEntity::setWhere(['username'=>['admin1','admin2']])->deleteAll();
        $this->assertEquals(2,$delete_number);
    }

    public function testMoreCondtionQuery()
    {
        parent::testMoreCondtionQuery();
    }

    public function testColumnQuery()
    {
        $user = AdminUserEntity::setWhere(['id'=>1])->setField("id,tel")->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $user = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel'])->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField("id,tel")->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel'])->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $user = AdminUserEntity::setWhere(['id'=>1])->setField(['id'=>'user_id','tel'])->fetchOne();
        $this->assertTrue((isset($user['user_id']) && isset($user['tel']) && !isset($user['id'])));

    }

    public function testRawQuery()
    {
        $userEntitys = AdminUserEntity::queryCmd("select * from \"web_admin_users\" where \"id\" in (1,2)");
        $this->assertEquals(2,count($userEntitys));

        $number = AdminUserEntity::execCmd('update "web_admin_users" set "status"=1 where "id"=3');
        $this->assertEquals(1,$number);

        $userEntitys = $this->hdbsession->query('hehe1')->queryCmd('select * from "web_admin_users" where "id" in (1,2)');
        $this->assertEquals(2,count($userEntitys));

        $number = $this->hdbsession->query('hehe1')->execCmd('update "web_admin_users" set "status"=2 where "id"=3');
        $this->assertEquals(1,$number);
    }

    public function testParams()
    {
        $users = AdminUserEntity::setWhere('"realName" like :realName')->setParam(['realName'=>'%哈哈熊%'])->fetchAll();
        $this->assertTrue(count($users) == 1 && $users[0]['realName'] == '哈哈熊');
    }


    public function testDbconn()
    {
        $db_conn = $this->hdbsession->getDbConnection('hehe1');
        $number = $db_conn->insert('web_admin_users',['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($number == 1);

        // 批量插入
//        $datas = [
//            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
//            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
//        ];
//
//        $number = $db_conn->insertAll('web_admin_users',$datas);
//        $this->assertTrue($number == 2);

        // 更新
        $number =$db_conn->update('web_admin_users',['tel'=>'135xxxx' .  rand(10000,99999)],['username'=>'admin']);
        $this->assertTrue($number == 1);

        $number =$db_conn->delete('web_admin_users',['username'=>'hello']);
        $this->assertTrue($number == 1);

        // 查询
        $user =$db_conn->fetchOne('web_admin_users',['id'=>1]);
        $this->assertTrue(!empty($user) && $user['username'] == 'hehe1');

        // 查询
        $users = $db_conn->fetchAll('web_admin_users',['id'=>[1,2]]);
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $users = $db_conn->fetchAll('web_admin_users',['id'=>[1,2]],['order'=>['id'=>SORT_DESC]]);
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'admin' &&
            $users[1]['username'] == 'hehe1'
        );

        // 查询sql
        $users = $db_conn->querySql('select * from "web_admin_users" where "id" in (1,2)');
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $number = $db_conn->execSql('update "web_admin_users" set "tel"=\'135xxxxbbbb\' where "id" = 2');
        $this->assertTrue($number == 1);

        $number = $db_conn->execSql('update "web_admin_users" set "tel"=:tel where "id" = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
        $this->assertTrue($number == 1);
    }

    public function testTblemode()
    {
        $this->hdbsession->setDb('hehe1');

        $number = $this->hdbsession->setTable('web_admin_users')
            ->setData(['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'])->addOne();
        $this->assertTrue($number == 1);

        // 批量插入
//        $datas = [
//            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
//            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
//        ];
//
//        $number = $this->hdbsession->setTable('web_admin_users')
//            ->setData($datas)->addAll();
//        $this->assertTrue($number == 2);

        // 更新
        $number = $this->hdbsession->setTable('web_admin_users')
            ->setData(['tel'=>'135xxxx' .  rand(10000,99999)])
            ->setWhere(['username'=>'admin'])
            ->updateOne();
        $this->assertTrue($number == 1);

        $number = $this->hdbsession->setTable('web_admin_users')
            ->setData(['tel'=>'135xxxb' .  rand(10000,99999)])
            ->setWhere(['username'=>'admin'])
            ->updateAll();
        $this->assertTrue($number == 1);

        $number = $this->hdbsession->setTable('web_admin_users')
            ->setWhere(['username'=>'hello'])
            ->deleteOne();
        $this->assertTrue($number == 1);

        // 查询
        $user = $this->hdbsession->setTable('web_admin_users')
            ->setWhere(['id'=>1])->fetchOne();
        $this->assertTrue(!empty($user) && $user['username'] == 'hehe1');

        // 查询
        $users = $this->hdbsession->setTable('web_admin_users')
            ->setWhere(['id'=>[1,2]])
            ->fetchAll();
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $users = $this->hdbsession->setTable('web_admin_users')
            ->setWhere(['id'=>[1,2]])
            ->setOrder(['id'=>SORT_DESC])
            ->fetchAll();
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'admin' &&
            $users[1]['username'] == 'hehe1'
        );

        // 查询sql
        $users = $this->hdbsession->queryCmd('select * from "web_admin_users" where "id" in (1,2)');
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $number = $this->hdbsession->execCmd('update "web_admin_users" set "tel"=\'135xxxxbbbb\' where "id" = 2');
        $this->assertTrue($number == 1);

        $number = $this->hdbsession->execCmd('update "web_admin_users" set "tel"=:tel where "id" = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
        $this->assertTrue($number == 1);

    }










}
