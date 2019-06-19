<?php

use EasySwoole\Template\Config;
use EasySwoole\Template\Render;
use EasySwoole\Template\RenderInterface;
use EasySwoole\Template\TestCase\Engine\Blade;
use EasySwoole\Template\TestCase\Engine\Think;
use EasySwoole\Template\TestCase\Engine\Plates;
use EasySwoole\Template\TestCase\Engine\Smarty;
use Swoole\Http\Request;
use Swoole\Http\Response;

require_once '../vendor/autoload.php';

// 命令行启动对应的模板引擎服务器
$param_arr = getopt('e:');
if (!isset($param_arr['e'])) {
    echo "\ncurrent support engine: 'blade' 'think' 'plates' 'smarty'\nuse command : 'php testServer.php -e {engineName}' to start choose engine server\n\n";
    exit;
}

// 实例化模板引擎
$name = $param_arr['e'];
$engine = Smarty::class;
$cacheDir = dirname(__FILE__) . '/TemplateCache';
$viewsDir = dirname(__FILE__) . '/TemplateViews';
$engines = ['blade' => Blade::class, 'think' => Think::class, 'smarty' => Smarty::class, 'plates' => Plates::class];
$templates = ['blade' => 'blade', 'think' => 'think', 'smarty' => 'smarty.tpl', 'plates' => 'plates'];
if (array_key_exists($name, $engines)) {
    $engine = $engines[$name];
} else {
    echo "\nnot supported engine: {$name}\n\n";
    exit;
}

/** @var RenderInterface $engine */
$engine = new $engine($viewsDir, $cacheDir);
$config = new Config;
$config->setRender($engine);
$render = new Render($config);

// 启动服务
$http = new swoole_http_server("0.0.0.0", 9501);
$http->on("request", function (Request $request, Response $response) use ($engine, $templates, $name) {
    $response->end($engine->render($templates[$name], [
        'engine' => $name,
        'time' => date('Y-m-d H:i:s')
    ]));
});

$render->attachServer($http);
echo "\ntemplate engine: {$param_arr['e']}\nlisten at: http://0.0.0.0:9501\n";
$http->start();