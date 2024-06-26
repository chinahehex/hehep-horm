<?php
namespace horm\tests\units\mongo;

use horm\QueryTable;
use horm\tests\common\mongo\AdminUserinfoNosqlEntity;
use horm\tests\TestCase;

class MongoCurdTest extends TestCase
{
    protected static $db_driver = 'mongodb';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_test','mongo.php',
            ['driver'=>'mongo','type'=>'mongodb','port'=>'27017','username'=>static::$db_config['mongo_user'],'password'=>static::$db_config['mongo_pwd']]);
    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testAdd()
    {
        $number = AdminUserinfoNosqlEntity::addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($number == 1);

        $adminUserEntity = AdminUserinfoNosqlEntity::setWhere(['username'=>"hehe3"])
            ->asArray()
            ->fetchOne();

        $this->assertEquals("hehe3",$adminUserEntity['username']);

        $adminUserEntity = new AdminUserinfoNosqlEntity();
        $adminUserEntity->username = 'hehe4';
        $adminUserEntity->password = '123123';
        $adminUserEntity->tel = '135xxxx' . rand(10000,99999);
        $adminUserEntity->realName = 'hehex';
        $number = $adminUserEntity->add();

        $this->assertTrue($number == 1);
        $adminUserEntity = AdminUserinfoNosqlEntity::setWhere(['username'=>"hehe4"])
            ->asArray()
            ->fetchOne();

        $this->assertEquals("hehe4",$adminUserEntity['username']);

        // 批量添加
        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        $result = AdminUserinfoNosqlEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {
        $number = AdminUserinfoNosqlEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['username'=>'admin']);
        $this->assertEquals(1,$number);

        $tel = '135axxx' .  rand(10000,99999);
        $adminUserEntity = new AdminUserinfoNosqlEntity();
        $adminUserEntity->tel = $tel;
        $adminUserEntity->id = 1;
        $number = $adminUserEntity->update();
        $this->assertEquals(1,$number);

        $number = AdminUserinfoNosqlEntity::updateAll(['tel'=>'136XXXX' . rand(10000,99999)],['id'=>[1,2]]);

        $this->assertEquals(2,$number);
    }

    public function testQuery()
    {
        $adminUserEntity = AdminUserinfoNosqlEntity::get(1);
        $this->assertEquals("hehe1",$adminUserEntity->username);
        $this->assertEquals(1,$adminUserEntity['id']);

//        $adminUserEntity = AdminUserinfoNosqlEntity::setWhere(['id'=>2])->fetchOne();
//        $this->assertEquals("admin",$adminUserEntity->username);
//        $this->assertEquals(2,$adminUserEntity->id);
//
//        $userEntitys = AdminUserinfoNosqlEntity::fetchAll(['id'=>[1,2]]);
//        $this->assertEquals(2,count($userEntitys));
//
//        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2]])->fetchAll();
//        $this->assertEquals(2,count($userEntitys));
//
//        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2]])->asArray()->fetchAll();
//        $this->assertEquals(2,count($userEntitys));
    }

    public function testDelete()
    {
        $delete_number = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->deleteOne();
        $this->assertEquals(1,$delete_number);

        // 删除实体
        $userEntity = AdminUserinfoNosqlEntity::get(2);
        $delete_number = $userEntity->delete();
        $this->assertEquals(1,$delete_number);

        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        AdminUserinfoNosqlEntity::addAll($datas);

        $delete_number = AdminUserinfoNosqlEntity::setWhere(['username'=>['admin1','admin2']])->deleteAll();
        $this->assertEquals(2,$delete_number);
    }

    public function testMoreCondtionQuery()
    {
        $where = ['status'=>1,'tel'=>['like','135%']];
        $userEntitys = AdminUserinfoNosqlEntity::setWhere($where)->fetchAll();
        $this->assertEquals(2,count($userEntitys));


        $where = ['or','status'=>1,'tel'=>"13564768841"];
        $userEntitys = AdminUserinfoNosqlEntity::setWhere($where)->fetchAll();

        $this->assertEquals(4,count($userEntitys));
    }

    // 嵌套条件查询
    public function testnestCondtionQuery()
    {
        $where = ['and','tel'=>['=','13564768841'],['or','roleId'=>['in',[2,3]],'status'=>1]];
        $userEntitys = AdminUserinfoNosqlEntity::setWhere($where)->fetchAll();
        $this->assertEquals(3,count($userEntitys));


        $where = ['and','tel'=>['like','135%'],['or','roleId'=>['in',[2,3]],'status'=>1,['and','username'=>'hehe1','realName'=>'hehe'] ]];
        $userEntitys = AdminUserinfoNosqlEntity::setWhere($where)->fetchAll();
        $this->assertEquals(4,count($userEntitys));


        $where = ['and','tel'=>['=','13564768841'],['and',['roleId'=>['in',[2,3,4]],'status'=>1]]];
        $userEntitys = AdminUserinfoNosqlEntity::setWhere($where)->fetchAll();
        $this->assertEquals(1,count($userEntitys));

    }

    public function testOrderQuery()
    {
        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['id'=>SORT_DESC])->fetchAll();
        $user_id = 4;
        foreach ($userEntitys as $user) {
            $this->assertEquals($user_id,$user['id']);
            $user_id--;
        }

        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['id'=>SORT_ASC])->fetchAll();
        $user_id = 1;
        foreach ($userEntitys as $user) {
            $this->assertEquals($user_id,$user['id']);
            $user_id++;
        }


        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['status'=>SORT_DESC,'roleId'=>SORT_ASC])->fetchAll();
        $index = 0;
        //4 1 3 2
        $ids = [1,2,4,3];
        foreach ($userEntitys as $user) {
            $user_id = $ids[$index];
            $this->assertEquals($user_id,intval($user['id']));
            $index++;
        }
    }

    public function testTernaryQuery()
    {
        $userEntitys = AdminUserinfoNosqlEntity::setWhere('id',[1,2],'in')->fetchAll();
        $this->assertEquals(2,count($userEntitys));

        $userEntitys = AdminUserinfoNosqlEntity::setWhere('id',1,'=')->fetchOne();
        $this->assertEquals('hehe1',$userEntitys['username']);
    }

    public function testRange()
    {
        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['roleId'=>['and', ['>=',1],['<=',3] ]])->fetchAll();
        $this->assertTrue(count($userEntitys) == 3);
    }

    public function testClosureQuery()
    {
        $userEntitys = AdminUserinfoNosqlEntity::setWhere(function(QueryTable $queryTable){
            $queryTable->setWhere(['id'=>['>=',2]]);
        })->fetchAll();

        $this->assertEquals(3,count($userEntitys));
    }

    public function testColumnQuery()
    {
        $user = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField("id,tel")->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $user = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField(['id','tel'])->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField("id,tel")->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $userEntitys = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField(['id','tel'])->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $user = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField(['id'=>'user_id','tel'])->fetchOne();
        $this->assertTrue((isset($user['user_id']) && isset($user['tel']) && !isset($user['id'])));

        $user = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField(['id as user_id','tel'])->fetchOne();
        $this->assertTrue((isset($user['user_id']) && isset($user['tel']) && !isset($user['id'])));

        $user = AdminUserinfoNosqlEntity::setWhere(['id'=>1])->setField(['id'=>['alias'=>'user_id'],'tel'])->fetchOne();
        $this->assertTrue((isset($user['user_id']) && isset($user['tel']) && !isset($user['id'])));

    }

    public function testScope()
    {
        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective')->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));

        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective,admin')->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));


        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->effective()->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));

        $users = AdminUserinfoNosqlEntity::effective()->setWhere(['id'=>[1,2,3,4]])->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
    }

//    public function testAsQuery()
//    {
//        $users_query = AdminUserinfoNosqlEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->asQuery()->fetchAll();
//        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>['in',$users_query]])->fetchAll();
//        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
//    }

    public function testLimit()
    {
        $users = AdminUserinfoNosqlEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setLimit(1)->fetchAll();
        $this->assertTrue(count($users) == 1 && in_array($users[0]['id'],[1,2]));
    }

//    public function testDistinct()
//    {
//        $users = AdminUserNosqlEntity::setField('tel')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setDistinct()->fetchAll();
//        $this->assertTrue(count($users) == 2 && in_array($users[0]['tel'],['13564768841','13564768842'])&& in_array($users[1]['tel'],['13564768841','13564768842']));
//    }

    public function testJoin()
    {
        $users = AdminUserinfoNosqlEntity::setAlias('u')
            ->setField('role.roleName as roleName')
            ->setJoin(['{{%admin_user_role}}','role'],['roleId'=>['raw','role.id']])
            ->setWhere(['role.status'=>1])
            ->setWhere(['id'=>[1,2]])->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['roleName']));
        }
    }

    public function testWith()
    {
        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setWith('role')->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }

        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setAlias('adu')->setInnerWith(['role'=>function(QueryTable $query){
            /** @var QueryTable $query **/
            $query->setWhere(['role.status'=>1]);

        }],false)->fetchAll();

        $this->assertTrue(count($users) == 3);

        $users = AdminUserinfoNosqlEntity::setWhere(['id'=>[1,2,3,4]])->setAlias('adu')->setWith('role',true)->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }
    }

    public function testAlias()
    {
        $users = AdminUserinfoNosqlEntity::setWhere(['#.id'=>[1,2,3,4]])->setAlias('adu')->setWith('role',true)->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }
    }

    public function testPolymerization()
    {
        $count = AdminUserinfoNosqlEntity::count();
        $this->assertTrue($count == 4);

        $count = AdminUserinfoNosqlEntity::asArray()->count('id');
        $this->assertTrue($count == 4);

        // 查询最大值
        $max = AdminUserinfoNosqlEntity::asArray()->queryMax('id');
        $this->assertTrue($max == 4);

        $max = AdminUserinfoNosqlEntity::queryMax('id');
        $this->assertTrue($max == 4);

        $min = AdminUserinfoNosqlEntity::queryMin('id');
        $this->assertTrue($min == 1);

        $sum = AdminUserinfoNosqlEntity::querySum('id');
        $this->assertTrue($sum == 10);

        $sum = AdminUserinfoNosqlEntity::setWhere(['status'=>1])->querySum('id');
        $this->assertTrue($sum == 3);
    }

    // 分组查询
    public function testGroup()
    {

        $users = AdminUserinfoNosqlEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel')->fetchAll();

        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[1]['tel']));

        $users = AdminUserinfoNosqlEntity::setField('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->fetchAll();
        $this->assertTrue(count($users) == 3 && isset($users[0]['tel']) && isset($users[0]['status']));

        $users = AdminUserinfoNosqlEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->setHaving(['status'=>0])->fetchAll();
        $this->assertTrue(count($users) == 1 && isset($users[0]['tel']));

        $users = AdminUserinfoNosqlEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setAndHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserinfoNosqlEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setOrHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();

        $this->assertTrue(count($users) == 4);

        $users = AdminUserinfoNosqlEntity::setField('tel,count(1) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[0]['total']));

        $users = AdminUserinfoNosqlEntity::setField('tel,count(1) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->setHaving(['total'=>['>',1]])->fetchAll();
        $this->assertTrue(count($users) == 1 && isset($users[0]['tel']) && $users[0]['total'] == 3);

        $users = AdminUserinfoNosqlEntity::setField('tel,sum(roleId) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[0]['total']));

        $users = AdminUserinfoNosqlEntity::setField('tel,avg(roleId) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[0]['total']));

        $users = AdminUserinfoNosqlEntity::setField('tel,max(roleId) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[0]['total']));

        $users = AdminUserinfoNosqlEntity::setField('tel,min(roleId) as total')->setWhere(['id'=>[1,2,3,4]])
            ->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2 && isset($users[0]['tel']) && isset($users[0]['total']));

    }

    public function testPage()
    {
        $users = AdminUserinfoNosqlEntity::asArray()->setWhere(['id'=>[1,2,3,4]])->setLimit(2)->setOffset(1)->fetchAll();
        $this->assertTrue(count($users) == 2);
    }

    public function testTransaction()
    {
        AdminUserinfoNosqlEntity::beginTransaction();
        $data = ['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserinfoNosqlEntity::addOne($data);
        AdminUserinfoNosqlEntity::commitTransaction();
        $user = AdminUserinfoNosqlEntity::setWhere(['username'=>'hehe3'])->fetchOne();
        $this->assertTrue($user['username'] == 'hehe3');

        AdminUserinfoNosqlEntity::beginTransaction();
        $data = ['username'=>"hehe4",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserinfoNosqlEntity::addOne($data);
        AdminUserinfoNosqlEntity::rollbackTransaction();
        $user = AdminUserinfoNosqlEntity::setWhere(['username'=>'hehe4'])->fetchOne();
        //var_dump($user);
        $this->assertTrue(empty($user));


        $this->hdbsession->beginTransaction();
        $data = ['username'=>"hehe5",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserinfoNosqlEntity::addOne($data);
        $this->hdbsession->commitTransaction();
        $user = AdminUserinfoNosqlEntity::setWhere(['username'=>'hehe5'])->fetchOne();
        $this->assertTrue($user['username'] == 'hehe5');

        $this->hdbsession->beginTransaction();
        $data = ['username'=>"hehe6",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserinfoNosqlEntity::addOne($data);
        $this->hdbsession->rollbackTransaction();
        $user = AdminUserinfoNosqlEntity::setWhere(['username'=>'hehe6'])->fetchOne();
        $this->assertTrue(empty($user));

    }

    public function testDbconn()
    {
        $db_conn = $this->hdbsession->getDbConnection('hehe1');
        $number = $db_conn->insert('web_admin_users',['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($number == 1);

        // 批量插入
        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        $number = $db_conn->insertAll('web_admin_users',$datas);
        $this->assertTrue($number == 2);

        // 更新
        $number = $db_conn->update('web_admin_users',['$set'=>['tel'=>'135xxxx' .  rand(10000,99999)]],['username'=>'admin']);
        $this->assertTrue($number == 1);

        $number = $db_conn->delete('web_admin_users',['username'=>'hello']);
        $this->assertTrue($number == 1);

        // 查询
        $user =$db_conn->fetchOne('web_admin_users',['id'=>1]);
        $this->assertTrue(!empty($user) && $user['username'] == 'hehe1');

        // 查询
        $users = $db_conn->fetchAll('web_admin_users',['id'=>['$in'=>[1,2]] ]);
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $users = $db_conn->fetchAll('web_admin_users',['id'=>['$in'=>[1,2]]],['sort'=>['id'=>-1]]);
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'admin' &&
            $users[1]['username'] == 'hehe1'
        );

    }















}
