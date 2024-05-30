<?php
namespace horm\base;

use horm\Dbsession;

/**
 * 数据库连接基类
 *<B>说明：</B>
 *<pre>
 *  数据库连接基类，所有数据库连接都必须继承此类
 *</pre>
 */
abstract class BaseConnection
{

    /**
     * 创建db 链接
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var Dbsession
     */
    protected $dbsession = null;

    /**
     * db key
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    public $dbKey = '';

	/**
	 * 数据库连接参数配置
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	public $config = [];

	protected $ref_method_dict = [];

    /**
     * 构造方法
     *<B>说明：</B>
     *<pre>
     *  初始化数据库连接
     *</pre>
     * @param array $attrs 数据库连接配置
     */
    public function __construct($attrs = [])
    {
        if (!empty($attrs)) {
            foreach ($attrs as $name=>$value) {
                $this->$name = $value;
            }
        }
    }

    protected function getMethodParams($method,$params = [])
    {
        $reflectionMethod = new \ReflectionMethod($this, $method);
        $ref_params = [];
        if (!isset($this->ref_method_dict[$method])) {
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $name = $parameter->getName();
                $ref_params[$name] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }

            $this->ref_method_dict[$method] = $ref_params;
        } else {
            $ref_params = $this->ref_method_dict[$method];
        }

        $method_params = [];
        foreach ($ref_params as $name=>$val) {
            if (isset($params[$name])) {
                $method_params[] = $params[$name];
            } else {
                $method_params[] = $val;
            }
        }

        return $method_params;
    }

    /**
     * 获取表前缀
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->config['prefix'];
    }

    public function getConfig($name)
    {
        return $this->config[$name];
    }

    /**
     * 获取主从状态
     *<B>说明：</B>
     *<pre>
     *  ；略
     *</pre>
     * @return boolean
     */
    public function isSlave()
    {
        return isset($this->config['onSlave']) ? $this->config['onSlave'] : false;
    }

    /**
     * 获取主从状态
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return boolean
     */
    public function getSlaveDbkey()
    {
        if (empty($this->_slaveHandler)) {
            $this->_slaveHandler = $this->dbsession->buildSlaveHandler($this->config['slaveHandler']);
            if (empty($this->_slaveHandler)) {
                $this->_slaveHandler = [$this,'randSlave'];
            }
        }

        return call_user_func_array($this->_slaveHandler,[$this]);
    }

    protected function randSlave(DbConnection $dbconn)
    {
        $slaves = $dbconn->getConfig("slaves");

        return $slaves[mt_rand(0,count($slaves) -1)];
    }

	/**
	 * 执行查询 返回数据行
	 *<B>说明：</B>
	 *<pre>
	 *  只执行查询的sql
	 *</pre>
	 * @param QueryCommand $command  sql 命令对象
	 * @return array|boolean
	 *<pre>
	 *  略
	 *</pre>
	 */
	abstract public function callQuery($command);

	/**
	 * 执行更新sql语句
     *<B>说明：</B>
     *<pre>
     *  只执行更新，删除，插入等sql语句
     *</pre>
	 * @param QueryCommand $command sql命令对象
	 * @return int
	 */
    abstract public function callExecute($command);

    public function beginTransaction()
    {

    }

    public function commit()
    {

    }

    public function rollback()
    {

    }

    public function getLastInsertID()
    {

    }

	/**
	 * 获取sql生成对象
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return BaseQueryBuilder
	 */
	public function getQueryBuilder(){}
}
