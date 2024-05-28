<?php
namespace horm\tests\units\pgsql;

// 分表
use horm\tests\units\mysql\ShardDbTableTest;

class PgsqlShardDbTableTest extends ShardDbTableTest
{
    protected static $db_driver = 'pgsql';

    protected function setUp()
    {

        static::makeDb('hehe','hehe_test','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432']);
        static::makeDb('hehe_0','hehe_test0','',['driver'=>'pgsql','port'=>'5432']);
        static::makeDb('hehe_1','hehe_test1','',['driver'=>'pgsql','port'=>'5432']);
        static::makeDb('hehe_2','hehe_test2','',['driver'=>'pgsql','port'=>'5432']);

        static::execPgsqlSql('hehe_test0','pgsql_shard_table.sql',"0");
        static::execPgsqlSql('hehe_test1','pgsql_shard_table.sql',"1");
        static::execPgsqlSql('hehe_test2','pgsql_shard_table.sql',"2");
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
