<?php
namespace horm\tests\units\sqlite;


use horm\tests\units\mysql\SalveTest;

class SqliteSalveTest extends SalveTest
{
    protected static $db_driver = 'sqlite';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe_master','D:\data\sqlite\hehe_master.db','sqlite_hehe.sql',['driver'=>'sqlite','port'=>'5432','onSlave'=>true,'slaves'=>['hehe_slave1','hehe_slave2']]);
        static::makeDb('hehe_slave1','D:\data\sqlite\hehe_slave1.db','sqlite_hehe.sql',['driver'=>'sqlite','port'=>'5432']);
        static::makeDb('hehe_slave2','D:\data\sqlite\hehe_slave2.db','sqlite_hehe.sql',['driver'=>'sqlite','port'=>'5432']);
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
