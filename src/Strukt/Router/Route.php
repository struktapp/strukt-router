<?php 

namespace Strukt\Router;

use Strukt\Event;

class Route{

	private $method;
	private $pattern;
	private $params;
	private $event;
	private $name;

	public function __construct($pattern, \Closure $callable, $method = "GET", $name = null){

		$this->method = $method;

		$this->pattern = $pattern;

		$this->name = $name;

		$this->params = [];

		$this->event = Event::create($callable);
	}

	public function getName(){

		return $this->name;
	}

	public function getMethod(){

		return $this->method;
	}

	public function getPattern(){

		return $this->pattern;
	}

	public function getEvent(){

		return $this->event;
	}

	public function mergeParams(array $params){

		$this->params = array_merge($params, $this->params);	

		return $this;
	}

	public function setParam($name, $param){

		$this->params[$name] = $param;

		return $this;
	}

	public function addParam($param){

		$this->params[] = $param;

		return $this;
	}

	public function exec(){

		if(!empty($this->params))
			$response = $this->event->applyArgs($this->params)->exec();
		else
			$response = $this->event->exec();

		return $response;
	}
}