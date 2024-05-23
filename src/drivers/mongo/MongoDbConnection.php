<?php
namespace  horm\drivers\mongo;

use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use horm\base\NosqlCommand;
use MongoDB\Driver\Manager;
use MongoDB\Client;

use Exception;

/**
 * MongoDb 扩展连接类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class MongoDbConnection extends DbConnection
{

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
     * @var Client
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
     * @return BaseQueryBuilder
     */
    public function createQueryBuilder()
    {
        return new MongoQueryBuilder($this);
    }

    protected function parseDsn($config = [])
    {
        $dsn = 'mongodb://'.($config['username']?"{$config['username']}":'').($config['password']?":{$config['password']}@":'').
            $config['host'].($config['port']?":{$config['port']}":'');

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
                $dbconn = new Client($this->parseDsn($this->config));

                $this->conn = $dbconn;

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

    protected function getCollection($collection)
    {
        $collection = $this->conn->selectCollection($this->config['database'], $collection);

        return $collection;
    }

    /**
     * 执行查询 返回数据行
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param MongoCommand $command  sql 命令对象
     * @return array
     *<pre>
     *  略
     *</pre>
     */
    public function query($command)
    {

        $this->initConnect();

        $result = false;
        $method = $command->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],[$command]);
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
     * @param MongoCommand $command sql 命令对象
     * @return array
     *<pre>
     *  略
     *</pre>
     */
    public function execute($command)
    {
        $this->initConnect();

        $result = false;
        $method = $command->getMethod();
        if ($method != '' && method_exists($this,$method)) {
            $result = call_user_func_array([$this,$method],[$command]);
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
     * @param NosqlCommand $command
     * @return int
     */
    public function insert($command = [])
    {
        $collection = $this->getCollection($command->getOptions('table'));
        $insertOneResult = $collection->insertOne($command->getOptions('data'),$command->getOptions('opts'));

        return $insertOneResult->getInsertedCount();
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
    public function update($command = [])
    {

        $collection = $this->getCollection($command->getOptions('table'));
        $updateResult = $collection->updateMany($command->getOptions('filter'),$command->getOptions('data'),$command->getOptions('opts'));

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
    public function delete($command = [])
    {
        $collection = $this->getCollection($command->getOptions('table'));
        $deleteResult = $collection->deleteMany($command->getOptions('filter'),$command->getOptions('opts'));

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
    public function find($command = [])
    {
        $collection = $this->getCollection($command->getOptions('table'));
        $cursor = $collection->find($command->getOptions('filter'),$command->getOptions('opts'));
        $datas = $this->cursorToArray($cursor);

        return $datas;
    }


    protected function cursorToArray($cursor)
    {
        $datas = [];
        foreach ($cursor as $document) {
            /** @var \MongoDB\Model\BSONDocument $document **/

            $datas[] = $document->getArrayCopy();;
        }

        return $datas;
    }

    protected function objToArray($cursor)
    {

        $datas = [];
        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document),true);
            $datas[] = $document;
        }

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
    public function scalar($command = [])
    {
        $collection = $this->getCollection($command->getOptions('table'));
        $cursor = $collection->aggregate($command->getOptions('pipelines'),$command->getOptions('opts'));

        $result = $this->cursorToArray($cursor);

        return $result;
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
    public function aggregate($command = [])
    {
        $collection = $this->getCollection($command->getOptions('table'));
        $cursor = $collection->aggregate($command->getOptions('pipelines'),$command->getOptions('opts'));

        $result = $this->cursorToArray($cursor);


        return $result;
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
        $cursor = $this->conn->getManager()->executeCommand($this->config['database'], $command);
        $result = $this->objToArray($cursor);

        return $result;
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
        $command = new \MongoDB\Driver\Command($command->getCommand());
        $cursor = $this->conn->getManager()->executeCommand($this->config['database'], $command);
        $result = $this->objToArray($cursor);

        if (isset($result) && isset($result[0]['n'])) {
            return $result[0]['n'];
        } else {
            return 0;
        }
    }

    public function beginTransaction()
    {
        $this->tranSession = $this->conn->startSession();
        $this->tranSession->startTransaction();
    }

    public function commit()
    {
        $this->tranSession->commitTransaction();
    }

    public function rollback()
    {
        $this->tranSession->abortTransaction();
    }



	
}