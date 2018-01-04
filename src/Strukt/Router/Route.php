<?php

namespace Strukt\Router;

use Strukt\Event\Single;

class Route{

	private $matcher;
	private $callable;
	private $params;
	private $event;

	public function __construct($tpl_url, \Closure $callable){

		$this->matcher = new Matcher($tpl_url);

		$this->event = Single::newEvent($callable);
	}

	public function isMatch($url){

		return $this->matcher->isMatch($url);
	}

	public function getEvent(){

		return $this->event->getEvent();
	}

	public function setParam($name, $param){

		$this->params[$name] = $param;

		return $this;
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

		// print_r($params);

		if(!empty($params))
			$response = $this->event->getEvent()->applyArgs($params)->exec();
		else
			$response = $this->event->exec();

		return $response;
	}
}