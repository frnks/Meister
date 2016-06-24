<?php

namespace app;

use Meister\Meister\init;

class AppInit extends init {

    public function getConfig($ambiente = null){
        return __DIR__ . '/config/config'.$ambiente.'.yml';
    }

	public function getCache(){
		return [
			"twig" => __DIR__.'/cache/twig'
		];
	}

    public function getRotas(){
        return [
			"teste::TesteController::indexAction" => "/home"
        ];
    }
}