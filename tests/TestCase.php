<?php
namespace horm\tests;

use horm\Dbsession;
use mysql_xdevapi\Exception;

class TestCase extends \PHPUnit\Framework\TestCase
{

    protected static $db_driver = 'mysql';

    protected static $db_config = [];

    /**
     *
     * @var \horm\Dbsession
     */
    protected  $hdbsession;

    /**
     *
     * @var \horm\Dbsession
     */
    protected static $_hdbsession;

    protected static $dbs = [];

    // 测试之前
    protected function setUp()
    {
        $this->hdbsession = static::getDbsession();
    }

    // 测试之后
    protected function tearDown()
    {

    }

    // 整个测试类之前
    public static function setUpBeforeClass()
    {
        static::$db_config = parse_ini_file(dirname(__DIR__) . '/test.ini');


        static::getDbsession();
    }

    public static function getPdo($driver = '',$db_name = '')
    {
        if (empty($driver)) {
            $driver = static::$db_driver;
        }

        $pdo = null;
        try {
            if ($driver == 'pgsql') {
                $dns = "pgsql:host=localhost;port=5432";
                if (!empty($db_name)) {
                    $dns = $dns . ';dbname=' . $db_name;
                }
                $pdo = new \PDO($dns, static::$db_config['user'], static::$db_config['pwd']);
            } else if ($driver == 'sqlite') {
                $dns =  "sqlite:{$db_name}";
                $pdo = new \PDO($dns);
            } else if ($driver == 'oci') {
                $dns =  "oci:dbname=//localhost:1521/" . $db_name . ";charset=utf8";
                $pdo = new \PDO($dns,static::$db_config['oci_user'],static::$db_config['oci_pwd']);

            } else if ($driver == 'mongo') {
                $dns =  "mongodb://" . static::$db_config['mongo_user'] . ":" . static::$db_config['mongo_pwd'] ."@localhost:27017";
                $pdo = new \MongoDB\Driver\Manager($dns);
            } else {
                $dns = static::$db_driver . ":host=localhost;charset=utf8";
                if (!empty($db_name)) {
                    $dns = $dns . ';dbname=' . $db_name;
                }
                $pdo = new \PDO($dns, static::$db_config['user'], static::$db_config['pwd']);


            }

            return $pdo;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            throw $e;
        }


    }

    // 整个测试类之前
    public static function tearDownAfterClass()
    {
        //static::clearDb();
    }

    public static function getDbsession()
    {
        if (is_null(static::$_hdbsession)) {
            static::$_hdbsession = new Dbsession();
        }

        return static::$_hdbsession;
    }

    protected static function clearDb($dbs = [])
    {

        if (!empty($dbs)) {
            $clean_dbs = $dbs;
        } else {
            $clean_dbs = static::$dbs;
        }
        foreach ($clean_dbs as  $db_name=>$dbconfig) {
            if (in_array($db_name,['hehe','mysql','sys','hehe',])) {
                continue;
            }

            if (!isset(static::$dbs[$db_name])) {
                continue;
            }

            $dbconfig = static::$dbs[$db_name];
            unset(static::$dbs[$db_name]);

//            static::$pdo->exec("USE " . $db_name);
//            $stmt = static::$pdo->query("SHOW TABLES");
//            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
//                static::$pdo->exec('DROP TABLE IF EXISTS ' . $row[0]);
//            }
            static::$_hdbsession->cleanAll();
            if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'sqlite') {
                // 文件删除
                if (file_exists($db_name) &&  is_writable($db_name)) {
//                    $pdo = static::getPdo($dbconfig['driver'],$db_name);
//                    unset($pdo);
//                    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
//                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//                        if ($row['name'] != 'sqlite_sequence') {
//                            $stmte = $pdo->query("SELECT * FROM {$row['name']} WHERE id > 0 limit 1");
//                            $rowd = $stmte->fetch(\PDO::FETCH_ASSOC);
//                            $reuslt = $pdo->exec('DROP TABLE IF EXISTS ' . $row['name'] . ";");
//                            var_dump("删除表:{$row['name']},");
//                            var_dump('DROP TABLE IF EXISTS ' . $row['name']);
//                            var_dump($pdo->errorInfo());
//                        }
//                    }

                    $reuslt = unlink($db_name);
                }
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'pgsql') {
                $pdo = static::getPdo($dbconfig['driver'],'postgres');
                $reuslt = $pdo->exec("DROP DATABASE IF EXISTS $db_name");
                unset($pdo);
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'mongo') {
                $pdo = static::getPdo($dbconfig['driver'],'mongo');
                $mongoCommand = new \MongoDB\Driver\Command(['dropDatabase'=>1]);
                $cursor = $pdo->executeCommand($db_name, $mongoCommand);
                //var_dump($cursor);
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'oci') {

                $pdo = static::getPdo($dbconfig['driver'],$db_name);
                $tables = ['web_admin_users','web_admin_user_role','web_admin_users_info_0','web_admin_users_info_1','web_admin_users_info_2'];

                $reuslt = $pdo->exec("DROP SEQUENCE \"web_admin_users_info_seq\"");
                foreach ($tables as $table) {
                    $reuslt = $pdo->exec("drop TABLE \"{$table}\"");
                    $reuslt = $pdo->exec("DROP SEQUENCE \"{$table}_seq\"");
                    //var_dump($reuslt);
                }

            } else {
                $pdo = static::getPdo($dbconfig['driver'],$db_name);
                $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
            }
        }
    }


    protected static function createDatabase($db_name,$dbconfig = [])
    {

        try {
            if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'sqlite') {
                $pdo = static::getPdo($dbconfig['driver'],$db_name);
                unset($pdo);
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'pgsql') {
                $createDbSql = "create database {$db_name} owner " . static::$db_config['user'];
                // 执行创建数据库的SQL语句
                $pdo = static::getPdo($dbconfig['driver'],'postgres');
                $result = $pdo->exec($createDbSql);
                unset($pdo);
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'oci') {
                // 执行创建数据库的SQL语句
                //var_dump("dddd");
                $pdo = static::getPdo($dbconfig['driver'],$db_name);
                //$result = $pdo->exec($createDbSql);
//                var_dump($pdo->errorInfo());
//                var_dump($createDbSql);
                unset($pdo);
            } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'mongo') {
                $createDbSql = "use {$db_name}";
                // 执行创建数据库的SQL语句
                $pdo = static::getPdo('mongo',static::$db_config['mongo_dbname']);
                /** @var  \MongoDB\Driver\Manager $pdo */
                //$pdo->executeCommand();
                //$result = $pdo->
                //var_dump($result);
                unset($pdo);
            } else {
                $pdo = static::getPdo($dbconfig['driver'],'');
                $createDbSql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8 COLLATE utf8_general_ci";
                // 执行创建数据库的SQL语句
                $result = $pdo->exec($createDbSql);
                unset($pdo);
            }
            static::$dbs[$db_name] = $dbconfig;

        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected static function createMysqlShardTable($db_name,$sql_file = '',$table_shard = '')
    {
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        $pdo = static::getPdo('mysql',$db_name);
        //$pdo->exec("USE " . $db_name);
        foreach ($sqlStatements as $sql) {
            $sql = str_replace('{{:shard}}',$table_shard,$sql);
            $sql = str_replace('--#',';',$sql);
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $pdo->exec($sql);
            }
        }
    }

    protected static function addDbconfig($db_alias,$db_name,$dbconfig = [])
    {

        $def_dbconfig = ['driver' => 'mysql','host' => 'localhost','database'=>$db_name,'username' => static::$db_config['user'],
            'password' => static::$db_config['pwd'],'port' => '3306','charset' => 'utf8','prefix' => 'web_',
        ];

        $dbconfig = array_merge($def_dbconfig,$dbconfig);


        static::$_hdbsession->addDb($db_alias,$dbconfig);
    }

    protected static function makeDb($db_alias,$db_name,$sql_file = '',$dbconfig = [],$shard = '')
    {

        static::createDatabase($db_name,$dbconfig);

        if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'sqlite') {
            static::execSqliteSql($db_name,$sql_file,$shard);
        } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'pgsql') {
            static::execPgsqlSql($db_name,$sql_file,$shard);
        } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'oci') {
            static::execOciSql($db_name,$sql_file,$shard);
        } else if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'mongo') {
            static::execMongoSql($db_name,$sql_file,$shard);
        } else {
            static::execMysqlSql($db_name,$sql_file,$shard);
        }

        static::addDbconfig($db_alias,$db_name,$dbconfig);
    }

    protected static function execMysqlSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        $pdo = static::getPdo('mysql',$db_name);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);
                $sql = str_replace('--#',';',$sql);
            }
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $result = $pdo->exec($sql);
            }
        }
    }

    protected static function execOciSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        $pdo = static::getPdo('oci',$db_name);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);

            }

            $sql = str_replace('--#',';',$sql);
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $result = $pdo->exec($sql);
//                var_dump($result);
//                var_dump($sql);
            }
        }
    }

    protected static function execPgsqlSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        $pdo = static::getPdo('pgsql',$db_name);
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);

            }

            $sql = str_replace('--#',';',$sql);
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $result = $pdo->exec($sql);
            }
        }
    }

    protected static function execSqliteSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        $pdo = static::getPdo('sqlite',$db_name);
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);
            }

            $sql = str_replace('--#',';',$sql);
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $result = $pdo->exec($sql);
            }
        }

        $pdo = null;
        unset($pdo);
    }

    protected static function execMongoSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        $pdo = static::getPdo('mongo',$db_name);
        // 加载数据
        $cmds = require __DIR__ . '/res/' . $sql_file;
        // 用分号分割SQL语句
        foreach ($cmds as $cmd) {
            if ($shard != '') {
                $cmd['table'] = str_replace('{{:shard}}',$shard,$cmd['table']);
            }
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->insert($cmd['data']);
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $insertOneResult = $pdo->executeBulkWrite($db_name . '.' . $cmd['table'], $bulk, $writeConcern);
        }
    }
}


