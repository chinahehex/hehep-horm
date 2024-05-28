<?php
namespace horm\tests\units\sqlite;


use horm\tests\units\mysql\ShardTableTest;

class SqliteShardTableTest extends ShardTableTest
{
    protected static $db_driver = 'sqlite';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        $db_name = 'D:\data\sqlite\hehe_test.db';
        static::makeDb('hehe1',$db_name,'sqlite_hehe.sql',['driver'=>'sqlite']);
        static::execSqliteSql($db_name,'sqlite_shard_table.sql',"0");
        static::execSqliteSql($db_name,'sqlite_shard_table.sql',"1");
        static::execSqliteSql($db_name,'sqlite_shard_table.sql',"2");
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
