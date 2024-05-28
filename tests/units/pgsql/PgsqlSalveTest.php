<?php
namespace horm\tests\units\pgsql;

use horm\tests\units\mysql\SalveTest;

// 分表
class PgsqlSalveTest extends SalveTest
{
    protected static $db_driver = 'pgsql';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe_master','hehe_master','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432','onSlave'=>true,'slaves'=>['hehe_slave1','hehe_slave2']]);
        static::makeDb('hehe_slave1','hehe_slave1','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432']);
        static::makeDb('hehe_slave2','hehe_slave2','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432']);
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
