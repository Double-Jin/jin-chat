<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/6
 * Time: 上午12:41
 */

namespace EasySwoole\Validate;

/**
 * 校验规则
 * 请以首字母排序校验方法以便后期维护
 * Class Rule
 * @package EasySwoole\Validate
 */
class Rule
{
    protected $ruleMap = [];

    function getRuleMap(): array
    {
        return $this->ruleMap;
    }

    /**
     * 给定的URL是否可以成功通讯
     * @param null|string $msg
     * @return $this
     */
    function activeUrl($msg = null)
    {
        $this->ruleMap['activeUrl'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 给定的参数是否是字母 即[a-zA-Z]
     * @param null|string $msg
     * @return $this
     */
    function alpha($msg = null)
    {
        $this->ruleMap['alpha'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    function alphaNum($msg = null)
    {
        $this->ruleMap['alphaNum'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    function alphaDash($msg = null)
    {
        $this->ruleMap['alphaDash'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 给定的参数是否在 $min $max 之间
     * @param integer $min 最小值 不包含该值
     * @param integer $max 最大值 不包含该值
     * @param null|string $msg
     * @return $this
     */
    function between($min, $max, $msg = null)
    {
        $this->ruleMap['between'] = [
            'msg' => $msg,
            'arg' => [
                $min, $max
            ]
        ];
        return $this;
    }

    /**
     * 给定参数是否为布尔值
     * @param null|string $msg
     * @return $this
     */
    function bool($msg = null)
    {
        $this->ruleMap['bool'] = [
            'msg' => $msg,
            'arg' => null
        ];
        return $this;
    }

    /**
     * 给定参数是否为小数格式
     * @param null|integer $precision 规定小数点位数 null 为不规定
     * @param null $msg
     * @return $this
     */
    function decimal(?int $precision = null, $msg = null)
    {
        $this->ruleMap['decimal'] = [
            'msg' => $msg,
            'arg' => $precision
        ];
        return $this;
    }

    /**
     * 给定参数是否在某日期之前
     * @param null|string $date
     * @param null|string $msg
     * @return $this
     */
    function dateBefore(?string $date = null, $msg = null)
    {
        $this->ruleMap['dateBefore'] = [
            'msg' => $msg,
            'arg' => $date
        ];
        return $this;
    }

    /**
     * 给定参数是否在某日期之后
     * @param null|string $date
     * @param null|string $msg
     * @return $this
     */
    function dateAfter(?string $date = null, $msg = null)
    {
        $this->ruleMap['dateAfter'] = [
            'msg' => $msg,
            'arg' => $date
        ];
        return $this;
    }

    /**
     * 验证值是否相等
     * @param $compare
     * @param $strict
     * @param null|string $msg
     * @return $this
     */
    function equal($compare, bool $strict = false, $msg = null)
    {
        $this->ruleMap['equal'] = [
            'msg' => $msg,
            'arg' => [$compare, $strict]
        ];
        return $this;
    }

    /**
     * 验证值是否不相等
     * @param $compare
     * @param $strict
     * @param null|string $msg
     * @return $this
     */
    function different($compare, bool $strict = false, $msg = null)
    {
        $this->ruleMap['different'] = [
            'msg' => $msg,
            'arg' => [$compare, $strict]
        ];
        return $this;
    }

    /**
     * 验证值是否相等
     * @param $fieldName
     * @param $strict
     * @param null|string $msg
     * @return $this
     */
    function equalWithColumn($fieldName, bool $strict = false, $msg = null)
    {
        $this->ruleMap['equalWithColumn'] = [
            'msg' => $msg,
            'arg' => [$fieldName, $strict]
        ];
        return $this;
    }

    /**
     * 验证值是否不相等
     * @param $fieldName
     * @param $strict
     * @param null|string $msg
     * @return $this
     */
    function differentWithColumn($fieldName, bool $strict = false, $msg = null)
    {
        $this->ruleMap['differentWithColumn'] = [
            'msg' => $msg,
            'arg' => [$fieldName, $strict]
        ];
        return $this;
    }

    /**
     * 验证值是否一个浮点数
     * @param null|string $msg
     * @return $this
     */
    function float($msg = null)
    {
        $this->ruleMap['float'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 调用自定义的闭包验证
     * @param callable $func
     * @param null|string $msg
     * @return $this
     */
    function func(callable $func, $msg = null)
    {
        $this->ruleMap['func'] = [
            'arg' => $func,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 值是否在数组中
     * @param array $array
     * @param bool $isStrict
     * @param null|string $msg
     * @return $this
     */
    function inArray(array $array, $isStrict = false, $msg = null)
    {
        $this->ruleMap['inArray'] = [
            'arg' => [ $array, $isStrict ],
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 是否一个整数值
     * @param null|string $msg
     * @return $this
     */
    function integer($msg = null)
    {
        $this->ruleMap['integer'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 是否一个有效的IP
     * @param array $array
     * @param null $msg
     * @return $this
     */
    function isIp($msg = null)
    {
        $this->ruleMap['isIp'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 是否不为空
     * @param null $msg
     * @return $this
     */
    function notEmpty($msg = null)
    {
        $this->ruleMap['notEmpty'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 是否一个数字值
     * @param null $msg
     * @return $this
     */
    function numeric($msg = null)
    {
        $this->ruleMap['numeric'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 不在数组中
     * @param array $array
     * @param bool $isStrict
     * @param null $msg
     * @return $this
     */
    function notInArray(array $array, $isStrict = false, $msg = null)
    {
        $this->ruleMap['notInArray'] = [
            'arg' => [ $array, $isStrict ],
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 验证数组或字符串的长度
     * @param int $len
     * @param null $msg
     * @return $this
     */
    function length(int $len, $msg = null)
    {
        $this->ruleMap['length'] = [
            'msg' => $msg,
            'arg' => $len
        ];
        return $this;
    }

    /**
     * 验证数组或字符串的长度是否超出
     * @param int $lengthMax
     * @param null $msg
     * @return $this
     */
    function lengthMax(int $lengthMax, $msg = null)
    {
        $this->ruleMap['lengthMax'] = [
            'msg' => $msg,
            'arg' => $lengthMax
        ];
        return $this;
    }

    /**
     * 验证数组或字符串的长度是否达到
     * @param int $lengthMin
     * @param null $msg
     * @return $this
     */
    function lengthMin(int $lengthMin, $msg = null)
    {
        $this->ruleMap['lengthMin'] = [
            'msg' => $msg,
            'arg' => $lengthMin
        ];
        return $this;
    }

    /**
     * 验证数组或字符串的长度是否在一个范围内
     * @param null $msg
     * @return $this
     */
    function betweenLen(int $min, int $max, $msg = null)
    {
        $this->ruleMap['betweenLen'] = [
            'msg' => $msg,
            'arg' => [
                $min,
                $max
            ]
        ];
        return $this;
    }

    /**
     * 验证值不大于(相等视为不通过)
     * @param int $max
     * @param null|string $msg
     * @return Rule
     */
    function max(int $max, ?string $msg = null): Rule
    {
        $this->ruleMap['max'] = [
            'arg' => $max,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 验证值不小于(相等视为不通过)
     * @param int $min
     * @param null|string $msg
     * @return Rule
     */
    function min(int $min, ?string $msg = null): Rule
    {
        $this->ruleMap['min'] = [
            'arg' => $min,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 验证值是合法的金额
     * 100 | 100.1 | 100.01
     * @param integer|null $precision 小数点位数
     * @param string|null  $msg
     * @return Rule
     */
    function money(?int $precision = null, string $msg = null): Rule
    {
        $this->ruleMap['money'] = [
            'arg' => $precision,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 设置值为可选参数
     * @return $this
     */
    function optional()
    {
        $this->ruleMap['optional'] = [
            'arg' => null,
            'msg' => null
        ];
        return $this;
    }

    /**
     * 正则表达式验证
     * @param $reg
     * @param null $msg
     * @return $this
     */
    function regex($reg, $msg = null)
    {
        $this->ruleMap['regex'] = [
            'arg' => $reg,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 必须存在值
     * @param null $msg
     * @return $this
     */
    function required($msg = null)
    {
        $this->ruleMap['required'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 值是一个合法的时间戳
     * @param null $msg
     * @return $this
     */
    function timestamp($msg = null)
    {
        $this->ruleMap['timestamp'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 时间戳在某指定日期之前
     * @param string $date 传入任意可被strtotime解析的字符串
     * @param null $msg
     * @return $this
     */
    function timestampBeforeDate($date, $msg = null)
    {
        $this->ruleMap['timestampBeforeDate'] = [
            'arg' => $date,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 时间戳在某指定日期之后
     * @param string $date 传入任意可被strtotime解析的字符串
     * @param null $msg
     * @return $this
     */
    function timestampAfterDate($date, $msg = null)
    {
        $this->ruleMap['timestampAfterDate'] = [
            'arg' => $date,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 指定时间戳在某时间戳之前
     * @param string|integer $beforeTimestamp 在该时间戳之前
     * @param null $msg
     * @return $this
     */
    function timestampBefore($beforeTimestamp, $msg = null)
    {
        $this->ruleMap['timestampBefore'] = [
            'arg' => $beforeTimestamp,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 指定时间戳在某时间戳之后
     * @param string|integer $afterTimestamp 在该时间戳之后
     * @param null $msg
     * @return $this
     */
    function timestampAfter($afterTimestamp, $msg = null)
    {
        $this->ruleMap['timestampAfter'] = [
            'arg' => $afterTimestamp,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 值是一个合法的链接
     * @param null $msg
     * @return $this
     */
    function url($msg = null)
    {
        $this->ruleMap['url'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }

    /**
     * 值是一个合法的链接
     * @param null $msg
     * @return $this
     */
    function allDigital($msg = null)
    {
        $this->ruleMap['allDigital'] = [
            'arg' => null,
            'msg' => $msg
        ];
        return $this;
    }
}