# 示例
假如你的项目的tp的风格，可以继承EasySwoole\Mysqli\TpORM来自定义

```php
<?php

namespace ezswoole;

use EasySwoole\Mysqli\TpORM;
use EasySwoole\EasySwoole\Config;
use your\pool\MysqlObject;
use your\context\MysqlContext;

/**
 * Class Model
 * @package your
 * @method mixed|static where($whereProps, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
 * @method mixed|static group(string $groupByField)
 * @method mixed|static order(string $orderByField, string $orderByDirection = "DESC", $customFieldsOrRegExp = null)
 * @method mixed|static field($field)
 *
 */
class Model extends TpORM
{
	protected $prefix;
	protected $modelPath = '\\App\\Model';
	protected $fields = [];
	protected $limit;
	protected $throwable;
	protected $createTime = false;
	protected $createTimeName = 'create_time';
	protected $softDelete = false;
	protected $softDeleteTimeName = 'delete_time';

	/**
	 * @param null $data
	 */
	public function __construct( $data = null )
	{
		$this->prefix = Config::getInstance()->getConf( 'MYSQL.prefix' );
		$db = \EasySwoole\Component\Context\ContextManager::getInstance()->get(MysqlContext::KEY);
		if( $db instanceof MysqlObject ){
			parent::__construct( $data );
			$this->setDb( $db );
		} else{
			return null;
		}
	}
	/**
	 * 批量添加
	 * @param array $datas
	 * @param bool $autoConvertData 自动转换model所需要的类型
	 * @return bool|mixed
	 */
	public function addMulti( array $datas = [], bool $convertData = false)
	{
		try{
			if( !empty( $datas ) ){
				if($convertData === true ){
					foreach($datas as $k => $d){
						$datas[$k] = $this->convertData($d);
					}
				}
				if( !is_array( $datas[0] ) ){
					return false;
				}
				$fields    = array_keys( $datas[0] );
				$db        = $this->getDb();
				$tableName = $this->getDbTable();
				$values    = [];
				foreach( $datas as $data ){
					$value = [];
					foreach( $data as $key => $val ){
						if( is_string( $val ) ){
							$val = '"'.addslashes( $val ).'"';
						} elseif( is_bool( $val ) ){
							$val = $val ? '1' : '0';
						} elseif( is_null( $val ) ){
							$val = 'null';
						}
						if( is_scalar( $val ) ){
							$value[] = $val;
						}
					}
					$values[] = '('.implode( ',', $value ).')';
				}
				$sql = 'INSERT INTO '.$tableName.' ('.implode( ',', $fields ).') VALUES '.implode( ',', $values );
				return $db->rawQuery( $sql );
			} else{
				return false;
			}
		}catch(\Exception $e){
			$this->throwable = $e;
			var_dump($e->getTraceAsString());
			return false;
		}

	}

	/**
	 * 批量修改
	 * @param array $multipleData
	 * @return bool
	 */
	public function editMulti( array $multipleData = [] )
	{
		try{
			if( !empty( $multipleData ) ){
				$db           = $this->getDb();
				$pk           = $this->getPrimaryKey();
				$tableName    = $this->getDbTable();
				$updateColumn = array_keys( $multipleData[0] );
				unset( $updateColumn[0] );
				$sql = "UPDATE ".$tableName." SET ";
				$pks = array_column( $multipleData, $pk );

				foreach( $updateColumn as $uColumn ){
					$sql .= "`{$uColumn}` = CASE ";
					foreach( $multipleData as $data ){
						$val = $data[$pk];
						// 判断是不是字符串
						if( is_string( $val ) ){
							$val = '"'.addslashes( $val ).'"';
						}  elseif( is_null( $val ) ){
							$val = 'NULL';
						}

						$_val = $data[$uColumn];
						if( is_string( $val ) ){
							$_val = '"'.addslashes( $_val ).'"';
						}  elseif( is_null( $_val ) ){
							$_val = 'NULL';
						}

						$sql .= "WHEN `".$pk."` = {$val} THEN {$_val} ";
					}
					$sql .= "ELSE `".$uColumn."` END, ";
				}

				$joinStr = join(",",$pks);
				$inStr = "'".str_replace(",","','",$joinStr)."'";

				$sql = rtrim( $sql, ", " )." WHERE `".$pk."` IN (".$inStr.")";
				return $db->rawQuery( $sql ) ?? false;
			} else{
				return false;
			}
		}catch(\Exception $e){
			$this->throwable = $e;
			var_dump($e->getTraceAsString());
			return false;
		}

	}
	/**
	 * @param null $data
	 * @return bool|int
	 */
	protected function add( $data = null )
	{
		try{
			if( $this->createTime === true ){
				$data[$this->createTimeName] = time();
			}
			return parent::insert( $data );
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @param null $data
	 * @return bool|mixed
	 */
	protected function edit( $data = null )
	{
		try{
			return $this->update( $data );
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @return bool|null
	 */
	protected function del()
	{
		try{
			if( $this->softDelete === true ){
				$data[$this->softDeleteTimeName] = time();
				return $this->update( $data );
			} else{
				return parent::delete();
			}
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @return array|bool|false|null
	 */
	protected function select()
	{
		try{
			return parent::select();
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @param string $name
	 * @return array|bool
	 */
	protected function column( string $name )
	{
		try{
			return parent::column( $name );
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @param string $name
	 * @return array|bool|null
	 */
	protected function value( string $name )
	{
		try{
			return parent::value( $name );
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @param string $column
	 * @return array|bool|int|null
	 */
	protected function count( string $column = '*')
	{
		try{
			return parent::count($column);
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @return array|bool
	 */
	protected function find( $id = null )
	{
		try{
			if( $id ){
				return $this->byId( $id );
			} else{
				return parent::find();
			}
		} catch( \EasySwoole\Mysqli\Exceptions\ConnectFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \EasySwoole\Mysqli\Exceptions\PrepareQueryFail $e ){
			$this->throwable = $e;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @return Model
	 */
	static function init()
	{
		return new static();
	}
}
```
## 示例

WikiModel.php

```php
<?php

namespace App\Model;

use yourPath\Model;


class Wiki extends Model
{
	protected $softDelete = true;
	protected $softDeleteTimeName = 'delete_time';
	protected $createTime = true;
	protected $createTimeName = 'create_time';
	protected $dbFields
		= [
			'content'     => ['text', 'required'],
			'id'          => ['int'],
			'name'        => ['text'],
			'create_time' => ['int'],
			'delete_time' => ['int'],
		];
}

?>
```

查询多条

```php
WikiModel::where( ['id' => ['>', 5]] )->field( ['id'] )->select();
WikiModel::where( ['id' => ['>', 5]] )->column( 'id' );
WikiModel::where( ['id' => ['>', 5]] )->value( 'id' );
WikiModel::where( ['id' => ['>', 5]] )->field( ['id'] )->select();
```

单条

```php
WikiModel::where( ['id' => ['>', 5]] )->find();
```

添加

```php
WikiModel::add( ['content' => "1111"] );
```

修改

> 注意：对象的主键。'id'是默认值。protected $primaryKey = 'id';
>
> 如果想改为其他，请继承并替换

```php
// 方式1，逻辑是先查询有没有，如果有就实例化出来一个对象，然后调用edit方法
$wiki   = WikiModel::byId( 4 );
$wiki->edit( ['content' => 'xxxxxxxxxxx'] );
// 方式2，不管有没有直接就修改
$wiki->edit( ['id'=>'4','content' => 'xxxxxxxxxxx'] );
// 还可以这么写
WikiModel::where( ['id' => ['>' => 64]] )->edit( ['content' => '我是64'] );
```

删除

```php
// 方式1
WikiModel::byId( 64 )->del();
// 方式2
WikiModel::where( ['id' => ['>=' => 63]] )->del();
```



