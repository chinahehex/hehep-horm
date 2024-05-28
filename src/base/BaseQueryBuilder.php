<?php
namespace  horm\base;

/**
 * sql 语句构建类
 *<B>说明：</B>
 *<pre>
 *  1、所有操作数据库都必须事先生成sql
 *</pre>
 */
class BaseQueryBuilder
{
    /** 表达式定义 */
    const EXP_EQ = 'eq';
    const EXP_NEQ = 'neq';
    const EXP_GT = 'gt';
    const EXP_EGT = 'egt';
    const EXP_LT = 'lt';
    const EXP_ELT = 'elt';
    const EXP_NOTLIKE = 'notlike';
    const EXP_LIKE = 'like';
    const EXP_NOTIN = 'notin';
    const EXP_EXP = 'exp';
    const EXP_OR = 'or';
    const EXP_AND = 'and';
    const EXP_INC = 'inc';
    const EXP_DEC = 'dec';
    const EXP_RAW = 'raw';
    const EXP_AS = 'as';
    const EXP_IN = 'in';

	/**
	 * 数据库连接类
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var DbConnection
	 */
    protected $dbconn = null;

	/**
	 * 数据库表达式
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
    public static $comparison = [
        'eq'=>'=',
        '='=>'=',
        'neq'=>'<>',
        '<>'=>'<>',
        'gt'=>'>',
        '>'=>'>',
        'egt'=>'>=',
        '>='=>'>=',
        'lt'=>'<',
        '<'=>'<',
        'elt'=>'<=',
        '<='=>'<=',
        'notlike'=>'NOT LIKE',
        'like'=>'LIKE',
        'in'=>'IN',
        'notin'=>'NOT IN',
        'exp'=>'exp',
        'raw'=>'raw',
        'inc'=>'inc',
        'dec'=>'dec',
        'between'=>'between',
    ];


	/**
	 * where 特殊处理函数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array build
	 */
	protected $builders = [
		'in'=>'bulidInWhere',
		'notin'=>'bulidInWhere',
		'between'=>'bulidBetweenWhere',
		'exp'=>'bulidExpressionWhere',
		'or'=>'bulidOrWhere',
		'and'=>'bulidAndWhere',
		'inc'=>'bulidIncData',// 加字段
		'dec'=>'bulidDecData',// 减字段,
        'raw'=>'bulidRawWhere',// 原始命令
        'as'=>'bulidAsWhere',// 别名
        'eq'=>'bulidNormalWhere',// 等于
        'neq'=>'bulidNormalWhere',//不等于
        'gt'=>'bulidNormalWhere',//
        'egt'=>'bulidNormalWhere',
        'lt'=>'bulidNormalWhere',
        'elt'=>'bulidNormalWhere',
        'notlike'=>'bulidNormalWhere',
        'like'=>'bulidNormalWhere',
	];

	/**
	 * 是否启用预处理
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var boolean
	 */
	protected $usebind = true;

	/**
	 * 绑定参数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
    protected $params = [];

	/**
	 * 绑定参数名称计数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
    protected $bindName = [];

	/**
	 * 构造函数
	 *<B>说明：</B>
	 *<pre>
	 *  初始化操作
	 *</pre>
	 * @param DbConnection $dbconn 数据库驱动类
	 */
	public function __construct($dbconn = null)
	{
		$this->dbconn = $dbconn;
	}

    /**
     * 字段名分析
     *<B>说明：</B>
     *<pre>
     *  此方法一般由继承类实现
     *</pre>
     * @param string $key
     * @return string
     */
    public function formatColumnName(Query $query,$column_name = '')
    {
        return $column_name;
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $column_name
     * @return string
     */
    public function parseColumnName(Query $query,$column_name  = '')
    {
        if (substr($column_name,0,2) == '#.') {
            $table_alias = $query->getAlias();
            if (!empty($table_alias)) {
                list($tb_alias,$col_name) = explode('.',$column_name);
                return $this->formatColumnName($query,$table_alias) . '.'. $this->formatColumnName($query,$col_name);
            } else {
                return str_replace('#.','',$column_name);
            }
        } else {
            if (strpos($column_name,'.') !== false) {
                list($table_alias,$col_name) = explode('.',$column_name);
                return $this->formatColumnName($query,$table_alias).'.' . $this->formatColumnName($query,$col_name);
            } else {
                return $this->formatColumnName($query,$column_name);
            }
        }
    }

    /**
     * 构建参数命令
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return QueryCommand
     */
    public function buildParamsCommand(Query $query)
    {
        // 清空上次创建命令的资源(绑定参数)
        $this->params = [];
        $rawCommand = $this->buildQueryRawCommand($query);

        return $this->createCommand($rawCommand,$query);
    }

    /**
     * 直接构建sql命令
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return QueryCommand
     */
    public function buildRawCommand(Query $query)
    {
        // 过滤原始sql,比如表名前缀
        $rawCommand = $this->replaceSql($query->getRawSql());

        return new QueryCommand(['command'=>$rawCommand,'params'=>$query->getParams()]);
    }

    /**
     * 格式化指令参数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|array $rawCommand 命令语句
     * @param Query $query sql 参数
     * @return QueryCommand
     */
    protected function createCommand($rawCommand,$query = null)
    {
        $params = $query->getParams();
        $params = $this->parseParams($params);

        return new QueryCommand(['command'=>$rawCommand,'params'=>$params]);
    }

    /**
     * 生成$query 对应的 sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query
     * @return string
     */
    public function buildQueryRawCommand(Query $query)
    {
        $build = $query->getBuild();
        list($method,$params) = $build;
        $rawCommand = call_user_func_array([$this,$method],$params);

        return $rawCommand;
    }

    /**
     * build 字段sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $columnName 绑定参数名
     * @param string $columnValue 绑定参数名值
     * @return string
     */
    protected function buildColumnValue(Query $query,$columnName,$columnValue)
    {
        if ($this->usebind) {
            if (0 !== strpos($columnValue,':')) {//判断第一字符是否为":",
                $columnValue = ':'.$this->bindParam($columnName,$columnValue);
            }
        } else {
            $columnValue = $this->parseColumnValue($query,$columnValue);
        }

        return $columnValue;
    }

    /**
     * 解析value分析
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param mixed $value 字段值
     * @return string
     */
    protected function parseColumnValue(Query $query,$value = '')
    {
        return  $value;
    }


    /**
     * 参数绑定
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $name 绑定参数名
     * @param string $value 绑定值
     * @return string 参数key
     */
    protected function bindParam($name = '',$value = '')
    {

        $name = str_replace("#",'',$name);
        $name = str_replace(".",'_',$name);
        $name = 'sys_' . $name;
        if (!isset($this->bindName[$name])) {
            $nums = '1';
            $this->bindName[$name] = 2;
        } else {
            $nums = $this->bindName[$name];
            $this->bindName[$name] = $nums + 1;
        }

        $key = !empty($nums) ? $name . '_' . $nums : $name;
        $this->params[':'.$key] = $value;

        return $key;
    }

    /**
     * 获取表达式方法名
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @return string
     */
    public function getExpressionMethod($expression = '')
    {
        if (!empty($expression) && isset($this->builders[$expression])) {
            return  $this->builders[$expression];
        } else {
            return 'bulidNormalWhere';
        }
    }

    /**
     * 调用表达式方法
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @param string $column 字段名
     * @param string $value 字段值
     * @return string
     */
    public function callExpressionMethod(Query $query,$expression = '',$column = '',$value = '')
    {
        $method = $this->getExpressionMethod($expression);

        if (empty($method)) {
            return "";
        }

        return call_user_func_array([$this, $method] ,[$query,$expression, [$column,$value]]);
    }



    /**
     * 获取绑定参数
     *<B>说明：</B>
     *<pre>
     *  获取绑定参数，清空绑定参数
     *</pre>
     * @param array $params 绑定参数
     * @return array
     */
    public function parseParams($params = [])
    {
        $params = array_merge($this->params,is_array($params) ? $params : []);
        $this->params = [];
        $this->bindName = [];

        return $params;
    }

    /**
     * 解析table
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string|Query $table 表名
     * @return string 表名，多个表名逗号隔开
     */
    protected function parseTable(Query $query,$table = '')
    {
        if ($table instanceof Query) {
            $table_sql = '(' . $this->buildQueryRawCommand($table) . ')';
        } else {
            if (is_array($table)) {
                list($tableName,$tableAlias) = $table;
                // 普通字符串
                $table = $this->getTableName($tableName);
                $table_sql = $this->parseColumnName($query,$table) . ' AS ' . $this->parseColumnName($query,$tableAlias);
            } else {
                // 普通字符串
                $table = $this->getTableName($table);
                $table_sql = $this->parseColumnName($query,$table);
            }
        }

        return $table_sql;
    }


    /**
     * 获取真实表名
     *<B>说明：</B>
     *<pre>
     *  主要功能给表加前缀
     *</pre>
     * @param string $table  表名
     * @param string $prefix  表前缀
     * @return string
     */
    public function getTableName($table = '',$prefix = '')
    {
        preg_match('/\\{\\{(%?[\w\-\.]+%?)\\}\\}(.*)?|\\[\\[(%?[\w\-\.]+%?)\\]\\](.*)?/', $table, $matches);
        if ($matches) {
            if (empty($prefix)) {
                $prefix = $this->dbconn->getTablePrefix();
            }

            if (!empty($matches[1])) {
                // 匹配中了{{}}
                $table =  $matches[1];
                $table = str_replace('%', $prefix, $table);
                if (isset($matches[2])) {
                    $table .=  $matches[2];
                }
            } else if (!empty($matches[3])) {
                // 匹配中了[[]]
                $table =  $matches[3];
                $table = str_replace('%', $prefix, $table);
                if (isset($matches[4])) {
                    $table .=  $matches[4];
                }
            }
            return $table;
        } else {
            return $table;
        }
    }

    /**
     * 替换SQL
     *<B>说明：</B>
     *<pre>
     *  1、替换{{}},[[]]大括号内的表名加上前缀
     *</pre>
     * @param string $sql
     * @return string
     */
    public function replaceSql($sql = '')
    {
        //替换表名
        return preg_replace_callback(
            '/\\{\\{(%?[\w\-\.]+%?)\\}\\}(.*)?|\\[\\[(%?[\w\-\.]+%?)\\]\\](.*)?/',
            function ($matches) {
                if (empty($prefix)) {
                    $prefix = $this->dbconn->getTablePrefix();
                }
                if (!empty($matches[1])) {
                    // 匹配中了{{}}
                    $table =  $matches[1];
                    $table = str_replace('%', $prefix, $table);
                    if (isset($matches[2])) {
                        $table .=  $matches[2];
                    }
                } else if (!empty($matches[3])) {
                    // 匹配中了[[]]
                    $table =  $matches[3];
                    $table = str_replace('%', $prefix, $table);
                    if (isset($matches[4])) {
                        $table .=  $matches[4];
                    }
                }

                return $table;
            },
            $sql
        );
    }
}
