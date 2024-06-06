<?php
namespace  horm\drivers\mongo;

use horm\base\BaseConnection;
use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use horm\base\NosqlCommand;
use horm\base\QueryCommand;
use horm\pools\PoolDbConnection;
use MongoDB\Driver\Manager;
use Exception;

/**
 * Mongodb驱动连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class MongodbDbConnection extends BaseConnection
{
    use PoolDbConnection;

    /**
     * Builder 实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var MongoQueryBuilder
     */
    private $builder;

    /**
     * 当前mongo连接实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var \MongoDB\Driver\Manager
     */
    protected $conn = null;

    /**
     * mongo事务session
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var \MongoDB\Driver\Session
     */
    protected $tranSession = null;

    /**
     * 获取sql生成对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return MongoQueryBuilder
     */
    public function getQueryBuilder():MongoQueryBuilder
    {
        if ($this->builder === null) {
            $this->builder = $this->createQueryBuilder();
        }

        return $this->builder;
    }

    /**
     * 创建生成sql类实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return MongoQueryBuilder
     */
    public function createQueryBuilder():MongoQueryBuilder
    {
        return new MongoQueryBuilder($this);
    }

    /**
     * 返回mongo连接字符串
     * @return string
     */
    protected function parseDsn():string
    {
        $dsn = 'mongodb://'.($this->config['username']?"{$this->config['username']}":'').($this->config['password']?":{$this->config['password']}@":'').
            $this->config['host'].($this->config['port']?":{$this->config['port']}":'');

        return $dsn;
    }

    /**
     * 连接数据库
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return Manager
     * @throws Exception
     */
    public function connect()
    {
        if (is_null($this->conn)) {
            try {
                $this->conn = new \MongoDB\Driver\Manager($this->parseDsn());
                return $this->conn;
            } catch (\Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            return $this->conn;
        }
    }

    /**
     * 获取连接
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     */
    public function getConn():Manager
    {
        if (is_null($this->conn)) {
            $this->conn = $this->connect();
        }

        return $this->conn;
    }

    /**
     * 生成命令对应方法的参数
     * @param NosqlCommand $queryCommand
     * @return array
     */
    protected function buildQueryCommandMethodParams(NosqlCommand $queryCommand):array
    {
        $method = $queryCommand->getMethod();
        $command_options = $queryCommand->getOptions();
        $command_options['queryCommand'] = $queryCommand;

        return $this->getMethodParams(static::class,$method,$command_options);
    }

    /**
     * 游标转数组
     * @param mixed $cursor
     * @return array
     */
    protected function cursorToArray($cursor):array
    {
        $datas = [];
        foreach ($cursor as $document) {
            $datas[] = (array)$document;
        }

        return $datas;
    }

    /**
     * 执行查询,返回数据行
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param NosqlCommand $queryCommand  命令对象
     * @return array
     */
    public function callQuery(QueryCommand $queryCommand)
    {

        $result = false;
        $method = $queryCommand->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],$this->buildQueryCommandMethodParams($queryCommand));
        } else {
            $result = $this->queryCmd($queryCommand);
        }

        return $result;
    }

    /**
     * 执行查询 返回数据行
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param NosqlCommand $queryCommand s命令对象
     * @return int
     */
    public function callExecute(QueryCommand $queryCommand)
    {
        $result = false;
        $method = $queryCommand->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],$this->buildQueryCommandMethodParams($queryCommand));
        } else {
            $result = $this->execCmd($queryCommand);
        }

        return $result;
    }

    protected function getCollection($namespace)
    {
        return $this->config['database'] . '.' . $namespace;
    }

    /**
     * 插入单个数据行
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $table
     * @param array $data
     * @param array $options
     * @param NosqlCommand $queryCommand
     * @return int
     */
    public function insert(string $table,array $data,array $options = [],NosqlCommand $queryCommand = null)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($data);
        $insertOneResult = $this->getConn()->executeBulkWrite(
            $this->getCollection($table),
            $bulk,
            $this->buildExecOptions($options)
        );

        return $insertOneResult->getInsertedCount();
    }

    public function insertAll(string $table,array $data,array $options = [],NosqlCommand $queryCommand = null)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        foreach ($data as $row) {
            $bulk->insert($row);
        }

        $insertOneResult = $this->getConn()->executeBulkWrite(
            $this->getCollection($table),
            $bulk,
            $this->buildExecOptions($options)
        );

        return $insertOneResult->getInsertedCount();
    }

    /**
     * 更新记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return int
     */
    public function update(string $table,array $data,array $filter = [] ,array $opts = [],NosqlCommand $queryCommand = null)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->update($filter,$data,$opts);
        $updateResult = $this->getConn()->executeBulkWrite(
            $this->getCollection($table),
            $bulk,
            $this->buildExecOptions()
        );

        return $updateResult->getModifiedCount();
    }

    /**
     * 删除记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return int
     */
    public function delete(string $table ,array $filter = [],array $opts = [], NosqlCommand $queryCommand = null)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->delete($filter,$opts);
        $deleteResult = $this->getConn()->executeBulkWrite(
            $this->getCollection($table),
            $bulk,
            $this->buildExecOptions()
        );

        return $deleteResult->getDeletedCount();
    }


    /**
     * 获取一条数据
     * @param string $table
     * @param array $condition
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function fetchOne(string $table,array $filter,array $opts = [],NosqlCommand $queryCommand = null)
    {
        $mongoQuery = new \MongoDB\Driver\Query($filter, $opts);
        $cursor = $this->getConn()->executeQuery($this->getCollection($table), $mongoQuery);
        $queryResult = $this->cursorToArray($cursor);

        if (empty($queryResult)) {
            return [];
        }

        return $queryResult[0];
    }


    /**
     * 查询多条记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $queryCommand
     * @return array
     */
    public function fetchAll(string $table,array $filter,array $opts = [],NosqlCommand $queryCommand = null)
    {
        $mongoQuery = new \MongoDB\Driver\Query($filter, $opts);
        $cursor = $this->getConn()->executeQuery($this->getCollection($table), $mongoQuery);
        $queryResult = $this->cursorToArray($cursor);

        return $queryResult;
    }

    /**
     * 聚合查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return array
     */
    public function scalar(string $table,array $pipelines = [],NosqlCommand $queryCommand = null)
    {
        $mongoCommand = new \MongoDB\Driver\Command([
            'aggregate' => $table,
            'pipeline' => $pipelines,
            'cursor' => (object)[]
        ]);

        $cursor = $this->getConn()->executeCommand($this->config['database'], $mongoCommand);

        return $this->cursorToArray($cursor);
    }

    /**
     * 分组查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return array
     */
    public function aggregate(string $table,array $pipelines = [],NosqlCommand $queryCommand = null)
    {
        $mongoCommand = new \MongoDB\Driver\Command([
            'aggregate' => $table,
            'pipeline' => $pipelines,
            'cursor' => (object)[]
        ]);

        $cursor = $this->getConn()->executeCommand($this->config['database'], $mongoCommand);
        $datas = $this->cursorToArray($cursor);

        return $datas;
    }

    /**
     * 执行原始命令
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return array
     */
    public function execCmd(NosqlCommand $queryCommand)
    {
        $mongoCommand = new \MongoDB\Driver\Command($queryCommand->getCommand());
        $cursor = $this->getConn()->executeCommand($this->config['database'], $mongoCommand);
        $result = $this->cursorToArray($cursor);

        if (isset($result) && isset($result[0]['n'])) {
            return $result[0]['n'];
        } else {
            return 0;
        }
    }

    /**
     * 查询原始命令
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return array
     */
    public function queryCmd(NosqlCommand $queryCommand)
    {
        $mongoCommand = new \MongoDB\Driver\Command($queryCommand->getCommand());
        $cursor = $this->getConn()->executeCommand($this->config['database'], $mongoCommand);
        $result = $this->cursorToArray($cursor);

        return $result;
    }


    public function beginTransaction()
    {
        $this->tranSession = $this->getConn()->startSession();
        $this->tranSession->startTransaction();

        return true;
    }

    public function commitTransaction()
    {
        if (!is_null($this->tranSession)) {
            $this->tranSession->commitTransaction();
            $this->tranSession->endSession();
            $this->tranSession = null;
        }

        return true;
    }

    public function rollbackTransaction()
    {
        if (!is_null($this->tranSession)) {
            $this->tranSession->abortTransaction();
            $this->tranSession->endSession();
            $this->tranSession = null;
        }

        return true;
    }

    /**
     * 析构方法
     *<B>说明：</B>
     *<pre>
     * 	释放查询资源
     * 	关闭连接
     *</pre>
     * @return string
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }

    /**
     * 关闭数据库
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return void
     */
    public function close()
    {
        $this->conn = null;
    }

    /**
     * 构建执行命令配置
     * @param array $options
     * @return array|mixed
     */
    protected function buildExecOptions($options = [])
    {
        if (!is_null($this->tranSession)) {
            $options['session'] = $this->tranSession;
        }

        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $options['writeConcern'] = $writeConcern;

        return $options;
    }

}
