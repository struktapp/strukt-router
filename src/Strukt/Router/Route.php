<?php

namespace Strukt\Router;

use Strukt\Event\Single;

class Route{

	private $matcher;
	private $callable;
	private $params;

	public function __construct($tpl_url, \Closure $callable){

		$this->matcher = new Matcher($tpl_url);

		$this->callable = $callable;
	}

	public function isMatch($url){

		return $this->matcher->isMatch($url);
	}

	public function addParam($param){

		$this->params[] = $param;

		return $this;
	}

	public function getParams(){

		return $this->matcher->getParams();
	}

	public function exec(){

		if(is_null($this->params)){

			$params = $this->matcher->getParams();
		}
		else{

			$params = $this->params;
		}

		$event = Single::newEvent($this->callable);

		if(!empty($params))
			$response = $event->getEvent()->applyArgs($params)->exec();
		else
			$response = $event->exec();

		return $response;
	}
}