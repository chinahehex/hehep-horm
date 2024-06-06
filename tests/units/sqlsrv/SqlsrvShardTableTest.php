<?php
namespace horm\tests\units\sqlsrv;

use horm\tests\units\mysql\ShardTableTest;

// 分表
class SqlsrvShardTableTest extends ShardTableTest
{
    protected static $db_driver = 'sqlsrv';

    protected function setUp()
    {
        static::makeDb('hehe1','hehe_test','sqlsrv_hehe.sql',['driver'=>'sqlsrv','port'=>'',
            'host'=>'LAPTOP-N5J7R81A\SQLEXPRESS','username'=>static::$db_config['sqlsrv_user'],'password'=>static::$db_config['sqlsrv_pwd']]);

        static::execSqlsrvSql('hehe_test','sqlsrv_shard_table.sql',"0");
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
