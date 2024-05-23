<?php
namespace horm\tests\units\sqlite;



use horm\tests\units\CurdTest;

class SqliteCurdTest extends CurdTest
{
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','D:\data\sqlite\hehe_test.db','sqlite_hehe.sql',['driver'=>'sqlite']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        static::clearDb();
    }






}
