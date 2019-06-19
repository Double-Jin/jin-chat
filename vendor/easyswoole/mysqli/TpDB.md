不走model，直接方便操作数据库的增删改查。

继承，方便自己再二次封装

```php
<?php

namespace yourApp;

use yourApp\pool\MysqlPool;
use yourApp\pool\MysqlObject;
use EasySwoole\Mysqli\TpDb;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;

/**
 * Class Model
 * @package ezswoole
 */
class Db extends TpDb
{
	protected $prefix;
	protected $fields = [];
	protected $limit;
	protected $throwable;

	public function setThrowable( $t )
	{
		$this->throwable = $t;
	}

	/**
	 * Model constructor.
	 * @throws \Exception
	 */
	public function __construct()
	{
		$this->prefix = Config::getInstance()->getConf( 'MYSQL.prefix' );
		$db           = PoolManager::getInstance()->getPool( MysqlPool::class )->getObj( Config::getInstance()->getConf( 'MYSQL.POOL_TIME_OUT' ) );
		if( $db instanceof MysqlObject ){
			$this->setDb( $db );
		} else{
			throw new \Exception( 'mysql pool is empty' );
		}
	}

	public function __destruct()
	{
		$db = $this->getDb();
		if( $db instanceof MysqlObject ){
			$db->gc();
			PoolManager::getInstance()->getPool( MysqlPool::class )->recycleObj( $db );
			$this->setDb( null );
		}
	}

	/**
	 * 为了不让select报错
	 * @return array|bool|false|null
	 */
	protected function select()
	{
		try{
			return parent::select();
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @return array|bool
	 */
	protected function find()
	{
		try{
			return parent::find();
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * todo log
	 * @param array $data
	 * @return bool|int
	 */
	protected function insert( $data = [] )
	{
		try{
			return parent::insert( $data );
		} catch( \ConnectFail $t ){
			$this->throwable = $t;
			return false;
		} catch( \PrepareQueryFail $t ){
			$this->throwable = $t;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

	/**
	 * @param array|null $data
	 * @return bool|mixed
	 */
	protected function update( $data = [] )
	{
		try{
			return parent::update( $data );
		} catch( \ConnectFail $t ){
			$this->throwable = $t;
			return false;
		} catch( \PrepareQueryFail $t ){
			$this->throwable = $t;
			return false;
		} catch( \Throwable $t ){
			$this->throwable = $t;
			return false;
		}
	}

}
```

## 示例

```php
Db::name( 'wiki' )->insert( ['content' => '222222'] );
Db::name( 'wiki' )->where( ['id' => ['>', 15]] )->update( ['content' => '33333333333'] );
```

