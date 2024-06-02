<?php
namespace horm\tests\units\oci;

use horm\tests\units\mysql\ShardTableTest;

// 分表
class OciShardTableTest extends ShardTableTest
{
    protected static $db_driver = 'oci';

    protected function setUp()
    {
        static::makeDb('hehe1','hehe_case','oci_hehe.sql',['driver'=>'oci','port'=>'1521',
            'username'=>static::$db_config['oci_user'],'password'=>static::$db_config['oci_pwd']]);

        static::execOciSql('hehe_case','oci_shard_table.sql',"0");
//        static::execOciSql('hehe_test','oci_shard_table.sql',"1");
//        static::execOciSql('hehe_test','oci_shard_table.sql',"2");
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
