<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午2:56
 */

namespace EasySwoole\Http;


use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Exception\ControllerPoolEmpty;
use EasySwoole\Http\Exception\RouterError;
use EasySwoole\Http\Message\Status;
use Swoole\Coroutine as Co;
use FastRoute\Dispatcher\GroupCountBased;

class Dispatcher
{
    private $router = null;
    private $routerRegister = null;
    private $controllerNameSpacePrefix;
    private $maxDepth;
    private $maxPoolNum;
    private $controllerPoolCreateNum = [];
    private $httpExceptionHandler = null;
    private $controllerPoolWaitTime = 5.0;

    function __construct(string $controllerNameSpace,int $maxDepth = 5,int $maxPoolNum = 200)
    {
        $this->controllerNameSpacePrefix = trim($controllerNameSpace,'\\');
        $this->maxPoolNum = $maxPoolNum;
        $this->maxDepth = $maxDepth;
    }

    /**
     * @param float $controllerPoolWaitTime
     */
    public function setControllerPoolWaitTime(float $controllerPoolWaitTime): void
    {
        $this->controllerPoolWaitTime = $controllerPoolWaitTime;
    }


    function setHttpExceptionHandler(callable $handler):void
    {
        $this->httpExceptionHandler = $handler;
    }

    public function dispatch(Request $request,Response $response):void
    {
        /*
         * 进行一次初始化判定
         */
        if($this->router === null){
            $class = $this->controllerNameSpacePrefix.'\\Router';
            try{
                if(class_exists($class)){
                    $ref = new \ReflectionClass($class);
                    if($ref->isSubclassOf(AbstractRouter::class)){
                        $this->routerRegister =  $ref->newInstance();
                        $this->router = new GroupCountBased($this->routerRegister->getRouteCollector()->getData());
                    }else{
                        $this->router = false;
                        throw new RouterError("class : {$class} not AbstractRouter class");
                    }
                }else{
                    $this->router = false;
                }
            }catch (\Throwable $throwable){
                $this->router = false;
                throw new RouterError($throwable->getMessage());
            }
        }
        $path = UrlParser::pathInfo($request->getUri()->getPath());
        if($this->router instanceof GroupCountBased){
            $handler = null;
            $routeInfo = $this->router->dispatch($request->getMethod(),$request->getUri()->getPath());
            if($routeInfo !== false){
                switch ($routeInfo[0]) {
                    case \FastRoute\Dispatcher::NOT_FOUND:{
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                    }
                    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:{
                        $handler = $this->routerRegister->getMethodNotAllowCallBack();
                        break;
                    }
                    case \FastRoute\Dispatcher::FOUND:{
                        $handler = $routeInfo[1];
                        //合并解析出来的数据
                        $vars = $routeInfo[2];
                        $data = $request->getQueryParams();
                        $request->withQueryParams($vars+$data);
                        break;
                    }
                    default:{
                        $handler = $this->routerRegister->getRouterNotFoundCallBack();
                        break;
                    }
                }
            }
            //如果handler不为null，那么说明，非为 \FastRoute\Dispatcher::FOUND ，因此执行
            if(is_callable($handler)){
                try{
                    //若直接返回一个url path
                    $ret = call_user_func($handler,$request,$response);
                    if(is_string($ret)){
                        $path = UrlParser::pathInfo($ret);
                    }else if($ret == false){
                        return;
                    }else{
                        //可能在回调中重写了URL PATH
                        $path = UrlParser::pathInfo($request->getUri()->getPath());
                    }
                    $request->getUri()->withPath($path);
                }catch (\Throwable $throwable){
                    $this->hookThrowable($throwable,$request,$response);
                    //出现异常的时候，不在往下dispatch
                    return;
                }
            }else if(is_string($handler)){
                $path = UrlParser::pathInfo($handler);
                $request->getUri()->withPath($path);
                goto response;
            }
            /*
                * 全局模式的时候，都拦截。非全局模式，否则继续往下
            */
            if($this->routerRegister->isGlobalMode()){
                return;
            }
        }
        response:{
            $this->controllerHandler($request,$response,$path);
        }
    }

    private function controllerHandler(Request $request,Response $response,string $path)
    {
        $pathInfo = ltrim($path,"/");
        $list = explode("/",$pathInfo);
        $actionName = null;
        $finalClass = null;
        $controlMaxDepth = $this->maxDepth;
        $currentDepth = count($list);
        $maxDepth = $currentDepth < $controlMaxDepth ? $currentDepth : $controlMaxDepth;
        while ($maxDepth >= 0){
            $className = '';
            for ($i=0 ;$i<$maxDepth;$i++){
                $className = $className."\\".ucfirst($list[$i] ?: 'Index');//为一级控制器Index服务
            }
            if(class_exists($this->controllerNameSpacePrefix.$className)){
                //尝试获取该class后的actionName
                $actionName = empty($list[$i]) ? 'index' : $list[$i];
                $finalClass = $this->controllerNameSpacePrefix.$className;
                break;
            }else{
                //尝试搜搜index控制器
                $temp = $className."\\Index";
                if(class_exists($this->controllerNameSpacePrefix.$temp)){
                    $finalClass = $this->controllerNameSpacePrefix.$temp;
                    //尝试获取该class后的actionName
                    $actionName = empty($list[$i]) ? 'index' : $list[$i];
                    break;
                }
            }
            $maxDepth--;
        }

        if(!empty($finalClass)){
            try{
                $c = $this->getController($finalClass);
            }catch (\Throwable $throwable){
                $this->hookThrowable($throwable,$request,$response);
                return;
            }
            if($c instanceof Controller){
                try{
                    $path = $c->__hook($actionName,$request,$response);
                    if(is_string($path)){
                        $path = UrlParser::pathInfo($path);
                        $request->getUri()->withPath($path);
                        $this->dispatch($request,$response);
                    }
                }catch (\Throwable $throwable){
                    $this->hookThrowable($throwable,$request,$response);
                }finally {
                    $this->recycleController($finalClass,$c);
                }
            }else{
                $throwable = new ControllerPoolEmpty('controller pool empty for '.$finalClass);
                $this->hookThrowable($throwable,$request,$response);
            }
        }else{
            if(in_array($request->getUri()->getPath(),['/','/index.html'])){
                $content = file_get_contents(__DIR__.'/Static/welcome.html');
            }else{
                $response->withStatus(Status::CODE_NOT_FOUND);
                $content = file_get_contents(__DIR__.'/Static/404.html');
            }
            $response->write($content);
        }
    }

    protected function getController(string $class)
    {
        $classKey = $this->generateClassKey($class);
        if(!isset($this->$classKey)){
            $this->$classKey = new Co\Channel($this->maxPoolNum+1);
            $this->controllerPoolCreateNum[$classKey] = 0;
        }
        $channel = $this->$classKey;
        //懒惰创建模式
        /** @var Co\Channel $channel */
        if($channel->isEmpty()){
            $createNum = $this->controllerPoolCreateNum[$classKey];
            if($createNum < $this->maxPoolNum){
                $this->controllerPoolCreateNum[$classKey] = $createNum+1;
                try{
                    //防止用户在控制器结构函数做了什么东西导致异常
                    return new $class();
                }catch (\Throwable $exception){
                    $this->controllerPoolCreateNum[$classKey] = $createNum;
                    //直接抛给上层
                    throw $exception;
                }
            }
            return $channel->pop($this->controllerPoolWaitTime);
        }
        return $channel->pop($this->controllerPoolWaitTime);
    }

    protected function recycleController(string $class,Controller $obj)
    {
        $classKey = $this->generateClassKey($class);
        /** @var Co\Channel $channel */
        $channel = $this->$classKey;
        $channel->push($obj);
    }

    protected function hookThrowable(\Throwable $throwable,Request $request,Response $response)
    {
        if(is_callable($this->httpExceptionHandler)){
            call_user_func($this->httpExceptionHandler,$throwable,$request,$response);
        }else{
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);
            $response->write(nl2br($throwable->getMessage()."\n".$throwable->getTraceAsString()));
        }
    }

    protected function generateClassKey(string $class):string
    {
        return substr(md5($class), 8, 16);
    }
}