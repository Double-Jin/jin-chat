<?php
namespace App\Model;

use App\Utility\Pool\MysqlObject;
use App\Utility\Pool\MysqlPool;
use EasySwoole\Component\Pool\PoolManager;

class Base
{
    public $db;

    public function __construct()
    {
        $mysqlObject = PoolManager::getInstance()->getPool(MysqlPool::class)->getObj();
        // 类型的判定
        if ($mysqlObject instanceof MysqlObject) {
            $this->db = $mysqlObject;
        } else {
            throw new \Exception('Mysql Pool is error');
        }
    }

    public function __destruct()
    {

        if ($this->db instanceof MysqlObject) {
            PoolManager::getInstance()->getPool(MysqlPool::class)->recycleObj($this->db);
            // 请注意 此处db是该链接对象的引用 即使操作了回收 仍然能访问
            // 安全起见 请一定记得设置为null 避免再次使用导致不可预知的问题
            $this->db = null;
        }

    }

    protected function getDb():MysqlObject
    {
        return $this->db;
    }

}