<?php
namespace horm\tests\units\pgsql;

use horm\tests\units\mysql\ShardTableTest;

// 分表
class PgsqlShardTableTest extends ShardTableTest
{
    protected static $db_driver = 'pgsql';

    protected function setUp()
    {
        static::makeDb('hehe1','hehe_test','pgsql_hehe.sql',['driver'=>'pgsql','port'=>'5432']);

        static::execPgsqlSql('hehe_test','pgsql_shard_table.sql',"0");
        static::execPgsqlSql('hehe_test','pgsql_shard_table.sql',"1");
        static::execPgsqlSql('hehe_test','pgsql_shard_table.sql',"2");
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
