<?php

namespace Meister\Meister;

use Meister\Meister\Interfaces\InitInterface;
use Meister\Meister\Libraries\Annotation;
use Meister\Meister\Libraries\Auth;
use Meister\Meister\Libraries\Mongo;
use Meister\Meister\Libraries\Redis;
use Meister\Meister\Libraries\Retorno;
use Meister\Meister\Libraries\Session;
use Pimple\Container;
use Symfony\Component\Yaml\Yaml;

abstract class init implements InitInterface{

    private $controller;

    private $action;

    private $app;

    private $config;

    private $db;

    private $cache;
    
    private $session;

    private $ambiente;
    
    public function __construct($ambiente = null){
        $this->app          = new Container();
        $this->ambiente     = $ambiente;
    }

    public function Run(){
        try{

            $this->loadConfig();

            $this->app['api']   = false;

            $router = filter_input(INPUT_GET, 'router');

            $this->checkRota($router);

            $ann = new Annotation($this->app,$this->config);
            
            $ann->validation($this->app['Contr'],$this->action);
            
            $action = $this->action;

            $this->controller->$action();

        }catch (\Exception $e){
            $retorno = new Retorno($this->app,$this->config);

            $retorno->twigException($e);
        }
    }

    private function checkRota($router){
        $rotas = $this->getRotas();
        $rota = "";

        foreach($rotas as $we){
            if($we['rota'] == "/{$router}"){
                $rota = $we;
                break;
            }
        }

        if(!$rota){
            throw new \Exception('Router not found',420404);
        }

        if($rota['options'] && $rota['options']['api']){
            $this->app['api'] = true;
        }

        list($modulo,$controller,$action) = explode('::', $rota['destino']);

        $se = (array_search($modulo,$this->config['modules']));
        
        if(is_bool($se) && !$se){
            throw new \Exception('Modulo não registrado');
        }

        $c = 'src\\'.$modulo.'\\Controller\\'.$controller;

        if(!class_exists($c)){
            throw new \Exception("Classe not found ($c)",421404);
        }

        $this->app['Controller']    = $controller;
        $this->app['Action']        = $action;
        $this->app['Module']        = 'src\\'.$modulo;
        $this->app['Contr']         = $c;

        $this->app['ModuleDir']     = str_replace('/web/app.php','',$_SERVER['SCRIPT_FILENAME']).'/src/'.$modulo;

        $this->start();
        
        $this->controller = new $c($this->app,$this->config,$this->db,$this->session);

        if(!method_exists($this->controller,$action)){
            throw new \Exception('Method not found',422404);
        }

        $this->action = $action;

    }

    public function loadConfig(){

        $file = $this->getConfig($this->ambiente);

        if(file_exists($file)){
            $this->config = Yaml::parse(file_get_contents($file));
            return $this;
        }

        throw  new \Exception('Config file not found',420502);

    }

    public function start(){
        $this->app['Modules']       = str_replace('/web/app.php','',$_SERVER['SCRIPT_FILENAME']).'/src/';
        $this->app['BASE_DIR']      = $this->getBaseDir();

        $this->app['cache'] = $this->getCache();

        $this->cache   = $this->Cache();
        $this->db      = $this->newDB();
        $this->session = $this->Session();
        
        $this->app['auth'] = $this->Auth();
    }

    public function newDB(){
        $type = $this->config['database']['type'];
        
        switch ($type){
            case 'mongo':
                $db = new Mongo($this->config,$this->app);
                break;
            default;
                $db = null;
        }
        
        if(!$db){
            throw new \Exception('Type database not found');
        }
        
        return $db;
    }
    
    private function Cache(){
        $type = $this->config['cache']['type'];

        switch ($type){
            case 'redis':
                $cache = new Redis($this->config);
                break;
            default;
                $cache = null;
        }

        if(!$cache){
            throw new \Exception('Type Cache not found');
        }

        return $cache;
    }
    
    private function Session(){
        $session = new Session($this->cache,$this->config["session"]["time"]);
        
        return $session;
    }

    private function Auth(){
        $auth = new Auth($this->app, $this->db, $this->config, $this->session);

        return $auth;

    }
}