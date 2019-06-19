# 示例
假如你的项目的tp的风格，可以继承EasySwoole\Mysqli\DbObject来自定义
```php
<?php

namespace App\Model;

use EasySwoole\Mysqli\DbObject;
use EasySwoole\Spl\SplString;

class Model extends DbObject
{
	protected $prefix = 'ez_';
	protected $modelPath = '\\App\\Model';
	protected $fields = [];
	protected $limit;
	protected $db;

	public function __construct( $data = null )
	{
		if( empty( $this->dbTable ) ){
			$split         = explode( "\\", get_class( $this ) );
			$end           = end( $split );
			$splString     = new SplString( $end );
			$name          = $splString->snake( '_' )->__toString();
			$this->dbTable = $this->prefix.$name." AS {$name}";
		}
		parent::__construct( $data );
		//这里的db请从pool中拿出,直接PoolManager::getInstance()->getPool(pool::class)->getObject();
		$this->db = $db = null;
		$this->setDb($db);
	}

	protected function joins( array $joins ) : Model
	{
		foreach( $joins as $join ){
			self::join( ...$join );
		}
		return $this;
	}

	protected function find() : array
	{
		$list = parent::get( 1, $this->fields );
		return isset( $list[0] ) ? $list[0] : [];
	}

	protected function field( $field ) : Model
	{
		$this->fields = $field;
		return $this;
	}

	protected function limit( $limit ) : Model
	{
		$this->limit = $limit;
		return $this;
	}

	protected function page( string $page ) : Model
	{
		$split = explode( ",", $page );
		$page  = $split[0] - 1;
		$rows  = $split[1];
		return $this->limit( "{$page},{$rows}" );
	}

	protected function select() : array
	{
		return parent::get( $this->limit, $this->fields );
	}

	protected function wheres( array $whereProps ) : Model
	{
		foreach( $whereProps as $whereProp ){
			$this->where( ...$whereProp );
		}
		return $this;
	}
	
	public function __destruct(){
	    //这里吧$this->db 回收到连接池中
    }

}
```
