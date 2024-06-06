<?php
namespace horm\base;

use horm\pools\PoolDbConnection;
use PDO;
use PDOException;
use PDOStatement;
use Exception;

/**
 * 数据库连接基类
 *<B>说明：</B>
 *<pre>
 *  数据库连接基类，所有数据库连接都必须继承此类
 *</pre>
 */
class DbConnection extends BaseConnection
{
    use PoolDbConnection;

	/**
	 * 当前连接PDO实例
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var PDO
	 */
    protected $conn = null;

	/**
	 * 事务指令数
	 *<B>说明：</B>
	 *<pre>
	 *  用于避免重复操作事务
	 *</pre>
	 * @var int
	 */
    protected $transTimes = 0;

    /**
     * 断线重连错误信息
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var array
     */
	protected $reErrors = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'failed with errno',
    ];

    /**
     * 格式化db 连接配置
     *<B>说明：</B>
     *<pre>
     *  初始化数据库连接
     *</pre>
     */
    protected function formatConfig()
    {
        if (!isset($this->config['options'])) {
            $this->config['options'] = [];
        }

        if (!isset($this->config['charset'])) {
            $this->config['charset'] = 'utf8';
        }

        if (!isset($this->config['port'])) {
            $this->config['port'] = '3306';
        }

        if (!isset($this->config['prefix'])) {
            $this->config['prefix'] = '';
        }

        if (!isset($this->config['dsn'])) {
            $this->config['dsn'] = $this->parseDsn();
        }

    }

    /**
     * 连接数据库
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
	 * @return PDO
	 * @throws Exception
     */
	public function connect()
	{
	    if (is_null($this->conn)) {
            try {
                $this->formatConfig();
                $username = isset($this->config['username']) ? $this->config['username'] : '';
                $password = isset($this->config['password']) ? $this->config['password'] : '';
                $options = isset($this->config['options']) ? $this->config['options'] : [];

                //创建pdo 数据库连接
                $this->conn = new PDO($this->config['dsn'], $username, $password,$options);

                return $this->conn;
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage());
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
     * @return PDO
     */
    public function getConn():PDO
    {
        if (is_null($this->conn)) {
            $this->conn = $this->connect();
        }

        return $this->conn;
    }

    /**
     * 重连连接
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     */
    protected function resetConnect()
    {
        $this->conn = null;
        $this->getConn();
    }

    /**
     * 解析pdo连接的dsn信息
     *<B>说明：</B>
     *<pre>
     *  	由数据库驱动类实现此方法
     *</pre>
     * @return string
     */
    protected function parseDsn()
    {
        return '';
    }

	/**
	 * 执行查询 返回数据行
	 *<B>说明：</B>
	 *<pre>
	 *  只执行查询的sql
	 *</pre>
	 * @param QueryCommand $command  sql 命令对象
	 * @return array
	 */
	public function callQuery(QueryCommand $queryCommand)
	{

        $pdoStatement = $this->executeCommand($queryCommand);

        return $this->getResult($pdoStatement);
	}

    /**
     * 执行更新sql语句
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param QueryCommand $queryCommand sql命令对象
     * @return int
     */
    public function callExecute(QueryCommand $queryCommand)
    {
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 执行查询 返回数据行
     *<B>说明：</B>
     *<pre>
     *  只执行查询的sql
     *</pre>
     * @param QueryCommand $queryCommand  sql命令对象
     * @return PDOStatement|boolean
     */
	protected function createPDOStatement(QueryCommand $queryCommand):PDOStatement
    {
        $pdoStatement = $this->getConn()->prepare($queryCommand->getCommand());
        // 参数绑定
        foreach ($queryCommand->getParams() as $key => $val) {
            if (is_array($val)) {
                $pdoStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $pdoStatement->bindValue($key, $val);
            }
        }

        return $pdoStatement;
    }


    protected function executeCommand(QueryCommand $queryCommand):PDOStatement
    {

        $pdoStatement = $this->createPDOStatement($queryCommand);
        try {
            $result = $pdoStatement->execute();
            if (empty($result)) {
                $errorInfo = $pdoStatement->errorInfo();
                throw new PDOException($errorInfo[2],$errorInfo[1]);
            }

            return $pdoStatement;
        } catch (Exception $e) {
            if ($this->checkReconnect($e)) {
                $this->resetConnect();
                return $this->executeCommand($queryCommand);
            }

            if ($e instanceof PDOException) {
                throw new PDOException($this->getErrorMessage($e),$e->getCode());
            } else {
                throw $e;
            }
        }
    }

    protected function getErrorMessage(\PDOException $e)
    {
        $message = ' database key:' . $this->dbKey . ' error info ' . $e->getMessage() ;

        return $message;
    }



    /**
     * 检测是否需要重连连接
     *<B>说明：</B>
     *<pre>
     *  ；略
     *</pre>
     * @param Exception $e
     * @return boolean
     */
	protected function checkReconnect($e)
    {
        if (!isset($this->config['reconnect']) || $this->config['reconnect'] == false) {
            return false;
        }

        $pdoerror = $e->getMessage();

        foreach ($this->reErrors as $errormsg) {
            if (false !== stripos($pdoerror, $errormsg)) {
                return true;
            }
        }

        return false;
    }

	/**
	 * 开启事务
	 *<B>说明：</B>
	 *<pre>
	 *  如果事务已经开启，则不再开启
	 *</pre>
	 * @return boolean true 表示事务开启成功，false 表示事务开启失败
	 */
	public function beginTransaction()
	{
	    $conn = $this->getConn();
        if (!$this->conn) {
            return false;
        }

        //数据rollback 支持
        if ($this->transTimes == 0) {
            $result = $conn->beginTransaction();
			if (!$result) {
				return false;
			}
        }

        $this->transTimes++;

        return true;
	}

	/**
	 * 提交事务
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
     * @return boolean true 表示事务提交成功，false 表示事务提交失败
	 */
	public function commitTransaction()
	{
        if ($this->transTimes > 0) {
            $result = $this->conn->commit();
			$this->clearTrans();
            if ($result === false) {
                return false;
            }
        }

        return true;
	}

	/**
	 * 回滚事务
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return boolean true 表示事务回滚成功，false 表示事务回滚失败
	 */
	public function rollbackTransaction()
	{
        if ($this->transTimes > 0) {
            $result = $this->conn->rollback();
            $this->clearTrans();
			if ($result === false) {
                return false;
            }
        }

        return true;
	}

	/**
	 * 清空事务参数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return void
	 */
	private function clearTrans()
	{
		$this->transTimes = 0;

		return ;
	}


	/**
	 * 获取最近插入的ID
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return string|int
	 */
    public function getLastId($sequence = '')
	{
        return $this->conn->lastInsertId();
    }

    /**
     * 获得查询数据
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return array
     */
    protected function getResult(PDOStatement $pdoStatement)
    {
        //返回数据集
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    /**
     * 插入单条数据
     * @param string $table
     * @param array $data
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function insert(string $table,array $data = [],$options = [])
    {

        $query_opts = array_merge($options,['table'=>$table,'data'=>$data]);
        $query = (new Query($query_opts));
        $query->setBuild('insert',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 批量插入数据
     * @param string $table
     * @param array $data
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function insertAll(string $table,array $data = [],$options = [])
    {
        $query_opts = array_merge($options,['table'=>$table,'data'=>$data]);
        $query = (new Query($query_opts));
        $query->setBuild('insertAll',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 更新数据
     * @param string $table
     * @param array $data
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function update(string $table,array $data = [],array $condition = [],array $options = [])
    {
        $query_opts = array_merge($options,['table'=>$table,'data'=>$data,'where'=>$condition]);
        $query = (new Query($query_opts));
        $query->setBuild('update',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 删除数据
     * @param string $table
     * @param array $data
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function delete(string $table,array $condition = [],array $options = [])
    {

        $query_opts = array_merge($options,['table'=>$table,'where'=>$condition]);
        $query = (new Query($query_opts));
        $query->setBuild('delete',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 获取一条数据
     * @param string $table
     * @param array $condition
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function fetchOne(string $table,array $condition = [],array $options = [])
    {

        $query_opts = array_merge($options,['table'=>$table,'where'=>$condition,'limit'=>1]);
        $query = (new Query($query_opts));
        $query->setBuild('select',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);
        $queryResult = $this->getResult($pdoStatement);
        if (empty($queryResult)) {
            return [];
        }

        return $queryResult[0];
    }

    /**
     * 获取一条数据
     * @param string $table
     * @param array $condition
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function fetchAll(string $table,array $condition = [],array $options = [])
    {

        $query_opts = array_merge($options,['table'=>$table,'where'=>$condition]);
        $query = (new Query($query_opts));
        $query->setBuild('select',[$query]);

        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);
        $queryResult = $this->getResult($pdoStatement);

        return $queryResult;
    }

    /**
     * 执行原始 sql
     * @param string $sql
     * @param array $params
     */
    public function execSql(string $sql,array $params = [])
    {
        $query = (new Query())->setRawCommand($sql,$params);
        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $pdoStatement->rowCount();
    }

    /**
     * 查询原始 sql
     * @param string $sql
     * @param array $params
     */
    public function querySql(string $sql,array $params = [])
    {
        $query = (new Query())->setRawCommand($sql,$params);
        $queryCommand = $this->getQueryBuilder()->buildQueryCommand($query);
        $pdoStatement = $this->executeCommand($queryCommand);

        return $this->getResult($pdoStatement);
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

}
