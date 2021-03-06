<?php

namespace Meister\Meister;

use Meister\Meister\Interfaces\DatabaseInterface;
use Meister\Meister\Libraries\Data;
use Meister\Meister\Libraries\Retorno;
use Meister\Meister\Libraries\Session;
use Pimple\Container;

class Controller {

    public $app;

    public $db;

    public $config;
    
    public $session;

    public function __construct(Container $app, array $config, DatabaseInterface $db, Session $session){
        $this->app     = $app;
        $this->db      = $db;
        $this->session = $session;
        $this->config  = $config;
    }

    protected function Render($data = []){

        $retorno = new Retorno($this->app,$this->config);

        if($this->app['api']){
            $retorno->jsonRPC($data);
        }

        $options = $this->app['options'];
        if(array_key_exists('notview',$options) && $options['notview']){
            exit();
        }

        $retorno->twig($data);
    }

    protected function getConfig($conf=null){
        if($conf){
            if(array_key_exists($conf,$this->config)){
                return $this->config[$conf];
            }

            return null;
        }

        return $this->config;
    }

    protected function data($data){
        return Data::serialize($data);
    }

    protected function request($val = null){

        if($val){
            $data = $this->app['data'];
            if(array_key_exists($val,$data)){
                return $data[$val];
            }

            return null;
        }

        return $this->app['data'];
    }
}