<?php
namespace horm\tests\units;

use horm\QueryTable;
use horm\tests\common\AdminUserEntity;
use horm\tests\TestCase;

class PgsqlCurdTest extends CurdTest
{
    protected function setUp()
    {
        parent::setUp();
        static::makeDb('hehe1','hehe_test','hehe.sql',['driver'=>'pgsql']);
    }

    protected function tearDown()
    {
        static::clearDb();
    }








}
