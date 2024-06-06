<?php
namespace horm\drivers\mongo;

use horm\base\Query;
use horm\builders\NosqlQueryBuilder;


/**
 * Mongodb 封装类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class MongoQueryBuilder extends NosqlQueryBuilder
{
    /**
     * 字段和表名处理
     * @access protected
     * @param string $column_name
     * @return string
     */
    public function parseColumnName(Query $query,string $column_name  = '')
    {
        if (substr($column_name,0,2) == '#.') {
            return str_replace('#.','',$column_name);
        } else {
            $table_alias = $query->getAlias();
            if (strpos($column_name,'.') !== false) {
                list($tb_alias,$col_name) = explode('.',$column_name);
                // 判断是否与主表一致
                if ($table_alias == $tb_alias) {
                    return $this->formatColumnName($query,$col_name);
                } else {
                    return $this->formatColumnName($query,$tb_alias).'.' . $this->formatColumnName($query,$col_name);
                }
            } else {
                return $this->formatColumnName($query,$column_name);
            }
        }
    }
}
