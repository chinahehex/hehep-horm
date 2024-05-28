<?php
namespace horm\tests\units\sqlite;

use horm\tests\units\mysql\CurdTest;

class SqliteCurdTest extends CurdTest
{
    protected static $db_driver = 'sqlite';

    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','D:\data\sqlite\hehe_test.db','sqlite_hehe.sql',['driver'=>'sqlite']);
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
