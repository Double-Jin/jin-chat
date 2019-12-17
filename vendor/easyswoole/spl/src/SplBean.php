<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:53
 */

namespace EasySwoole\Spl;


/*
 * 仅能获取protected 和public成员变量
 */

use EasySwoole\Spl\Exception\Exception;

class SplBean implements \JsonSerializable
{
    const FILTER_NOT_NULL = 1;
    const FILTER_NOT_EMPTY = 2;
    const FILTER_NULL = 3;
    const FILTER_EMPTY = 4;


    public function __construct(array $data = null, $autoCreateProperty = false)
    {
        if ($data) {
            $this->arrayToBean($data, $autoCreateProperty);
        }
        $this->initialize();
        $this->classMap();
    }

    final public function allProperty(): array
    {
        $data = [];
        $class = new \ReflectionClass($this);
        $protectedAndPublic = $class->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
        );
        foreach ($protectedAndPublic as $item) {
            if ($item->isStatic()) {
                continue;
            }
            array_push($data, $item->getName());
        }
        $data = array_flip($data);
        unset($data['_keyMap']);
        unset($data['_classMap']);
        return array_flip($data);
    }

    function toArray(array $columns = null, $filter = null): array
    {
        $data = $this->jsonSerialize();
        if ($columns) {
            $data = array_intersect_key($data, array_flip($columns));
        }
        if ($filter === self::FILTER_NOT_NULL) {
            return array_filter($data, function ($val) {
                return !is_null($val);
            });
        } else if ($filter === self::FILTER_NOT_EMPTY) {
            return array_filter($data, function ($val) {
                return !empty($val);
            });
        } else if ($filter === self::FILTER_NULL) {
            return array_filter($data, function ($val) {
                return is_null($val);
            });
        } else if ($filter === self::FILTER_EMPTY) {
            return array_filter($data, function ($val) {
                return empty($val);
            });
        } else if (is_callable($filter)) {
            return array_filter($data, $filter);
        }
        return $data;
    }

    /*
     * 返回转化后的array
     */
    function toArrayWithMapping(array $columns = null, $filter = null)
    {
        $array = $this->toArray();
        $array = $this->beanKeyMap($array);

        if ($columns) {
            $array = array_intersect_key($array, array_flip($columns));
        }
        if ($filter === self::FILTER_NOT_NULL) {
            return array_filter($array, function ($val) {
                return !is_null($val);
            });
        } else if ($filter === self::FILTER_NOT_EMPTY) {
            return array_filter($array, function ($val) {
                if ($val === 0 || $val === '0') {
                    return true;
                } else {
                    return !empty($val);
                }
            });
        } else if (is_callable($filter)) {
            return array_filter($array, $filter);
        }
        return $array;
    }

    final private function arrayToBean(array $data, $autoCreateProperty = false): SplBean
    {

        $data = $this->dataKeyMap($data);

        if ($autoCreateProperty == false) {
            $data = array_intersect_key($data, array_flip($this->allProperty()));
        }
        foreach ($data as $key => $item) {
            $this->addProperty($key, $item);
        }
        return $this;
    }

    final public function addProperty($name, $value = null): void
    {
        $this->$name = $value;
    }

    final public function getProperty($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    final public function jsonSerialize(): array
    {
        $data = [];
        foreach ($this as $key => $item) {
            $data[$key] = $item;
        }
        unset($data['_keyMap']);
        unset($data['_classMap']);
        return $data;
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /*
     * 在子类中重写该方法，可以在类初始化的时候进行一些操作
     */
    protected function initialize(): void
    {

    }

    /*
     * 如果需要用到keyMap  请在子类重构并返回对应的map数据
     * return ['beanKey'=>'dataKey']
     * return ['实际的键名'=>'传人的键名']
     */
    protected function setKeyMapping(): array
    {
        return [];
    }

    /*
     * return ['property'=>class string]
     */
    protected function setClassMapping(): array
    {
        return [];
    }

    /*
     * 恢复到属性定义的默认值
     */
    public function restore(array $data = [], $autoCreateProperty = false)
    {
        $this->clear();
        $this->arrayToBean($data + get_class_vars(static::class), $autoCreateProperty);
        $this->initialize();
        $this->classMap();
        return $this;
    }

    private function clear()
    {
        $keys = $this->allProperty();
        $ref = new \ReflectionClass(static::class);
        $fields = array_keys($ref->getDefaultProperties());
        $fields = array_merge($fields, array_values($this->setKeyMapping()));
        // 多余的key
        $extra = array_diff($keys, $fields);

        foreach ($extra as $key => $value) {
            unset($this->$value);
        }
    }

    private function classMap()
    {
        $propertyList = $this->allProperty();
        foreach ($this->setClassMapping() as $property => $class) {
            if (in_array($property, $propertyList)) {
                $val = $this->$property;
                $force = true;
                if (strpos($class, '@') !== false) {
                    $force = false;
                    $class = substr($class, 1);
                }
                if (is_object($val)) {
                    if (!$val instanceof $class) {
                        throw new Exception("value for property:{$property} dot not match in " . (static::class));
                    }
                } else if ($val === null) {
                    if ($force) {
                        $this->$property = $this->createClass($class);
                    }
                } else {
                    $this->$property = $this->createClass($class, $val);
                }
            } else {
                throw new Exception("property:{$property} not exist in " . (static::class));
            }
        }
    }

    /**
     * @param string $class
     * @param null   $arg
     * @return object
     * @throws \ReflectionException
     */
    private function createClass(string $class, $arg = null)
    {
        $ref = new \ReflectionClass($class);
        return $ref->newInstance($arg);
    }

    /**
     * beanKeyMap
     * 将Bean的属性名转化为data数据键名
     *
     * @param array $array
     * @return array
     */
    final private function beanKeyMap(array $array): array
    {
        foreach ($this->setKeyMapping() as $dataKey => $beanKey) {
            if (array_key_exists($beanKey, $array)) {
                $array[$dataKey] = $array[$beanKey];
                unset($array[$beanKey]);
            }
        }
        return $array;
    }

    /**
     * dataKeyMap
     * 将data中的键名 转化为Bean的属性名
     *
     * @param array $array
     * @return array
     */
    final private function dataKeyMap(array $array): array
    {
        foreach ($this->setKeyMapping() as $dataKey => $beanKey) {
            if (array_key_exists($dataKey, $array)) {
                $array[$beanKey] = $array[$dataKey];
                unset($array[$dataKey]);
            }
        }
        return $array;
    }
}
