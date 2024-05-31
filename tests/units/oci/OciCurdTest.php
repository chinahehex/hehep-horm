<?php
namespace horm\tests\units\oci;

use horm\tests\units\mysql\CurdTest;

class OciCurdTest extends CurdTest
{
    protected static $db_driver = 'oci';
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
        static::makeDb('hehe1','hehe_test','oci_hehe.sql',['driver'=>'oci','port'=>'1521',
            'username'=>static::$db_config['oci_user'],'password'=>static::$db_config['oci_pwd']]);
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
