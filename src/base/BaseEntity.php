<?php
namespace  horm\base;

use horm\Dbsession;
use horm\Entity;
use horm\shard\base\ShardRule;
use horm\QueryTable;
use horm\util\DbUtil;


/**
 * 数据实体基类
 *<B>说明：</B>
 *<pre>
 * 通过对象的方式操作数据
 *</pre>
 * @method static QueryTable setScope($scope,...$args)
 * @method static QueryTable setShard($shard_columns = [])
 * @method static QueryTable setField($fields = [])
 * @method static QueryTable setWhere($where = [], $params = [])
 * @method static QueryTable setTable($table = '')
 * @method static QueryTable setAlias($alias = '')
 * @method static QueryTable setJoin($table, $on, $joinType = '')
 * @method static QueryTable setLeftJoin($table, $on)
 * @method static QueryTable setInnerJoin($table, $on)
 * @method static QueryTable setWith($with,$join = false,$load = true)
 * @method static QueryTable setLeftWith($with,$load = true)
 * @method static QueryTable setInnerWith($with,$load = true)
 * @method static QueryTable setOrder($order = [])
 * @method static QueryTable setGroup($group = [])
 * @method static QueryTable setLimit($length = null)
 * @method static QueryTable setOffset($offset = null)
 * @method static QueryTable setParam($params = null)
 * @method static QueryTable asArray($asArray = true)
 * @method static QueryTable asQuery($asQuery = true)
 * @method static QueryTable asMaster($asMaster = true)
 * @method static QueryTable asId($asId = true)
 * @method static QueryTable queryCmd($queryCommand, $params = [])
 * @method static QueryTable execCmd($execCommand, $params = [])
 * @method static QueryTable addParams($params = [])
 * @method static QueryTable count($field = null,$where = [])
 * @method static QueryTable queryMax($field = null, $where = [])
 * @method static QueryTable queryMin($field = null, $where = [])
 * @method static QueryTable querySum($field = null, $where = [])
 * @method static QueryTable queryAvg($field = null, $where = [])
 *
 */
class BaseEntity
{
    /**
     * 实体属性值集合
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected $_values = [];

    /**
     * 隐式id
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string|int
     */
    protected $_id = null;

    /**
     * 对象属性与table字段对应关系
     *<B>说明：</B>
     *<pre>
     *  存储当前类属性与db 的对应关系
     *  ['对象属性'=>'表字段']
     *</pre>
     * @var array
     */
    protected static $_attrs = null;



    /**
     * 更新过的属性名称
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected $_updateAttrs = [];

    /**
     * 分库规则对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var ShardRule
     */
    protected static $_dbShardRule = null;

    /**
     * 分表规则对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var ShardRule
     */
    protected static $_tbShardRule = null;

    public function __construct($attrs = [])
    {
        if (!empty($attrs)) {
            foreach ($attrs as $name=>$value) {
                $this->$name = $value;
            }
        }
    }

    protected static function getTbShardRule()
    {
        if (static::$_tbShardRule == null) {
            static::$_tbShardRule =  static::tbShardRule();
        }

        return static::$_tbShardRule;
    }

    protected static function getDbShardRule()
    {
        if (static::$_dbShardRule == null) {
            static::$_dbShardRule =  static::dbShardRule();
        }

        return static::$_dbShardRule;
    }

    /**
     * 定义 db管理器
     * @return Dbsession
     */
    public static function dbSession()
    {
        return null;
    }

    /**
     * 定义数据库key
     * @return string
     */
    public static function dbKey()
    {
        return '';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        return '';
    }

    /**
     * 定义分表规则
     */
    public static function tbShardRule()
    {
        return '';
    }

    /**
     * 定义分库规则
     */
    public static function dbShardRule()
    {
        return '';
    }

    /**
     * 定义主从库规则
     */
    public static function dbSlave()
    {
        return '';
    }

    /**
     * 是否自增
     *<B>说明：</B>
     *<pre>
     * 返回true表示实体有自增id,
     * 返回string,表示自增序列的名称
     * 返回false 表示实体中没有自增列
     *</pre>
     * @return bool|string true 表示实体有自增id,
     */
    public static function autoIncrement()
    {
        return false;
    }

    /**
     * 定义表分区方式
     *<B>说明：</B>
     *<pre>
     * QueryTable::class 不分区
     * ShardTable::class 分表
     * ShardDb::class 分库
     * ShardDbTable::class 分库分表
     *</pre>
     * @return string
     */
    public static function queryTable()
    {
        return QueryTable::class;
    }

    /**
     * 定义主键列名
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     */
    public static function pk()
    {
        return '';
    }

    /**
     * 设置属性值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $name 属性名称
     * @param string $value 属性值
     */
    public function __set($name, $value):void
    {
        $this->_updateAttrs[] = $name;
        $this->_values[$name] = $value;
    }

    /**
     * 设置属性值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $name 属性名称
     * @return string
     */
    public function __get($name)
    {
        if (isset($this->_values[$name])) {
            return  $this->_values[$name];
        } else {
            return null;
        }
    }


    /**
     * 属性转换成真实表字段
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs 属性名称值
     * @return array
     */
    protected static function attrToColumn($attrs = []):array
    {
        if (!empty(static::$_attrs)) {
            foreach (static::$_attrs as $attrName=>$columnName) {
                if (isset($attrs[$attrName])) {
                    $attrs[$columnName] = $attrs[$attrName];
                    unset($attrs[$attrName]);
                }
            }
        }

        return $attrs;
    }

    /**
     * 属性转换成真实表字段
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $listAttrs 属性名称值列表
     * @return array
     */
    protected static function attrsToColumns($listAttrs = []):array
    {
        $list = [];
        foreach ($listAttrs as $index=>$attrs) {
            $list[$index] = static::attrToColumn($attrs);
        }

        return $list;
    }

    /**
     * 真实表字段换成属性转
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $columns 表字段值
     * @return array
     */
    protected static function columnToAttr($columns = []):array
    {
        if (!empty(static::$_attrs)) {
            foreach (static::$_attrs as $attrName=>$columnName) {
                if (isset($columns[$columnName])) {
                    $columns[$attrName] = $columns[$columnName];
                    unset($columns[$columnName]);
                }
            }
        }

        return $columns;
    }

    /**
     * 获取属性对应的表字段名
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrName 属性名称
     * @return string
     */
    protected static function getColumnName($attrName)
    {
        if (isset(static::$_attrs[$attrName])) {
            return static::$_attrs[$attrName];
        } else {
            return $attrName;
        }
    }

    /**
     * 获取主键对应的表字段
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    protected static function getPkColumn()
    {
        return static::getColumnName(static::pk());
    }

    /**
     * 设置对象属性值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $value 属性值数组
     * @return string
     */
    public function setValues($value = [])
    {
        if (!is_null($value)) {
            $this->_values = $value;
        }

        return $this;
    }

    /**
     * 设置对象属性值
     *<B>说明：</B>
     *<pre>
     * 此方法设置属性后会标识属性更新状态
     *</pre>
     * @param array $attrs 属性值数组
     * @return string
     */
    public function setAttrs($attrs = [])
    {
        foreach ($attrs as $attrName=>$value) {
            $this->setAttr($attrName);
        }

        $this->_values = $attrs;
    }

    protected function setAttr($attrName)
    {
       $this->_updateAttrs[$attrName] = $attrName;
    }

    /**
     * 添加数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs 属性值
     * @return int
     */
    public function add($attrs = null)
    {

        if (!is_null($attrs)) {
            $this->setValues($attrs);
        }

        if (empty($this->_values)) {
            return false;
        }

        $queryTable = static::getQueryTable();
        $values =  static::attrToColumn($this->_values);
        $result = $queryTable->setData($values)->addOne();

        $id = static::pk();

        // 判断是否读取自增id
        if (!empty(static::autoIncrement())  && isset($id) && !isset($this->_values[$id])) {
            $lastId = $queryTable->getLastId(static::autoIncrement());
            $this->_values[$id] = $lastId;
            $this->_id = $lastId;
        }

        return $result;
    }

    /**
     * 保存属性
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs
     * @return int
     * @throws \Exception
     */
    public function update($attrs = [])
    {
        if (!empty($attrs)) {
            $this->setAttrs($attrs);
        }

        $values = $this->getUpdateData();
        if (empty($values)) {
            return false;
        }

        $values =  static::attrToColumn($values);
        $where = [];
        $id = static::pk();
        if (!empty($id) && isset($values[$id])) {
            $where = [
                static::getPkColumn()=> $values[$id]
            ];

            unset($values[$id]);
        }

        if (empty($where)) {
            throw new \Exception('entity %s update where is empty',get_class($this));
        }

        $queryTable = static::getQueryTable();
        $result = $queryTable->setData($values)->setWhere($where)->updateOne();

        return $result;
    }

    /**
     * 批量添加
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs 属性值列表
     * @return int
     */
    public static function addOne(array $attrs = [])
    {
        $attrs = static::attrToColumn($attrs);
        $queryTable = static::getQueryTable();

        return $queryTable->setData($attrs)->addOne();
    }

    /**
     * 批量添加
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $listAttrs 属性值列表
     * @return int
     */
    public static function addAll($listAttrs)
    {
        $listAttrs = static::attrsToColumns($listAttrs);
        $queryTable = static::getQueryTable();

        return $queryTable->setData($listAttrs)->addAll();
    }

    /**
     * 更新一条数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs 属性
     * @param array $where 条件
     * @param array $params 预处理参数
     * @return boolean|int
     */
    public static function updateOne(array $attrs,array $where = [],array $params = [])
    {
        $values =  static::attrToColumn($attrs);
        $pk_where = [];
        $id = static::pk();
        if (!empty($id) && isset($values[$id])) {
            $pk_where = [
                static::getPkColumn()=> $values[$id]
            ];

            unset($values[$id]);
        }

        $where = array_merge($pk_where,$where);
        if (empty($where)) {
            return false;
        }

        $queryTable = static::getQueryTable();
        $result = $queryTable->setData($values)->setWhere($where)->addParams($params)->updateOne();

        return $result;
    }

    /**
     * 更新多条数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $attrs 属性
     * @param array $where 条件
     * @param array $params 预处理参数
     * @return boolean|int
     */
    public static function updateAll(array $attrs,array $where = [],array $params = [])
    {
        $values =  static::attrToColumn($attrs);
        $pk_where = [];
        $id = static::pk();
        if (!empty($id) && isset($values[$id])) {
            $pk_where = [
                static::getPkColumn()=> $values[$id]
            ];

            unset($values[$id]);
        }

        $where = array_merge($pk_where,$where);
        if (empty($where)) {
            return false;
        }

        $queryTable = static::getQueryTable();
        $result = $queryTable->setData($values)->setWhere($where)->addParams($params)->updateAll();

        return $result;
    }

    /**
     * 实例化当前对象
     *<B>说明：</B>
     *<pre>
     *  从库拉取一条数据,填充对象属性
     *</pre>
     * @param string|array $condition 查询条件
     * @return static
     */
    public static function get($condition = null)
    {
        if (is_null($condition)) {
            return self::make();
        }

        $where = static::formatCondition($condition);
        $entity = static::getQueryTable()->setWhere($where)->fetchOne();
        if (empty($entity)) {
            return null;
        }

        return $entity;
    }

    /**
     * 获取一条实体记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|array $condition 查询条件
     * @param array $orders 排序规则
     * @param array $params 预处理参数
     * @return static
     */
    public static function fetchOne($condition = null,$orders = [],array $params = []):?self
    {
        $where = static::formatCondition($condition);
        $data = static::getQueryTable()->setWhere($where,$params)->setOrder($orders)->fetchOne();

        return $data;
    }

    /**
     * 获取多条实体记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|array $condition 查询条件
     * @param array $orders 排序规则
     * @param array $params 预处理参数
     * @return static[]
     */
    public static function fetchAll($condition = null,$orders = [],array $params = []):? array
    {
        $where = static::formatCondition($condition);
        $datas = static::getQueryTable()->setWhere($where,$params)->setOrder($orders)->fetchAll();

        return $datas;
    }

    /**
     * 删除当前对象记录
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return integer
     * @throws \Exception
     */
    public function delete()
    {
        $where = $this->buildPkWhere();
        // 没有条件,不执行删除操作
        if (empty($where)) {
            throw new \Exception('entity %s delete where is empty',get_class($this));
        }

        $where = static::toWhereColumn($where);
        $deleteResult = static::getQueryTable()->setWhere($where)->deleteOne();
        // 清空当前对象数据
        $this->clear();

        return $deleteResult;
    }

    /**
     * 根据条件删除记录
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition
     * @param array $params
     * @return integer
     */
    public static function deleteAll($condition = [],array $params = [])
    {
        $where = static::formatCondition($condition);
        if (empty($where)) {
            throw new \Exception('entity %s delete where is empty',static::class);
        }

        $deleteResult = static::getQueryTable()->setWhere($where,$params)->deleteAll();

        return $deleteResult;
    }

    /**
     * 根据条件删除记录
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition
     * @param array $params
     * @return integer
     */
    public static function deleteOne($condition = [],array $params = [])
    {
        $where = static::formatCondition($condition);
        if (empty($where)) {
            throw new \Exception('entity %s delete where is empty',static::class);
        }

        $deleteResult = static::getQueryTable()->setWhere($where,$params)->deleteOne();

        return $deleteResult;
    }


    /**
     * 格式化查询条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array|int|string $condition
     * @return integer
     */
    protected static function formatCondition($condition = null)
    {
        if (empty($condition)) {
            return null;
        }

        if (is_array($condition)) {
            if (DbUtil::checkIdInWhere($condition)) {
                $pk = static::pk();
                $where = [
                    $pk=>[BaseQueryBuilder::EXP_IN,$condition]
                ];
            } else {
                $where = static::toWhereColumn($condition);
            }
        } else {
            $pk = static::pk();
            $where = [
                $pk=>$condition
            ];
        }

        return $where;
    }

    /**
     * 清空当前数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     */
    protected function clear()
    {
        $this->_values = [];
    }

    /**
     * 根据对象属性值创建对象
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $data 对象属性
     * @return static
     */
    public static function make($data = []):self
    {
        $entity  = new static();
        $entity->setValues($data);

        return $entity;
    }

    /**
     * 根据表字段值创建对象
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $column 表字段值
     * @return static
     */
    public static function makeByColumn($column = []):self
    {
        $entity  = new static();
        $column = static::columnToAttr($column);
        $entity->setValues($column);

        $pk = static::pk();
        if (!empty($pk) && isset($column[$pk])) {
            $entity->_id = $column[$pk];
        }

        return $entity;
    }

    /**
     * 返回当前属性值全部
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return array
     */
    public function toArray()
    {
        return $this->_values;
    }

    function __tostring()
    {
        //对象转化为json格式
        return json_encode($this->_values,JSON_FORCE_OBJECT);
    }

    /**
     * 当前属性值转成sql对应表字段值
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return array
     */
    public function toColumnArray()
    {
        return static::attrToColumn($this->_values);
    }

    /**
     * 构建主键条件
     *<B>说明：</B>
     *<pre>
     * 用于save,delete,添加的自动获取
     *</pre>
     * @param  array
     * @return array|null
     */
    protected function buildPkWhere()
    {
        $pk = static::pk();
        if (isset($this->_values[$pk])) {
            $where = [$pk=>$this->_values[$pk]];
        } else {
            $where = [];
        }

        return $where;
    }

    /**
     * where 条件转成表字段
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param  array $whereColumn
     * @return array
     */
    protected static function toWhereColumn($whereColumn = [])
    {
        return $whereColumn;
    }

    /**
     * 获取更新的数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return array
     */
    protected function getUpdateData()
    {
        if (!empty($this->_updateAttrs)) {
            $data = [];
            foreach ($this->_updateAttrs as $attr) {
                if (isset($this->_values[$attr])) {
                    $data[$attr] = $this->_values[$attr];
                }
            }

            return $data;
        } else {
            return [];
        }
    }

    /**
     * 创建QueryTable　对象
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return QueryTable
     */
    public static function getQuery()
    {
        return static::getQueryTable();
    }

    /**
     * 创建表对象
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return QueryTable
     */
    public static function getQueryTable():QueryTable
    {

        $queryTableClass = static::queryTable();

        /** @var QueryTable $queryTable*/
        $queryTable =  new $queryTableClass();
        $queryTable->setDbkey(static::dbKey())
            ->setPk(static::pk())
            ->setSeq(static::autoIncrement())
            ->setTbRule(static::getTbShardRule())
            ->setDbRule(static::getDbShardRule())
            ->setTable(static::tableName())
            ->setClass(static::class)
            ->setEntity(static::class)
            ->setDbsession(static::dbSession());

        return $queryTable;
    }

    /**
     * 事务开启
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param boolean $isTransaction 是否返回事务对象
     * @return boolean|Transaction
     */
    public static function beginTransaction(bool $isTransaction = false)
    {
        if ($isTransaction) {
            $transaction = static::dbSession()->getTransaction(static::dbKey());
            $transaction->beginTransaction();
            return $transaction;
        } else {
            return static::dbSession()->beginTransaction();
        }
    }

    /**
     * 事务提交
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return boolean
     */
    public static function commitTransaction()
    {
        return static::dbSession()->commitTransaction();
    }

    /**
     * 事务回滚
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return boolean
     */
    public static function rollbackTransaction()
    {
        return static::dbSession()->rollbackTransaction();
    }

    /**
     * 获取最后一条sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    public static function getLastCmd():string
    {
        return static::dbSession()->getLastCmd();
    }

    /**
     * 获取最近的自增id
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string|int|mixed
     */
    public static function getLastId()
    {
        return static::dbSession()->getLastId(static::autoIncrement());
    }

    /**
     * 隐式调用QueryTable方法
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $method
     * @param array $args
     * @return QueryTable|array|mixed
     */
    public function __call($method, $args)
    {
        $queryTable = static::getQueryTable();

        return call_user_func_array([$queryTable, $method], $args);
    }

    /**
     * 隐式调用QueryTable方法
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $method
     * @param array $args
     * @return QueryTable|array|mixed
     */
    public static function __callStatic($method, $args)
    {
        $queryTable = static::getQueryTable();

        return call_user_func_array([$queryTable, $method], $args);
    }

    /**
     * 描述一对一关系
     *<B>说明：</B>
     *<pre>
     * 　略
     *</pre>
     * @param string $model
     * @param array $refs
     * @return QueryTable
     */
    public static function hasOne($model,$refs):QueryTable
    {
        return static::createRelationQueryTable($model,$refs,false);
    }

    /**
     * 描述一对多关系
     *<B>说明：</B>
     *<pre>
     * 　略
     *</pre>
     * @param string $model
     * @param array $refs
     * @return QueryTable
     */
    public static function hasMany($model,$refs):QueryTable
    {
        return static::createRelationQueryTable($model,$refs,true);
    }

    /**
     * 创建关联QueryTable 对象
     *<B>说明：</B>
     *<pre>
     * 　略
     *</pre>
     * @param Entity $model
     * @param array $refs
     * @return QueryTable
     */
    protected static function createRelationQueryTable($model,$refs,$multiple)
    {
        $queryTable = $model::getQueryTable();
        $queryTable->model = $model;
        $queryTable->refs = $refs;
        $queryTable->multiple = $multiple;

        return $queryTable;
    }

}

?>
