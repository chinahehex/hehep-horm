<?php
namespace horm\tests\units\sqlsrv;

use horm\tests\common\AdminUserEntity;
use horm\tests\units\mysql\CurdTest;

class SqlsrvCurdTest extends CurdTest
{
    protected static $db_driver = 'sqlsrv';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_test','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'',
        'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);

    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testPage()
    {
        $users = AdminUserEntity::asArray()->setWhere(['id'=>[1,2,3,4]])->setOrder(['id'=>SORT_DESC])->setLimit(2)->setOffset(1)->fetchAll();
        $this->assertTrue(count($users) == 2);
    }

}
