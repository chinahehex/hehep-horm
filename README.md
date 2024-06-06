# hehep-horm

## 介绍
- hehep-horm 是一个PHP数据库操作组件

## 安装
- **gitee下载**:
```
git clone git@gitee.com:chinahehex/hehep-horm.git
```

- **github下载**:
```
git clone git@github.com:chinahehex/hehep-horm.git
```
- 命令安装：
```
composer require hehex/hehep-horm
```

## 基础配置
- 组件配置
```php
$conf = [
    // 默认db key
    'dbkey'=>'hehe',
    
    // 线程安全类定义
    'saleLocal'=>'horm\base\SaleLocal',
    
    // db列表
    'dblist'=>[
        'hehe' => ['driver' => 'mysql','host' => 'localhost','database'=>'hehe','username' => 'root',
            'password' => '123123','port' => '3306','charset' => 'utf8','prefix' => 'web_',
            'reconnect'=>false,// 连接断线是否重连
            'options'=>[],// 其他参数
            'onSlave'=>true,// 启动读写分离,从库分离
            'slaveHandler'=>[],// 读取从库处理事件函数,
            'slaves'=>['hh0','hh1']// 从库列表
        ],
    
        //单数据库
        'hh0' => ['driver' => 'mysql','host' => 'localhost','database'=>'hehe','username' => 'root',
            'password' => '123123','port' => '3306','charset' => 'utf8','prefix' => 'web_'
        ],
    
        //单数据库
        'hh1' => ['driver' => 'mysql','host' => 'localhost','database'=>'hehe','username' => 'root',
            'password' => '123123','port' => '3306','charset' => 'utf8','prefix' => 'web_'
        ],
    
        //单数据库
        'hh2' => ['driver' => 'mysql','host' => 'localhost','database'=>'hh2','username' => 'root',
            'password' => '123123','port' => '3306','charset' => 'utf8','prefix' => 'web_'
        ],
    ],
];

```

## 数据库
- mysql 数据库
```php
$dblist = [
    'hehe' => ['driver'=>'mysql','host' => 'localhost','database'=>'hehe','username' => 'root',
        'password' => '123123','port' => '3306','charset' => 'utf8','prefix' => 'web_'
     ]
];
    
```

- sqlite 数据库
```php
$dblist = [
    'sqlite' => ['driver'=>'sqlite','database'=>'/home/hehe/www/db/hehe.db','prefix' => 'web_'],
];
    
```

- pgsql 数据库
```php
$dblist = [
    'pgsql' => ['driver'=>'pgsql','host' => 'localhost','database'=>'hehedb','username' => 'postgres',
        'password' => '123123','port' => '5432','charset' => 'utf8','prefix' => 'web_'
    ],
];
    
```

- oracle 数据库
```php
$dblist = [
    'oci' => ['driver'=>'oci','host' => 'localhost','database'=>'xe','username' => 'hehe',
                        'password' => '123123','port' => '1521','charset' => 'utf8','prefix' => 'web_'
    ]
];
    
```

- mongodb 数据库
```php
$dblist = [
    'mongo' => ['driver' => 'mongo','type'=>'mongo','host' => 'localhost','database'=>'hehedb','username' => '',
        'password' => '','port' => '27017','charset' => 'utf8','prefix' => 'web_'
    ],
    
    'mongo' => ['driver' => 'mongo','type'=>'mongodb','host' => 'localhost','database'=>'hehedb','username' => '',
        'password' => '','port' => '27017','charset' => 'utf8','prefix' => 'web_'
    ],
];
    
```

- tidb 数据库
```php
$dblist = [
  'tidb' => ['driver' => 'mysql','host' => '127.0.0.1','database'=>'hehedb','username' => 'hehead',
        'password' => 'ad123123','port' => '4000','charset' => 'utf8','prefix' => 'web_',
   ],
];
    
```

## 基本示例

- 实体类定义
```php
use horm\Entity;
use horm\QueryTable;

/**
 * 实体示例类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class UserEntity extends Entity
{
    /**
     * 实体属性与表字段对应关系
     * 格式['属性'=>'字段']
     */
    protected static $_attrs = [
        'id'=>'UserId',
        'name'=>'UserName'
    ];
    
    /**
     * 定义db 管理器
     */
    public static function dbSession()
    {
        return \he::$ctx->hdbsession;
    }

    /**
     * 表对象定义(支持四种对象:默认表(非分区表),分表,分库,分表分库)
     */
    public static function queryTable()
    {
        // return ShardDb::class;// 分库
        // return ShardDbTable::class;// 分表分库
        // return ShardTable::class;// 分表
        return QueryTable::class;
    }

    /**
     * 定义分表规则
     * @return ModShardRule
     */
    public static function tbShardRule()
    {
        return new ModShardRule(3,'id');
    }

    /**
     * 定义分库规则
     * @return ModShardRule
     */
    public static function dbShardRule()
    {
        return new ModShardRule(3,'id');
    }

    /**
     * 定义数据库标识
     */
    public static function dbKey()
    {
        return 'hehe';
    }

    /**
     * 定义表名
     */
    public static function tableName()
    {
        return '{{%admin_users}}';
    }

    /**
     * 是否自增定义
     */
    public static function autoIncrement()
    {
        return false;
    }

    /**
     * 定义表主键字段
     */
    public static function pk()
    {
        return 'id';
    }
    
    /**
     * 获取从库db key
     */
    public static function dbSlave()
    {
        return '';
    }
    
    /**
     * 定义scope 操作
     * 采用操作集合
     */
    public static function effectiveScope(QueryTable $queryTable)
    {
        $queryTable->setWhere(['status'=>1]);
    }
    
    
    /**
     * 定义scope 操作
     * 采用操作集合
     */
    public static function AdminScope(QueryTable $queryTable,$adminType)
    {
        if ($adminType == 'admin') {
            $queryTable->setWhere(['status'=>1]);
        } else if ($adminType == 'user') {
            $queryTable->setWhere(['status'=>1]);
        }
    }

}

```

## CURD 操作

- 新增操作
```php
use admin\entitys\AdminUserEntity;

// 添加单条数据
$data = ['username'=>'admin','password'=>'123123','tel'=>'13511113333'];
$result = AdminUserEntity::addOne($data);

// 批量添加多条数据
$datas = [
    ['username'=>'admin1','password'=>'123123','tel'=>'13511113333'],
    ['username'=>'admin2','password'=>'123123','tel'=>'13511113333']
];

$result = AdminUserEntity::addAll($datas);

// 实体方式添加数据
$userEntity = new AdminUserEntity();
$userEntity->username = 'admin9';
$userEntity->password = '123123';
$userEntity->tel = '13511113333';
$result = $userEntity->add();

// 自增id
$id = $userEntity->id;

// 获取最后的id
$lastId = AdminUserEntity::getLastId();


```

- 更新操作
```php
use admin\entitys\AdminUserEntity;

$data = [
    'password'=>'xw_'.rand(1000,9999)
];

// 更新一条数据
$result = AdminUserEntity::updateOne($data,['id'=>9]);

$data = [
    'password'=>'xw_'.rand(1000,9999)
];

// 更新多条数据
$result = AdminUserEntity::updateAll($data,['id'=>['in',['9','8']]]);
$result = AdminUserEntity::updateAll($data,['id'=>['9','8']]);

// 实体方式更新
$userEntity = new AdminUserEntity();
$userEntity->password = 'ol_'.rand(1000,9999);
$userEntity->id = 9;
$result = $userEntity->update();

```

- 删除操作
```php
use admin\entitys\AdminUserEntity;

// 删除一条数据
$result = AdminUserEntity::setWhere(['id'=>9])->deleteOne();

// 删除一条数据
$where = ['tel'=>'135xxxxxxxx'];
$result = AdminUserEntity::setWhere($where)->deleteAll();

// 删除实体
$userEntity = AdminUserEntity::get(9);
$userEntity->delete();

```

- 查询操作
```php
use admin\entitys\AdminUserEntity;

// 读取一条数据,返回对象格式
$userEntity = AdminUserEntity::get(9);
$userEntity = AdminUserEntity::setWhere(['id'=>9])->fetchOne();

// 查询多个条数据,返回对象格式
$userEntitys = AdminUserEntity::fetchAll(['id'=>[8,9]]);
$userEntitys = AdminUserEntity::setWhere(['id'=>[8,9]])->fetchAll();

// 查询多条数据,返回数组格式
$userEntitys = AdminUserEntity::setWhere(['id'=>[8,9]])->asArray()->fetchAll();

```

## 常规查询操作

- or,'and' 多条件查询
```php
$where = ['status'=>1,'tel'=>'13511113333'];
AdminUserEntity::setWhere($where)->fetchAll();

$where = ['and','status'=>1,'tel'=>'13511113333'];
AdminUserEntity::setWhere($where)->fetchAll();

$where = ['or','status'=>1,'tel'=>"13564768841"];
AdminUserEntity::setWhere($where)->fetchAll();

```

- 多层嵌套条件查询
```php
$where = ['and','name'=>['eq','admin'],['or','roleId'=>1,'ctime'=>['eq',time()]] ];
$where = ['and','tel'=>['like','135%'],['or','roleId'=>['in',[2,3]],'status'=>1,['and','username'=>'hehe1','realName'=>'hehe'] ]];
$where = ['id'=>9,['or','tel'=>'13511113333','username'=>'admin9' ]];
$where = ['and','id'=>9,['and','tel'=>'13511113333','username'=>'admin9' ]];

$userEntitys = AdminUserEntity::setWhere($where)->fetchAll();

```
- 多次调用setWhere查询
```php
$userEntitys = AdminUserEntity::setWhere(['id'=>9])->setWhere(['tel'=>'13511113333'])->fetchAll();
// sql where id=9 and tel='13511113333'
```

- 排序查询
```php
// id 降序
$userEntitys = AdminUserEntity::setWhere(['status'=>1])
->setOrder(['id'=>SORT_DESC])->fetchAll();

// id 升序
$userEntitys = AdminUserEntity::setWhere(['status'=>1])
->setOrder(['id'=>SORT_ASC])->fetchAll();

// 多字段排序
$userEntitys = AdminUserEntity::setWhere(['status'=>1])
->setOrder(['id'=>SORT_ASC,'ctime'=>SORT_DESC])->fetchAll();

```

- 三元表达式查询
```php
$userEntitys = AdminUserEntity::setWhere('id',[8,9],'in')->fetchAll();
$userEntitys = AdminUserEntity::setWhere('id',9,'=')->fetchAll();

```

- 闭包查询
```php
$userEntitys = AdminUserEntity::setWhere(function(QueryTable $queryTable){
			$queryTable->setWhere(['id'=>['>=',11]]);
})->fetchAll();

```

- 范围查询
```php
AdminUserEntity::setWhere(['roleId'=>[['>=',1],['<=',3] ]])->fetchAll();
AdminUserEntity::setWhere(['roleId'=>['and', ['>=',1],['<=',3] ]])->fetchAll();
AdminUserEntity::setWhere(['roleId'=>['or', ['>=',1],['<=',3] ]])->fetchAll();

```

- 指定读取列
```php
// 读取指定字段查询
$userEntity = AdminUserEntity::setWhere(['id'=>9])->setField("id,tel")->fetchOne();
$userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel'])->fetchAll();

// 指定列别名,如指定id列名的别名user_id
$userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField(['id'=>'user_id','tel'])->fetchOne();

// 指定列按原始输出
$userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel',['(status+1) as age']])->fetchOne();
$userEntitys = AdminUserEntity::setWhere(['id'=>1])->setField(['id','tel',['(status+1) as age','(status+1) as age1']])->fetchOne();

```

- 设置表别名
```php
AdminUserEntity::setWhere(['tb.id'=>1])->setAlias('tb')->fetchOne();

```

## 执行原始命令

```php
use \horm\Dbsession;

// 执行查询操作
$result = AdminUserEntity::queryCmd("select * from web_admin_users where id in(1,2)");

// 执行更新操作
$result = AdminUserEntity::execCmd("update web_admin_users set status=1 where id=2");

$hdbsession = new Dbsession();
$hdbsession->addDb('hehe1',[]);

$hdbsession->query('hehe1')->queryCmd("select * from web_admin_users limit 2");
$hdbsession->query('hehe1')->queryCmd("select * from web_admin_users limit 2");

```

## 预定义功能(scope)

- 定义预定义功能集合
```php
class AdminUserEntity extends \horm\Entity
{
    // 其他方法省略
    
    // 定义有效的用户条件查询
    public static function scopeEffective(\horm\QueryTable $queryTable,$status=2)
    {
        $queryTable->setWhere(['status'=>1]);
    }
    
    public static function scopeAdmin(QueryTable $queryTable)
    {
        $queryTable->setWhere(['roleId'=>['>=',0]]);
    }
}
```
- 调用预定义功能集合
```php
$users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective')->fetchAll();
$users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->setScope('effective,admin')->fetchAll();
$users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->effective()->fetchAll();
$users = AdminUserEntity::effective()->setWhere(['id'=>[1,2,3,4]])->fetchAll();

// 预定义功能方法传参
$users = AdminUserEntity::setWhere(['id'=>[1,2,3,4]])->effective(3)->fetchAll();

```

## 其他设置方法

### asQuery 方法
- asQuery 说明
```
不执行命令语句,返回query对象,一般用于子查询
```
- asQuery 示例
```php
 $users_query = AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->asQuery()->fetchAll();
 $users = AdminUserEntity::setWhere(['id'=>['in',$users_query]])->fetchAll();
```

### asId 方法
- asId 说明
```
返回自增id，用于单条插入数据
```
- asId 示例
```php
$data = ['username'=>'admin','password'=>'123123','tel'=>'13511113333'];
$id = AdminUserEntity::setData($data)->asId()->addOne();
```

### setLimit,setOffset
- 说明
```
setOffset 起始位置为0,如从第一行读取,则设置为0,如从第二行读取,则设置为1
```

- setLimit,setOffset 示例
```php
// 从起始位置0(从第1行开始)读取1条记录
AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setOffset(0)->setLimit(1)->fetchAll();

// 从起始位置1(从第2行开始)读取2条记录
AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setOffset(1)->setLimit(2)->fetchAll();
```

### setDistinct
- setDistinct 示例
```php
// 取消重复行(即返回不重复的手机号)
$users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4],'status'=>1])->setDistinct()->fetchAll();
```

### setParam 设置预定义参数
- setParam 示例
```php
$users = AdminUserEntity::setWhere('realName like :realName')->setParam(['realName'=>'%哈哈熊%'])->fetchAll();
```


## 设置方法
方法 | 说明
----------|-------------
setWhere  | 设置查询条件
setAndWhere  | 设置查询and条件
setOrWhere  | 设置查询and条件
setScope  | 设置scope作用域
asArray  | 查询以数组形式返回
asQuery  | 返回Query对象
asId  | 返回自增id
setTable  | 设置表名
setJoin  | 设置连表
setLeftJoin  | 设置Left连表
setInnerJoin  | 设置Inner连表
setWith  | 设置关系
setLeftWith  | 设置Left关系
setInnerWith  | 设置Inner关系
setOrder  | 设置查询排序
setParam  | 设置参数
setField  | 设置查询读取字段
setAlias  | 设置当前表别名
setUnion  | 设置联合查询
setLimit  | 设置影响条数
setDistinct  | 取消重复行
setGroup  | 设置分组查询
setHaving  | 设置分组查询条件
setAndHaving  | 设置分组查询and条件
setOrHaving  | 设置分组查询or条件

## 表达式表
表达式 | 说明 | 示例
----------|-------------|------------
eq  | =,等于 | $where = ['id'=>['eq',9]]
neq  | !=,不等于 | $where = ['id'=>['neq',9]]
gt  | >,大于 | $where = ['id'=>['gt',9]]
egt  | >=,大于等于 | $where = ['id'=>['egt',9]]
lt  | <,小于 | $where = ['id'=>['lt',9]]
elt  | <=,小于等于 | $where = ['id'=>['elt',9]]
like  | like 模糊查询 | $where = ['tel'=>['like','%135%']]
notlike  | not like 模糊查询 | $where = ['tel'=>['notlike','%135%']]
in  | in 查询 | $where = ['id'=>['in',[8,9]]]
notin  | not in 查询 | $where = ['id'=>['notin',[8,9]]]
exp  | 原始字符串 | $where = ['id'=>['exp','=9']] sql:`id`  =9
raw  | 原始字符串(用于连表) | ['u.UserName'=>['raw','t.UserName']](u.UserName = t.UserName), ['id'=>['raw','9']](`id` = 9);
inc  | 字段递增 | $data = ['id'=>['inc',1]] sql:`id` = `id` + 1
dec  | 字段递减 | $data = ['id'=>['dec',1]] sql:`id` = `id` - 1

## 别名设置及引用
- 别名说明
```
设置别名的地方主要有三处，主表别名,字段别名,连表别名,表别名的引用一般为"表别名.列名",
主表别名可也通过"#"代替,格式:"#.列名"
```
- 操作示例
```php
$users = AdminUserEntity::setField(['tel','realName'=>['as','name']])->fetchAll();

$users = AdminUserEntity::setField(['tel','id'=>['as',['total','count']]])->setGroup('tel')->fetchAll();

$users = AdminUserEntity::setField(['tel','id'=>['as',['total','max']]])->setGroup('tel')->fetchAll();
// 主表别名
$users = AdminUserEntity::setWhere(['adu.id'=>[1,2]])->setAlias('adu')->fetchAll();
// # 符号代替主表别名
$users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('adu')->fetchAll();

// 如未设置主表别名,系统会自动会剔除"#."
$users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->fetchAll();

$users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('user')->setField('#.*')
    ->setJoin("{{%admin_user_role}} as role",['#.roleId'=>['raw','role.id']])->fetchAll();

$users = AdminUserEntity::setWhere(['#.id'=>[1,2]])->setAlias('user')->setField('#.*')
    ->setJoin("{{%admin_user_role}} as role",['role.id'=>['raw','#.roleId']])->fetchAll();

```
- 实体定义
```php
class AdminUserEntity extends Entity
{
    // 其他代码省略
    
    // 定义与其他实体关系
    public static function getRole()
    {
        return static::hasOne(AdminUserRoleEntity::class,['id'=>'#.roleId'])
        ->setWhere(['status'=>1]);
    }
}
```

## 连表关联查询(join)
- join 说明
```
连表的表名格式:"{{%表名}}" 或 "[[表名]]",百分号%表示表前缀
连表的on 条件与where 格式一致
```

- join 示例
```php
use admin\entitys\AdminUserEntity;
// 单表关联查询
$where = ['u.id'=>7];
$join = ['{{%admin_user_role}}','r'];
$joinOn = ['u.roleId'=>['raw','r.id']];
$userEntitys = AdminUserEntity::setAlias("u")->setWhere($where)->setJoin($join,$joinOn)->fetchAll();

// 多表关联查询
$where = ['u.id'=>7];
$join1 = ['{{%admin_user_role}}','r'];
$join1On = ['u.roleId'=>['raw','r.id']];

$join2 = ['{{%admin_user_info}}','us'];
$join2On = ['u.id'=>['raw','us.mid']];

$userEntitys = AdminUserEntity::setAlias("u")->setWhere($where)->setJoin($join1,$join1On)->setJoin($join2,$join2On)->fetchAll();

// 指定关联方式
$where = ['u.id'=>7];
$join = ['{{%admin_user_role}}','r'];
$joinOn = ['u.roleId'=>['raw','r.id']];
$userEntitys = AdminUserEntity::setAlias("u")->setWhere($where)->setJoin($join,$joinOn,'LEFT JOIN')->fetchAll();

```


## with关联查询
- with 说明
```
1.定义主表与关联表的关系
2.用于快捷的连表操作,省去连表操作的关系定义
3.快速读取关联表数据
4.hasOne('关联模型','关联实体外键','当前实体主键');
5.hasMany('关联模型','关联实体外键','当前实体主键');
```

- with 方法定义
```php
class AdminUserEntity extends Entity
{
    public static function getRole()
    {
        // AdminUserEntity 与 AdminUserRoleEntity 1对1关系,['AdminUserRoleEntity字段'=>'AdminUserEntity字段']
        return static::hasOne(AdminUserRoleEntity::class,['id'=>'roleId']);
    }
}
```
- with 示例
```php
$adminUserEntity = AdminUserEntity::setWhere(['username'=>"admin"])->setWith('role')->fetchAll();
$adminUserEntity = AdminUserEntity::setWhere(['username'=>"admin"])->setWith('role as rol')->fetchAll();

// 连表join 查询
$users = AdminUserEntity::setWhere(['adu.id'=>[1,2,3,4]])->setAlias('adu')->setWith('role',true)->fetchAll();
$users = AdminUserEntity::setWhere(['adu.id'=>[1,2,3,4]])->setAlias('adu')->setWith('role','left join')->fetchAll();

// 闭包
 $users = AdminUserEntity::setWhere(['adu.id'=>[1,2,3,4]])->setAlias('adu')->setInnerWith(['role'=>function(QueryTable $query){
            /** @var QueryTable $query **/
            $query->setWhere(['status'=>1]);
}],false)->fetchAll();

// 取消关联表数据的加载
$adminUserEntity = AdminUserEntity::setWhere(['username'=>"admin"])->setWith('role','left join',false)->fetchAll();

```

## 分组查询(group)
- 分组说明
```
分组条件(having) 与where 规则一致
```

- 分组示例
```php
$users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel')->fetchAll();
$users = AdminUserEntity::setField('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->fetchAll();
$users = AdminUserEntity::setField('tel,status')->setWhere(['id'=>[1,2,3,4]])->setGroup(['tel','status'])->fetchAll();
```
- 分组条件
```php
$users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status')->setHaving(['status'=>0])->fetchAll();

$users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
            ->setAndHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();
            
$users = AdminUserEntity::setField('tel')->setWhere(['id'=>[1,2,3,4]])->setGroup('tel,status,roleId')
        ->setOrHaving(['status'=>0,'roleId'=>['>',0]])->fetchAll();

```

## 子查询(subQuery)
```php
$users_query = AdminUserEntity::setField('id')->setWhere(['id'=>[1,2,3,4],])->asQuery()->fetchAll();
$users = AdminUserEntity::setWhere(['id'=>['in',$users_query]])->setWhere(['status'=>1])->fetchAll();
```

## 聚合查询(scalar)
```php
// 统计行数
$count = AdminUserEntity::count();
$count = AdminUserEntity::asArray()->count('id');

// 查询最大值
$max = AdminUserEntity::asArray()->queryMax('id');
$max = AdminUserEntity::queryMax('id');

// 查询最小值
$min = AdminUserEntity::queryMin('id');

// 累加指定字段
$sum = AdminUserEntity::querySum('id');

```

## 联合查询(Union)
```php

//返回3条数据
$query = AdminUserEntity::asQuery()->fetchAll(['id'=>[1,2]]);
$adminUserEntitys = AdminUserEntity::asArray()->setUnion($query)->fetchAll(['id'=>3]);

// 两个查询集合后,可以设置排序规则,以及读取的条数
$query = AdminUserEntity::asQuery()->fetchAll(['id'=>[1,2]]);
$adminUserEntitys = AdminUserEntity::asArray()->setUnion($query)->setLimit(2)->setOrder(['id'=>'desc'])->fetchAll(['id'=>3]);
```

## 事务操作(transaction)
- 数据实体操作事务
```php
// 开启事务
AdminUserEntity::beginTransaction();
$data = ['username'=>"hehe3",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
AdminUserEntity::addOne($data);

// 提交事务
AdminUserEntity::commitTransaction();

// 回滚事务
AdminUserEntity::rollbackTransaction();
```
- db管理器操作事务
```php

use horm\Dbsession;
$hdbsession = new Dbsession();
// 开启事务
$hdbsession->beginTransaction();
$data = ['username'=>"hehe5",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'];
AdminUserEntity::addOne($data);

// 提交事务
$hdbsession->commitTransaction();

// 回滚事务
$hdbsession->rollbackTransaction();
```

## 分表分库

### 分表
- 分表说明
```
目前只支持单个字段分表
```
- 定义实体
```php
use horm\shard\rule\ModShardRule;
use horm\shard\ShardTable;
class UserEntity extends Entity
{
    /**
     * ShardTable 分表
     * @return string
     */
    public static function queryTable()
    {
        return ShardTable::class;
    }

    /**
     * 分表规则定义
     * @return ModShardRule 取模
     */
    public static function tbShardRule()
    {
        return new ModShardRule(3,'userId');
    }
    
    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        // :shard 分区号
        // return '{{%users_:shard}}'
        return '{{%users_info_}}';
    }

    // 其他方法的定义省略
}

```
- 示例代码
```php

// 添加数据
AdmminUserinfoEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
$datas = [
    ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
    ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
];

$result = AdmminUserinfoEntity::addAll($datas);

// 更新
AdmminUserinfoEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>1,'userId'=>1]);
AdmminUserinfoEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>[1,2,3]]);
AdmminUserinfoEntity::updateAll(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>['in',[1,2,3]] ]);


// 删除
AdmminUserinfoEntity::setWhere(['id'=>1,'userId'=>1])->deleteOne();
AdmminUserinfoEntity::setWhere(['id'=>1,'userId'=>1])->deleteAll();


// 查询
AdmminUserinfoEntity::setWhere(['userId'=>[1,2]])->fetchAll();
AdmminUserinfoEntity::setWhere(['userId'=>1])->fetchOne();

```
- 指定(setShard)分区列示例
```php
// 新增
UserEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

// 更新
UserEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);

// 查询
UserEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();

// 删除
UserEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);

```



### 分库
- 分表说明
```
目前只支持单个字段分表
```

- 定义实体
```php
class UserEntity extends Entity
{
    /**
     * ShardTable 分库
     * @return string
     */
    public static function queryTable()
    {
        return ShardDb::class;
    }

    /**
     * 分库规则定义
     * @return ModShardRule
     */
    public static function tbShardRule()
    {
        return new ModShardRule(3,'userId');
    }


    /**
     * 定义数据库标识
     * @return string
     */
    public static function dbKey()
    {
        // return 'hehe_:shard';
        return 'hehe_';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * 定义表主键字段
     * @return string
     */
    public static function pk()
    {
        return 'userId';
    }

}

```

- 示例代码
```php
// 添加数据
UserEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
$datas = [
    ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
    ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
];

UserEntity::addAll($datas);

// 更新
UserEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>1,'userId'=>1]);
UserEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>[1,2,3]]);
UserEntity::updateAll(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>['in',[1,2,3]] ]);

// 删除
UserEntity::setWhere(['id'=>1,'userId'=>1])->deleteOne();
UserEntity::setWhere(['id'=>1,'userId'=>1])->deleteAll();


// 查询
UserEntity::setWhere(['userId'=>[1,2]])->fetchAll();
UserEntity::setWhere(['userId'=>1])->fetchOne();
```

- 指定(setShard)分区列示例
```php
// 新增
UserEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

// 更新
UserEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);

// 查询
UserEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();

// 删除
UserEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);

```

### 分库分表
- 分表说明
```
目前只支持单个字段分表
```

- 定义实体
```php
class UserEntity extends Entity
{

    public static function queryTable()
    {
        return ShardDbTable::class;
    }

    // 分表规则定义
    public static function tbShardRule()
    {
        return new ModShardRule(3,'userId');
    }
    
    // 定义分库规则 
    public static function dbShardRule()
    {
        return new ModShardRule(2,'userId');
    }

    // 定义数据库标识
    public static function dbKey()
    {
        return 'hehe_';
    }

    /**
     * 定义表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%users_info_}}';
    }
}

```

- 示例代码
```php
// 添加数据
UserEntity::addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);
$datas = [
    ['userId'=>2,'tel'=>'135xxxxxxxb','realName'=>'hehex','sex'=>'男','education'=>'高中'],
    ['userId'=>3,'tel'=>'135xxxxxxxc','realName'=>'hehex','sex'=>'男','education'=>'高中'],
];

UserEntity::addAll($datas);

// 更新
UserEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['id'=>1,'userId'=>1]);
UserEntity::updateOne(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>[1,2,3]]);
UserEntity::updateAll(['tel'=>'135xxxx' .  rand(10000,99999)],['userId'=>['in',[1,2,3]] ]);

// 删除
UserEntity::setWhere(['id'=>1,'userId'=>1])->deleteOne();
UserEntity::setWhere(['id'=>1,'userId'=>1])->deleteAll();


// 查询
UserEntity::setWhere(['userId'=>[1,2]])->fetchAll();
UserEntity::setWhere(['userId'=>1])->fetchOne();
```

- 指定(setShard)分区列示例
```php
// 新增
UserEntity::setShard(['userId'=>3])->addOne(['userId'=>1,'tel'=>'135xxxxxxxx','realName'=>'hehex','sex'=>'男','education'=>'高中']);

// 更新
UserEntity::setShard(['userId'=>3])->updateOne(['tel'=>'135xxxxxxxok'],['userId'=>1]);

// 查询
UserEntity::setShard(['userId'=>3])->setWhere(['userId'=>1])->fetchOne();

// 删除
UserEntity::setShard(['userId'=>3])->deleteOne(['userId'=>1]);

```
## DB连接操作模式
```php
$hdbsession = new \horm\Dbsession([]);
$db_conn = $hdbsession->getDbConnection('hehe1');
$number = $db_conn->insert('web_admin_users',['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex']);

// 批量插入
$datas = [
    ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
    ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
];
$number = $db_conn->insertAll('web_admin_users',$datas);

// 更新
$number =$db_conn->update('web_admin_users',['tel'=>'135xxxx' .  rand(10000,99999)],['username'=>'admin']);

// 删除操作
$number =$db_conn->delete('web_admin_users',['username'=>'hello']);

// 查询一条记录
$user =$db_conn->fetchOne('web_admin_users',['id'=>1]);

// 查询多条记录
$users = $db_conn->fetchAll('web_admin_users',['id'=>[1,2]]);
$users = $db_conn->fetchAll('web_admin_users',['id'=>[1,2]],['order'=>['id'=>SORT_DESC]]);

// 执行查询sql
$users = $db_conn->querySql('select * from web_admin_users where id in (1,2)');

// 执行更新sql
$number = $db_conn->execSql('update web_admin_users set tel="135xxxxbbbb" where id = 2');
$number = $db_conn->execSql('update web_admin_users set tel=:tel where id = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
```
## 数据表(queryTable)操作模式
```php
// 设置默认db key 
$this->hdbsession->setDb('hehe1');

$number = $this->hdbsession->setTable('web_admin_users')
    ->setData(['username'=>"okb",'password'=>'123123','tel'=>'135xxxxxxxx','realName'=>'hehex'])->addOne();

// 批量插入
$datas = [
    ['username'=>'admin1','password'=>'123123','tel'=>'1351111' . rand(10000,99999)],
    ['username'=>'admin2','password'=>'123123','tel'=>'1351111' . rand(10000,99999)]
];

$number = $this->hdbsession->setTable('web_admin_users')
    ->setData($datas)->addAll();

// 更新
$number = $this->hdbsession->setTable('web_admin_users')
    ->setData(['tel'=>'135xxxx' .  rand(10000,99999)])
    ->setWhere(['username'=>'admin'])
    ->updateOne();

$number = $this->hdbsession->setTable('web_admin_users')
    ->setData(['tel'=>'135xxxb' .  rand(10000,99999)])
    ->setWhere(['username'=>'admin'])
    ->updateAll();

$number = $this->hdbsession->setTable('web_admin_users')
    ->setWhere(['username'=>'hello'])
    ->deleteOne();

// 查询一条记录
$user = $this->hdbsession->setTable('web_admin_users')
    ->setWhere(['id'=>1])->fetchOne();

// 查询多条记录
$users = $this->hdbsession->setTable('web_admin_users')
    ->setWhere(['id'=>[1,2]])
    ->fetchAll();

// 查询多条记录
$users = $this->hdbsession->setTable('web_admin_users')
    ->setWhere(['id'=>[1,2]])
    ->setOrder(['id'=>SORT_DESC])
    ->fetchAll();

// 执行查询sql
$users = $this->hdbsession->querySql('select * from web_admin_users where id in (1,2)');

// 执行更新sql
$number = $this->hdbsession->execSql("update web_admin_users set tel='135xxxxbbbb' where id = 2");
$number = $this->hdbsession->execSql('update web_admin_users set tel=:tel where id = 2',['tel'=>'135xxxx' .  rand(10000,99999)]);
```


## 主从数据库
- 说明
```
以下几种情况系统会调用选择主库操作
1.执行更新操作,比如:新增(add),更新(update),删除(delete),执行命令(execCmd);
2.主动调用asMaster()方法
```

### 全库读写分离

- 示例代码
```php
use horm\Dbsession;
$dbconf = [
    'hehe' => [
        'onSlave'=>true,// 开启主从库
        'slaves'=>['hh0','hh1'],//从库列表
        'slaveHandler'=>[],// 从库读取规则函数,比如随机读取从库
    ],
];
$hdbsession = new Dbsession($dbconf);
$data = ['username'=>'admin' .rand(1000,9999),'password'=>'123123','tel'=>'13511113333'];
// 写入数据至主库
$result = UserEntity::addOne($data);

// 从从库读取数据
UserEntity::setWhere(['id'=>1])->fetchOne();

// 主动选择主库读取数据
UserEntity::setWhere(['id'=>1])->asMaster()->fetchOne();

// 从库查询(如是在同一个事务之间,同一进程或协程,则还是从主库查询)
$userEntitys = UserEntity::setOrder(['id'=>SORT_DESC])->setLimit(2)->asArray()->fetchAll();

```

### 单表读写分离
- 示例代码
```php
class UserEntity extends Entity
{
    // 定义主库
    public static function dbKey()
    {
        return 'hehe';
    }
    
    // 获取从库
    public static function dbSlave()
    {   
        // 随机从从库列表读取一个从库
        $slaves = ['hehe1','hehe2'];
        return $slaves[mt_rand(0,count($slaves) -1)];;
    }
}

$data = ['username'=>'admin' .rand(1000,9999),'password'=>'123123','tel'=>'13511113333'];
        
// 写入数据至主库
$result = UserEntity::addOne($data);

// 从从库读取数据
UserEntity::setWhere(['id'=>1])->fetchOne();

// 主动选择主库读取数据
UserEntity::setWhere(['id'=>1])->asMaster()->fetchOne();

// 从库查询(如是在同一个事务之间,同一进程或协程,则还是从主库查询)
$userEntitys = UserEntity::setOrder(['id'=>SORT_DESC])->setLimit(2)->asArray()->fetchAll();

```

## 连接池
```php

// 单进程模式连接池配置
$dbconf = [
    'hehe' => [
        // 连接池设置,同一个进程下的连接池毫无意义,常用于协程
        'pool'=>[
            'class'=>'process',// 连接池模式对象,默认为ProcessConnectionPool,可通过重写此类扩展功能
        ]
    ],
];

//多线程或多协程模式连接池配置
$dbconf = [
    'hehe' => [
        // 连接池设置,同一个进程下的连接池毫无意义,常用于协程
        'pool'=>[
            'class'=>'thread',// 连接池模式对象,默认为 ThreadConnectionPool,可通过重写此类扩展功能
            'maxSize'=>1,// 最大连接数据,
            'maxcachedSize'=>1,// 缓存池最大数量,
            'preload'=>0,// 预加载连接数量
        ]
    ],
];

$dbsession = new Dbsession($dbconf);

```

## 线程安全
- 重新定义线程安全类

```php
namespace common\lib;

class SwooleSaleLocal
{
    protected $_attributes = [];
    
    // 设置类对象属性
    public function setAttribute(string $name,$value)
    {
        $ident = $this->getIdent();

        if (is_null($value)) {
            unset($this->_attributes[$name][$ident]);
        } else {
            $this->_attributes[$name][$ident] = $value;
        }
    }
    
    // 获取类对象属性
    public function getAttribute(string $name)
    {

        if (!isset($this->_attributes[$name])) {
            return null;
        }

        $ident = $this->getIdent();

        if (!isset($this->_attributes[$name][$ident])) {
            return null;
        }

        return $this->_attributes[$name][$ident];
    }
    
    // 获取协程id
    protected function getIdent()
    {
        return posix_getpid();
    }

}

// 组件配置
$conf = [
    'saleLocal'=>'common\lib\SwooleSaleLocal',
    'dbconf'=>['略...'],
];

```





