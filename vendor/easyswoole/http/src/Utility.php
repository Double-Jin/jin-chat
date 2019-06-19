<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午3:40
 */

namespace EasySwoole\Http;


use EasySwoole\Http\Message\Message;

class Utility
{
    static function toString(Message $message){
        if ($message instanceof Request) {
            $msg = trim($message->getMethod() . ' '
                    . $message->getRequestTarget())
                . ' HTTP/' . $message->getProtocolVersion();
            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: " . $message->getUri()->getHost();
            }
        } elseif ($message instanceof Response) {
            $msg = 'HTTP/' . $message->getProtocolVersion() . ' '
                . $message->getStatusCode() . ' '
                . $message->getReasonPhrase();
        } else {
            throw new \InvalidArgumentException('Unknown message type');
        }

        foreach ($message->getHeaders() as $name => $values) {
            $msg .= "\r\n{$name}: " . implode(', ', $values);
        }

        return "{$msg}\r\n\r\n" . $message->getBody();
    }

    static function headerItemToArray(string $header):array
    {
        return array_map('trim', explode(',', $header));
    }
}