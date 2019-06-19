<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/29
 * Time: 下午4:00
 */

namespace EasySwoole\Http\AbstractInterface;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;


abstract class AbstractRouter
{
    private $routeCollector;
    private $methodNotAllowCallBack = null;
    private $routerNotFoundCallBack = null;
    private $globalMode = false;
    final function __construct()
    {
        $this->routeCollector = new RouteCollector(new Std(),new GroupCountBased());
        $this->initialize($this->routeCollector);
    }

    abstract function initialize(RouteCollector $routeCollector);

    function getRouteCollector():RouteCollector
    {
        return $this->routeCollector;
    }


    function setMethodNotAllowCallBack(callable $call)
    {
        $this->methodNotAllowCallBack = $call;
    }

    function getMethodNotAllowCallBack()
    {
        return $this->methodNotAllowCallBack;
    }

    /**
     * @return null
     */
    public function getRouterNotFoundCallBack()
    {
        return $this->routerNotFoundCallBack;
    }

    /**
     * @param null $routerNotFoundCallBack
     */
    public function setRouterNotFoundCallBack($routerNotFoundCallBack): void
    {
        $this->routerNotFoundCallBack = $routerNotFoundCallBack;
    }

    /**
     * @return bool
     */
    public function isGlobalMode(): bool
    {
        return $this->globalMode;
    }

    /**
     * @param bool $globalMode
     * @return void
     */
    public function setGlobalMode(bool $globalMode): void
    {
        $this->globalMode = $globalMode;
    }
}