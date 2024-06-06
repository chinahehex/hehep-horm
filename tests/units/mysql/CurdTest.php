<?php
namespace horm\tests\units\mysql;
use horm\QueryTable;
use horm\tests\common\AdminUserEntity;
use horm\tests\TestCase;

class CurdTest extends TestCase
{
    protected static $db_driver = 'mysql';
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_test','hehe.sql');
    }

    protected function tearDown()
    {
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
            ->asArray()->setOrder(['id'=>SORT_DESC])
            ->fetchOne();
        $this->assertEquals("hehe4",$adminUserEntity['username']);

        // 批量添加
        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        $result = AdminUserEntity::addAll($datas);
        $this->assertTrue($result == 2);

        $userId = AdminUserEntity::asId()->addOne(['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);
        $this->assertTrue($userId > 0);
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

        $adminUserEntity = AdminUserEntity::fetchOne(['id'=>10]);
        $adminUserEntitys = AdminUserEntity::fetchAll(['id'=>10]);

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

    public function testUnion()
    {
        $query = AdminUserEntity::asQuery()->fetchAll(['id'=>[1,2]]);
        $adminUserEntitys = AdminUserEntity::asArray()->setUnion($query)->fetchAll(['id'=>3]);

        $this->assertEquals(3,count($adminUserEntitys));
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

        $user = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel',['(status+1) as age']])->fetchOne();
        $this->assertTrue((isset($user['id']) && isset($user['tel']) && isset($user['age'])));



    }

    public function testRawQuery()
    {
        $userEntitys = AdminUserEntity::queryCmd("select * from web_admin_users where id in (1,2)");
        $this->assertEquals(2,count($userEntitys));

        $number = AdminUserEntity::execCmd("update web_admin_users set status=1 where id=3");
        $this->assertEquals(1,$number);

        $userEntitys = $this->hdbsession->query('hehe1')->queryCmd("select * from web_admin_users where id in (1,2)");
        $this->assertEquals(2,count($userEntitys));

        $number = $this->hdbsession->query('hehe1')->execCmd("update web_admin_users set status=2 where id=3");
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
        $users_query = AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->asQuery()->fetchAll();
        $users = AdminUserEntity::setWhere(['id'=>['in',$users_query]])->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
    }

    public function testSubQuery()
    {
        $users_query = AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],])->asQuery()->fetchAll();
        $users = AdminUserEntity::setWhere(['id'=>['in',$users_query]])->setWhere(['status'=>1])->fetchAll();
        $this->assertTrue(count($users) == 2 && in_array($users[0]['id'],[1,2]) && in_array($users[1]['id'],[1,2]));
    }

    public function testLimit()
    {
        $users = AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setLimit(1)->fetchAll();
        $this->assertTrue(count($users) == 1 && in_array($users[0]['id'],[1,2]));
    }

    public function testDistinct()
    {
        $users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setDistinct()->fetchAll();
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
            ->setField('u.*,r.roleName')
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

    public function testAlias()
    {
        $users = AdminUserEntity::setField(['tel','realName'=>['as','name']])->fetchAll();
        $users = AdminUserEntity::setField(['tel','id'=>['as',['total','count']]])->setGroup('tel')->fetchAll();
        // 主表别名
        $users = AdminUserEntity::setWhere(['adu.id'=>[1,2]])->setAlias('adu')->fetchAll();
        // # 符号代替主表别名
        $users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('adu')->fetchAll();

        // 如未设置主表别名,系统会自动会剔除"#."
        $users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->fetchAll();

        $users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('user')->setField('#.*')
            ->setJoin("{{%admin_user_role}} as role",['#.roleId'=>['raw','role.id']])->fetchAll();

        $users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('user')->setField('#.*')
            ->setJoin("{{%admin_user_role}} as role",['role.id'=>['raw','#.roleId']])->fetchAll();

        $users = AdminUserEntity::setWhere(['#.id'=>[1,2,3,4]])->setAlias('adu')->setWith('role',true)->fetchAll();
        foreach ($users as $user) {
            $this->assertTrue(isset($user['role']['roleName']));
        }
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

        $sum = AdminUserEntity::setWhere(['status'=>1])->querySum('id');
        $this->assertTrue($sum == 3);


    }

    // 分组查询
    public function testGroup()
    {

        $users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setField('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->fetchAll();
        $this->assertTrue(count($users) == 3);

        $users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->setHaving(['status'=>0])->fetchAll();
        $this->assertTrue(count($users) == 1);

        $users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setAndHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
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

    public function testPage()
    {
        $users = AdminUserEntity::asArray()->setWhere(['id'=>[1,2,3,4]])->setLimit(2)->setOffset(1)->fetchAll();
        $this->assertTrue(count($users) == 2);
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
        $users = $db_conn->querySql('select * from web_admin_users where id in (1,2)');
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $number = $db_conn->execSql("update web_admin_users set tel='135xxxxbbbb' where id = 2");
        $this->assertTrue($number == 1);

        $number = $db_conn->execSql('update web_admin_users set tel=:tel where id = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
        $this->assertTrue($number == 1);
    }

    public function testTblemode()
    {
        $this->hdbsession->setDb('hehe1');

        $number = $this->hdbsession->setTable('web_admin_users')
            ->setData(['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'])->addOne();
        $this->assertTrue($number == 1);

        // 批量插入
        $datas = [
            ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
            ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
        ];

        $number = $this->hdbsession->setTable('web_admin_users')
            ->setData($datas)->addAll();
        $this->assertTrue($number == 2);

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
        $users = $this->hdbsession->queryCmd('select * from web_admin_users where id in (1,2)');
        $this->assertTrue(!empty($users) &&
            count($users) == 2 &&
            $users[0]['username'] == 'hehe1' &&
            $users[1]['username'] == 'admin'
        );

        $number = $this->hdbsession->execCmd("update web_admin_users set tel='135xxxxbbbb' where id = 2");
        $this->assertTrue($number == 1);

        $number = $this->hdbsession->execCmd('update web_admin_users set tel=:tel where id = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
        $this->assertTrue($number == 1);

    }







}
