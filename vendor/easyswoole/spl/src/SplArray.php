<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:52
 */

namespace EasySwoole\Spl;


class SplArray extends \ArrayObject
{
    function __get($name)
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            return null;
        }
    }

    function __set($name, $value): void
    {
        $this[$name] = $value;
    }

    function __toString(): string
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    function getArrayCopy(): array
    {
        return (array)$this;
    }

    function set($path, $value): void
    {
        $path = explode(".", $path);
        $temp = $this;
        while ($key = array_shift($path)) {
            $temp = &$temp[$key];
        }
        $temp = $value;
    }

    function unset($path)
    {
        $finalKey = null;
        $path = explode(".", $path);
        $temp = $this;
        while (count($path) > 1 && $key = array_shift($path)) {
            $temp = &$temp[$key];
        }
        $finalKey = array_shift($path);
        if (isset($temp[$finalKey])) {
            unset($temp[$finalKey]);
        }
    }

    function get($path)
    {
        $paths = explode(".", $path);
        $data = $this->getArrayCopy();
        while ($key = array_shift($paths)) {
            if (isset($data[$key])) {
                $data = $data[$key];
            } else {
                if ($key == '*') {
                    $temp = [];
                    if (is_array($data)) {
                        if (!empty($paths)) {
                            $path = implode("/", $paths);
                        } else {
                            $path = null;
                        }
                        foreach ($data as $key => $datum) {
                            if (is_array($datum)) {
                                $ctemp = (new SplArray($datum))->get($path);
                                if ($ctemp !== null) {
                                    $temp[][$path] = $ctemp;
                                }
                            } else if ($datum !== null) {
                                $temp[$key] = $datum;
                            }

                        }
                    }
                    return $temp;
                } else {
                    return null;
                }
            }
        }
        return $data;
    }

    public function delete($key): void
    {
        $this->unset($key);
    }

    /**
     * 数组去重取唯一的值
     * @return SplArray
     */
    public function unique(): SplArray
    {
        return new SplArray(array_unique($this->getArrayCopy(), SORT_REGULAR));
    }

    /**
     * 获取数组中重复的值
     * @return SplArray
     */
    public function multiple(): SplArray
    {
        $unique_arr = array_unique($this->getArrayCopy(), SORT_REGULAR);
        return new SplArray(array_udiff_uassoc($this->getArrayCopy(), $unique_arr, function ($key1, $key2) {
            if ($key1 === $key2) {
                return 0;
            }
            return 1;
        }, function ($value1, $value2) {
            if ($value1 === $value2) {
                return 0;
            }
            return 1;
        }));
    }

    /**
     * 按照键值升序
     * @return SplArray
     */
    public function asort(): SplArray
    {
        parent::asort();
        return $this;
    }

    /**
     * 按照键升序
     * @return SplArray
     */
    public function ksort(): SplArray
    {
        parent::ksort();
        return $this;
    }

    /**
     * 自定义排序
     * @param int $sort_flags
     * @return SplArray
     */
    public function sort($sort_flags = SORT_REGULAR): SplArray
    {
        $temp = $this->getArrayCopy();
        sort($temp, $sort_flags);
        return new SplArray($temp);
    }

    /**
     * 取得某一列
     * @param string      $column
     * @param null|string $index_key
     * @return SplArray
     */
    public function column($column, $index_key = null): SplArray
    {
        return new SplArray(array_column($this->getArrayCopy(), $column, $index_key));
    }

    /**
     * 交换数组中的键和值
     * @return SplArray
     */
    public function flip(): SplArray
    {
        return new SplArray(array_flip($this->getArrayCopy()));
    }

    /**
     * 过滤本数组
     * @param string|array $keys 需要取得/排除的键
     * @param bool         $exclude true则排除设置的键名 false则仅获取设置的键名
     * @return SplArray
     */
    public function filter($keys, $exclude = false): SplArray
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }
        $new = array();
        foreach ($this->getArrayCopy() as $name => $value) {
            if (!$exclude) {
                in_array($name, $keys) ? $new[$name] = $value : null;
            } else {
                in_array($name, $keys) ? null : $new[$name] = $value;
            }
        }
        return new SplArray($new);
    }


    public function keys($path = null): array
    {
        if (!empty($path)) {
            $temp = $this->get($path);
            if (is_array($temp)) {
                return array_keys($temp);
            } else {
                return [];
            }
        }
        return array_keys((array)$this);
    }

    /**
     * 提取数组中的值
     * @return SplArray
     */
    public function values(): SplArray
    {
        return new SplArray(array_values($this->getArrayCopy()));
    }

    public function flush(): SplArray
    {
        foreach ($this->getArrayCopy() as $key => $item) {
            unset($this[$key]);
        }
        return $this;
    }

    public function loadArray(array $data)
    {
        parent::__construct($data);
        return $this;
    }

    function merge(array $data)
    {
        return $this->loadArray($data + $this->getArrayCopy());
    }

    /*
     $test = new \EasySwoole\Spl\SplArray([
        'title'=>'title',
        'items'=>[
            ['title'=>'Some string', 'number' => 1],
            ['title'=>'Some string', 'number' => 2],
            ['title'=>'Some string', 'number' => 3]
        ]
    ]);
     */
    public function toXML($CD_DATA = false, $rootName = 'xml', $encoding = 'UTF-8')
    {
        $data = $this->getArrayCopy();
        if ($CD_DATA) {
            /*
             * 默认制定
             */
            $xml = new class('<?xml version="1.0" encoding="' . $encoding . '" ?>' . "<{$rootName}></{$rootName}>") extends \SimpleXMLElement
            {
                public function addCData($cdata_text)
                {
                    $dom = dom_import_simplexml($this);
                    $cdata = $dom->ownerDocument->createCDATASection($cdata_text);
                    $dom->appendChild($cdata);
                }
            };
        } else {
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="' . $encoding . '" ?>' . "<{$rootName} ></{$rootName}>");
        }
        $parser = function ($xml, $data) use (&$parser, $CD_DATA) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    if (!is_numeric($k)) {
                        $ch = $xml->addChild($k);
                    } else {
                        $ch = $xml->addChild(substr($xml->getName(), 0, -1));
                    }
                    $parser($ch, $v);
                } else {
                    if (is_numeric($k)) {
                        $xml->addChild($k, $v);
                    } else {
                        if ($CD_DATA) {
                            $n = $xml->addChild($k);
                            $n->addCData($v);
                        } else {
                            $xml->addChild($k, $v);
                        }
                    }
                }
            }
        };
        $parser($xml, $data);
        unset($parser);
        $str = $xml->asXML();
        return substr($str, strpos($str, "\n") + 1);
    }

}
