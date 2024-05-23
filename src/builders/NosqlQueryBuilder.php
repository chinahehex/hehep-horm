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

//        'exp'=>'exp',
//        'raw'=>'raw',
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
        'and'=>'bulidAndWhere'
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
            'table'=>$this->parseTable($query->getTable()),
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
            'table'=>$this->parseTable($query->getTable()),
            'filter'=>$this->parseWhere($query->getWhere()),
            'opts'=>[
                'limit'=>$this->parseLimit($query->getLimit()),
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
            'table'=>$this->parseTable($query->getTable()),
            'data'=>$this->parseSet($query->getData()),
            'filter'=>$this->parseWhere($query->getWhere()),
            'opts'=>[
                'limit'=>$this->parseLimit($query->getLimit()),
            ],
        ];

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
    public function select($query)
    {
        if (!empty($query->getGroup())) {
            $command = $this->parseGroupQueryAggregate($query);
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
    public function queryScalar($query,$method)
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
            $pipelines[] = ['$match' => $this->parseWhere($query->getWhere())];
        }

        $pipelines[] = ['$group' =>[
            '_id' => 0,
            '__result' => [
                '$' . $method => $groupField
            ],
        ]];

        if (!empty($query->getHaving())) {
            $pipelines[] = ['$match' => $this->parseWhere($query->getHaving())];
        }

        if (!empty($query->getOrder())) {
            $pipelines[] = ['$sort' => $this->parseOrder($query->getOrder())];
        }

        if (!empty($query->getLimit())) {
            $pipelines[] = ['$limit' => $this->parseLimit($query->getLimit())];
        }

        if (!empty($query->getOffset())) {
            $pipelines[] = ['$skip' => $this->parseOffset($query->getOffset())];
        }

        $opts = [
            'table'=>$this->parseTable($query->getTable()),
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
        return array_merge(['_id'=>0],$fields);
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
    public function parseQueryCommand($query)
    {

        $fields = $this->parseField($query->getField());
        $opts = [
            'table'=>$this->parseTable($query->getTable()),
            'filter'=>$this->parseWhere($query->getWhere()),
            'opts'=>[
                'projection'=>$this->buildProjects($fields['fields']),
                'sort'=>$this->parseOrder($query->getOrder()),
                'limit'=>$this->parseLimit($query->getLimit()),
                'skip'=>$this->parseOffset($query->getOffset()),
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
    protected function parseGroupQueryAggregate($query)
    {

        $fields = $this->parseField($query->getField());
        $pipelines = [];

        if (!empty($query->getWhere())) {
            $pipelines[] = ['$match' => $this->parseWhere($query->getWhere())];
        }

        $pipelines = $this->parseAggregateGroupField($pipelines,$fields['methods'],$query);

        if (!empty($query->getHaving())) {
            $pipelines[] = ['$match' => $this->parseWhere($query->getHaving())];
        }

        if (!empty($query->getOrder())) {
            $pipelines[] = ['$sort' => $this->parseOrder($query->getOrder())];
        }

        if (!empty($query->getLimit())) {
            $pipelines[] = ['$limit' => $this->parseLimit($query->getLimit())];
        }

        if (!empty($query->getOffset())) {
            $pipelines[] = ['$skip' => $this->parseOffset($query->getOffset())];
        }

        $opts = [
            'table'=>$this->parseTable($query->getTable()),
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
    private function parseAggregateGroupField($pipelines,$methodFields = [],$query)
    {
        $newGroups = $columns = [];
        $countGroups = [];

        if (!empty($methodFields)) {
            foreach ($methodFields as $column=>$operator) {
                $alias = isset($operator['alias']) ? $operator['alias'] : $column;
                if (isset($operator['method'])) {
                    if ($operator['method'] == 'count') {
                        $countGroups[$alias] =  ['$'.$this->getMethod($operator['method'])=>1];
                    } else {
                        $countGroups[$alias] =  ['$'.$this->getMethod($operator['method'])=>'$'.$column];
                    }
                } else {
                    $newGroups[$alias] = '$'.$column;
                }
            }
        }


        if (!empty($countGroups)) {
            $project = [];
            foreach ($newGroups as $alias=>$value) {
                $project[$alias] = '$_id.' . $alias;
            }

            $groupList = ['_id'=>$newGroups];
            foreach ($countGroups as $alias=>$value) {
                $groupList[$alias] = $value;
                $project[$alias] = 1;
            }

            $pipelines[] = ['$group'=>$groupList];
            $pipelines[] = ['$project'=>$this->buildProjects($project)];

        } else {
            $pipelines[] = ['$group'=>$this->buildProjects($newGroups)];
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
    protected function parseSet($data = []) {
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
    protected function parseField($fields = [])
    {
        $methodFields = [];

        $regmethods = implode('|',array_keys($this->methods));

        $newFields = [];
        if (empty($fields) || $fields == '*') {
            $fields = [];
        } else if (!is_array($fields)) {
            $fields = explode(',',$fields);
        }

        foreach ($fields as $column=>$field) {
            if (is_numeric($column)) {
                if (is_string($field)) {
                    $column = $field;
                } else if (is_array($field)) {
                    $column = $field['field'];
                }
            }

            $fieldvalue = [];
            if (!is_array($field)) {
                // 识别min
                $alias = '';
                preg_match('/(.+)\s+as\s+(.+)/i', $field, $asmatches);
                if (!empty($asmatches)) {
                    $aliascolumn = $asmatches[2];
                    $fieldvalue['alias'] = $aliascolumn;
                    $field = $asmatches[1];
                }

                preg_match('/(' . $regmethods . ')\\((\w+)\\)(.?)/', $field, $methodmatches);
                if (!empty($methodmatches)) {
                    $fieldvalue['method'] = $methodmatches[1];
                    $column = trim($methodmatches[2]);
                } else {
                    $column = $field;
                }
            } else {
                $fieldvalue = $field;

            }

            $fieldvalue['field'] = $column;
            $methodFields[$column] = $fieldvalue;

            if (isset($fieldvalue['alias'])) {
                $aliascolumn = $fieldvalue['alias'];
            } else {
                $aliascolumn = $column;
            }

            if (isset($fieldvalue['method'])) {
                $newFields[$column] = ['$'.$this->getMethod($fieldvalue['method'])=>'$'.$column];
            } else {
                $newFields[$column] = 1;
            }
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
    protected function parseWhere($where,$sqlKeyword = '$match')
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

            $whereSql = $operator == 'and' ? $this->bulidAndWhere($where) : $this->bulidOrWhere($where);
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
    protected function parseHaving($having = [])
    {
        return  !empty($having)?  $this->parseWhere($having,'$match') : [];
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
    protected function bulidAndWhere($condition = [])
    {
        $buildWhere = $this->buildWhere($condition);
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
    protected function bulidOrWhere($condition = [])
    {
        $buildWhere = $this->buildWhere($condition);
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
    protected function buildWhere($condition = [])
    {
        $conditionSql = [];
        foreach ($condition as $column => $value) {
            $whereItem = $this->parseWhereItem($column,$value);
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
    protected function parseWhereItem($column = '',$value = '')
    {
        $whereSql = '';
        if (is_string($column)) {// 字段名为非数字
            if ($value instanceof RawExpression) {
                $whereSql = $this->buidlRawExpression($value,$column);
            } else if (!is_array($value)) {// (ps:['username'=>'name'])
                $whereSql = $this->callExpressionMethod(self::EXP_EQ,$column,$value);
            }else {// 字段值为数组,
                $comparison = current($value);// 抽取表达式,抽取数组第一个元素['in','value']

                if (!is_array($comparison)) {// (ps:['age'=>['or',['gt',2],['lt'=>10]]])
                    if (is_string($comparison) && in_array($comparison,[self::EXP_OR,self::EXP_AND])) {//
                        array_shift($value);
                        $range_where_sql = [];
                        foreach ($value as $exp=>$val) {
                            $range_where_sql[] = $this->callExpressionMethod($val[0],$column,$val[1]);
                        }
                        $whereSql = implode(' '.$comparison.' ',$range_where_sql);
                    } else {// （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                        if ($comparison instanceof RawComparison) {
                            $whereSql = $this->callExpressionMethod($comparison->getComparison(),$column,$value[1]);
                        } else if (isset(static::$comparison[$comparison])) {
                            // （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                            $whereSql = $this->callExpressionMethod($comparison,$column,$value[1]);
                        } else {
                            // // （ps:['userId'=>['1','2','5'])
                            $whereSql = $this->callExpressionMethod(BaseQueryBuilder::EXP_IN,$column,$value);
                        }
                    }
                } else {// （ps:['age'=>[['gt',4],['lt','9']]])
                    $range_where_sql = [];
                    foreach ($value as $exp=>$val) {
                        $range_where_sql[] = $this->callExpressionMethod($val[0],$column,$val[1]);
                    }

                    $whereSql = implode(' and ',$range_where_sql);
                }
            }

            return $whereSql;

        } else {// 字段名为数字，一般情况下，$value 为数字递归解析where
            if ($value instanceof RawExpression) {
                return $this->buidlRawExpression($value);
            } else {

                if (is_array($value)) {
                    $comparison = current($value);
                } else {
                    $comparison = $value;
                }

                if ($comparison instanceof RawComparison) {
                    return $this->callExpressionMethod($comparison->getComparison(),$value[1],$value[2]);
                } else if (!is_array($comparison))  {
                    if (isset(static::$comparison[$comparison]) && count($value) == 3 && DbUtil::isIndexArray($value)) {
                        $whereSql = $this->callExpressionMethod($value[0],$value[1],$value[2]);
                        return $whereSql;
                    } else {
                        return $this->parseWhere($value,null);
                    }
                } else {
                    return $this->parseWhere($value,null);
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
    protected function bulidInWhere($expression,$condition = [])
    {
        list($column, $values) = $condition;

        $expression = $this->getExpression($expression);
        if (!empty($column)) {
            return [$column=>[$expression=>$values]];
        } else {
            return [$expression=>$values];
        }
    }

    protected function buidlRawExpression($rawExpression,$columnName = '')
    {
        if (!empty($columnName)) {
            $columnName = $this->parseColumnName($columnName);
        }

        $buildSql = " {$columnName} {$rawExpression->getExpression()}";

        return $buildSql;
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
    protected function bulidBetweenWhere($expression = '',$condition = [])
    {
        list($column, $values) = $condition;
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
    protected function bulidNormalWhere($expression = '',$condition = [])
    {
        list($column, $values) = $condition;
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
    protected function bulidExpressionWhere($expression = '',$condition = [])
    {
        list($column, $values) = $condition;
        $values = $values[0];
        $column = $this->parseKey($column);
        $buildSql = " {$column}  {$values}";
        return $buildSql;
    }

    protected function parseLimit($length = null,$offset = null)
    {
        return $length;
    }

    protected function parseOffset($offset = null)
    {
        return $offset;
    }

    /**
     * 解析排序order
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $order
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
    protected function parseOrder($order)
    {
        if (is_array($order)) {
            foreach ($order as $key=>$value) {
                if (is_numeric($key)) {
                    $order =  [$value=>1];
                } else {
                    $order = [$key=>$value == SORT_DESC  ? -1 : 1];
                }
            }
        }

        return $order;
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
    public function callExpression($expression = '',$column = '',$value = '')
    {
        $method = $this->getExpressionMethod($expression);

        return call_user_func_array([$this, $method] ,[$expression, [$column,$value]]);
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
