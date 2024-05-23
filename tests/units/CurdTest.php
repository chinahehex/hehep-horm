<?php
namespace horm\tests\units;
use horm\QueryTable;
use horm\tests\common\AdminUserEntity;
use horm\tests\TestCase;

class CurdTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        static::makeDb('hehe1','hehe_test','hehe.sql');
    }

    protected function tearDown()
    {
        parent::tearDown();
        static::clearDb();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

    }

    public function testAdd()
    {
        AdminUserEntity::addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue(AdminUserEntity::getLastId() > 0);

        $adminUserEntity = AdminUserEntity::setWhere(['username'=>"hehe3"])
            ->asArray()
            ->fetchOne();

        $this->assertEquals("hehe3",$adminUserEntity['username']);

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

        // 批量添加
        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        $result = AdminUserEntity::addAll($datas);
        $this->assertTrue($result == 2);
    }

    public function testUpdate()
    {
        $number = AdminUserEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['username'=>'admin']);
        $this->assertEquals(1,$number);

        $tel = '135axxx' .  rand(10000,99999);
        $adminUserEntity = new AdminUserEntity();
        $adminUserEntity->tel = $tel;
        $adminUserEntity->id = 1;
        $number = $adminUserEntity->update();
        $this->assertEquals(1,$number);

        $number = AdminUserEntity::updateAll(['tel'=>'136XXXX' . rand(10000,99999)],['id'=>['1','2']]);
        $this->assertEquals(2,$number);
    }

    public function testQuery()
    {
        $adminUserEntity = AdminUserEntity::get(1);
        $this->assertEquals("hehe1",$adminUserEntity->username);
        $this->assertEquals(1,$adminUserEntity['id']);

        $adminUserEntity = AdminUserEntity::setWhere(['id'=>2])->fetchOne();
        $this->assertEquals("admin",$adminUserEntity->username);
        $this->assertEquals(2,$adminUserEntity->id);

        $userEntitys = AdminUserEntity::fetchAll(['id'=>[1,2]]);
        $this->assertEquals(2,count($userEntitys));

        $userEntitys = AdminUserEntity::setWhere(['id'=>[1,2]])->fetchAll();
        $this->assertEquals(2,count($userEntitys));

        $userEntitys = AdminUserEntity::setWhere(['id'=>[1,2]])->asArray()->fetchAll();
        $this->assertEquals(2,count($userEntitys));
    }

    public function testDelete()
    {
        $delete_number = AdminUserEntity::setWhere(['id'=>1])->deleteOne();
        $this->assertEquals(1,$delete_number);

        // 删除实体
        $userEntity = AdminUserEntity::get(2);
        $delete_number = $userEntity->delete();
        $this->assertEquals(1,$delete_number);

        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        AdminUserEntity::addAll($datas);

        $delete_number = AdminUserEntity::setWhere(['username'=>['admin1','admin2']])->deleteAll();
        $this->assertEquals(2,$delete_number);
    }

    // or,'and' 多条件查询
    public function testMoreCondtionQuery()
    {
        $where = ['status'=>1,'tel'=>['like','135%']];
        $userEntitys = AdminUserEntity::setWhere($where)->fetchAll();
        $this->assertEquals(2,count($userEntitys));


        $where = ['or','status'=>1,'tel'=>"13564768841"];
        $userEntitys = AdminUserEntity::setWhere($where)->fetchAll();

        $this->assertEquals(4,count($userEntitys));
    }

    // 嵌套条件查询
    public function testnestCondtionQuery()
    {
        $where = ['and','tel'=>['=','13564768841'],['or','roleId'=>['in',[2,3]],'status'=>1]];
        $userEntitys = AdminUserEntity::setWhere($where)->fetchAll();
        $this->assertEquals(3,count($userEntitys));


        $where = ['and','tel'=>['like','135%'],['or','roleId'=>['in',[2,3]],'status'=>1,['and','username'=>'hehe1','realName'=>'hehe'] ]];
        $userEntitys = AdminUserEntity::setWhere($where)->fetchAll();
        $this->assertEquals(4,count($userEntitys));


        $where = ['and','tel'=>['=','13564768841'],['and',['roleId'=>['in',[2,3,4]],'status'=>1]]];
        $userEntitys = AdminUserEntity::setWhere($where)->fetchAll();
        $this->assertEquals(1,count($userEntitys));

    }

    public function testOrderQuery()
    {
        $userEntitys = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['id'=>SORT_DESC])->fetchAll();
        $user_id = 4;
        foreach ($userEntitys as $user) {
            $this->assertEquals($user_id,$user['id']);
            $user_id--;
        }

        $userEntitys = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['id'=>SORT_ASC])->fetchAll();
        $user_id = 1;
        foreach ($userEntitys as $user) {
            $this->assertEquals($user_id,$user['id']);
            $user_id++;
        }


        $userEntitys = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setOrder(['status'=>SORT_DESC,'roleId'=>SORT_ASC])->fetchAll();
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
        $userEntitys = AdminUserEntity::setWhere('id',[1,2],'in')->fetchAll();
        $this->assertEquals(2,count($userEntitys));

        $userEntitys = AdminUserEntity::setWhere('id',1,'=')->fetchOne();
        $this->assertEquals('hehe1',$userEntitys['username']);
    }

    public function testRange()
    {
        $userEntitys = AdminUserEntity::setWhere(['roleId'=>['and', ['>=',1],['<=',3] ]])->fetchAll();
        $this->assertTrue(count($userEntitys) == 3);
    }

    public function testClosureQuery()
    {
        $userEntitys = AdminUserEntity::setWhere(function(QueryTable $queryTable){
            $queryTable->setWhere(['id'=>['>=',2]]);
        })->fetchAll();

        $this->assertEquals(3,count($userEntitys));
    }

    public function testColumnQuery()
    {
        $user = AdminUserEntity::setWhere(['id'=>1])->setSelect("id,tel")->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $user = AdminUserEntity::setWhere(['id'=>1])->setSelect(['id','tel'])->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));

        $userEntitys = AdminUserEntity::setWhere(['id'=>1])->setSelect("id,tel")->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $userEntitys = AdminUserEntity::setWhere(['id'=>1])->setSelect(['id','tel'])->fetchAll();
        foreach ($userEntitys as $user) {
            $this->assertTrue((isset($user['id']) && isset($user['tel']) && !isset($user['username'])));
        }

        $user = AdminUserEntity::setWhere(['id'=>1])->setSelect(['id'=>'user_id','tel'])->fetchOne();
        $this->assertTrue((isset($user['user_id']) && isset($user['tel']) && !isset($user['id'])));

        $user = AdminUserEntity::setWhere(['id'=>1])->setSelect(['id','tel',['(status+1) as age']])->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && isset($user['age'])));



    }

    public function testRawQuery()
    {
        $userEntitys = AdminUserEntity::queryCmd("select * from web_admin_users where id in (1,2)");
        $this->assertEquals(2,count($userEntitys));

        $number = AdminUserEntity::executeCmd("update web_admin_users set status=1 where id=3");
        $this->assertEquals(1,$number);

        $userEntitys = $this->hdbsession->query('hehe1')->queryCmd("select * from web_admin_users where id in (1,2)");
        $this->assertEquals(2,count($userEntitys));

        $number = $this->hdbsession->query('hehe1')->executeCmd("update web_admin_users set status=2 where id=3");
        $this->assertEquals(1,$number);
    }

    public function testTableAlias()
    {
        $user = AdminUserEntity::setWhere(['tb.id'=>1])->setAlias('tb')->fetchOne();
        $this->assertEquals('hehe1',$user['username']);

    }

    public function testScope()
    {
        $users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective')->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));

        $users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective,admin')->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));


        $users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->effective()->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));

        $users = AdminUserEntity::effective()->setWhere(['id'=>[1,2,3,4]])->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
    }

    public function testAsQuery()
    {
        $users_query = AdminUserEntity::setSelect('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->asQuery()->fetchAll();
        $users = AdminUserEntity::setWhere(['id'=>['in',$users_query]])->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
    }

    public function testLimit()
    {
        $users = AdminUserEntity::setSelect('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setLimit(1)->fetchAll();
        $this->assertTrue(count($users) == 1 && in_array($users[0]['id'],[1,2]));
    }

    public function testDistinct()
    {
        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setDistinct()->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['tel'],['13564768841','13564768842'])&& in_array($users[1]['tel'],['13564768841','13564768842']));
    }

    public function testParams()
    {
        $users = AdminUserEntity::setWhere('realName like :realName')->setParam(['realName'=>'%哈哈熊%'])->fetchAll();
        $this->assertTrue(count($users) == 1 && $users[0]['realName'] == '哈哈熊');
    }

    // 连表
    public function testJoin()
    {
        $users = AdminUserEntity::setAlias('u')
            ->setSelect('u.*,r.roleName')
            ->setJoin(['{{%admin_user_role}}','r'],['u.roleId'=>['raw','r.id']])
            ->setWhere(['u.id'=>[1,2]])->fetchAll();

        foreach ($users as $user) {
            $this->assertTrue(isset($user['roleName']));
        }
    }

    public function testWith()
    {
        $users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setWith('role')->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }

        $users = AdminUserEntity::setWhere(['adu.id'=>[1,2,3,4]])->setAlias('adu')->setWith('role',true)->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }

        $users = AdminUserEntity::setWhere(['adu.id'=>[1,2,3,4]])->setAlias('adu')->setInnerWith(['role'=>function(QueryTable $query){
            /** @var QueryTable $query **/
            $query->setWhere(['status'=>1]);

        }],false)->fetchAll();

        $this->assertTrue(count($users) == 3);


    }

    public function testPolymerization()
    {
        $count = AdminUserEntity::count();
        $this->assertTrue($count == 4);

        $count = AdminUserEntity::asArray()->count('id');
        $this->assertTrue($count == 4);

        // 查询最大值
        $max = AdminUserEntity::asArray()->queryMax('id');
        $this->assertTrue($max == 4);

        $max = AdminUserEntity::queryMax('id');
        $this->assertTrue($max == 4);

        $min = AdminUserEntity::queryMin('id');
        $this->assertTrue($min == 1);

        $sum = AdminUserEntity::querySum('id');
        $this->assertTrue($sum == 10);
    }

    // 分组查询
    public function testGroup()
    {

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setSelect('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->fetchAll();
        $this->assertTrue(count($users) == 3);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->setHaving(['status'=>0])->fetchAll();
        $this->assertTrue(count($users) == 1);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setAndHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setOrHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();

        $this->assertTrue(count($users) == 4);

    }

    public function testTransaction()
    {
        AdminUserEntity::beginTransaction();
        $data = ['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserEntity::addOne($data);
        AdminUserEntity::commitTransaction();
        $user = AdminUserEntity::setWhere(['username'=>'hehe3'])->fetchOne();
        $this->assertTrue($user['username'] == 'hehe3');

        AdminUserEntity::beginTransaction();
        $data = ['username'=>"hehe4",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserEntity::addOne($data);
        AdminUserEntity::rollbackTransaction();
        $user = AdminUserEntity::setWhere(['username'=>'hehe4'])->fetchOne();
        $this->assertTrue(empty($user));


        $this->hdbsession->beginTransaction();
        $data = ['username'=>"hehe5",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserEntity::addOne($data);
        $this->hdbsession->commitTransaction();
        $user = AdminUserEntity::setWhere(['username'=>'hehe5'])->fetchOne();
        $this->assertTrue($user['username'] == 'hehe5');

        $this->hdbsession->beginTransaction();
        $data = ['username'=>"hehe6",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
        AdminUserEntity::addOne($data);
        $this->hdbsession->rollbackTransaction();
        $user = AdminUserEntity::setWhere(['username'=>'hehe6'])->fetchOne();
        $this->assertTrue(empty($user));

    }





}
