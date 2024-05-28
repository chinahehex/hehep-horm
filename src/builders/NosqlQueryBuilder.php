<?php
namespace horm\builders;

use horm\base\NosqlCommand;
use horm\base\Query;
use horm\base\RawComparison;
use horm\base\RawExpression;
use horm\base\BaseQueryBuilder;
use horm\mongo\MongoCommand;
use horm\util\DbUtil;

/**
 * nosql 封装类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class NosqlQueryBuilder extends BaseQueryBuilder
{

    /**
     * 直接构建sql命令
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return NosqlCommand
     */
    public function buildRawCommand(Query $query)
    {
        // 过滤原始sql,比如表名前缀
        $rawCommand = $query->getRawSql();

        return new NosqlCommand(['command'=>$rawCommand]);
    }


    /**
     * 格式化指令参数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array $rawCommand 命令语句
     * @param Query $query sql 参数
     * @return NosqlCommand
     */
    protected function createCommand($rawCommand = [],$query = null)
    {
        $params = $query->getParams();
        $params = $this->parseParams($params);

        return new NosqlCommand(['command'=>$rawCommand,'params'=>$params]);
    }

    /**
     * 数据库表达式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    public static $comparison = [
        'eq'=>'$eq',
        '='=>'$eq',
        'neq'=>'$ne',// 不等于

        'gt'=>'$gt',// 大于
        '>'=>'$gt',// 大于
        'egt'=>'$gte',// 大于等于
        '>='=>'$gte',// 大于等于

        'lt'=>'$lt',// 小于
        '<'=>'$lt',// 小于
        'elt'=>'$lte',// 小于等于
        '<='=>'$lte',// 小于等于

        'in'=>'$in',// in 属于集合id
        'not in'=>'$nin',
        'like'=>'$regex',
//        'exp'=>'exp',
        'raw'=>'raw',
//        'inc'=>'inc',
//        'dec'=>'dec',
//        'between'=>'between',
    ];

    /**
     * mongodb 方法
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected  $methods = [
        'count'=>'sum',// 统计条数
        'sum'=>'sum',// 统计和
        'min'=>'min',// 最小值
        'avg'=>'avg',// 平均值
        'max'=>'max',// 最大值
    ];


    /**
     * where 特殊处理函数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected $whereBluiders = [
        'in'=>'bulidInWhere',
        'notin'=>'bulidInWhere',
        'between'=>'bulidBetweenWhere',
        'exp'=>'bulidExpressionWhere',
        'or'=>'bulidOrWhere',
        'and'=>'bulidAndWhere',
        'like'=>'bulidLikeWhere',
        'raw'=>'bulidRawWhere',
    ];

    /**
     * 插入单挑记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return array
     */
    public function insert(Query $query)
    {
        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'data'=>$query->getData(),
            'opts'=>[

            ],
        ];

        $command = [
            'method'=>'insert',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 批量记录
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return array
     */
    public function insertAll(Query $query)
    {
        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'data'=>$query->getData(),
            'opts'=>[

            ],
        ];

        $command = [
            'method'=>'insertAll',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 生成删除行sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return array
     */
    public function delete(Query $query)
    {
        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'filter'=>$this->parseWhere($query,$query->getWhere()),
            'opts'=>[
                'limit'=>$this->parseLimit($query,$query->getLimit()),
            ],
        ];

        $command = [
            'method'=>'delete',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 生成update
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     *　@param Query $query 命令对象
     * @return array
     */
    public function update(Query $query)
    {
        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'data'=>$this->parseSet($query,$query->getData()),
            'filter'=>$this->parseWhere($query,$query->getWhere()),
            'opts'=>[
                'multi'=>true
            ],
        ];

        $limit = $query->getLimit();
        if (is_numeric($limit) && $limit == 1) {
            $opts['opts']['multi'] = false;
        }

        $command = [
            'method'=>'update',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 生成查询记录sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query sql 参数
     * @return array
     */
    public function select(Query $query)
    {
        if (!empty($query->getGroup()) || !empty($query->getJoin())) {
            $command = $this->parseQueryAggregate($query);
        } else {
            $command = $this->parseQueryCommand($query);
        }

        return $command;
    }

    /**
     * 生成"返回数据的第一行的第一列的值"sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query sql 参数
     * @param string $method 聚合查询方法
     * @return array
     */
    public function queryScalar(Query $query,$method)
    {
        if (empty($query->getField()) || $query->getField() == '*') {
            $groupField = 1;
        } else {
            $groupField = $query->getField();
        }

        if ($method == 'count') {
            $groupField = 1;
        } else {
            $groupField = "$" . $groupField;
        }

        if (!empty($method) && isset($this->methods[$method])) {
            $method = $this->methods[$method];
        }

        $pipelines = [];

        if (!empty($query->getWhere())) {
            $pipelines[] = ['$match' => $this->parseWhere($query,$query->getWhere())];
        }

        $pipelines[] = ['$group' =>[
            '_id' => 0,
            '__result' => [
                '$' . $method => $groupField
            ],
        ]];

        if (!empty($query->getHaving())) {
            $pipelines[] = ['$match' => $this->parseWhere($query,$query->getHaving())];
        }

        if (!empty($query->getOrder())) {
            $pipelines[] = ['$sort' => $this->parseOrder($query,$query->getOrder())];
        }

        if (!empty($query->getLimit())) {
            $pipelines[] = ['$limit' => $this->parseLimit($query,$query->getLimit())];
        }

        if (!empty($query->getOffset())) {
            $pipelines[] = ['$skip' => $this->parseOffset($query,$query->getOffset())];
        }

        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'pipelines'=>$pipelines,
            'opts'=>[]
        ];

        $command = [
            'method'=>'scalar',
            'options'=>$opts
        ];

        return $command;
    }

    protected function buildProjects($fields)
    {
        if (empty($fields)) {
            return ['_id'=>0];
        } else {
            return array_merge(['_id'=>0],$fields);
        }
    }

    /**
     * 替换SQL语句中表达式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query sql 参数
     * @return string
     */
    public function parseQueryCommand(Query $query)
    {

        $fields = $this->parseField($query,$query->getField());
        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'filter'=>$this->parseWhere($query,$query->getWhere()),
            'opts'=>[
                'projection'=>$this->buildProjects($fields['fields']),
                'sort'=>$this->parseOrder($query,$query->getOrder()),
                'limit'=>$this->parseLimit($query,$query->getLimit()),
                'skip'=>$this->parseOffset($query,$query->getOffset()),
            ],
        ];

        $command = [
            'method'=>'find',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 解析分组 group 后排序 limit , aggregate查询
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param Query $query sql 参数
     * @return array
     */
    protected function parseQueryAggregate(Query $query)
    {

        $pipelines = [];

        if (!empty($query->getJoin())) {
            list($lookups,$matchs) = $this->parseJoin($query,$query->getJoin());
            foreach ($lookups as $lookup) {
                $pipelines[] = ['$lookup'=>$lookup];
                $pipelines[] = ['$unwind'=>'$'.$lookup['as']];
            }

            foreach ($matchs as $match) {
                $pipelines[] = ['$match'=>$match];
            }
        }

        $fields = $this->parseField($query,$query->getField());

        if (!empty($query->getWhere())) {
            $pipelines[] = ['$match' => $this->parseWhere($query,$query->getWhere())];
        }

        if (!empty($query->getGroup())) {
            $pipelines = $this->parseAggregateGroupField($query,$pipelines,$fields['methods'],$query);
        } else {
            $pipelines[] = ['$project'=>$this->buildProjects($fields['fields'])];
        }



        if (!empty($query->getOrder())) {
            $pipelines[] = ['$sort' => $this->parseOrder($query,$query->getOrder())];
        }

        if (!empty($query->getLimit())) {
            $pipelines[] = ['$limit' => $this->parseLimit($query,$query->getLimit())];
        }

        if (!empty($query->getOffset())) {
            $pipelines[] = ['$skip' => $this->parseOffset($query,$query->getOffset())];
        }

        $opts = [
            'table'=>$this->parseTable($query,$query->getTable()),
            'pipelines'=>$pipelines,
            'opts'=>[]
        ];

        $command = [
            'method'=>'aggregate',
            'options'=>$opts
        ];

        return $command;
    }

    /**
     * 解析Aggregate 分组 group
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $pipelines 分组参数
     * @param array $methodFields 字段参数
     * @param Query $query sql 参数
     * @return array
     */
    private function parseAggregateGroupField(Query $query,$pipelines,$methodFields = [])
    {
        $groups = $this->parseGroup($query,$query->getGroup());
        $id_groups = [];
        $method_groups = [];
        $projects = [];
        $having_projects = [];
        foreach ($groups as $group_col) {
            // 判断用户是否设置了读取字段
            if (isset($methodFields[$group_col])) {
                if (!empty($methodFields[$group_col]['method']) && $methodFields[$group_col]['method']  != 'count') {

                } else {
                    $id_groups[$group_col] = '$'.$group_col;
                }
            } else {
                $id_groups[$group_col] = '$'.$group_col;
            }
        }

        if (!empty($methodFields)) {
            foreach ($methodFields as $column=>$field) {
                $alias = !empty($field['alias']) ? $field['alias'] : $field['field'];
                if (!empty($field['method'])) {
                    if ($field['method'] == 'count') {
                        $method_groups[$alias] =  ['$'.$this->getMethod($field['method'])=>1];
                    } else {
                        $method_groups[$alias] =  ['$'.$this->getMethod($field['method'])=>'$'.$field['field']];
                    }
                }
                $projects[$alias] = '$'.$alias;
                $having_projects[$alias] = '$'.$alias;
            }
        }

        $group_list = ['_id'=>$id_groups];
        foreach ($id_groups as $alias=>$val) {
            $having_projects[$alias] = '$_id.' . $alias;
        }

        if (!empty($method_groups)) {
            foreach ($method_groups as $alias=>$val) {
                $group_list[$alias] = $val;
            }
        }

        if (!empty($group_list)) {
            $pipelines[] = ['$group'=>$group_list];
        }

        if (!empty($having_projects)) {
            $pipelines[] = ['$project'=>$this->buildProjects($having_projects)];
        }

        if (!empty($query->getHaving())) {
            $pipelines[] = ['$match' => $this->parseWhere($query,$query->getHaving())];
        }

        if (!empty($projects)) {
            $pipelines[] = ['$project'=>$this->buildProjects($projects)];
        }

        return $pipelines;
    }


    /**
     * 解析update data
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array $data 数据(一维数组)
     * @return array
     */
    protected function parseSet(Query $query,$data = []) {
        $newData   =  array();
        foreach ($data as $key=>$value){
            if (is_array($value)) {
                switch($value[0]) {
                    case 'inc':
                        $newData['$inc'][$key]  =  abs((int)$value[1]);
                        break;
                    case 'dec':
                        $newData['$dec'][$key]  =  -abs((int)$value[1]);
                        break;
                    case 'set':
                    case 'unset':
                    case 'push':
                    case 'pushall':
                    case 'addtoset':
                    case 'pop':
                    case 'pull':
                    case 'pullall':
                        $newData['$'.$value[0]][$key] = $value[1];
                        break;
                    default:
                        $newData['$set'][$key] =  $value;
                }
            } else {
                $newData['$set'][$key]    = $value;
            }
        }

        return $newData;
    }

    /**
     * 解析字段field
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string|array $fields
     *<pre>
     * 		$fields = 'name,user,pass',// 字符串格式
     * 		$fields = array('name','user','pass'),索引数组
     * 		$fields = array('name','user'=>'username','pass'=>'password') //别名格式
     * 		$fields = array('pass'=>['alias'=>'别名','method'=>'方法min,max,avg 等等']) //别名格式
     *</pre>
     * @return string
     */
    protected function parseField(Query $query,$fields = [])
    {
        $methodFields = [];
        $newFields = [];

        // 判断
        if (empty($fields) || $fields == '*') {
            $fields = [];
        } else if ($fields == '#.*') {
            $fields = [];
        } else if (!is_array($fields)) {
            $fields = explode(',',$fields);
        }

        foreach ($fields as $column=>$field) {
            // 别名
            $alias = '';

            // 字段应用的聚合方法
            $method = '';

            if (is_string($column) && is_string($field)) {
                $alias = $field;
            } else if (is_numeric($column) && is_string($field)) {
                preg_match('/(.+)\s+as\s+(.+)/i', $field, $alias_matches);
                if (!empty($alias_matches)) {
                    $alias = $alias_matches[2];
                    $field = $alias_matches[1];
                }
                preg_match('/(' . implode('|',array_keys($this->methods)) . ')\\((\w+)\\)(.?)/', $field, $method_matches);
                if (!empty($method_matches)) {
                    $method = $method_matches[1];
                    $column = trim($method_matches[2]);
                } else {
                    $column = $field;
                }
            } else if (is_numeric($column) && is_array($field)) {
                if (isset($field['field'])) {
                    $column = $field['field'];
                }

                if (isset($field['alias'])) {
                    $alias = $field['alias'];
                }

                if (isset($field['method'])) {
                    $method = $field['method'];
                }
            } else if (is_string($column) && is_array($field)) {
                if (isset($field['alias'])) {
                    $alias = $field['alias'];
                }

                if (isset($field['method'])) {
                    $method = $field['method'];
                }
            }

            if (!empty($alias) && !empty($method)) {
                $newFields[$column] = ['$'.$this->getMethod($method)=>'$'.$alias];
            } else if (!empty($alias)) {
                $newFields[$alias] = '$'.$column;
            } else {
                $newFields[$column] = 1;
            }

            $methodFields[] = [
                'alias'=>$alias,
                'method'=>$method,
                'field'=>$column,
            ];
        }

        return ['fields'=>$newFields,'methods'=>$methodFields];
    }

    /**
     * 解析 where，或having 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array|string $where 条件参数
     * @param string $sqlKeyword 条件SQL关键词,比如where,having
     * @return string
     */
    protected function parseWhere(Query $query,$where,$sqlKeyword = '$match')
    {
        if (is_string($where)) { // 字符串,直接返回
            $whereSql = $where;
        } else {// 数组
            // 获取数组第一个元素，判断条件是and ,or 条件
            $operator = reset($where);
            $column = key($where);
            if (is_string($column)) {// $value 键值为字段名
                $operator = 'and';
            } else {
                if (!is_array($operator) && in_array($operator,['or','and'])) {
                    array_shift($where);// 移除or,and
                } else {
                    $operator = 'and';
                }
            }

            $whereSql = $operator == 'and' ? $this->bulidAndWhere($query,$where) : $this->bulidOrWhere($query,$where);
        }

        if (empty($whereSql)) {
            $whereSql = [];
        }

        return $whereSql ;
    }

    /**
     * 解析分组条件having
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string|array $having
     * @return string
     */
    protected function parseHaving(Query $query,$having = [])
    {
        return  !empty($having)?  $this->parseWhere($query,$having,'$match') : [];
    }

    /**
     * 解析 and where条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @return string
     */
    protected function bulidAndWhere(Query $query,$condition = [])
    {
        $buildWhere = $this->buildWhere($query,$condition);
        if (!empty($buildWhere)) {
            return ['$and'=>$buildWhere];
        } else {
            return [];
        }
    }

    /**
     * 解析 or where条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @return string
     */
    protected function bulidOrWhere(Query $query,$condition = [])
    {
        $buildWhere = $this->buildWhere($query,$condition);
        if (!empty($buildWhere)) {
            return ['$or'=>$buildWhere];
        } else {
            return [];
        }
    }

    /**
     * 解析where 条件数组
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @return array
     * <pre>
     * 		['name=:name','userid=:userid']
     *</pre>
     */
    protected function buildWhere(Query $query,$condition = [])
    {
        $conditionSql = [];
        foreach ($condition as $column => $value) {
            $whereItem = $this->parseWhereItem($query,$column,$value);
            if (empty($whereItem)) {
                continue;
            }

            $conditionSql[] = $whereItem;
        }

        return $conditionSql;
    }


    /**
     * 解析 where 数组元素
     *<B>说明：</B>
     *<pre>
     * 		不支持数字作为字段名
     *</pre>
     * @param string $column
     * @param string $value
     * @return string
     */
    protected function parseWhereItem(Query $query,$column = '',$value = '')
    {
        $whereSql = '';
        if (is_string($column)) {// 字段名为非数字
            if ($value instanceof RawExpression) {
                $whereSql = $this->buidlRawExpression($query,$value,$column);
            } else if (!is_array($value)) {// (ps:['username'=>'name'])
                $whereSql = $this->callExpressionMethod($query,self::EXP_EQ,$column,$value);
            }else {// 字段值为数组,
                $comparison = current($value);// 抽取表达式,抽取数组第一个元素['in','value']

                if (!is_array($comparison)) {// (ps:['age'=>['or',['gt',2],['lt'=>10]]])
                    if (is_string($comparison) && in_array($comparison,[self::EXP_OR,self::EXP_AND])) {//
                        array_shift($value);
                        $range_where_sql = [];
                        foreach ($value as $exp=>$val) {
                            $range_where_sql[] = $this->callExpressionMethod($query,$val[0],$column,$val[1]);
                        }
                        $whereSql = ['$'.$comparison=>$range_where_sql];
                    } else {// （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                        if ($comparison instanceof RawComparison) {
                            $whereSql = $this->callExpressionMethod($query,$comparison->getComparison(),$column,$value[1]);
                        } else if (isset(static::$comparison[$comparison])) {
                            // （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                            $whereSql = $this->callExpressionMethod($query,$comparison,$column,$value[1]);
                        } else {
                            // // （ps:['userId'=>['1','2','5'])
                            $whereSql = $this->callExpressionMethod($query,BaseQueryBuilder::EXP_IN,$column,$value);
                        }
                    }
                } else {// （ps:['age'=>[['gt',4],['lt','9']]])
                    $range_where_sql = [];
                    foreach ($value as $exp=>$val) {
                        $range_where_sql[] = $this->callExpressionMethod($query,$val[0],$column,$val[1]);
                    }

                    $whereSql = ['$and'=>$range_where_sql];
                }
            }

            return $whereSql;

        } else {// 字段名为数字，一般情况下，$value 为数字递归解析where
            if ($value instanceof RawExpression) {
                return $this->buidlRawExpression($query,$value);
            } else {

                if (is_array($value)) {
                    $comparison = current($value);
                } else {
                    $comparison = $value;
                }

                if ($comparison instanceof RawComparison) {
                    return $this->callExpressionMethod($query,$comparison->getComparison(),$value[1],$value[2]);
                } else if (!is_array($comparison))  {
                    if (isset(static::$comparison[$comparison]) && count($value) == 3 && DbUtil::isIndexArray($value)) {
                        $whereSql = $this->callExpressionMethod($query,$value[0],$value[1],$value[2]);
                        return $whereSql;
                    } else {
                        return $this->parseWhere($query,$value,null);
                    }
                } else {
                    return $this->parseWhere($query,$value,null);
                }
            }
        }
    }

    /**
     * 构建 in where 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidInWhere(Query $query,$expression,$condition = [])
    {
        list($column, $values) = $condition;
        $column = $this->parseColumnName($query,$column);
        $expression = $this->getExpression($expression);
        if (!empty($column)) {
            return [$column=>[$expression=>$values]];
        } else {
            return [$expression=>$values];
        }
    }

    protected function buidlRawExpression(Query $query,$rawExpression,$columnName = '')
    {
        if (!empty($columnName)) {
            $columnName = $this->parseColumnName($query,$columnName);
        }

        $buildSql = " {$columnName} {$rawExpression->getExpression()}";

        return $buildSql;
    }

    protected function bulidRawWhere(Query $query,$operator,$condition = [])
    {
        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($query,$columnName);

        return [$columnName=>['$eq'=>$columnValue]];
    }

    /**
     * 构建 between where 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidBetweenWhere(Query $query,$expression = '',$condition = [])
    {
        list($column, $values) = $condition;
        $column = $this->parseColumnName($query,$column);
        $egtExpression = $this->getExpression('egt');//大于等于
        $eltExpression = $this->getExpression('elt');//小于等于

        return [$column=>[$egtExpression=>$values[0],$eltExpression=>$values[1]]];
    }

    /**
     * 构建普通操作where 条件sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidNormalWhere(Query $query,$expression = '',$condition = [])
    {
        list($column, $values) = $condition;
        $column = $this->parseColumnName($query,$column);
        $expression = strtolower($expression);
        if ($expression == 'eq') {
            return [$column=>$values];
        } else {
            $expression = $this->getExpression($expression);
            if (!empty($column)) {
                return [$column=>[$expression=>$values]];
            } else {
                return [$expression=>$values];
            }
        }
    }

    /**
     * 构建普通操作where 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidExpressionWhere(Query $query,$expression = '',$condition = [])
    {
        list($column, $values) = $condition;
        $column = $this->parseColumnName($query,$column);
        $values = $values[0];
        $buildSql = " {$column}  {$values}";

        return $buildSql;
    }

    /**
     * 构建普通操作where 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidLikeWhere(Query $query,$expression = '',$condition = [])
    {
        list($column, $values) = $condition;
        $column = $this->parseColumnName($query,$column);

        if (substr($values,0,1) == '%' && substr($values, -1) == '%') {
            $regex = str_replace('%','',$values);
        } else if (substr($values,0,1) == '%') {
            $regex = str_replace('%','',$values) .'$';
        } else if (substr($values, -1) == '%') {
            $regex = '^' . str_replace('%','',$values);
        } else {
            $regex = $values;
        }

        return [$column=>new \MongoDB\BSON\Regex($regex,'i')];
    }

    /**
     * 解析分组group
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string|array $group
     * @return string
     */
    protected function parseGroup(Query $query,$groups = [])
    {
        if (empty($groups)) {
            return [];
        }

        if (is_string($groups)) {
            $groups = explode(',',$groups);
        }

        return $groups;
    }


    protected function parseLimit(Query $query,$length = null,$offset = null)
    {
        return $length;
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
    protected function buildTable(Query $query,$table = '')
    {
        $table_name = '';
        $table_alias = '';

        if (is_array($table)) {
            list($table_name,$table_alias) = $table;
            // 普通字符串
            $table_name = $this->getTableName($table_name);
        } else {
            preg_match('/(.+)\s+as\s+(.+)/i', $table, $table_matches);
            if (!empty($table_matches)) {
                $table_name = $table_matches[1];
                $table_alias = $table_matches[2];
            } else {
                $table_name = $table;
            }
            // 普通字符串
            $table_name = $this->getTableName($table_name);
        }

        return [$table_name,$table_alias];
    }

    /**
     * 解析连表join
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $joins 连表参数
     * @return string
     */
    protected function parseJoin(Query $query,$joins = [])
    {
        $lookups = [];
        $matchs = [];
        foreach ($joins as $i=>$join) {
            list ($table, $ons,$joinType) = $join;
            list ($table_name,$table_alias) = $this->buildTable($query,$table);
            $wheres = [];
            $on_index = 0;
            foreach ($ons as $on_col=>$on_val) {
                if ($on_index == 0) {
                    if (strpos($on_col,'.') !== false && strpos($on_val[1],'.') !== false) {
                        list($on_table_alias1,$col_name1) = explode('.',$on_col);
                        list($on_table_alias2,$col_name2) = explode('.',$on_val[1]);
                        if ($on_table_alias1 == $table_alias) {
                            $localField = $col_name2;
                            $foreignField = $col_name1;
                        } else {
                            $localField = $col_name1;
                            $foreignField = $col_name2;
                        }
                    } else if (strpos($on_col,'.') === false) {
                        $localField = $on_col;
                        list($on_table_alias2,$foreignField) = explode('.',$on_val[1]);
                    } else if (strpos($on_val[1],'.') === false) {
                        $localField = $on_val[1];
                        list($on_table_alias2,$foreignField) = explode('.',$on_col);
                    }
                } else {
                    $wheres[$on_col] = $on_val;
                }

                $on_index++;
            }

            $lookups[] = [
                'from'=>$table_name,
                'localField'=>$localField,
                'foreignField'=>$foreignField,
                'as'=>$table_alias,
            ];

            if (!empty($wheres)) {
                $matchs[] =  $this->parseWhere($query,$wheres,'ON');
            }
        }

        return [$lookups,$matchs];
    }

    protected function parseOffset(Query $query,$offset = null)
    {
        return $offset;
    }

    /**
     * 解析排序order
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $orders
     *<pre>
     *      array:
     *      $order = array(
     *	        name=>SORT_DESC,// 3 降序
     *          id =>SORT_ASC,// 4 升序
     *      )
     *		或
     * 		$order = array(
     *	        'name','id'
     *      )
     *</pre>
     * @return string
     */
    protected function parseOrder(Query $query,$orders)
    {
        $sorts = [];
        if (is_array($orders)) {
            foreach ($orders as $key=>$value) {
                if (is_numeric($key)) {
                    $sorts[$value] =  1;
                } else {
                    $sorts[$key] = ($value == SORT_DESC || strtolower($value) == 'desc')  ? -1 : 1;
                }
            }
        }

        return $sorts;
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
        if (!empty($expression) && isset($this->whereBluiders[$expression])) {
            return  $this->whereBluiders[$expression];
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
    public function callExpression(Query $query,$expression = '',$column = '',$value = '')
    {
        $method = $this->getExpressionMethod($expression);

        return call_user_func_array([$this, $method] ,[$query,$expression, [$column,$value]]);
    }

    /**
     * 获取表达式名称
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $expression 表达式名称
     * @return string
     */
    private function getExpression($expression = '')
    {
        return isset(static::$comparison[$expression]) ? static::$comparison[$expression] : '';
    }

    /**
     * 获取方法名称
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $method 方法名
     * @return string
     */
    private function getMethod($method = '')
    {
        return isset($this->methods[$method]) ? $this->methods[$method] : '';
    }


}
