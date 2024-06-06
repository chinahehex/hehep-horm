<?php
namespace horm\tests\units\sqlsrv;

use horm\tests\units\mysql\SalveTest;

// 分表
class SqlsrvSalveTest extends SalveTest
{
    protected static $db_driver = 'sqlsrv';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();

        static::makeDb('hehe_master','hehe_master','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'','onSlave'=>true,'slaves'=>['hehe_slave1','hehe_slave2'],
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);
        static::makeDb('hehe_slave1','hehe_slave1','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);
        static::makeDb('hehe_slave2','hehe_slave2','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);

    }

    protected function tearDown()
    {
        static::clearDb();
    }

    public function testAdd()
    {
        parent::testAdd();
    }





}
