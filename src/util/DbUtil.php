<?php
namespace  horm\util;

use horm\QueryTable;

/**
 * db 帮助类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class DbUtil
{
    /**
     * 分离带as表名
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $alias 表名 比如user as name 、user
     * @return array  表分离后的数组
     *<pre>
     *   $out = ['user','u'] user表名,u 表别名
     *</pre>
     */
    public static function splitAlias(string $alias = ''):array
    {
        $preg =  '/^(.+)\s+AS\s+(.+)\s*.*/i';
        preg_match ($preg, $alias,$alias_matches);

        if (empty($alias_matches)) {
            return [$alias,''];
        } else {
            return [$alias_matches[1],$alias_matches[2]];
        }
    }

    /**
     * 判断是否索引数组
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return boolean true 索引数组　false 非索引数组
     */
    public static function isIndexArray($array)
    {
        if (is_array($array)) {
            $keys = array_keys($array);
            $result = $keys === array_keys($keys);

            return $result;
        }

        return false;
    }

    /**
     * 判断id 是否in 查询的值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $ids
     * @return boolean true 是　false 否
     */
    public static function checkIdInWhere($ids = [])
    {
        if (!is_array($ids)) {
            return false;
        }

        // 必须为索引数组
        if (!self::isIndexArray($ids)) {
            return false;
        }

        $values = array_values($ids);
        $result = true;
        foreach ($values as $value) {
            if (is_array($value)) {
                $result = false;
                break;
            }
        }

        return $result;
    }


    public static function getColumn($array, $name)
    {
        $value_list = [];
        foreach ($array as $arr) {
            if (isset($arr[$name])) {
                $value_list[] = $arr[$name];
            }
        }

        return $value_list;
    }

    public static function mapIndex($datas,$key)
    {
        $result = [];
        foreach ($datas as $data) {
            $result[$data[$key]][] = $data;
        }

        return $result;
    }

    public static function index($array, $key)
    {
        $result = [];
        foreach ($array as $element) {
            if (isset($element[$key])) {
                $value = $element[$key];
                $result[$value] = $element;
            }
        }

        return $result;
    }


    /**
     * 参数转化为where
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param array|hehe\libs\Forms $datas
     * @param array|\horm\QueryTable $query_where 其他where 条件
     * @param array $rules ['列名称','col'=>'db 字段名','empty'=>'判空方式,empty:php empty 判空,int:0 表示空,str:""表示空,null:php is_null','op'=>'操作符号, in,=,like']
     * @return array
     */
    public static function toQueryWhere($datas = [],$query_where = [],$rules = [])
    {

        $formatFormWhere = function ($datas = [],$rules = []) {
            if (empty($datas)) {
                return [];
            }

            $whereList = [];
            foreach ($rules as $rule) {
                $name = $rule[0];

                // 字段
                if (isset($rule['col'])) {
                    $col = $rule['col'];
                } else {
                    $col = $name;
                }

                // 判空的方式
                $empty_method = '';
                if (isset($rule['empty'])) {
                    $empty_method = $rule['empty'];
                } else {
                    $empty_method = 'empty';
                }

                $value = null;
                if (isset($datas[$name])) {
                    if ($empty_method == 'empty') {
                        if (!empty($datas[$name])) {
                            $value = $datas[$name];
                        }
                    } else if ($empty_method == 'int') {
                        if (is_numeric($datas[$name]) && $datas[$name] != 0) {
                            $value = $datas[$name];
                        }
                    } else if ($empty_method == 'str') {
                        if ($datas[$name] !== '') {
                            $value = $datas[$name];
                        }
                    } else if ($empty_method == 'null') {
                        if (!is_null($datas[$name])) {
                            $value = $datas[$name];
                        }
                    }
                }

                if (isset($rule['val']) && isset($value)) {
                    $value = sprintf($rule['val'],$value);
                }

                // 操作符
                if (isset($rule['op']) && isset($value)) {
                    if ($rule['op'] == 'like' && !isset($rule['val'])) {
                        $value = [$rule['op'], "%" . $value . "%"];
                    } else {
                        $value = [$rule['op'], $value];
                    }
                }

                if (!is_null($value)) {
                    // 如果已经存在$name 的查询条件
                    if (isset($whereList[$col])) {
                        $whereList[] = [$col=>$value];
                    } else {
                        $whereList[$col] = $value;
                    }
                }
            }

            return $whereList;
        };

        if (!empty($query_where)) {
            if ($query_where instanceof QueryTable) {
                $query_where->setWhere($formatFormWhere($datas,$rules));
            } else {
                $query_where = array_merge($formatFormWhere($datas,$rules),$query_where);
            }

            return $query_where;
        } else {
            return $formatFormWhere($datas,$rules);
        }
    }

}
