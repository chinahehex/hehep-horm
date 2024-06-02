<?php
namespace  horm\drivers\mongo;

use horm\base\BaseConnection;
use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use horm\base\NosqlCommand;
use horm\pools\PoolDbConnection;
use MongoDB\Driver\Manager;
use Exception;

/**
 * Mongodb扩展连接类
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
     * @var BaseQueryBuilder
     */
    private $builder;

    /**
     * 当前连接PDO实例
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var \MongoDB\Driver\Manager
     */
    protected $conn = null;

    /**
     * 事务session
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
     * @return BaseQueryBuilder
     */
    public function getQueryBuilder()
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
     * 连接数据库
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     */
    protected function initConnect()
    {
        if (is_null($this->conn)) {
            $this->conn = $this->connect();
        }
    }

    protected function formatCommandMethodParams(NosqlCommand $command)
    {
        $method = $command->getMethod();
        $command_options = $command->getOptions();
        $command_options['command'] = $command;

        return $this->getMethodParams(static::class,$method,$command_options);
    }


    protected function cursorToArray($cursor)
    {
        $datas = [];
        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document),true);
            $datas[] = $document;
        }

        return $datas;
    }

    /**
     * 执行查询 返回数据行
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param NosqlCommand $command  sql命令对象
     * @return array
     */
    public function callQuery($command)
    {

        $this->initConnect();

        $result = false;
        $method = $command->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],$this->formatCommandMethodParams($command));
        } else {
            $result = $this->queryCmd($command);
        }

        return $result;
    }

    /**
     * 执行查询 返回数据行
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param NosqlCommand $command sql 命令对象
     * @return array
     */
    public function callExecute($command)
    {
        $this->initConnect();

        $result = false;
        $method = $command->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],$this->formatCommandMethodParams($command));
        } else {
            $result = $this->execCmd($command);
        }

        return $result;
    }

    protected function buildNamespace($namespace)
    {
        return $this->config['database'].'.' . $namespace;
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
     * @param NosqlCommand $command
     * @return int
     */
    public function insert(string $table,array $data,array $options = [],$command = [])
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($data);
        $insertOneResult = $this->conn->executeBulkWrite(
            $this->buildNamespace($table),
            $bulk,
            $this->buildExecOptions($options)
        );

        return $insertOneResult->getInsertedCount();
    }

    public function insertAll(string $table,array $data,array $options = [],$command = [])
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        foreach ($data as $row) {
            $bulk->insert($row);
        }

        $insertOneResult = $this->conn->executeBulkWrite(
            $this->buildNamespace($table),
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
    public function update(string $table,array $data,array $filter = [] ,array $opts = [],$command = [])
    {

        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->update($filter,$data,$opts);
        $updateResult = $this->conn->executeBulkWrite(
            $this->buildNamespace($table),
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
    public function delete(string $table ,array $filter = [],array $opts = [], $command = [])
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->delete($filter,$opts);
        $deleteResult = $this->conn->executeBulkWrite(
            $this->buildNamespace($table),
            $bulk,
            $this->buildExecOptions()
        );

        return $deleteResult->getDeletedCount();
    }


    /**
     * 查询记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param NosqlCommand $command
     * @return array
     */
    public function find(string $table,array $filter,array $opts = [],$command = [])
    {

        $query = new \MongoDB\Driver\Query($filter, $opts);
        $cursor = $this->conn->executeQuery($this->buildNamespace($table), $query);
        $datas = $this->cursorToArray($cursor);

        return $datas;
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
    public function scalar(string $table,array $pipelines = [],$command = [])
    {

        $command = new \MongoDB\Driver\Command([
            'aggregate' => $table,
            'pipeline' => $pipelines,
            'cursor' => (object)[]
        ]);

        $cursor = $this->conn->executeCommand($this->config['database'], $command);

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
    public function aggregate(string $table,array $pipelines = [],$command = [])
    {

        $mongoCommand = new \MongoDB\Driver\Command([
            'aggregate' => $table,
            'pipeline' => $pipelines,
            'cursor' => (object)[]
        ]);

        $cursor = $this->conn->executeCommand($this->config['database'], $mongoCommand);
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
    public function execCmd($command)
    {
        $mongoCommand = new \MongoDB\Driver\Command($command->getCommand());
        $cursor = $this->conn->executeCommand($this->config['database'], $mongoCommand);
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
    public function queryCmd($command)
    {
        $command = new \MongoDB\Driver\Command($command->getCommand());
        $cursor = $this->conn->executeCommand($this->config['database'], $command);
        $result = $this->cursorToArray($cursor);

        return $result;
    }


    public function beginTransaction()
    {
        $this->initConnect();
        $this->tranSession = $this->conn->startSession();
        $this->tranSession->startTransaction();

        return true;
    }

    public function commit()
    {
        if (!is_null($this->tranSession)) {
            $this->tranSession->commitTransaction();
            $this->tranSession->endSession();
            $this->tranSession = null;
        }

        return true;
    }

    public function rollback()
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
