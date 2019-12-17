<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/6
 * Time: 上午12:41
 */

namespace EasySwoole\Validate;


use EasySwoole\Spl\SplArray;

/**
 * 数据验证器
 * Class Validate
 * @package EasySwoole\Validate
 */
class Validate
{
    protected $columns = [];

    protected $error;

    protected $verifiedData = [];

    function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * 添加一个待验证字段
     * @param string $name
     * @param null|string $alias
     * @param bool $reset
     * @return Rule
     */
    public function addColumn(string $name, ?string $alias = null,bool $reset = false): Rule
    {
        if(!isset($this->columns[$name]) || $reset){
            $rule = new Rule();
            $this->columns[$name] = [
                'alias' => $alias,
                'rule'  => $rule
            ];
        }
        return $this->columns[$name]['rule'];
    }

    /**
     * 验证字段是否合法
     * @param array $data
     * @return bool
     */
    function validate(array $data)
    {
        $this->verifiedData = [];
        $spl = new SplArray($data);
        foreach ($this->columns as $column => $item) {
            /** @var Rule $rule */
            $rule = $item['rule'];
            $rules = $rule->getRuleMap();

            /*
             * 优先检测是否带有optional选项
             * 如果设置了optional又不存在对应字段，则跳过该字段检测
             * 额外的如果这个字段是空字符串一样会认为不存在该字段
             */
            if (isset($rules['optional']) && (!isset($data[$column]) || $data[$column] === '')) {
                $this->verifiedData[$column] = $spl->get($column);
                continue;
            }
            foreach ($rules as $rule => $ruleInfo) {
                if (!call_user_func([ $this, $rule ], $spl, $column, $ruleInfo['arg'])) {
                    $this->error = new Error($column, $spl->get($column), $item['alias'], $rule, $ruleInfo['msg'], $ruleInfo['arg']);
                    return false;
                }
            }
            $this->verifiedData[$column] = $spl->get($column);
        }
        return true;
    }

    /**
     * 获取验证成功后的数据
     * @return array
     */
    public function getVerifiedData(): array
    {
        return $this->verifiedData;
    }


    /**
     * 给定的URL是否可以成功通讯
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function activeUrl(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                return false;
            }
            return checkdnsrr(parse_url($data, PHP_URL_HOST));
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否是字母 即[a-zA-Z]
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function alpha(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            return preg_match('/^[a-zA-Z]+$/', $data);
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否是字母和数字组成 即[a-zA-Z0-9]
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function alphaNum(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            return preg_match('/^[a-zA-Z0-9]+$/', $data);
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否是字母和数字下划线破折号组成 即[a-zA-Z0-9\-\_]
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function alphaDash(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            return preg_match('/^[a-zA-Z0-9\-\_]+$/', $data);
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否在 $min $max 之间
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function between(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $min = array_shift($args);
        $max = array_shift($args);
        if (is_numeric($data) || is_string($data)) {
            if ($data <= $max && $data >= $min) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否为布尔值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function bool(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if ($data === 1 || $data === true || $data === 0 || $data === false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否合法的小数
     * @param SplArray     $splArray
     * @param string       $column
     * @param null|integer $arg
     * @return bool
     */
    private function decimal(SplArray $splArray, string $column, $arg): bool
    {
        $data = strval($splArray->get($column));
        if (is_null($arg)) {
            return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
        } elseif (intval($arg) === 0) {
            // 容错处理 如果小数点后设置0位 则验整数
            return filter_var($data, FILTER_VALIDATE_INT) !== false;
        } else {
            $regex = '/^(0|[1-9]+[0-9]*)(.[0-9]{1,' . $arg . '})?$/';
            return preg_match($regex, $data);
        }
    }

    /**
     * 给定参数是否在某日期之前
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function dateBefore(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (empty($arg)) {
            $arg = date('ymd');
        }
        $beforeUnixTime = strtotime($arg);
        if (is_string($data)) {
            $unixTime = strtotime($data);
            if (is_bool($beforeUnixTime) || is_bool($unixTime)) {
                return false;
            }
            if ($unixTime < $beforeUnixTime) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否在某日期之后
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function dateAfter(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (empty($arg)) {
            $arg = date('ymd');
        }
        $afterUnixTime = strtotime($arg);
        if (is_string($data)) {
            $unixTime = strtotime($data);
            if (is_bool($afterUnixTime) || is_bool($unixTime)) {
                return false;
            }
            if ($unixTime > $afterUnixTime) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证值是否相等
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function equal(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $value = array_shift($args);
        $strict = array_shift($args);
        if ($strict) {
            if ($data !== $value) {
                return false;
            }
        } else {
            if ($data != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证值是否不相等
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function different(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $value = array_shift($args);
        $strict = array_shift($args);
        if ($strict) {
            if ($data === $value) {
                return false;
            }
        } else {
            if ($data == $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证值是否相等
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function equalWithColumn(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $fieldName = array_shift($args);
        $strict = array_shift($args);
        $value = $splArray->get($fieldName);
        if ($strict) {
            if ($data !== $value) {
                return false;
            }
        } else {
            if ($data != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证值是否不相等
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function differentWithColumn(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $fieldName = array_shift($args);
        $strict = array_shift($args);
        $value = $splArray->get($fieldName);
        if ($strict) {
            if ($data === $value) {
                return false;
            }
        } else {
            if ($data == $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证值是否一个浮点数
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function float(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * 调用自定义的闭包验证
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function func(SplArray $splArray, string $column, $arg): bool
    {
        return call_user_func($arg, $splArray, $column);
    }

    /**
     * 值是否在数组中
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function inArray(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $array = array_shift($args);
        $isStrict = array_shift($args);
        return in_array($data, $array, $isStrict);
    }

    /**
     * 是否一个整数值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function integer(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 是否一个有效的IP
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function isIp(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_IP);
    }

    /**
     * 是否不为空
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function notEmpty(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if ($data === 0 || $data === '0') {
            return true;
        } else {
            return !empty($data);
        }
    }

    /**
     * 是否一个数字值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function numeric(SplArray $splArray, string $column, $arg): bool
    {
        return is_numeric($splArray->get($column));
    }

    /**
     * 不在数组中
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function notInArray(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $array = array_shift($args);
        $isStrict = array_shift($args);
        return !in_array($data, $array, $isStrict);
    }

    /**
     * 验证数组或字符串的长度
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function length(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) == $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) == $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证数组或字符串的长度是否超出
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function lengthMax(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) <= $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) <= $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证数组或字符串的长度是否达到
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function lengthMin(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) >= $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) >= $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证数组或字符串的长度是否在一个区间里面
     * @param SplArray $splArray
     * @param string $column
     * @param $args
     * @return bool
     */
    private function betweenLen(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $min = array_shift($args);
        $max = array_shift($args);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) >= $min && strlen($data) <= $max) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) >= $min && count($data) <= $max) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证值不大于(相等视为通过)
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function max(SplArray $splArray, string $column, $arg): bool
    {
        if (!$this->numeric($splArray, $column, $arg)) {
            return false;
        }
        $data = $splArray->get($column) * 1;
        if ($data > $arg) {
            return false;
        }
        return true;
    }

    /**
     * 给定值是否一个合法的金额
     * @param SplArray $splArray
     * @param string   $column
     * @param          $arg
     * @return false|int
     */
    private function money(SplArray $splArray, string $column, $arg)
    {
        if (is_null($arg)) $arg = '';
        $data = $splArray->get($column);
        $regex = '/^(0|[1-9]+[0-9]*)(.[0-9]{1,' . $arg . '})?$/';
        return preg_match($regex, $data);
    }

    /**
     * 验证值不小于(相等视为通过)
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function min(SplArray $splArray, string $column, $arg): bool
    {
        if (!$this->numeric($splArray, $column, $arg)) {
            return false;
        }
        $data = $splArray->get($column) * 1;
        if ($data < $arg) {
            return false;
        }
        return true;
    }

    /**
     * 设置值为可选参数
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function optional(SplArray $splArray, string $column, $arg)
    {
        return true;
    }

    /**
     * 正则表达式验证
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function regex(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            return preg_match($arg, $data);
        } else {
            return false;
        }
    }

    /**
     * 验证字符串是否由数字构成
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function allDigital(SplArray $splArray, string $column): bool
    {
        return $this->regex($splArray, $column, '/^\d+$/');
    }

    /**
     * 必须存在值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function required(SplArray $splArray, string $column, $arg): bool
    {
        return isset($splArray[$column]);
    }

    /**
     * 值是一个合法的时间戳
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function timestamp(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data)) {
            if (strtotime(date("d-m-Y H:i:s", $data)) === (int)$data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 时间戳在某指定日期之前
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function timestampBeforeDate(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data)) {
            $time = strtotime($arg);
            if ($time !== false && $time > 0 && $time > $data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 时间戳在某指定日期之后
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function timestampAfterDate(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data)) {
            $time = strtotime($arg);
            if ($time !== false && $time > 0 && $time < $data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 时间戳是否在某时间戳之前
     * @param SplArray $splArray
     * @param string   $column
     * @param          $arg
     * @return bool
     */
    private function timestampBefore(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) && is_numeric($arg)) {
            return intval($data) < intval($arg);
        } else {
            return false;
        }
    }

    /**
     * 时间戳是否在某时间戳之后
     * @param SplArray $splArray
     * @param string   $column
     * @param          $arg
     * @return bool
     */
    private function timestampAfter(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) && is_numeric($arg)) {
            return intval($data) > intval($arg);
        } else {
            return false;
        }
    }

    /**
     * 值是一个合法的链接
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function url(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_URL);
    }

}
