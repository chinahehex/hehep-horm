<?php
namespace horm\tests\units\sqlite;

// 分表
use horm\tests\units\ShardDbTest;

class SqliteShardDbTest extends ShardDbTest
{
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        $db_name = 'D:\data\sqlite\hehe_test.db';
        $db_test_name0 = 'D:\data\sqlite\hehe_test0.db';
        $db_test_name1 = 'D:\data\sqlite\hehe_test1.db';
        $db_test_name2 = 'D:\data\sqlite\hehe_test2.db';
        static::makeDb('hehe',$db_name,'sqlite_hehe.sql',['driver'=>'sqlite']);
        static::makeDb('hehe_0',$db_test_name0,'',['driver'=>'sqlite']);
        static::makeDb('hehe_1',$db_test_name1,'',['driver'=>'sqlite']);
        static::makeDb('hehe_2',$db_test_name2,'',['driver'=>'sqlite']);

        static::execSqliteSql($db_test_name0,'sqlite_shard_db.sql',"0");
        static::execSqliteSql($db_test_name1,'sqlite_shard_db.sql',"1");
        static::execSqliteSql($db_test_name2,'sqlite_shard_db.sql',"2");
    }

    protected function tearDown()
    {
        static::clearDb();
    }






}
