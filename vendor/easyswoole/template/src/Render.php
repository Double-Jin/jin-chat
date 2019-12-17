<?php


namespace EasySwoole\Template;




use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Component\Singleton;

class Render
{
    use Singleton;

    protected $config;
    private $worker = [];

    function __construct()
    {
        $this->config = new Config();
    }

    public function getConfig():Config
    {
        return $this->config;
    }

    function attachServer(\swoole_server $server)
    {
        $list = $this->generateProcessList();
        foreach ($list as $p){
            $server->addProcess($p->getProcess());
        }
    }

    function render(string $template,array $data = [],array $options = []):?string
    {
        /*
         * 随机找一个进程
         */
        mt_srand();
        $id = rand(1,$this->config->getWorkerNum());
        $sockFile = $this->config->getTempDir()."/Render.{$this->config->getSocketPrefix()}Worker.{$id}.sock";
        $client = new UnixClient($sockFile);
        $client->send(Protocol::pack(serialize([
            'template'=>$template,
            'data'=>$data,
            'options'=>$options
        ])));
        $data = $client->recv($this->config->getTimeout());
        if($data){
            $data = Protocol::unpack($data);
            return unserialize($data);
        }
        return null;
    }

    function restartWorker()
    {
        /** @var AbstractProcess $process */
        foreach ($this->worker as $process){
            $process->getProcess()->write('shutdown');
        }
    }

    protected function generateProcessList():array
    {
        $array = [];
        for ($i = 1;$i <= $this->config->getWorkerNum();$i++){
            $config = new RenderProcessConfig();
            $config->setProcessName("Render.{$this->config->getSocketPrefix()}Worker.{$i}");
            $config->setSocketFile($this->config->getTempDir()."/Render.{$this->config->getSocketPrefix()}Worker.{$i}.sock");
            $config->setRender($this->config->getRender());
            $config->setAsyncCallback(false);
            $array[$i] = new RenderProcess($config);
            $this->worker[$i] = $array[$i];
        }
        return $array;
    }
}