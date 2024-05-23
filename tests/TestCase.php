<?php
namespace horm\tests;

use horm\Dbsession;

class TestCase extends \PHPUnit\Framework\TestCase
{

    protected static $db_user = '';

    protected static $user_pwd = '';

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

    protected static $pdo;

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
        $db_config = parse_ini_file(dirname(__DIR__) . '/test.ini');
        static::$db_user = $db_config['user'];
        static::$user_pwd = $db_config['pwd'];

        static::$pdo = new \PDO("mysql:host=localhost;charset=utf8", static::$db_user, static::$user_pwd);
        static::getDbsession();
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

            if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'sqlite') {
                // 文件删除
                if (file_exists($db_name) &&  is_writable($db_name)) {
                    static::$_hdbsession->cleanAll();
                    $pdo = new \PDO("sqlite:{$db_name}");
                    unset($pdo);
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
            } else {
                static::$pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
            }
        }
    }


    protected static function createDatabase($db_name,$dbconfig = [])
    {
        try {

            if (isset($dbconfig['driver']) && $dbconfig['driver'] == 'sqlite') {
                $pdo = new \PDO("sqlite:{$db_name}");
                unset($pdo);
            } else {
                $createDbSql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8 COLLATE utf8_general_ci";
                // 执行创建数据库的SQL语句
                static::$pdo->exec($createDbSql);
            }
            static::$dbs[$db_name] = $dbconfig;

        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected static function createShardTable($db_name,$table_shard,$sql_file = 'shard_table.sql')
    {
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        static::$pdo->exec("USE " . $db_name);
        foreach ($sqlStatements as $sql) {
            $sql = str_replace('{{:shard}}',$table_shard,$sql);
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                static::$pdo->exec($sql);
            }
        }
    }

    protected static function addDbconfig($db_alias,$db_name,$dbconfig = [])
    {

        $def_dbconfig = ['driver' => 'mysql','host' => 'localhost','database'=>$db_name,'username' => static::$db_user,
            'password' => static::$user_pwd,'port' => '3306','charset' => 'utf8','prefix' => 'web_',
        ];

        $dbconfig = array_merge($def_dbconfig,$dbconfig);

        static::$_hdbsession->addDbconf($db_alias,$dbconfig);
    }

    protected static function execSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        static::$pdo->exec("USE " . $db_name);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);
            }
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                static::$pdo->exec($sql);
            }
        }
    }

    protected static function makeDb($db_alias,$db_name,$sql_file = '',$dbconfig = [],$shard = '')
    {

        static::createDatabase($db_name,$dbconfig);

        if (isset($dbconfig['driver']) || $dbconfig['driver'] != 'sqlite') {
            static::execSqliteSql($db_name,$sql_file,$shard);
        } else {
            static::execSql($db_name,$sql_file,$shard);
        }

        static::addDbconfig($db_alias,$db_name,$dbconfig);
    }

    protected static function execSqliteSql($db_name,$sql_file = '',$shard = '')
    {
        if (empty($sql_file)) {
            return ;
        }

        $pdo = new \PDO("sqlite:{$db_name}");
        // 加载数据
        $sqlContent = file_get_contents(__DIR__ . '/res/' . $sql_file);
        // 用分号分割SQL语句
        $sqlStatements = explode(';', $sqlContent);
        foreach ($sqlStatements as $sql) {
            if ($shard != '') {
                $sql = str_replace('{{:shard}}',$shard,$sql);
            }
            // 跳过空语句
            if (trim($sql) !== '') {
                // 使用PDO执行SQL语句
                $result = $pdo->exec($sql);
            }
        }

        $pdo = null;
        unset($pdo);
    }
}


