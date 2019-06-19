<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:06
 */

namespace EasySwoole\FastCache;


class Protocol
{
    public static function pack(string $data): string
    {
        return pack('N', strlen($data)).$data;
    }

    public static function packDataLength(string $head): int
    {
        return unpack('N', $head)[1];
    }

    public static function unpack(string $data):string
    {
        return substr($data,4);
    }
}