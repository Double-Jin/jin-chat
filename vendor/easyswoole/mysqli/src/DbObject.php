<?php
/**
 *
 * Created by PHPStorm.
 * User: hanwenbo
 * Date: 2019-01-27
 * Time: 20:09
 *
 */

namespace EasySwoole\Mysqli;

use EasySwoole\Spl\SplString;

/**
 * Mysqli Model wrapper
 * @method DbObject selectForUpdate(bool $bool):Mysqli
 * @method DbObject lockInShare(bool $bool):Mysqli
 * @method DbObject connect(): bool
 * @method DbObject disconnect(): void
 * @method DbObject getMysqlClient(): \Swoole\Coroutine\MySQL
 * @method DbObject resetDbStatus(): void
 * @method DbObject startTrace(): void
 * @method DbObject endTrace(): array
 * @method DbObject startTransaction(): bool
 * @method DbObject commit(): bool
 * @method DbObject rollback($commit = true)
 * @method DbObject where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND'): Mysqli
 * @method DbObject whereOr($whereProp, $whereValue = 'DBNULL', $operator = '='): Mysqli
 * @method DbObject whereNull($whereProp, $cond = 'AND'): Mysqli
 * @method DbObject whereNotNull($whereProp, $cond = 'AND'): Mysqli
 * @method DbObject whereEmpty($whereProp, $cond = 'AND'): Mysqli
 * @method DbObject whereNotEmpty($whereProp, $cond = 'AND'): Mysqli
 * @method DbObject whereIn($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject whereNotIn($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject whereBetween($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject whereNotBetween($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject whereLike($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject whereNotLike($whereProp, $whereValue, $cond = 'AND'): Mysqli
 * @method DbObject onDuplicate($updateColumns, $lastInsertId = null)
 * @method DbObject tableExists($tables)
 * @method mixed|static fetchSql(bool $fetch = true)
 * @method DbObject getAffectRows(): int
 * @method DbObject getLastStatement(): ?Statement
 * @method DbObject getSubQuery()
 * @method DbObject subQuery($subQueryAlias = ""): Mysqli
 * @method DbObject orderBy($orderByField, $orderByDirection = "DESC", $customFieldsOrRegExp = null)
 * @method DbObject having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND')
 * @method DbObject groupBy($groupByField)
 **/
class DbObject
{
	/**
	 * 先创建Mysqli的实例
	 *
	 * @var Mysqli
	 */
	private $db;

	/**
	 * 模型的路径
	 *
	 * @var string
	 */
	protected $modelPath;
	/**
	 * 保存对象数据的数组
	 *
	 * @var array
	 */
	public $data;
	/**
	 * 要定义is对象的标志是新的或从数据库加载的
	 *
	 * @var boolean
	 */
	public $isNew = true;
	/**
	 * 一个持有的数组有*个对象，这些对象应该与main一起加载
	 * 对象与主对象在一起
	 *
	 * @var array
	 */
	private $_with = [];
	/**
	 * 分页的每个页面限制
	 *
	 * @var int
	 */
	public static $pageLimit = 20;
	/**
	 * 变量，该变量保存上一次paginate()查询的总页数
	 *
	 * @var int
	 */
	public static $totalPages = 0;
	/**
	 * 变量，该变量在分页查询期间保存返回的行数
	 * @var string
	 */
	public static $totalCount = 0;
	/**
	 * 保存插入/更新/选择错误的数组
	 *
	 * @var array
	 */
	public $errors = null;
	/**
	 * 对象的主键。'id'是默认值。
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	/**
	 * 对象的表名。默认情况下将使用类名
	 *
	 * @var string
	 */
	protected $dbTable;
	/**
	 * 要返回的类型
	 * @var string
	 */
	protected $returnType = 'Object';
	/**
	 * 在验证、准备和保存期间将跳过的字段的名称
	 * @var array
	 */
	protected $toSkip = [];

	/**
	 * 需要自动转为json的字段
	 * 如：Array('options');
	 * @var array
	 */
	protected $jsonFields = [];

	/**
	 * 需要自动转成时间戳的字段
	 * 如：Array ('createdAt', 'updatedAt');
	 * @var array
	 */
	protected $timestamps = [];
	/**
	 * 需要自动转成数组的字段
	 * 如：Array('sections');
	 * @var array
	 */
	protected $arrayFields = [];
	/**
	 *  要验证的字段，如果约束了字段，不在该约束下的字段将会过滤掉，示例：
	 *  protected $dbFields = Array(
	 *      'login' => Array('text', 'required'),
	 *      'password' => Array('text'),
	 *      'createdAt' => Array('datetime'),
	 *      'updatedAt' => Array('datetime'),
	 *      'custom' => Array('/^test/'),
	 * );
	 * @var array
	 */
	protected $dbFields = [];

	/**
	 * 关联模型，如：
	 * hasOne
	 * Array(
	 *      'person' => Array("hasOne", "person", 'id');
	 * );
	 * hasMany
	 * Array(
	 *      'products' => Array("hasMany", "product", 'userid')
	 * );
	 * @var array
	 */
	protected $relations = [];

	/**
	 * 需要隐藏的字段，如：
	 * array(
	 *  'password', 'token'
	 * );
	 * @var array
	 */
	protected $hiddenFields = [];

	/**
	 * DbObject constructor.
	 * @param null $data
	 */
	public function __construct( $data = null )
	{
		if( empty ( $this->dbTable ) ){
			$this->dbTable = end( explode( "\\", strtolower( get_class( $this ) ) ) );
		}

		if( $data ){
			$this->data = $data;
		}
	}

	/**
	 * Mysqli | null
	 * null用于回收并清理db
	 * @param Mysqli $db
	 * @return DbObject | null
	 */
	public function setDb( $db )
	{
		if( $db instanceof Mysqli ){
			$this->db = $db;
			return $this;
		} else{
			return null;
		}
	}

	public function setDbTable( string $name )
	{
		$this->dbTable = $name;
		return $this;
	}

	/**
	 * 帮助函数来创建一个虚拟表类
	 *
	 * @param string tableName Table name
	 * @return DbObject
	 */
	public static function table( $tableName ) : DbObject
	{
		$tableName = preg_replace( "/[^-a-z0-9_]+/i", '', $tableName );
		if( !class_exists( $tableName ) ){
			return new class extends DbObject
			{

			};
		}
		return new $tableName();
	}

	public function setData( $data = null )
	{
		$this->data = $data;
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getPrimaryKey() : string
	{
		return $this->primaryKey;
	}

	/**
	 * @return bool|int
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function insert( $data = null )
	{
		if( !empty( $data ) ){
			$this->setData( $data );
		}
		$sqlData = $this->prepareData();
		if( !$this->validate( $sqlData ) ){
			return false;
		}

		$id = $this->db->insert( $this->dbTable, $sqlData );
		if( !empty ( $this->primaryKey ) && empty ( $this->data[$this->primaryKey] ) ){
			$this->data[$this->primaryKey] = $id;
		}
		$this->isNew  = false;
		$this->toSkip = [];
		return $id;
	}

	/**
	 * 可选的更新数据应用于对象
	 * @param null $data
	 * @return bool|mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function update( $data = null )
	{
		if( empty ( $this->dbFields ) ){
			return false;
		}

		if( empty ( $this->data[$this->primaryKey] ) ){
			return false;
		}

		if( $data ){
			foreach( $data as $k => $v ){
				if( in_array( $k, $this->toSkip ) ){
					continue;
				}
				$this->$k = $v;
			}
		}

		$sqlData = $this->prepareData();
		if( !$this->validate( $sqlData ) ){
			return false;
		}

		$this->db->where( $this->primaryKey, $this->data[$this->primaryKey] );
		$res          = $this->db->update( $this->dbTable, $sqlData );
		$this->toSkip = [];
		return $res;
	}

	/**
	 * 保存或更新对象
	 * @param null $data
	 * @return bool|int|mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	public function save( $data = null )
	{
		if( $this->isNew ){
			return $this->insert();
		} else{
			return $this->update( $data );
		}
	}

	/**
	 * 删除的方法。只在定义了对象primaryKey时才有效
	 * @return bool|null 表示成功。0或1。
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	public function delete()
	{
		if( empty ( $this->data[$this->primaryKey] ) ){
			return false;
		}

		$this->db->where( $this->primaryKey, $this->data[$this->primaryKey] );
		$res          = $this->db->delete( $this->dbTable );
		$this->toSkip = [];
		return $res;
	}

	/**
	 * 链接的方法，该方法将一个或多个字段附加到跳过
	 * @param mixed|array|false $field 字段名;数组的名字;空跳如果为假
	 * @return $this
	 */
	public function skip( $field )
	{
		if( is_array( $field ) ){
			foreach( $field as $f ){
				$this->toSkip[] = $f;
			}
		} else if( $field === false ){
			$this->toSkip = [];
		} else{
			$this->toSkip[] = $field;
		}
		return $this;
	}

	/**
	 * 通过主键获取对象
	 * @param string $id     主键
	 * @param null   $fields 要获取的字段的数组或昏迷分隔列表
	 * @return DbObject|\EasySwoole\Mysqli\Mysqli|mixed|null|static
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	private function byId( string $id, $fields = null )
	{
		$this->db->where( $this->dbTable.'.'.$this->primaryKey, $id );
		return $this->getOne( $fields );
	}


	/**
	 * 获取一个对象。大部分会和where()在一起
	 * @param null $fields
	 * @return DbObject|\EasySwoole\Mysqli\Mysqli|mixed|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function getOne( $fields = null )
	{
		$this->processHasOneWith();
		$results = $this->db->getOne( $this->dbTable, $fields );
		if( $results === null ){
			return null;
		}

		$this->processArrays( $results );
		$this->data = $results;
		$this->processAllWith( $results );
		// 方便用于查询过某条再添加或保存
		if( $this->returnType === 'Object' ){
			$item        = new static ( $results );
			$item->isNew = false;
			return $item;
		} else{
			return $results;
		}
	}

	/**
	 * 获取所有对象
	 * @param null $limit  数组以格式数组($count， $offset)定义SQL限制，或者是条数
	 * @param null $fields 要获取的字段的数组或昏迷分隔列表
	 * @return array|false|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\Option
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */

	protected function get( $limit = null, $fields = null )
	{
		$this->processHasOneWith();
		$results = $this->db->get( $this->dbTable, $limit, $fields );
		if( is_array( $results ) ){
			if( count( $results ) === 0 ){
				return null;
			}
			foreach( $results as $k => &$r ){
				$this->processArrays( $r );
				$this->data = $r;
				$this->processAllWith( $r, false );
			}
		}

		$this->_with = [];

		return $results;
	}


	/**
	 * 设置一个或多个与主对象一起加载的对象
	 * @param $objectName
	 * @return $this
	 * @throws \Exception
	 */
	private function with( string $objectName )
	{
		if( !property_exists( $this, 'relations' ) || !isset ( $this->relations[$objectName] ) ){
			throw new \Exception ( "No relation with name $objectName found" );
		}
		$this->_with[$objectName] = $this->relations[$objectName];
		return $this;
	}

	/**
	 * 将对象连接到另一个对象
	 * @param string $objectName 对象名称
	 * @param string $joinStr    关联天条件字符串
	 * @param string $joinType   SQL join type: LEFT, RIGHT,  INNER, OUTER
	 * @return $this
	 * @todo alias
	 * @throws Exceptions\JoinFail
	 */
	protected function join( string $objectName, string $joinStr, string $joinType = 'LEFT' )
	{
		// 兼容小写as
		$objectName = str_replace(' as ',' AS ',$objectName);
		// 别名默认为$objectName
		$alias = strtolower($objectName);
		if( strstr(  $objectName,' AS ' ) ){
			$explode    = explode( ' AS ', $objectName );
			$objectName = $explode[0];
			$alias      = $explode[1];
		}
		// 如果不是命名空间索引，走默认的model命名空间的路径，转符号为骆峰式，如:goods_category 就是 GoodsCategory
		$splString  = new SplString( $objectName );
		$class_name = $this->modelPath."\\".$splString->studly()->__toString();
		/**
		 * @var $joinObj static
		 */
		$joinObj = new $class_name;
		// join时自动加别名
		$joinObj->setDbTable( $joinObj->dbTable." AS `{$alias}`" );
		$this->db->join( $joinObj->dbTable, $joinStr, $joinType );
		return $this;
	}

	public function getDb() : Mysqli
	{
		return $this->db;
	}

	/**
	 * @param string $column
	 * @return array|int|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function count( string $column = '*' )
	{
		$res = $this->db->getValue( $this->dbTable, "count($column)" );
		return $res ?? 0;
	}

	/**
	 * @param string $name
	 * @return array
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function column( string $name )
	{
		return $this->db->getColumn( $this->dbTable, $name );
	}

	/**
	 * @param string $name
	 * @return array|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function value( string $name )
	{
		return $this->db->getValue( $this->dbTable, $name );
	}

	/**
	 * @param $filedName
	 * @return array|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function sum( $filedName )
	{
		return $this->db->sum( $this->dbTable, $filedName );
	}

	/**
	 * @param $filedName
	 * @return mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function max( $filedName )
	{
		return $this->db->max( $this->dbTable, $filedName );
	}

	/**
	 * @param $filedName
	 * @return mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function min( $filedName )
	{
		return $this->db->min( $this->dbTable, $filedName );
	}

	/**
	 * @param $filedName
	 * @return mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function avg( $filedName )
	{
		return $this->db->avg( $this->dbTable, $filedName );
	}

	/**
	 * @param $num
	 * @return array
	 */
	protected function inc( $num )
	{
		return $this->db->inc( $num );
	}

	/**
	 * @param $num
	 * @return array
	 */
	protected function dec( $num )
	{
		return $this->db->dec( $num );
	}

	/**
	 * @param     $filedName
	 * @param int $num
	 * @return mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function setInc( $filedName, $num = 1 )
	{
		return $this->db->update( $this->dbTable, [$filedName => $this->inc( $num )] );
	}

	/**
	 * @param     $filedName
	 * @param int $num
	 * @return mixed
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function setDec( $filedName, $num = 1 )
	{
		return $this->db->update( $this->dbTable, [$filedName => $this->dec( $num )] );
	}

	/**
	 * @param array $insertData
	 * @return bool|int|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	protected function replace( array $insertData )
	{
		return $this->db->replace( $this->dbTable, $insertData );
	}

	/**
	 * @param $groupByField
	 * @return $this
	 */
	protected function group( $groupByField )
	{
		if( $groupByField ){
			$this->db->groupBy( $groupByField );
		}
		return $this;
	}

	/**
	 * 获取最后插入的数据ID
	 * @return int
	 */
	protected function getInsertId()
	{
		return $this->db->getInsertId();
	}

	/**
	 * 获取最后一次查询的语句
	 * @return mixed
	 */
	protected function getLastQuery()
	{
		return $this->db->getLastQuery();
	}

	/**
	 * 获取最后一次查询错误的内容
	 * @return string
	 */
	protected function getLastError()
	{
		return $this->db->getLastError();
	}

	/**
	 * 获取最后一次查询错误的编号
	 * @return mixed
	 */
	protected function getLastErrno()
	{
		return $this->db->getLastErrno();
	}

	/**
	 * 捕获对未定义方法的调用。
	 * 提供对类的私有函数和本机公共mysqlidb函数的神奇访问
	 *
	 * @param string $method
	 * @param mixed  $arg
	 *
	 * @return mixed
	 */
	public function __call( $method, $arg )
	{
		if( method_exists( $this, $method ) ){
			return call_user_func_array( [$this, $method], $arg );
		}

		call_user_func_array( [$this->db, $method], $arg );
		return $this;
	}

	/**
	 * 捕获对未定义静态方法的调用
	 * 透明地创建DbObject类来提供平滑的API，如name::get() name::orderBy()->get()
	 * @param $method
	 * @param $arg
	 * @return DbObject|mixed
	 */
	public static function __callStatic( $method, $arg )
	{
		$obj    = new static;
		$result = call_user_func_array( [$obj, $method], $arg );
		if( method_exists( $obj, $method ) ){
			return $result;
		}
		return $obj;
	}

	/**
	 * 将对象数据转换为关联数组
	 *
	 * @return array Converted data
	 */
	public function toArray() : array
	{
		$data = $this->data;
		$this->processAllWith( $data );
		foreach( $data as &$d ){
			if( $d instanceof DbObject ){
				$d = $d->data;
			}
		}
		return $data;
	}

	/**
	 * @return string
	 */
	public function toJson() : string
	{
		return json_encode( $this->toArray() );
	}

	/**
	 * 将对象数据转换为JSON字符串。
	 *
	 * @return string Converted data
	 */
	public function __toString() : string
	{
		return $this->toJson();
	}

	/**
	 * 如果需要，函数查询有很多关系，还可以转换hasOne对象名
	 *
	 * @param array $data
	 */
	private function processAllWith( array &$data, $shouldReset = true ) : void
	{
		if( count( $this->_with ) == 0 ){
			return;
		}

		foreach( $this->_with as $name => $opts ){
			$relationType = strtolower( $opts[0] );
			$modelName    = $opts[1];
			if( $relationType == 'hasone' ){
				$obj        = new $modelName;
				$table      = $obj->dbTable;
				$primaryKey = $obj->primaryKey;

				if( !isset ( $data[$table] ) ){
					$data[$name] = $this->$name;
					continue;
				}
				if( $data[$table][$primaryKey] === null ){
					$data[$name] = null;
				} else{

					$data[$name] = $data[$table];

				}
				unset ( $data[$table] );
			} else
				$data[$name] = $this->$name;
		}
		if( $shouldReset ){
			$this->_with = [];
		}
	}

	/**
	 * 函数构建对于get/getOne方法有一个连接
	 * @throws Exceptions\Option
	 */
	private function processHasOneWith() : void
	{
		if( count( $this->_with ) == 0 ){
			return;
		}
		foreach( $this->_with as $name => $opts ){
			$relationType = strtolower( $opts[0] );
			$modelName    = $opts[1];
			$key          = null;
			if( isset ( $opts[2] ) ){
				$key = $opts[2];
			}
			if( $relationType == 'hasone' ){
				$this->db->setQueryOption( "MYSQLI_NESTJOIN" );
				$this->join( $modelName, $key );
			}
		}
	}

	/**
	 * 用于过滤json array
	 * @param array $data
	 */
	private function processArrays( array &$data ) : void
	{
		if( is_array( $this->jsonFields ) && !empty( $this->jsonFields ) ){
			foreach( $this->jsonFields as $key ){
				if( isset( $data[$key] ) ){
					$data[$key] = json_decode( $data[$key], true );
				}
			}
		}

		if( is_array( $this->arrayFields ) && !empty( $this->arrayFields ) ){
			foreach( $this->arrayFields as $key ){
				if( isset( $data[$key] ) ){
					$data[$key] = explode( "|", $data[$key] );
				}
			}
		}

		if( is_array( $this->hiddenFields ) && !empty( $this->hiddenFields ) ){
			foreach( $this->hiddenFields as $key ){
				if( isset( $data[$key] ) ){
					unset( $data[$key] );
				}
			}
		}
	}

	/**
	 * @return $this
	 * @throws Exceptions\Option
	 */
	public function withTotalCount()
	{
		$this->db->withTotalCount();
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTotalCount() : int
	{
		return $this->db->getTotalCount();
	}

	/** 设置额外查询参数
	 * @param mixed $options $options 查询参数 可传入数组设置多个
	 * @return $this
	 * @throws Exceptions\Option
	 */
	public function setQueryOption( $options )
	{
		$this->db->setQueryOption( $options );
		return $this;
	}

	/**
	 * @param       $query
	 * @param array $bindParams
	 * @return $this
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 */
	public function rawQuery( $query, array $bindParams = [] )
	{
		return $this->db->rawQuery( $query, $bindParams );
	}

	/**
	 * 验证字段
	 * @param array $data
	 * @return bool
	 */
	private function validate( array $data ) : bool
	{
		if( !$this->dbFields ){
			return true;
		}

		foreach( $this->dbFields as $key => $desc ){
			if( in_array( $key, $this->toSkip ) ){
				continue;
			}

			$type     = null;
			$required = false;
			if( isset ( $data[$key] ) ){
				$value = $data[$key];
			} else{
				$value = null;
			}

			if( is_array( $value ) ){
				continue;
			}

			if( isset ( $desc[0] ) ){
				$type = $desc[0];
			}
			if( isset ( $desc[1] ) && ($desc[1] == 'required') ){
				$required = true;
			}

			if( $required && strlen( $value ) == 0 ){
				$this->errors[] = [$this->dbTable.".".$key => "is required"];
				continue;
			}
			if( $value == null ){
				continue;
			}

			switch( $type ){
			case "text":
				$regexp = null;
			break;
			case "int":
				$regexp = "/^[0-9]*$/";
			break;
			case "double":
				$regexp = "/^[0-9\.]*$/";
			break;
			case "bool":
				$regexp = '/^(yes|no|0|1|true|false)$/i';
			break;
			case "datetime":
				$regexp = "/^[0-9a-zA-Z -:]*$/";
			break;
			default:
				$regexp = $type;
			break;
			}
			if( !$regexp ){
				continue;
			}

			if( !preg_match( $regexp, $value ) ){
				$this->errors[] = [$this->dbTable.".".$key => "$type validation failed"];
				continue;
			}
		}
		return !count( $this->errors ) > 0;
	}

	/**
	 * @return array|null
	 * @throws Exceptions\ConnectFail
	 * @throws Exceptions\PrepareQueryFail
	 * @throws \Throwable
	 */
	private function prepareData() : ?array
	{
		$this->errors = [];
		$sqlData      = [];
		if( count( $this->data ) == 0 ){
			return [];
		}

		if( method_exists( $this, "preLoad" ) ){
			$this->preLoad( $this->data );
		}

		if( !$this->dbFields ){
			return $this->data;
		}

		foreach( $this->data as $key => &$value ){
			if( in_array( $key, $this->toSkip ) ){
				continue;
			}

			if( $value instanceof DbObject && $value->isNew == true ){
				$id = $value->save();
				if( $id ){
					$value = $id;
				} else{
					$this->errors = array_merge( $this->errors, $value->errors );
				}
			}

			if( !in_array( $key, array_keys( $this->dbFields ) ) ){
				continue;
			}

			if( !is_array( $value ) ){
				$sqlData[$key] = $value;
				continue;
			}

			if( isset ( $this->jsonFields ) && in_array( $key, $this->jsonFields ) ){
				$sqlData[$key] = json_encode( $value );
			} else if( isset ( $this->arrayFields ) && in_array( $key, $this->arrayFields ) ){
				$sqlData[$key] = implode( "|", $value );
			} else{
				$sqlData[$key] = $value;
			}
		}
		return $sqlData;
	}

	public function __set( string $name, $value )
	{
		if( array_search( $name, $this->hiddenFields ) !== false ){
			return;
		}
		$this->data[$name] = $value;
	}

	public function __get( string $name )
	{
		if( array_search( $name, $this->hiddenFields ) !== false ){
			return null;
		}

		if( isset ( $this->data[$name] ) && $this->data[$name] instanceof DbObject ){
			return $this->data[$name];
		}

		if( property_exists( $this, 'relations' ) && isset ( $this->relations[$name] ) ){
			$relationType = strtolower( $this->relations[$name][0] );
			$modelName    = $this->relations[$name][1];
			switch( $relationType ){
			case 'hasone':
				$key = isset ( $this->relations[$name][2] ) ? $this->relations[$name][2] : $name;
				$obj = new $modelName;
				return $this->data[$name] = $obj->byId( $this->data[$key] );
			break;
			case 'hasmany':
				$key = $this->relations[$name][2];
				$obj = new $modelName;
				return $this->data[$name] = $obj->where( $key, $this->data[$this->primaryKey] )->get();
			break;
			default:
			break;
			}
		}

		if( isset ( $this->data[$name] ) ){
			return $this->data[$name];
		}

		if( property_exists( $this->db, $name ) ){
			return $this->db->$name;
		}
	}

	public function __isset( string $name )
	{
		if( isset ( $this->data[$name] ) ){
			return isset ( $this->data[$name] );
		}

		if( property_exists( $this->db, $name ) ){
			return isset ( $this->db->$name );
		}
	}

	public function __unset( string $name )
	{
		unset ( $this->data[$name] );
	}
}