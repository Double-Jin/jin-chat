<?php

namespace EasySwoole\Utility;

/**
 * 随机生成器
 * Class Random
 * @author  : evalor <master@evalor.cn>
 * @package EasySwoole\Utility
 */
class Random
{
    /**
     * 生成随机字符串 可用于生成随机密码等
     * @param int    $length   生成长度
     * @param string $alphabet 自定义生成字符集
     * @author : evalor <master@evalor.cn>
     * @return bool|string
     */
    static function character($length = 6, $alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789')
    {
        /*
         * mt_srand() is to fix:
            mt_rand(0,100);
            if(pcntl_fork()){
                var_dump(mt_rand(0,100));
            }else{
                var_dump(mt_rand(0,100));
            }
         */
        mt_srand();
        // 重复字母表以防止生成长度溢出字母表长度
        if ($length >= strlen($alphabet)) {
            $rate = intval($length / strlen($alphabet)) + 1;
            $alphabet = str_repeat($alphabet, $rate);
        }

        // 打乱顺序返回
        return substr(str_shuffle($alphabet), 0, $length);
    }

    /**
     * 生成随机数字 可用于生成随机验证码等
     * @param int $length 生成长度
     * @author : evalor <master@evalor.cn>
     * @return bool|string
     */
    static function number($length = 6)
    {
        return static::character($length, '0123456789');
    }

    static function arrayRandOne(array $data)
    {
        if(empty($data)){
            return null;
        }
        mt_srand();
        return $data[array_rand($data)];
    }
}