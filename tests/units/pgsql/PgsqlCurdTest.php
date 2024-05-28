<?php
namespace horm\tests\units\pgsql;

use horm\tests\common\AdminUserEntity;
use horm\tests\units\mysql\CurdTest;

class PgsqlCurdTest extends CurdTest
{
    protected static $db_driver = 'pgsql';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_test','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432']);
    }

    protected function tearDown()
    {
        static::clearDb();
    }


    public function testWith()
    {
        parent::testWith();
    }



    public function testParams()
    {
        $users = AdminUserEntity::setWhere('"realName" like :realName')->setParam(['realName'=>'%哈哈熊%'])->fetchAll();
        $this->assertTrue(count($users) == 1 && $users[0]['realName'] == '哈哈熊');
    }

    public function testJoin()
    {
        $users = AdminUserEntity::setAlias('u')
            ->setSelect('u.*,r."roleName"')
            ->setJoin(['{{%admin_user_role}}','r'],['u."roleId"'=>['raw','r.id']])
            ->setWhere(['u.id'=>[1,2]])->fetchAll();

        foreach ($users as $user) {
            $this->assertTrue(isset($user['roleName']));
        }

    }

    public function testGroup()
    {

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel')->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setSelect('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->fetchAll();
        $this->assertTrue(count($users) == 3);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->setHaving(['status'=>0])->fetchAll();
        $this->assertTrue(count($users) == 1);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,"roleId"')
            ->setAndHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();
        $this->assertTrue(count($users) == 2);

        $users = AdminUserEntity::setSelect('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,"roleId"')
            ->setOrHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();

        $this->assertTrue(count($users) == 4);

    }










}
