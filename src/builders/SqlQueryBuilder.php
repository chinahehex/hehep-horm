<?php
namespace  horm\builders;

use horm\base\BaseQueryBuilder;
use horm\base\QueryCommand;
use horm\base\DbConnection;
use horm\base\Query;
use horm\base\RawComparison;
use horm\base\RawExpression;
use horm\util\DbUtil;

class SqlQueryBuilder extends BaseQueryBuilder
{
    /**
     * 查询sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $selectSql  = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%ALIAS%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';

    /**
     * 插入sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%)';

    /**
     * 更新sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $updateSql = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%';

    /**
     * 删除sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $deleteSql = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%';


    /**
     * 生成插入单行记录sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function insert(Query $query)
    {
        $values = $fields = [];
        $data = $query->getData();
        foreach ($data as $columnName=>$columnValue) {
            $fields[] = $this->parseColumnName($columnName);
            if (is_array($columnValue)) {
                $operator = $columnValue[0];
                $values[] = $this->callExpressionMethod($operator,$columnName,$columnValue[1]);
            } else {
                $values[] = $this->buildColumnValue($columnName,$columnValue);
            }
        }

        $replace = $query->getReplace() ? true : false;
        $sql = str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($query->getTable()),
                implode(' , ', $fields),
                implode(' , ', $values),
            ], $this->insertSql);

        $sql .= $this->parseLock($query->getLock());

        return $sql;
    }

    /**
     * 生成批量插入
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return array
     */
    public function insertAll(Query $query)
    {
        $queryList = [];
        $datas = $query->getData();
        foreach ($datas as $data) {
            $insertQuery = $query->cloneQuery(['data'=>$data]);
            $queryList[] = $this->insert($insertQuery);
        }

        return $queryList;
    }

    /**
     * 生成 update sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function update(Query $query)
    {

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseAlias($query->getAlias()),
                $this->parseSet($query->getData()),
                $this->parseWhere($query->getWhere()),
                $this->parseOrder($query->getOrder()),
                $this->parseLimit($query->getLimit()),
                $this->parseLock($query->getLock()),
            ], $this->updateSql);

        return $sql;
    }

    /**
     * 生成delete sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function delete(Query $query)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseAlias($query->getAlias()),
                $this->parseWhere($query->getWhere()),
                $this->parseOrder($query->getOrder()),
                $this->parseLimit($query->getLimit()),
                $this->parseLock($query->getLock()),
            ], $this->deleteSql);

        return $sql;
    }


    /**
     * 生成select 查询sql
     *<B>说明：</B>
     *<pre>
     *  多记录数据，二维数组
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function select($query)
    {
        $sql = $this->buildSelectSql($query);

        return $sql;
    }

    /**
     * 生成 Scalar "返回数据的第一行的第一列的值"sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @param string $method 字段方法，或者空
     * @return string
     */
    public function queryScalar($query,$method)
    {
        $methods = ['count'=>'count','min'=>'min','max'=>'max','avg'=>'avg','sum'=>'sum'];
        if (!empty($method) && isset($methods[$method])) {
            $method = $methods[$method];
            $field = $method .'(' . $query->getField() . ') as __result';
            $query->setField($field);
        }

        $sql = $this->buildSelectSql($query);

        return $sql;
    }


    /**
     * 生成查询SQL语句
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query sql解析参数
     * @return string sql
     */
    public function buildSelectSql($query)
    {
        $sql  = $this->parseSql($this->selectSql,$query);
        $sql .= $this->parseLock($query->getLock());
        $this->params = array_merge($this->params,$query->getParams());

        return $sql;
    }

    /**
     * 替换SQL语句中表达式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $sql sql语句
     * @param Query $query sql参数
     * @return string
     */
    public function parseSql($sql,$query)
    {
        $sql   = str_replace(
            ['%TABLE%','%DISTINCT%','%FIELD%','%ALIAS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%','%UNION%','%COMMENT%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseDistinct($query->getDistinct()),
                $this->parseField($query,$query->getField()),
                $this->parseAlias($query->getAlias()),
                $this->parseJoin($query->getJoin(),$query->getSeq()),
                $this->parseWhere($query->getWhere()),
                $this->parseGroup($query->getGroup()),
                $this->parseHaving($query->getHaving()),
                $this->parseOrder($query->getOrder()),
                $this->parseLimit($query->getLimit(),$query->getOffset()),
                $this->parseUnion($query->getUnion()),
            ],$sql);

        return $sql;
    }

    /**
     * 解析锁机制
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param boolean $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (!$lock) {
            return '';
        }

        return ' FOR UPDATE ';
    }

    /**
     * 解析 set sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array $data 数据(一维数组)
     * @return string
     */
    protected function parseSet($data = [])
    {
        $setSqls = [];
        foreach ($data as $column => $value) {
            if (is_array($value)) {
                $operator = $value[0];
                $setSqls[] = $this->callExpressionMethod($operator,$column,$value[1]);
            } else {
                $setSqls[] = $this->buildColumnExpression($column,$value);
            }
        }

        return implode(',',$setSqls);
    }

    /**
     * build 字段表达式sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $columnName 字段名称
     * @param string $columnValue 字段值
     * @param string $operator 操作符
     * @return string
     */
    protected function buildColumnExpression($columnName = '',$columnValue = '',$operator = "=")
    {
        $columnValue = $this->buildColumnValue($columnName,$columnValue);
        $columnName = $this->parseColumnName($columnName);
        $sql = " {$columnName} {$operator} {$columnValue} ";

        return $sql;
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
    protected function parseColumnName($key = '')
    {
        return $key;
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
    protected function parseColumnValue($value = '')
    {
        $columnValueType = gettype($value);
        if ($columnValueType === 'string') {
            $value =  '\''.$this->escapeString($value).'\'';
        } else if (in_array($columnValueType,['integer','double'])) {

        } else if (is_bool($value)) {
            $value =  $value ? '1' : '0';
        } else if (is_null($value)){
            $value =  'null';
        } else {
            // 未知,字符串
            $value =  '\''.$this->escapeString($value).'\'';
        }

        return $value;
    }

    /**
     * 解析字段field
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param Query $query
     * @param string|array $fields
     *<pre>
     * 		$fields = array('name','user','pass')
     * 		$fields = array('name'=>'admin','user'=>'username','pass'=>'password') //别名格式
     *</pre>
     * @return string
     */
    protected function parseField($query,$fields = [])
    {
        // 未设置,默认*
        if (empty($fields)) {
            return '*';
        }

        if (!is_array($fields)) {
            return $fields;
        }

        // $fields 为数组的情况 完善数组方式传字段名的支持
        // 支持 ['columnName'=>'columnValue'] 或 ['columnName'=>['exp','columnValue']]这样的字段别名定义
        $fieldSqls = [];
        foreach ($fields as $columnName=>$field){
            if (is_numeric($columnName) && is_array($field)) {
                $fieldSqls =  array_merge($fieldSqls,$field);
            } else if(is_numeric($columnName)) {// 字段串字段['column1','column2'], 没有别名
                $fieldSqls[] =  $this->parseColumnName($field);
            } else {
                if (is_array($field)) {// 别名为字符串
                    $fieldSqls[] =  $this->callExpressionMethod($field[0],$columnName,$field[1]); ;
                } else {// 别名为数组,表达式
                    $fieldSqls[] =  $this->parseColumnName($columnName).' AS '.$this->parseColumnName($field);
                }
            }
        }

        return implode(',', $fieldSqls);
    }

    /**
     * 解析表别名alias
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string|array $alias
     * @return string
     */
    protected function parseAlias($alias = '')
    {
        $buildSql = '';
        if (!empty($alias)) {
            $buildSql = ' as ' . $alias;
        }

        return $buildSql;
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
    protected function parseWhere($where,$sqlKeyword = 'WHERE')
    {
        if (empty($where)) {
            return '';
        }

        if (is_string($where)) { // 字符串,直接返回
            $whereSql = $where;
        } else {// 数组
            // 获取数组第一个元素，判断条件是and ,or 条件
            $operator = reset($where);// 输出数组中的当前元素,并把数组的内部指针重置到数组中的第一个元素：

            $column = key($where);//当前内部指针位置返回元素键名：

            if (is_string($column)) {// $value 键值为字段名
                $operator = self::EXP_AND;
            } else {
                if (!is_array($operator) && in_array($operator,[self::EXP_OR,self::EXP_AND])) {
                    array_shift($where);// 移除or,and
                } else {
                    $operator = self::EXP_AND;
                }

            }

            $whereSql = $operator == self::EXP_AND ? $this->bulidXorWhere($where) : $this->bulidXorWhere($where,'OR');
        }

        if (empty($whereSql)) {
            return '';
        }

        if (is_null($sqlKeyword)) {
            return $whereSql;
        } else {
            return ' ' . $sqlKeyword . ' ' . $whereSql ;
        }
    }


    /**
     * 解析读取条数limit
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $length 长度
     * @param array $offset 偏移位置
     * @return string
     */
    protected function parseLimit($length = null,$offset = null)
    {
        if (isset($offset)) {
            $limitSql = $offset . ',' . $length;
        } else {
            $limitSql = $length;
        }

        return !empty($limitSql)?   ' LIMIT ' . $limitSql . ' ' : '';
    }

    /**
     * 解析连表join
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $joins 连表参数
     * @param string $seq 分表标识
     * @return string
     */
    protected function parseJoin($joins = [],$seq = '')
    {
        foreach ($joins as $i=>$join) {
            list ($table, $on,$joinType) = $join;
            $readTable = $this->parseTable($table);
            if (empty($joinType)) {
                $joinType = ' LEFT JOIN ';
            }

            $joins[$i] = " $joinType $readTable";
            if (isset($on)) {
                $condition =  $this->parseWhere($on,'ON');
                if ($condition !== '') {
                    $joins[$i] .= $condition;
                }
            }
        }

        return implode(' ', $joins);
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
            $array   =  array();
            foreach ($order as $key=>$value) {
                if (is_numeric($key)) {
                    $array[] =  $this->parseColumnName($value);
                } else {
                    $array[] =  $this->parseColumnName($key) . ' '. ($value == SORT_DESC  ? 'desc' : 'asc');
                }
            }

            $order   =  implode(',',$array);
        }

        return !empty($order)?  ' ORDER BY ' . $order : '';
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
    protected function parseGroup($group = [])
    {
        if (empty($group)) {
            return '';
        }

        if (is_string($group)) {
            $group = explode(',',$group);
        }

        return !empty($group)? ' group by ' . implode(',',$group) : '';
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
        return  !empty($having) ?  $this->parseWhere($having,'HAVING') : '';
    }

    /**
     * 解析取消重复行distinct
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param boolean $distinct
     * @return string
     */
    protected function parseDistinct($distinct = false)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * 解析联合查询union
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $union
     * @return string
     */
    protected function parseUnion($union = [])
    {
        if (empty($union))
            return '';
        if (!isset($union['_all']) || $union['_all'] === true) {
            $unionSql  =   'UNION ALL ';
            unset($union['_all']);
        } else {
            $unionSql  =   'UNION  ';
        }

        $sql = [];

        foreach ($union as $query){
            $sql[] = $unionSql . (is_string($query) ? $query : $this->buildQueryRawCommand($query));
        }

        return implode(' ',$sql);
    }

    /**
     * 构建 and where条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @param string $xor sql 异或sql关键词 and or
     * @return string
     */
    protected function bulidXorWhere($condition = [],$xor = "AND")
    {
        $build = $this->buildWhere($condition);
        if (!empty($build)) {
            if (count($build) == 1) {
                $buildSql =  $build[0];
            } else {
                $buildSql = '(' . implode(' ' . $xor . ' ', $build) . ')';
            }
        } else {
            $buildSql = '';
        }

        return $buildSql;
    }

    /**
     * 构建 where and 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @return string
     */
    protected function bulidAndWhere($condition = [])
    {
        return $this->bulidXorWhere($condition,'AND');
    }

    /**
     * 构建 where or 条件
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $condition 条件参数
     * @return string
     */
    protected function bulidOrWhere($condition)
    {
        return $this->bulidXorWhere($condition,'OR');
    }

    /**
     * 构建where 条件数组
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
            $conditionSql[] = $this->parseWhereItem($column,$value);
        }

        return $conditionSql;
    }


    /**
     * 解析 where 数组元素
     *<B>说明：</B>
     *<pre>
     * 	不支持数字作为字段名
     *</pre>
     * @param string $column
     * @param string|array $value
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
            }else {
                // 字段值为数组,
                $comparison = current($value);// 抽取表达式,抽取数组第一个元素['in','value']

                if (!is_array($comparison)) {
                    if (is_string($comparison) && in_array($comparison,[self::EXP_OR,self::EXP_AND])) {
                        // (ps:['age'=>['or',['gt',2],['lt'=>10]]])
                        array_shift($value);
                        $range_where_sql = [];
                        foreach ($value as $exp=>$val) {
                            $range_where_sql[] = $this->callExpressionMethod($val[0],$column,$val[1]);
                        }
                        $whereSql = implode(' '.$comparison.' ',$range_where_sql);
                    } else {
                        // （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                        if ($comparison instanceof RawComparison) {
                            $whereSql = $this->callExpressionMethod($comparison->getComparison(),$column,$value[1]);
                        } else if (isset(static::$comparison[$comparison])) {
                            // （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
                            $whereSql = $this->callExpressionMethod($comparison,$column,$value[1]);
                        } else {
                            //（ps:['userId'=>['1','2','5'])
                            $whereSql = $this->callExpressionMethod(static::EXP_IN,$column,$value);
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

        } else {// 字段名为数字，一般情况下，$column 为数字,则where 为嵌套条件
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
                        return $this->callExpressionMethod($value[0],$value[1],$value[2]);
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
     * 构建 in where 条件 sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidInWhere($operator,$condition = [])
    {
        $operator = static::$comparison[strtolower($operator)];
        list($columnName, $values) = $condition;

        //  支持子查询
        if ($values instanceof Query) {
            $buildSql = " {$columnName} $operator ({$this->buildSelectSql($values)})";
        } else {
            if (is_string($values)) {
                $values = explode(',',$values);
            }

            // 只有一个查询条件
            if (count($values) == 1) {
                $buildSql = $this->buildColumnExpression($columnName,$values[0]);
            } else {
                $inValues = [];
                foreach ($values as $value) {
                    $inValues[] = $this->buildColumnValue($columnName,$value);
                }

                $columnName = $this->parseColumnName($columnName);
                $conditionSql = implode(',',$inValues);
                $buildSql = " {$columnName} $operator ({$conditionSql})";
            }
        }

        return $buildSql;
    }

    /**
     * 构建 between where 条件 sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidBetweenWhere($operator,$condition = [])
    {
        list($columnName, $values) = $condition;
        $betweenValues = [];
        foreach ($values as $value) {
            $betweenValues[] = $this->buildColumnValue($columnName,$value);
        }

        $columnName = $this->parseColumnName($columnName);
        $buildSql = " {$columnName} BETWEEN {$betweenValues[0]} AND {$betweenValues[1]}";

        return $buildSql;
    }

    /**
     * 构建普通操作where 条件sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidNormalWhere($operator,$condition = [])
    {
        list($columnName, $columnValue) = $condition;
        if (isset(static::$comparison[strtolower($operator)])) {
            $operator = static::$comparison[strtolower($operator)];
        }

        if ($columnValue instanceof Query) {
            $buildSql = " {$columnName} $operator ({$this->buildSelectSql($columnValue)})";
        } else {
            $buildSql = $this->buildColumnExpression($columnName,$columnValue,$operator);
        }

        return $buildSql;
    }

    /**
     * 构建普通操作where 条件sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidExpressionWhere($operator,$condition = [])
    {

        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($columnName);
        $buildSql = " {$columnName}  {$columnValue}";
        return $buildSql;
    }

    /**
     * 构建别名as 表达式
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidAsWhere($operator,$condition = [])
    {

        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($columnName);

        if (is_array($columnValue)) {
            // 带方法 比如min,max
            list($columnAlias,$sqlMethod) = $columnValue;
            $columnAlias = $this->parseColumnName($columnAlias);
            $buildSql = "{$sqlMethod}({$columnName}) AS {$columnAlias}";
        } else {
            $columnValue = $this->parseColumnName($columnValue);
            // 字符串
            $buildSql = " {$columnName} AS {$columnValue}";
        }

        return $buildSql;
    }

    /**
     * 构建Update 数据项累加sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidIncData($operator,$condition = [])
    {
        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($columnName);
        $buildSql = " {$columnName} = {$columnName} + {$columnValue}";

        return $buildSql;
    }

    /**
     * 构建Update 数据项减少sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidDecData($operator,$condition = [])
    {
        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($columnName);
        $buildSql = " {$columnName} = {$columnName} - {$columnValue}";

        return $buildSql;
    }

    /**
     * 构建原始表达式
     *<B>说明：</B>
     *<pre>
     * 一般用与left on 条件
     * ->setJoin(['{{%users1}}','u'],['u.UserName'=>['raw','t.UserName']])->all();
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidRawWhere($operator,$condition = [])
    {
        list($columnName, $columnValue) = $condition;
        $columnName = $this->parseColumnName($columnName);
        $buildSql = " {$columnName} = {$columnValue}";

        return $buildSql;
    }

    /**
     * 构建原始表达式
     *<B>说明：</B>
     *<pre>
     * 　适用所有场景(条件,更新,添加)
     *  $where['userId'] = new RawExpression('> 10');
     *</pre>
     * @param RawExpression $rawExpression
     * @param string $columnName 左边字段名
     * @return string
     */
    protected function buidlRawExpression($rawExpression,$columnName = '')
    {
        if (!empty($columnName)) {
            $columnName = $this->parseColumnName($columnName);
        }

        $buildSql = " {$columnName} {$rawExpression->getExpression()}";

        return $buildSql;
    }




    /**
     * SQL指令安全过滤
     *<B>说明：</B>
     *<pre>
     *      当SQL 未使用预处理时使用此方法过滤危险字符
     *</pre>
     * @param string $sql sql语句
     * @return string
     */
    public function escapeString($sql = '')
    {
        return addslashes($sql);
    }



}
