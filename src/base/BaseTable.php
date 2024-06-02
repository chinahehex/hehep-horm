<?php
namespace  horm\base;

use common\entitys\AdminUserRoleEntity;
use horm\base\BaseQueryBuilder;
use horm\base\DbConnection;
use Exception;
use horm\Dbsession;
use horm\Entity;
use horm\QueryTable;
use horm\shard\base\ShardRule;
use horm\util\DbUtil;

/**
 * 实体基类
 *<B>说明：</B>
 *<pre>
 * 基础此类，必须实现,更新与新增的底层抽象方法（addInternal,updateInternal,deleteInternal,queryInternal,queryScalarInternal）
 * 通过实现扩展这些抽象方法，可以实现其他功能,比如 分表,分库
 *</pre>
 */
abstract class BaseTable
{
    /**
     * sql参数
     *<B>说明：</B>
     *<pre>
     *  存储表名，查询条件，排序
     * $options = [
     *      'table'=>'user',// 表名
     *      'field'=>'username,role_id',// 查询字段名，
     *      'alias'=>'u',// 表别名 ps:user as u
     *      'where'=>[],// 条件
     *      'join'=>'',// 连表设置
     *      'with'=>[],// with设置
     *      'lock'=>false,// 是否加锁(锁表或锁行)
     *      'order'=>'',// 查询排序设置
     *      'limit'=>[],// 读取行数，或读取范围
     *      'distinct'=>false,// 是否取消重复行
     *      'file'=>'',// 通过文件导入数据至数据库设置
     *      'group'=>'',// 分组
     *      'having'=>[],// 分组条件
     *      'params'=>[],// 绑定参数
     *      'shard'=>[],// 分库分表规则
     *      'seq'=>'',// 序号
     *      'dbkey'=>'',//数据库连接键名
     *      'isMaster'=>false,// 是否强制从主库读取
     *      'formats'=>[],
     * ]
     *</pre>
     * @var array
     */
    protected $options = [];

    /**
     * 实体类路径
     * @var string
     */
    protected $entity = null;

    /**
     * Db管理器
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var Dbsession
     */
    protected $dbsession = null;

    public function __construct(array $attrs = [])
    {
        if (!empty($attrs)) {
            foreach ($attrs as $name=>$value) {
                $this->$name = $value;
            }
        }
    }


    /**
     * @param $dbShardRule
     * @return $this
     */
    public function setDbRule($dbShardRule)
    {
        return $this;
    }

    /**
     * @param $tbShardRule
     * @return $this
     */
    public function setTbRule($tbShardRule)
    {
        return $this;
    }

    /**
     * 定义当前表名
     *<B>说明：</B>
     *<pre>
     *  eg:{{%tablename}} 表示tablename不带前缀
     *  tablename 真实表名
     *</pre>
     * @return string
     */
    public function tableName()
    {
        return '';
    }

    /**
     * 定义当前数据库key
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
    public function dbKey()
    {
        return '';
    }


    public function setDbkey(string $dbKey):self
    {
        $this->options['dbkey'] = $dbKey;

        return $this;
    }

    public function setPk(string $pk = ''):self
    {
        $this->options['pk'] = $pk;

        return $this;
    }

    /**
     * 定义表主键字段名
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return string
     */
    public function getPk()
    {
        if (isset($this->options['pk'])) {
            return $this->options['pk'];
        }

        return '';
    }



    /**
     * 获取db管理器
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return Dbsession
     */
    public function getDbsession():Dbsession
    {
        return $this->dbsession;
    }

    public function setDbsession(Dbsession $dbsession):void
    {
        $this->dbsession = $dbsession;
    }


    /**
     * 连接数据库
     *<B>说明：</B>
     *<pre>
     *  实例化DbConnection类对象
     *</pre>
     * @param string $dbkey 数据库连接键名
     * @return DbConnection
     * @throws Exception
     */
    public function getDb(string $dbkey = ''):?BaseConnection
    {
        $dbConnection = $this->getDbsession()->getDbConnection($dbkey ? $dbkey : $this->dbKey());

        return $dbConnection;
    }


    /**
     * 设置结果集是否包装成类
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param  string|boolean|\Closure $stdClass
     * @return static
     */
    public function setClass($stdClass = true):self
    {
        $this->options['class'] = $stdClass;
        $this->asArray(false);

        return $this;
    }

    /**
     * 设置实体类
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param  string $entity
     * @return static
     */
    public function setEntity(string $entity):self
    {
        $this->options['entity'] = $entity;
        $this->entity = $entity;

        return $this;
    }

    /**
     * 设置类
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|array $scope
     * @param mixed ...$args
     * @return static
     */
    public function setScope($scope,...$args):self
    {
        if (empty($scope)) {
            throw new Exception('scope not empty');
        }

        array_unshift($args, $this);

        if ($scope instanceof \Closure) {
            call_user_func_array($scope, $args);
            return $this;
        }

        if (is_string($scope)) {
            $scope = explode(',', $scope);
        }

        if ($this->entity) {
            foreach ($scope as $name) {
                $method = 'scope'. ucfirst(trim($name)) ;
                if (method_exists($this->entity, $method)) {
                    call_user_func_array([$this->entity, $method], $args);
                }
            }
        }

        return $this;
    }

    /**
     * 设置添加、更新数据
     *<B>说明：</B>
     *<pre>
     *  不支持多次调用
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：添加单行记录
     *  $data = array('name'=>'admin','pwd'=>'123456');
     *  $this->setData($data)->addRows();
     *  示例2：批量添加多行记录
     *    $data = array(
     *    array('name'=>'admin','pwd'=>'123456'),
     *    array('name'=>'admin2','pwd'=>'123457')
     *  );
     *  $this->setData($data)->addRows();
     *</pre>
     * @param  array $data 数据行(一维数组或或二维数组)
     * @return static
     */
    public function setData($data = []):self
    {
        $this->options['data'] = $data;

        return $this;
    }

    /**
     * 设置表名
     *<B>说明：</B>
     *<pre>
     *  设置的表名不带表前缀
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：查询name等于admin 的数据行
     *  $where = array('name'=>'admin');
     *  $this->setTable('User')->setWhere($where)->queryRows();
     *
     *  示例2：查询数据-定义表别名
     *  $where = array('name'=>'admin');
     *  $this->setTable('User as u')->setWhere($where)->queryRows();
     *
     *  示例3：查询数据-定义表别名
     *  $where = array('name'=>'admin');
     *  $this->setTable(['User','u'])->setWhere($where)->queryRows();
     *</pre>
     * @param string $table 表名
     * @return static
     */
    public function setTable($table = ''):self
    {
        // 分析表名是否带as
        if (is_array($table)) {
            list($table,$alias) = $table;
        } else {
            $table = DbUtil::splitAlias($table);
            list($table,$alias) = $table;
        }

        $this->options['table'] = $table;
        if (!empty($alias)) {
            $this->setAlias($alias);
        }

        return $this;
    }

    public function getTable()
    {
        return isset($this->options['table']) ? $this->options['table'] : '';
    }


    /**
     * 设置读取字段列
     *<B>说明：</B>
     *<pre>
     * 1、一般用查询时，设置读取的字段列
     * 2、如果参数$fields 为空，则读取所有字段列
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：查询数据-字符串字段列
     *    $where = array('name'=>'admin');
     *    $this->setTable('User')->setField('id,username,pwd')->setWhere($where)->queryRows();
     *
     *    示例2：查询数据-数组字段列
     *    $where = array('name'=>'admin');
     *    $this->setTable('User')->setField(['id','username','pwd'])->setWhere($where)->queryRows();
     *
     *    示例3：查询数据-定义别名字段列，
     *    $this->setTable('User')->setField(['id'=>'_id','username'=>'name','password'=>'pwd'])->setWhere($where)->queryRows();
     *
     *    示例4：查询数据-定义字段方法，别名, id 最小值
     *    $this->setTable('User')->setField(['id'=>['alias'=>'_id','method'=>'min']])->setWhere($where)->queryRows();
     *</pre>
     * @param array|string $fields 字段名
     *<pre>
     *        $fields = 'name,user,pass',// 字符串格式
     *        $fields = array('name','user','pass'),索引数组
     *        $fields = array('name','user'=>'username','pass'=>'password') //别名格式
     *        $fields = array('pass'=>['as','别名']) //别名格式
     *        $fields = array('pass'=>['as',['别名','方法min,max,avg 等等']]) //别名格式
     *</pre>
     * @return static
     */
    public function setField($fields = []):self
    {
        if (!empty($fields) && is_string($fields)) {
            $fields = explode(',',$fields);
        }

        $this->options['field'] = empty($fields) ? '*' : $fields;

        return $this;
    }

    /**
     * 设置表别名
     *<B>说明：</B>
     *<pre>
     *  1、给setTable方法设置的表定义别名
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：单表别名
     *    $this->setTable('User')->setAlias('u')->setWhere(['name'=>'admin'])->queryRow();
     *</pre>
     * @param string $alias 表别名
     * @return static
     */
    public function setAlias($alias = ''):self
    {
        $this->options['alias'] = $alias;

        return $this;
    }

    public function getAlias()
    {
        return isset($this->options['alias']) ? $this->options['alias'] : '';
    }


    /**
     * 设置条件
     *<B>说明：</B>
     *<pre>
     *    1、设置查询条件、更新条件
     *    2、sql条件 支持三种格式:字符串方式，hash（哈希）方式,混合 字符串+hash，对应hash,混合方式的条件，数组的每一个元素 都可以是嵌套的hash,或混合条件
     *            字符串:'userid=2'
     *          基本格式['字段'] = ['操作符','数值'];
     *            hash:即关联数组,['username'=>'admin','userid'=>['in','1,2,3']]
     *            混合:['userid'=>['in','1,2,3'],'userid=2']
     *    3、支持多次调用
     *    4、where 条件不支持全数字作为字段名
     *    5、where 条件的解析使用了递归方式
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：字符串条件 不推荐使用此种方式
     *    $where = "id = 1 and username='admin'";
     *    $this->setTable->('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where id=1 and username="admin";
     *
     *    示例2：hash条件
     *    $where = ['name'=>'admin'];
     *    $this->setTable->('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where name="admin";
     *
     *    示例3：混合条件
     *    $where = ['name'=>['eq','admin'],'userId=1'];
     *    $this->setTable->('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where name="admin" and userId=1;
     *
     *    示例4：and 查询条件,where 第一个参数必须是and,不填默认为 and
     *        $where = ['and','name'=>['eq','admin'],'userid'=>1];
     *        $this->setTable->('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where name="admin" and userId=1;
     *
     *    示例5：or 查询条件
     *        $where = ['or','name'=>['eq','admin'],'userid'=>1];
     *        $this->setTable->('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where name="admin" or userId=1;
     *
     *        示例6：嵌套条件
     *        $where = ['and','name'=>['eq','admin'],['or',['roleid'=>1,'ctime'=>['eq',time()]]]];
     *        $this->setTable('user')->setWhere($where)->queryRows();
     *        生成sql:select * from user where name="admin" and (roleid=1 or ctime=1524141411);
     *
     *        示例6：多次调用setWhere
     *        $where = ['name'=>['eq','admin']];
     *        $this->setTable('user');
     *        $this->setWhere($where);
     *        $where1 = ['userId'=>2];
     *        $this->setWhere($where1);
     *        生成sql:select * from user where name="admin" and userId=2;
     *
     *        示例7：操作符条件,操作符格式 $map['字段1']  = array('表达式','查询条件1');
     *              支持以下操作符:['eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN'];
     *        $where = ['name'=>['eq','admin']];
     *        $where = ['name'=>['gt','admin']];
     *
     *        示例9 in 查询
     *        $where = ['userId'=>['in',[1,2,3,4]]];
     *         or
     *        $where = ['userId'=>[1,1,2,3,4]];
     *
     *        示例10  array('表达式','字段名称','字段值');
     *  　　　 $where[] = ['=','userId',2];
     *
     *</pre>
     * @param array|string $where 条件参数
     * @param array $params 绑定参数
     * @param string $op 操作符
     * @return static
     */
    public function setWhere($where = [], $params = [],$op = null):self
    {
        // 闭包方式
        if ($where instanceof \Closure) {
            call_user_func_array($where,[$this]);
            return $this;
        }

        // 数组方式
        if (is_array($where)) {
            $this->options['where'] = isset($this->options['where']) && !empty($this->options['where'])
                ? array_merge($this->options['where'], $where) : $where;
            $this->addParams($params);
            return $this;
        }

        // 字符方式
        if (is_string($where) && is_null($op)) {
            $this->setAndWhere([$where],$params);
            return $this;
        }

        // 三元方式
        if (is_null($op)) {
            // 操作符为in,eq 或
            if (is_array($params)) {
                $op = BaseQueryBuilder::EXP_IN;
            } else {
                $op = BaseQueryBuilder::EXP_EQ;
            }
        }

        $this->setAndWhere([$where=>[$op, $params]]);

        return $this;
    }

    public function getWhere()
    {
        return isset($this->options['where']) ? $this->options['where'] : null;
    }

    /**
     * 设置and条件
     *<B>说明：</B>
     *<pre>
     *   与设置的where 条件与当前的条件形成与关系
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：and 查询条件
     *  $where = ['or','username'=>'admin','userId'=>20]
     *  $this->setWhere($where);
     *  $where1 = ['roleid'=>30,'userid'=>10];
     *  $this->setAndWhere($where1);
     *  生成sql:select * from user where ((username="admin" or userId=0)) and (roleid=30 and userid=10);
     *</pre>
     * @param array|string $where 条件参数
     * @param array $params 绑定参数
     * @return static
     */
    public function setAndWhere($where = [], $params = []):self
    {
        if (isset($this->options['where']) && !empty($this->options['where'])) {
            $this->options['where'] = ['and', $this->options['where'], $where];
        } else {
            $this->options['where'] = $where;
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * 设置or条件
     *<B>说明：</B>
     *<pre>
     *  与设置的where 条件与当前的条件形成或关系
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：and 查询条件
     *  $where = ['or','username'=>'admin','userId'=>20]
     *  $this->setWhere($where);
     *  $where1 = ['roleid'=>30,'userid'=>10];
     *  $this->setOrWhere($where1);
     *  生成sql:select * from user where ((username="admin" or userId=0)) or (roleid=30 and userid=10);
     *</pre>
     * @param array|string $where 条件参数
     * @param array $params 绑定参数
     * @return static
     */
    public function setOrWhere($where = [], $params = []):self
    {
        if (isset($this->options['where']) && !empty($this->options['where'])) {
            $this->options['where'] = ['or', $this->options['where'], $where];
        } else {
            $this->options['where'] = $where;
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * 设置行锁或表锁
     *<B>说明：</B>
     *<pre>
     *  生成 update for
     *</pre>
     * @param boolean $lock 是否开启锁
     * @return static
     */
    public function setLock($lock = true):self
    {
        $this->options['lock'] = $lock;

        return $this;
    }

    /**
     * 是否返回query 对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param boolean $isQuery 是否返回Query
     * @return static
     */
    public function asQuery($isQuery = true):self
    {
        $this->options['isQuery'] = $isQuery;

        return $this;
    }

    /**
     * 设置自增序列
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|bool $sequence 序列名称
     * @return static
     */
    public function setSeq($sequence = true):self
    {
        $this->options['seq'] = $sequence;

        return $this;
    }

    /**
     * 设置连表参数
     *<B>说明：</B>
     *<pre>
     *  连表操作的表可以不写表前缀，如果$join 参数为字符串，支持多次调用
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：字符串连表查询
     *  $where = ['name'=>array('eq','admin')];
     *  $leftTable = ['role','R'];
     *  $leftTableOn = ['u.RoleId'=>['raw','R.RoleId']];
     *
     *  $this->setTable->(['table','u'])->setJoin($leftTable,$leftTableOn)->setWhere($where)->queryRows();
     *  示例2：多次调用setJoin
     *  $this->setJoin($left2)->setJoin($left2)->queryRows();
     *  $this->queryRows();
     *</pre>
     * @param string $table 表名
     * @param array $on 条件
     * @param string $joinType 连接类型
     * @return static
     */
    public function setJoin($table, $on, $joinType = ''):self
    {

        $this->options['join'][] = [$table, $on, $joinType];

        return $this;
    }

    /**
     * 设置with查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array|string $with 关系定义
     * @param bool|string $join 是否连表
     * @param bool $load 是否加载关系表数据
     * @return static
     */
    public function setWith($with,$join = false,$load = true):self
    {
        if (is_null($with)) {
            $this->options['with'] = [];
            return $this;
        }

        $query_withs = [];
        if (is_string($with)) {
            $query_withs[] = [$with,$load,$join,null];
        } else if (is_array($with)) {
            $with_keys = array_keys($with);
            $name = $with_keys[0];
            $hander = $with[$name];
            $query_withs[] = [$name,$load,$join,$hander];
        }

        foreach ($query_withs as $query_with) {
            $this->options['with'][] = $query_with;
        }

        return $this;
    }

    /**
     * 设置left with查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array|string $with 关系定义
     * @param bool $load 是否加载关系表数据
     * @return static
     */
    public function setLeftWith($with,$load = true):self
    {
        $this->setWith($with,'left join',$load);

        return $this;
    }

    /**
     * 设置inner with查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array|string $with 关系定义
     * @param bool $load 是否加载关系表数据
     * @return static
     */
    public function setInnerWith($with,$load = true):self
    {
        $this->setWith($with,'inner join',$load);

        return $this;
    }

    /**
     * 联合查询
     *<B>说明：</B>
     *<pre>
     *  union 对查询结果合并，重复数据只显示一次
     *  union all 对查询结果合并，重复数据全部显示
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：sql 查询
     *</pre>
     * @param  string|Query $union 子查询
     * @param  boolean $all
     * @return static
     */
    public function setUnion($union, $all = false):self
    {
        if ($all === true) {
            $this->options['union']['_all'] = true;
        }

        $this->options['union'][] = $union;

        return $this;
    }

    /**
     * 设置查询排序
     *<B>说明：</B>
     *<pre>
     *    连贯操作
     *        不支持字符串
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：查询单个排序
     *  $this->setTable->('table')->setOrder(['ctime'=>SORT_DESC])->setWhere($where)->queryRows();
     *
     *  示例2：查询多个排序
     *  $this->setTable->('table')->setOrder(['roleid'=>SORT_DESC，'id'=>SORT_ASC])->setWhere($where)->queryRows();
     *</pre>
     * @param array $order 排序参数
     * @return static
     */
    public function setOrder($order = []):self
    {
        $this->options['order'] = $order;

        return $this;
    }

    public function getOrder()
    {
        return $this->options['order'] ?? [];
    }

    /**
     * 设置读取数据行数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：读取2条记录
     *  $list = $this->setTable('Users')->setLimit(1)->queryRows();
     *</pre>
     * @param  int $length 读取的行数大小
     * @return static
     */
    public function setLimit($length = null):self
    {
        $this->options['limit'] = $length;

        return $this;
    }

    /**
     * 设置读取数据行起始行数
     *<B>说明：</B>
     *<pre>
     *  1、连贯操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：读取第二2条记录
     *  $list = $this->setTable('Users')->setOffset(1)->queryRows();
     *</pre>
     * @param  int $offset 起始行数
     * @return static
     */
    public function setOffset($offset = null):self
    {
        $this->options['offset'] = $offset;

        return $this;
    }

    /**
     * 设置取消重复行
     *<B>说明：</B>
     *<pre>
     *  连贯操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：取消重复行
     *  $list = $this->setTable('Users')->setDistinct()->queryRows();
     *</pre>
     * @param boolean $distinct 是否取消重复行 true 表示取消,false 表示不取消
     * @return static
     */
    public function setDistinct($distinct = true):self
    {
        $this->options['distinct'] = $distinct;

        return $this;
    }

    /**
     * 设置查询分组
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：字符串分组查询
     *  $list = $this->setTable('Users')->setGroup('RoleId')->queryRows();
     *  $list = $this->setTable('Users')->setGroup('RoleId,sex')->queryRows();
     *  示例2：多字段列分组查询
     *  $list = $e_user->setTable('Users')->setGroup(['RoleId','sex'])->queryRows();
     *</pre>
     * @param array|string $group 分组参数
     * @return static
     */
    public function setGroup($group = []):self
    {
        $this->options['group'] = $group;

        return $this;
    }

    /**
     * 设置分组查询条件
     *<B>说明：</B>
     *<pre>
     *  连贯操作
     *    分组条件查询规则与where相同
     *    案例请参照where
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：分组条件
     *  $list = $this->setfield('name,SUM(age) as ag')->setTable('test')->setGroup('name')->setHaving(['ag'=>['gt',39]])->queryRows();
     *</pre>
     * @param array|string $having 分组查询条件
     *<pre>
     * string:$having = 'sum(sex) > 1';
     * array:$having = array('age'=>array('gt',2));
     *</pre>
     * @param array $params 绑定参数
     * @return static
     */
    public function setHaving($having = [], array $params = []):self
    {
        if (is_string($having)) {
            $having = [$having];
        }

        if (isset($this->options['having'])) {
            $this->options['having'] = $this->options['having'] + $having;
        } else {
            $this->options['having'] = $having;
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * 设置分组and条件
     *<B>说明：</B>
     *<pre>
     *    与设置的having 条件与当前的条件形成与关系
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：having and 查询条件
     *    $having = ['or','name'=>'admin','userId'=>20]
     *    $this->setHaving($having);
     *    $having1 = ['roleid'=>30];
     *    $this->setTable('user')->setAndHaving($having1)->setGroup('name,roleid')->queryRows();
     *    生成sql:select * from user groub by name having (name = 'admin' or userId=20) and (roleid =30)
     *</pre>
     * @param array|string $having 条件参数
     * @param array $params 绑定参数
     * @return static
     */
    public function setAndHaving($having = [], array $params = []):self
    {
        if (isset($this->options['having'])) {
            $this->options['having'] = ['and', $this->options['having'], $having];
        } else {
            $this->options['having'] = $having;
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * 设置分组or条件
     *<B>说明：</B>
     *<pre>
     *    与设置的having 条件与当前的条件形成或关系
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：having or 查询条件
     *    $having = ['or','name'=>'admin','userId'=>20]
     *    $this->setHaving($having);
     *    $having1 = ['roleid'=>30];
     *    $this->setTable('user')->setOrHaving($having1)->setGroup('name,roleid')->queryRows();
     *    生成sql:select * from user groub by name having (name = 'admin' or userId=20) or (roleid =30)
     *</pre>
     * @param array|string $having 条件参数
     * @param array $params 绑定参数
     * @return static
     */
    public function setOrHaving($having = [],array $params = []):self
    {
        if (isset($this->options['having'])) {
            $this->options['having'] = ['or', $this->options['having'], $having];
        } else {
            if (is_array($having)) {
                array_unshift($having,'or');
            } else {
                $this->options['having'] = ['or',$having];
            }
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * sql参数绑定
     *<B>说明：</B>
     *<pre>
     *  连贯操作
     *  绑定参数的名称不能带有下划线+数字格式，比如User_1
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：设置绑定参数
     *  $where = ['name'=>['eq',':name']];
     *    $params = [':name'=>'admin'];
     *    $this->setWhere($where)->setParam($params)->queryRows();
     *</pre>
     * @param array|null $params 绑定参数
     *<pre>
     *    null:清空绑定参数
     *    array:array(':username'=>'admin',':RoleId'=>2)
     *</pre>
     * @return static
     */
    public function setParam($params = null):self
    {

        if (is_null($params)) {
            $this->options['params'] = null;
            return $this;
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * 添加绑定参数
     *<B>说明：</B>
     *<pre>
     *  累加绑定参数，不会清空绑定参数
     *</pre>
     * @param array $params
     * @return static
     */
    public function addParams(array $params = []):self
    {
        if (!empty($params)) {
            if (empty($this->options['params'])) {
                $this->options['params'] = $params;
            } else {
                foreach ($params as $name => $value) {
                    if (is_integer($name)) {
                        $this->options['params'][] = $value;
                    } else {
                        $this->options['params'][$name] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * 设置分库分表数据
     *<B>说明：</B>
     *<pre>
     *  此设置用于添加，修改，查询
     *  根据分表的数据计算出对应的表名
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：设置分表数据-分表字段RoleId
     *  $this->setTable('Users')->setShard(['RoleId'=>2])->setWhere('name'=>'admin');
     *  生成sql
     *  SELECT * FROM web_Users_1 WHERE ( `UserName` = "admin" )->queryRows();
     *</pre>
     * @param array $shard_columns 分区数据
     * @return static
     */
    public function setShard($shard_columns = []):self
    {
        $this->options['shard'] = $shard_columns;

        return $this;
    }

    /**
     * 设置是否返回数组
     *<B>说明：</B>
     *<pre>
     *   略
     *</pre>
     * @param boolean $asArray
     * @return static
     */
    public function asArray(bool $asArray = true):self
    {
        $this->options['isArray'] = $asArray;

        return $this;
    }

    /**
     * 是否强制从主库操作数据
     *<B>说明：</B>
     *<pre>
     *   略
     *</pre>
     * @param boolean $asMaster
     * @return static
     */
    public function asMaster(bool $asMaster = true):self
    {
        $this->options['isMaster'] = $asMaster;

        return $this;
    }

    /**
     * 是否返回自增id
     *<B>说明：</B>
     *<pre>
     *  只对插入单条记录有效
     *</pre>
     * @return static
     */
    public function asId(bool $asId = true)
    {
        $this->options['isId'] = $asId;

        return $this;
    }

    /**
     * 查询一行记录
     *<B>说明：</B>
     *<pre>
     *　略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：
     *  根据主键id查询一行记录
     *  $e_user = $this->fetchOne(2);
     *  根据数组条件查询一行记录
     *  $where = array('name'=>'admin');
     *  $e_user = $this->fetchOne($where);
     *
     *  示例2：
     *  如果表本身没有主键，请通过设置sql参数的方式调用此方法
     *  方式1：
     *  $where = array('name'=>'admin');
     *  $this->setWhere($where)->fetchOne();
     *  方式2：
     *  $where = array('name'=>'admin');
     *  $this->fetchOne($where);
     *</pre>
     * @param int|array|string $id 表主键id
     * <pre>
     *        int: 1 //主表主键id,自增，一般为数字
     *        array:['id'=>['in','1,2,3']] 查询参数
     *        string:'1,2,3' 多个主键，逗号隔开
     * </pre>
     * @return array|boolean|null
     * <pre>
     *        array:数据行 一维数组
     *        boolean:false,// 查询失败，一般为数据库失败，或SQL 语句错误
     *        null:查询不到数据
     * </pre>
     */
    public function fetchOne($id = null)
    {
        if (is_array($id)) {
            return $this->setWhere($id)->queryRow();
        } else {
            return $this->queryRow($id);
        }
    }

    /**
     * 根基指定id查询一条数据
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|int $id id值
     */
    public function fetchById($id)
    {

        $pk = $this->getPk();
        $where = [
            $pk =>$id
        ];

        return $this->setWhere($where)->queryRow();
    }

    /**
     * 查询多行记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：普通查询
     *    $where = ['username'=>'admin'];
     *    $this->setWhere($where)->getAll();
     *
     *    示例2：
     *    $where = ['username'=>['like','%admin%']];
     *    $this->getAll($where);
     *</pre>
     * @param array|string $where 查询条件
     * @return null|array|boolean|Entity[] 二维数组
     * <pre>
     *        null:未查询数据
     *        array:数据行(二维数组)
     *        boolean: false 表示 sql 错误
     * </pre>
     */
    public function fetchAll($where = [])
    {
        return $this->setWhere($where)->queryRows();
    }

    /**
     * 更新一行记录
     *<B>说明：</B>
     *<pre>
     *  根据主键id更新记录
     *  根据数组条件更新单条记录
     *  参数$data包含了主键id字段名,自动作为更新条件
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：普通更新
     *    $data = ['updteTime'=>time()];
     *    $where = ['userId'=>'230'];
     *    $this->updateOne($data,$where);
     *
     *    示例2：data 包含主键id,必须定义主键id属性
     *    $data = ['updteTime'=>time(),'userId'=>'230'];
     *    $this->updateOne($data);
     *</pre>
     * @param array $data 更新数据
     * @param int|array|string $where 更新条件
     * <pre>
     *    int:表主键id
     *    array:数组条件
     *    string:字符串条件
     * </pre>
     * @return boolean|int
     * <pre>
     *    boolean:false 表示更新失败，有可能是sql错误，或系统错误
     *    int: 0 表示未更新数据 大于0 表示更新的数量
     * </pre>
     */
    public function updateOne($data = [], $where = null)
    {
        // 条件处理
        if (!is_null($where)) {
            if (!is_array($where)) {
                $pk = $this->getPk();
                if ($pk) {
                    $this->setWhere([$pk => $where]);
                }
            } else {
                $this->setWhere($where);
            }
        }

        $this->setLimit(1);
        $result = $this->updateRows($data);

        return $result;
    }

    /**
     * 更新多行记录
     *<B>说明：</B>
     *<pre>
     *        1、根据主键id更新记录
     *        2、根据数组条件更新单条记录
     *    3、参数$data包含了主键id字段名,自动作为更新条件
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：普通更新
     *    $data = ['updteTime'=>time()];
     *    $where = ['userId'=>'230'];
     *    $this->update($data,$where);
     *
     *    例2：data 包含主键id,必须定义主键id属性
     *    $data = ['updteTime'=>time(),'userId'=>'230'];
     *    $this->update($data);
     *</pre>
     * @param array $data 更新数据
     * @param int|array|string $where 更新条件
     * <pre>
     *    int:表主键id
     *    array:数组条件
     *    string:字符串条件
     * </pre>
     * @return boolean|int
     * <pre>
     *    boolean:false 表示更新失败，有可能是sql错误，或系统错误
     *    int: 0 表示未更新数据 大于0 表示更新的数量
     * </pre>
     */
    public function updateAll($data = [], $where = null)
    {
        // 条件处理
        if (!is_null($where)) {
            if (!is_array($where)) {
                $pk = $this->getPk();
                if ($pk) {
                    $this->setWhere([$pk => $where]);
                }
            } else {
                $this->setWhere($where);
            }
        }

        $result = $this->updateRows($data);

        return $result;
    }

    /**
     * 添加一行记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：
     *    $data = array('name'=>'admin','roleId'=>2);
     *    $this->addOne($data);
     *
     *    示例2：
     *    $data = array('name'=>'admin','roleId'=>2);
     *    $this->setData($data)->addOne();
     *</pre>
     * @param array $data 数据(一维数组)
     * @return int|boolean
     *<pre>
     *    int:插入成功的行数
     *    boolean:false 添加失败，SQL错误
     *</pre>
     */
    public function addOne($data = [])
    {
        return $this->addRow($data);
    }

    /**
     * 添加多行记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：批量添加多条记录
     *    $data = array(
     *        array('name'=>'admin','roleid'=>2),
     *        array('name'=>'admin1','roleid'=>3),
     *        array('name'=>'admin3','roleid'=>4),
     *    );
     *    $this->addAll($data);
     *</pre>
     * @param array $datas 数据(二维数组)
     * @return int|bool
     *<pre>
     *    int:插入成功的行数
     *    boolean:false 添加失败，SQL错误
     *</pre>
     */
    public function addAll($datas = [])
    {
        return $this->addRows($datas);
    }

    /**
     * 统计行数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：统计行数
     *    $where = ['name'=>'admin'];
     *    $this->count($where);
     *
     *    示例2：统计行数
     *    $where = ['name'=>'admin'];
     *    $this->setWehere($where)->count();
     *</pre>
     * @param string $field 统计字段,如果不填，默认为表主键
     * @param array $where 统计条件
     * @return int|boolean 统计数量 如果未统计到任何数据，则返回0
     *<pre>
     *    int:统计行数
     *    boolean：sql 错误
     *</pre>
     */
    public function count($field = null,$where = [])
    {
        if (!empty($where)) {
            $this->setWhere($where);
        }

        return $this->queryCount($where, $field);
    }

    /**
     * 删除行记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：根据id删除单行记录
     *    $this->deleteOne(1);
     *</pre>
     * @param int|string|array $where 删除条件
     * @return int|boolean 更新条数
     *<pre>
     *    int: 0 成功删除行数
     *    boolean:false sql错误
     *</pre>
     */
    public function deleteOne($where = null)
    {
        // 条件处理
        if (!is_null($where)) {
            if (!is_array($where)) {
                $pk = $this->getPk();
                if ($pk) {
                    $this->setWhere([$pk => $where]);
                }
            } else {
                $this->setWhere($where);
            }
        }

        $this->setLimit(1);

        $result = $this->deleteRows($where);
        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 删除行记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *    示例1：根据id删除单行记录
     *    $this->deleteAll(1);
     *
     *    示例2：根据id 字符串删除多行记录
     *    $this->deleteAll('1,2,3,4');
     *
     *    示例3：索引数组删除多行记录
     *    $this->deleteAll([1,2,3,4]);
     *</pre>
     * @param int|string|array|null $where 删除条件
     *<pre>
     *    int:1,// 表主键id
     *    string:'1,2,3,4',// 多个主键id 逗号隔开
     *    array:[1,2,3,4],// 多个主键id 数组
     *</pre>
     * @return int|boolean 更新条数
     *<pre>
     *    int: 0 成功删除行数
     *    boolean:false sql错误
     *</pre>
     */
    public function deleteAll($where = null)
    {
        // 条件处理
        if (!is_null($where)) {
            if (!is_array($where)) {
                $pk = $this->getPk();
                if ($pk) {
                    $this->setWhere([$pk => $where]);
                }
            } else {
                $this->setWhere($where);
            }
        }

        $result = $this->deleteRows($where);
        if ($result === false) {
            return false;
        }

        return $result;
    }


    /**
     * 查询单条数据行
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：普通查询-查询用户名为"admin"的行记录，按创建时间降序
     *  $where = array('UserName'=>'admin');
     *  $order = array('ctime'=>'desc')
     *  $e_list = $this->setTable('Users')->setWhere($where)->setOrder($order)->queryRow();
     *
     *  示例2：查询主键为1的行记录
     *  $e_list = $this->setTable('Users')->queryRow(1);
     *  示例3：查询用户名为"admin"的行记录，按创建时间降序
     *  $options = array();
     *  $options['where'] = array('UserName'=>'admin');
     *  $options['order'] = array('ctime'=>'desc');
     *  $e_list = $this->setTable('Users')->queryRow($options);
     *</pre>
     * @param  array|int|boolean $options sql参数
     *<pre>
     *    array:$options = array('where'=>array(),'order'=>'name desc')//sql参数
     *    int:$options = 1; 根据主键id读取数据
     *    boolean:$options = false; 生成包含()的sql语句，主要用于子查询
     *</pre>
     * @return array|null|boolean|string 数据行
     *<pre>
     *    array:数据行(一维数组，hash)
     *    null:查找不到数据行
     *    boolean:数据库报错
     *    string:sql 语句
     *</pre>
     */
    public function queryRow($options = null)
    {
        if (!is_null($options) && !is_array($options)) {
            $pk = $this->getPk();
            $where = [];
            if ($pk) {
                $where[$pk] = $options;
            }

            if (count($where) == 0) {
                return [];
            }

            $options = [];
            $options['where'] = $where;
        }

        // 总是查找一条记录
        $options['limit'] = 1;
        // 分析表达式
        $query = $this->getQuery($options);
        $query->setBuildMethod(Query::BUILD_SELECT);

        $query = $this->queryInternal($query, 'queryRow');
        if ($query->asQueryStatus()) {
            return $query;
        }

        $queryResult = $query->getResult();
        $queryResult = $this->queryWith($query,$queryResult);
        unset($query);
        if (false === $queryResult) {//系统错误
            return false;
        }

        if (empty($queryResult)) {
            return null;
        }

        return $queryResult[0];
    }

    /**
     * 查询多条数据行
     *<B>说明：</B>
     *<pre>
     *  执行操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：普通查询-查询用户名为"admin"的记录，按创建时间降序
     *  $where = array('UserName'=>'admin');
     *  $order = array('ctime'=>'desc')
     *  $e_list = $this->setTable('Users')->setWhere($where)->setOrder($order)->queryRows();
     *
     *  示例2：查询主键为1的记录
     *  $e_list = $this->setTable('Users')->select(1);
     *
     *  示例3：查询主键为1,2,3,4记录
     *  $e_list = $this->setTable('Users')->select('1,2,3,4');
     *
     *  示例4：查询用户名为"admin"的记录，按创建时间降序
     *  $options = array();
     *  $options['where'] = array('UserName'=>'admin');
     *  $options['order'] = array('ctime'=>'desc');
     *  $e_list = $this->setTable('Users')->select($options);
     *</pre>
     * @param array|int|string|boolean $options sql参数
     *<pre>
     *        array:$options = array();//sql 参数
     *        int:$options = 1; 根据主键id读取数据
     *        string:$options = '1,2,3,4'; 主键 in 查询
     *        boolean:$options = false; 生成包含()的sql语句，主要用于子查询
     *</pre>
     * @return array|boolean|null|Query 数据行（二维数组）
     *<pre>
     *        null:未找到记录 返回空数组
     *        boolean:false 表示sql错误，系统错误
     *        array:记录二维数组(key/value)
     *</pre>
     */
    public function queryRows($options = null)
    {
        if (!is_null($options) && !is_array($options)) {
            // 根据主键查询
            $pk = $this->getPk();
            $where = [];
            if ($pk) {
                if (strpos($options, ',')) {
                    $where[$pk] = [BaseQueryBuilder::EXP_IN, $options];
                } else {
                    $where[$pk] = $options;
                }
            }

            if (count($where) == 0) {
                return [];
            }

            $options = [];
            $options['where'] = $where;
        }

        $query = $this->getQuery($options);
        $query->setBuildMethod(Query::BUILD_SELECT);
        $query = $this->queryInternal($query, 'queryRows');

        if ($query->asQueryStatus()) {
            return $query;
        }

        $queryResult = $query->getResult();

        $queryResult = $this->queryWith($query,$queryResult);
        // 释放对象
        unset($query);
        if (false === $queryResult) {//系统错误
            return null;
        }

        return $queryResult;
    }


    /**
     * 统计行数
     *<B>说明：</B>
     *<pre>
     *  如果未填统计字段,默认为主键id,若主键id 字段也喂定义，则默认*
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：按条件统计
     *  $where = array('name'=>'admin');
     *  $this->count($where);
     *</pre>
     * @param array $options 统计条件
     * @param string $field 统计字段
     * @return int|boolean
     *<pre>
     *   int：统计数量　０表示找不到统计行
     *   boolean:false  sql 错误
     *</pre>
     */
    public function queryCount($options = [], $field = null)
    {

        if (!is_null($field)) {
            $options['field'] = $field;
        } else {
            $pk = $this->getPk();
            if ($pk) {
                $options['field'] = $pk;
            } else {
                $options['field'] = '*';
            }
        }

        $query = $this->getQuery($options);
        $result = $this->queryScalar($query, 'count');

        // 释放对象
        unset($query);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * 获取最大值
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $field 最大值字段
     * @param array $where 查询条件
     * @return int|boolean
     *<pre>
     *  int:10,最大数值
     *  boolean:sql 错误
     *</pre>
     */
    public function queryMax($field = null, $where = [])
    {
        $options = [];
        if (!empty($field)) {
            $options['field'] = $field;
        }

        if (!empty($where)) {
            $options['where'] = $where;
        }

        $query = $this->getQuery($options);
        $result = $this->queryScalar($query, 'max');
        // 释放对象
        unset($query);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * 获取最小值
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $field 最大值字段
     * @param array $where 查询条件
     * @return int|boolean
     *<pre>
     *  int:10,最小数值
     *  boolean:sql 错误
     *</pre>
     */
    public function queryMin($field = null, $where = [])
    {
        $options = [];
        if (!empty($field)) {
            $options['field'] = $field;
        }

        if (!empty($where)) {
            $options['where'] = $where;
        }

        $query = $this->getQuery($options);
        $result = $this->queryScalar($query, 'min');
        // 释放对象
        unset($query);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * 获取字段累加
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $field 最大值字段
     * @param array $where 查询条件
     * @return int|boolean
     *<pre>
     *  int:10,最小数值
     *  boolean:sql 错误
     *</pre>
     */
    public function querySum($field = null, $where = [])
    {
        $options = [];
        if (!empty($field)) {
            $options['field'] = $field;
        }

        if (!empty($where)) {
            $options['where'] = $where;
        }

        $query = $this->getQuery($options);
        $result = $this->queryScalar($query, 'sum');
        // 释放对象
        unset($query);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * 获取字段平均值
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $field 最大值字段
     * @param array $where 查询条件
     * @return int|boolean
     *<pre>
     *  int:10,最小数值
     *  boolean:sql 错误
     *</pre>
     */
    public function queryAvg($field = null, $where = [])
    {
        $options = [];
        if (!empty($field)) {
            $options['field'] = $field;
        }

        if (!empty($where)) {
            $options['where'] = $where;
        }

        $query = $this->getQuery($options);
        $result = $this->queryScalar($query, 'avg');
        // 释放对象
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * 添加单行数据
     *<B>说明：</B>
     *<pre>
     *  执行操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：添加单行数据
     *  $data = array(
     *        'name'=>'admin',
     *        'roleId'=>2,
     *  );
     *  $this->setTable('Users')->addRow($data);
     *
     *  示例2：采用replace 关键字添加数据
     *  $data = array(
     *        'name'=>'admin',
     *        'roleId'=>2,
     *  );
     *  $this->setTable('Users')->setReplace()->addRow($data);
     *</pre>
     * @param  array $data 数据(一维数组)
     * @param  array $options sql参数
     * @return int|boolean|Query
     *<pre>
     *    boolean:false sql错误
     *    int: 0 表示没插入任何记录,大于0 表示插入数据的行数
     *</pre>
     */
    public function addRow($data = [], $options = [])
    {
        if (!empty($data)) {
            $this->setData($data);
        }

        $query = $this->getQuery($options);
        $query->setBuildMethod(Query::BUILD_INSERT);
        $query = $this->addInternal($query, 'addRow');

        if ($query->asQueryStatus()) {
            return $query;
        }

        $result = $query->getResult();

        if ($query->asId()) {
            $result = $this->getLastId($query->getSeq);
        }

        // 释放对象
        unset($query);

        return $result;
    }

    /**
     * 批量添加数据
     *<B>说明：</B>
     *<pre>
     *  执行操作
     *  支持批量添加
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例１：批量添加数据
     *  $data = array(
     *      array('name'=>'admin1','roleId'=>1),
     *      array('name'=>'admin2','roleId'=>2),
     *      array('name'=>'admin3','roleId'=>3),
     *      array('name'=>'admin4','roleId'=>4),
     *  );
     *  $this->setTable('Users')->addRows($data);
     *
     *  示例2：采用replace 关键字添加数据
     *  $data = array(
     *    'name'=>'admin',
     *    'roleId'=>2,
     *  );
     *  $this->setTable('Users')->setReplace()->addRows($data);
     *</pre>
     * @param  array $datas 数据(二维数组)
     * @return int|boolean|Query
     *<pre>
     *    boolean:false sql错误
     *    int: 0 表示没插入任何记录,大于0 表示插入数据的行数
     *</pre>
     */
    public function addRows($datas = [])
    {
        if (!empty($datas)) {
            $this->setData($datas);
        }

        $query = $this->getQuery();
        $query->setBuildMethod(Query::BUILD_INSERTALL);
        $query = $this->addInternal($query, 'addRows');

        if ($query->asQueryStatus()) {
            return $query;
        }

        $result = $query->getResult();
        // 释放对象
        unset($query);

        return $result;
    }

    /**
     * 保存数据
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：更新数据
     *  $data = ['name'=>'12'];
     *  $this->setTable('Users')->setWhere(['userId'=>50])->updateRows($data);
     *
     *  示例2：根据主键id更新数据
     *  $data = ['name'=>'12'],'userId'=>50);
     *  $this->setTable('Users')->updateRows($data);
     *</pre>
     * @param  array $data 数据(一维数组)
     * @param  array $options sql 参数
     * @return int|boolean|null|Query
     * <pre>
     * boolean:false sql错误
     * int: 0 表示未更新任何记录
     *     大于0 表示更新记录的行数
     * null:参数错误，比如data　未定义,为设置更新条件(视乎有点不妥)
     * </pre>
     */
    public function updateRows($data = [], $options = [])
    {
        if (!empty($data)) {
            $this->setData($data);
        }

        // 分析表达式
        $query = $this->getQuery($options);
        $query->setBuildMethod(Query::BUILD_UPDATE);
        $query = $this->updateInternal($query, __FUNCTION__);

        if ($query->asQueryStatus()) {
            return $query;
        }

        $result = $query->getResult();
        // 释放对象
        unset($query);

        return $result;
    }

    /**
     * 删除记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：根据主键删除记录
     *  $this->setTable('UserId')->deleteRows(1);
     *
     *  示例2：根据主键删除多条记录
     *  $this->setTable('UserId')->deleteRows('1,2,3');
     *  $this->setTable('UserId')->deleteRows([1,2,3]);
     *
     *  示例3：根据条件删除多条记录
     *  $where = ['roleid'=>'2'];
     *  $this->setTable('UserId')->deleteRows($where);
     *</pre>
     * @param array|int|string $where 参数
     *<pre>
     *    array:$options = array('where'=>[],'order'=>'name desc')//sql 指令参数
     *    int:$options = 1; 根据主键id删除记录
     *    string:$options = '1'; 根据主键id删除记录
     *</pre>
     * @return boolean|int|null
     *<pre>
     *    boolean:sql 错误
     *    int:删除成功数据行数
     *    null:参数错误,比如where 条件未设置
     *</pre>
     */
    public function deleteRows($where = [])
    {
        $options = [];
        if (!empty($where)) {
            $options['where'] = $where;
        }

        // 分析表达式
        $query = $this->getQuery($options);
        if ($query->isEmptyWhere()) {
            return 0;
        }

        $query->setBuildMethod(Query::BUILD_DELETE);
        $query = $this->deleteInternal($query, __FUNCTION__);
        $result = $query->getResult();
        // 释放对象
        unset($query);

        return $result;
    }


    /**
     * 返回查询结果第一行，第一列
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param  Query $query sql参数
     * @param  string $method 方法名 比如min,max,count
     * @return array|boolean|Query
     */
    public function queryScalar($query, $method = '')
    {
        $query->setBuildMethod(Query::BUILD_SCALAR);
        $query = $this->queryScalarInternal($query, $method);
        if ($query->asQueryStatus()) {
            return $query;
        }

        $queryReuslt = $query->getResult();
        unset($query);
        if ($queryReuslt === false) {
            return false;
        }

        return $queryReuslt;
    }

    /**
     * 执行查询sql
     *<B>说明：</B>
     *<pre>
     *  1、执行操作
     *  2、支持预处理
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：sql 查询
     *  $list = $this->query('select * from {{Users}} where pid=356');
     *
     *  示例2：sql 预处理查询
     *  $params = array(':pid'=>358);
     *  $list = $this->query('select * from {{Users}} where pid=:pid',[':pid'=>20]);
     *</pre>
     * @param  string $queryCommand sql指令
     * @param  array $params 绑定参数
     * @return array|boolean 数据行(二维数组)
     *<pre>
     *  array:数据行
     *  boolean:false sql 错误
     *</pre>
     */
    public function queryCmd($queryCommand, array $params = [])
    {
        $query = $this->getQuery();
        $query->setRawCommand($queryCommand, $params);
        $query->asWrite(false);
        $rawCommand = $query->buildQueryCommand();
        // 记录sql
        $this->getDbsession()->addQueryCommand($rawCommand);
        $result = $query->getDb()->callQuery($rawCommand);
        // 释放对象
        unset($query);

        return $result;
    }

    public function querySql($queryQql, array $params = [])
    {
        $query = $this->getQuery();
        $query->setRawCommand($queryQql, $params);
        $query->asWrite(false);
        $rawCommand = $query->buildQueryCommand();
        // 记录sql
        $this->getDbsession()->addQueryCommand($rawCommand);
        $result = $query->getDb()->callQuery($rawCommand);
        // 释放对象
        unset($query);

        return $result;
    }

    /**
     * 执行更新sql
     *<B>说明：</B>
     *<pre>
     *  执行操作
     *  支持预处理
     *  一般用于，更新，添加，删除的sql 操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：删除UserId=315 记录
     *  $result = $this->executeCmd('delete from {{Users}} where UserId=:UserId',array(':UserId'=>315));
     *
     *  示例2：修改UserId=315 的邮件地址
     *  $result = $this->executeCmd('update {{Users}} set Email="admin163.com" where UserId=:UserId',array(':UserId'=>316));
     *</pre>
     * @param  string $queryCommand sql语句
     * @param  array $params 绑定参数
     * @return int|boolean
     *<pre>
     *    int:影响的行数
     *    boolean:false sql 错误
     *</pre>
     */
    public function execCmd($queryCommand, array $params = array())
    {
        $query = $this->getQuery();
        $query->setRawCommand($queryCommand, $params);
        $query->asWrite(true);
        $rawCommand = $query->buildQueryCommand();
        // 记录sql
        $this->getDbsession()->addQueryCommand($rawCommand);

        $result = $query->getDb()->callExecute($rawCommand);
        // 释放对象
        unset($query);

        return $result;
    }

    /**
     * 执行更新sql
     *<B>说明：</B>
     *<pre>
     *  执行操作
     *  支持预处理
     *  一般用于，更新，添加，删除的sql 操作
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例1：删除UserId=315 记录
     *  $result = $this->executeCmd('delete from {{Users}} where UserId=:UserId',array(':UserId'=>315));
     *
     *  示例2：修改UserId=315 的邮件地址
     *  $result = $this->executeCmd('update {{Users}} set Email="admin163.com" where UserId=:UserId',array(':UserId'=>316));
     *</pre>
     * @param  string $queryCommand sql语句
     * @param  array $params 绑定参数
     * @return int|boolean
     *<pre>
     *    int:影响的行数
     *    boolean:false sql 错误
     *</pre>
     */
    public function execSql($execSql, array $params = array())
    {
        $query = $this->getQuery();
        $query->setRawCommand($execSql, $params);
        $query->asWrite(true);
        $rawCommand = $query->buildQueryCommand();
        // 记录sql
        $this->getDbsession()->addQueryCommand($rawCommand);

        $result = $query->getDb()->callExecute($rawCommand);
        // 释放对象
        unset($query);

        return $result;
    }


    /**
     * 添加数据行总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query 数据
     * @param  string $queryType 操作方法
     * @return Query
     *<pre>
     *    int:10,添加成功行数
     *    boolean:false sql 错误
     *</pre>
     */
    abstract public function addInternal(Query $query, $queryType = '');

    /**
     * 修改记录总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query
     * @param string $queryType 操作方法
     * @return Query
     *<pre>
     *        int:10,更新成功行数
     *        boolean:false sql 错误
     *</pre>
     */
    abstract public function updateInternal(Query $query, $queryType = '');

    /**
     * 删除记录总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query
     * @param string $queryType 操作方法
     * @return Query
     *<pre>
     *  int:10,删除成功行数
     *  boolean:false sql 错误
     *</pre>
     */
    abstract public function deleteInternal(Query $query, $queryType = '');


    /**
     * 查询总接口总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query
     * @param  string $queryType 操作方法
     * @return Query
     *<pre>
     *  array:1数据行(二维数组)
     *  boolean:false sql 错误
     *</pre>
     */
    abstract public function queryInternal(Query $query, $queryType = '');

    /**
     * 查询第一行，第一列总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query
     * @param  string $method 操作方法 count,min,max,avg
     * @return Query
     *<pre>
     *    array:数据行(二维数组)
     *    boolean:false sql 错误
     *</pre>
     */
    abstract public function queryScalarInternal(Query $query, $method = '');

    /**
     * 执行sql指令.
     *<B>说明：</B>
     *<pre>
     * 1、内部使用
     *</pre>
     * @param Query $query
     * @return int
     */
    protected function executeCommand(Query $query)
    {
        $command = $query->buildQueryCommand();
        // 记录sql
        $this->getDbsession()->addQueryCommand($command);

        $dbconn = $query->getDb();
        try {
            $executeResult = $dbconn->callExecute($command);
        } catch (Exception $e) {
            throw $e;
        } finally {
            if (!$this->getDbsession()->hasBeginTransaction()) {
                $dbconn->free();
            }
        }

        return $executeResult;
    }

    /**
     * 执行查询sql
     *<B>说明：</B>
     *<pre>
     *    1、内部使用
     *</pre>
     * @param Query $query
     * @return array|boolean
     *<pre>
     *    array:数据行(二维数组)
     *    boolean:false sql 错误
     *</pre>
     */
    protected function queryCommand(Query $query)
    {
        $command = $query->buildQueryCommand();

        // 记录sql
        $this->getDbsession()->addQueryCommand($command);

        $dbconn = $query->getDb();
        try {
            $queryResult = $dbconn->callQuery($command);
        } catch (Exception $e) {
            throw $e;
        } finally {
            if (!$this->getDbsession()->hasBeginTransaction()) {
                $dbconn->free();
            }
        }

        return $queryResult;
    }


    /**
     * 启动事务
     *<B>说明：</B>
     *<pre>
     *  开启事务之前，自动回提交之前的事务
     *</pre>
     * @return boolean true 启动成功,false 启动失败
     */
    public function beginTransaction()
    {
        return $this->getDbsession()->beginTransaction();
    }

    /**
     * 提交事务
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return boolean true 提交成功,false 提交失败
     */
    public function commitTransaction()
    {
        return $this->getDbsession()->commitTransaction();
    }

    /**
     * 事务回滚
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return boolean true 回滚成功,false 回滚失败
     */
    public function rollbackTransaction()
    {
        return $this->getDbsession()->rollbackTransaction();
    }

    /**
     * 获取最后插入sql的自增id
     *<B>说明：</B>
     *<pre>
     * 如果自增id
     * 如果开启自动生成序号，则返回最后产生的序号
     *</pre>
     * @return string 最后插入sql自增id
     */
    public function getLastId($sequence = '')
    {
        return $this->getDbsession()->getLastId($sequence);
    }

    /**
     * 获取最后执行sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string sql 语句
     */
    public function getLastCommand()
    {
        return $this->getDbsession()->getLastCommand();
    }

    /**
     * 生成查询SQL
     *<B>说明：</B>
     *<pre>
     *  主要用于子查询
     *</pre>
     *<B>示例：</B>
     *<pre>
     *  示例：
     *  $where = array('name'=>'admin');
     *  $sql = $this->setTable('User')->setWhere($where)->buildSql();
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function buildSql(Query $query)
    {
        $command = $query->buildQueryCommand();

        return $this->getDbsession()->buildSqlByCommand($command);
    }


    /**
     * 解析sql 参数
     *<B>说明：</B>
     *<pre>
     *  查询，更新表记录sql指令的操作都必须调用方法解析sql参数
     *  解析sql 参数完成后，参数将被清空
     *</pre>
     * @param array $options sql 参数
     * @return Query
     */
    protected function getQuery($options = [])
    {
        if (is_array($options)) {
            $opts = array_merge($this->options, $options);
        } else {
            $opts = $this->options;
        }

        // 默认表名处理
        if (empty($opts['table'])) {
            $opts['table'] = $this->tableName();
        }

        // 默认表名前缀处理
        if (empty($opts['dbkey'])) {
            $opts['dbkey'] = $this->dbKey();
        }

        // 清空参数，防止下次影响查询
        $this->options = [];

        $opts['dbsession'] = $this->getDbsession();

        $query = new Query($opts);
        $this->parseWith($query);

        return $query;
    }

    /**
     * with 以连表的方式追加到指定的Query
     * @param Query $query
     * @return  void
     */
    protected function parseWith(Query $query):void
    {
        $withs = $query->getWith();
        if (empty($withs)) {
            return ;
        }

        $classEntity = $query->getEntity();
        $with_result = false;

        foreach ($withs as $name=>$item) {
            list($name,$load,$join,$hander) = $item;
            list($name,$ref_alias)  = DbUtil::splitAlias($name);

            if (empty($join)) {
                continue;
            }

            /** @var QueryTable $ref_query **/
            $ref_query = call_user_func([$classEntity,'get'.ucfirst($name)]);
            if (!empty($hander) && $hander instanceof \Closure) {
                $hander($ref_query);
            }

            if (empty($ref_alias)) {
                $ref_alias = $ref_query->getAlias();
            }

            if (empty($ref_alias)) {
                $ref_alias = $name;//
            }

            if (is_bool($join)) {
                $joinType = 'left JOIN';
            } else {
                $joinType = $join;
            }

            $refs = [];
            foreach ($ref_query->refs as $with_ref_column=>$mai_column) {
                $refs["{$ref_alias}.{$with_ref_column}"] = ['raw',"#.{$mai_column}"];
            }

            // where 条件
            $ref_where = $ref_query->getWhere();
            if (!empty($ref_where)) {
                foreach ($ref_where as $key=>$ref_condi) {
                    if (!is_array($key) && is_string($key)) {
                        if (strpos($key,'.') === false) {
                            $refs["{$ref_alias}.{$key}"] = $ref_condi;
                        } else {
                            $refs[$key] = $ref_condi;
                        }
                    }
                }
            }

            $join = [[$ref_query->getTable(),$ref_alias],$refs,$joinType];
            $query->addJoin($join);

            $with_result = true;
        }

        $select = $query->getField();
        if ($with_result && empty($select)) {
            $select = "#.*";
            $query->setField($select);
        }

    }

    /**
     * 根据查询结果再查with对应的表数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param Query $query
     * @param array|Entity[] $result
     * @return Query
     */
    protected function queryWith(Query $query,$result)
    {
        if (empty($result)) {
            return $result;
        }

        $withs = $query->getWith();
        if (empty($withs)) {
            return $result;
        }

        $classEntity = $query->getEntity();
        foreach ($withs as $name=>$item) {
            list($name, $load,$join, $hander) = $item;
            list($name, $alias) = DbUtil::splitAlias($name);

            if (!$load) {
                continue;
            }

            /** @var QueryTable $ref_query **/
            $ref_query = call_user_func([$classEntity, 'get' . ucfirst($name)]);
            if (!empty($hander) && $hander instanceof \Closure) {
                $hander($ref_query);
            }

            $refs = $ref_query->refs;
            $id_keys = array_keys($refs);
            $pk_key = $id_keys[0];
            $ref_key = $refs[$pk_key];

            $pk_value_list = DbUtil::getColumn($result,$ref_key);
            if (!empty($pk_value_list)) {
                $pk_value_list = array_unique($pk_value_list);
            }

            if ($query->asArrayStatus()) {
                $ref_query->asArray();
            }

            $ref_entity_list = $ref_query->setWhere([$pk_key=>['in',$pk_value_list]])->fetchAll();
            if ($ref_query->multiple) {
                // 一对多
                $ref_entity_dict = DbUtil::mapIndex($ref_entity_list,$pk_key);
            } else {
                // 一对一
                $ref_entity_dict = DbUtil::index($ref_entity_list,$pk_key);
            }

            foreach ($result as $index=>$entity) {
                $pk_id = $entity[$ref_key];
                $entity[$name] = isset($ref_entity_dict[$pk_id]) ? $ref_entity_dict[$pk_id]:null;
                $result[$index] = $entity;
            }
        }

        return $result;
    }

    /**
     * 隐式调用实体类静态方法
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $method
     * @param array $args
     * @return static
     */
    public function __call($method, $args)
    {
        if ($this->entity && method_exists($this->entity, 'scope'.ucfirst($method))) {
            $this->setScope($method,...$args);
            return $this;
        } else if ($this->entity && method_exists($this->entity, $method)) {
            array_unshift($args, $this);
            call_user_func_array([$this->entity, $method], $args);
            return $this;
        } else  {
            throw new Exception('class ' . $this->entity . ' method ' . $method . ' not exist');
        }
    }

}

?>
