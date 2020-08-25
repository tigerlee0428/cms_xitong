<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Socket extends Command
{
    protected function configure()
    {
        $this->setName('Socket')->setDescription('This is a Socket Server!');
        //$this->addArgument('name', 1); //必传参数
    }

    protected function execute(Input $input, Output $output)
    {
        $address = "127.0.0.1";
        $port = 3000;
        $websocket =  new \fast\WebSocket($address,$port);      
    }
    
}