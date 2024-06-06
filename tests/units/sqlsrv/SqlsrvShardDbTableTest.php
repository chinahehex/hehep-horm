<?php
namespace horm\tests\units\sqlsrv;

// 分表
use horm\tests\units\mysql\ShardDbTableTest;

class SqlsrvShardDbTableTest extends ShardDbTableTest
{
    protected static $db_driver = 'sqlsrv';

    protected function setUp()
    {

        static::makeDb('hehe','hehe_test','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);
        static::makeDb('hehe_0','hehe_test0','',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);
        static::makeDb('hehe_1','hehe_test1','',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);
        static::makeDb('hehe_2','hehe_test2','',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);


        static::execSqlsrvSql('hehe_test0','sqlsrv_shard_table.sql',"0");
        static::execSqlsrvSql('hehe_test1','sqlsrv_shard_table.sql',"1");
        static::execSqlsrvSql('hehe_test2','sqlsrv_shard_table.sql',"2");

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
