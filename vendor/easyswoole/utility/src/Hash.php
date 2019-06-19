<?php

namespace EasySwoole\Utility;

/**
 * 哈希助手类库
 * 用于快速处理哈希密码以及数据完整性校验等场景
 * Class Hash
 * @author  : evalor <master@evalor.cn>
 * @package EasySwoole\Utility
 */
class Hash
{

    // TODO 使用密码加密和解密一段数据
    // TODO 计算一段数据的CRC32 以校验数据完整性

    /**
     * 从一个明文值生产哈希
     * @param string  $value 需要生产哈希的原文
     * @param integer $cost  递归的层数 可根据机器配置调整以增加哈希的强度
     * @author : evalor <master@evalor.cn>
     * @return false|string 返回60位哈希字符串 生成失败返回false
     */
    static function makePasswordHash($value, $cost = 10)
    {
        return password_hash($value, PASSWORD_BCRYPT, [ 'cost' => $cost ]);
    }

    /**
     * 校验明文值与哈希是否匹配
     * @param string $value
     * @param string $hashValue
     * @author : evalor <master@evalor.cn>
     * @return bool
     */
    static function validatePasswordHash($value, $hashValue)
    {
        return password_verify($value, $hashValue);
    }
}