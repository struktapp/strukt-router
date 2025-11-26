<?php

namespace Strukt\Router;

class UrlMatcher{

	private $patterns;
	private $params;

	public function __construct(array $patterns){

		$this->patterns = $patterns;
		$this->params = [];
	}

	public function get(string $url){

		if(trim($url)=="/")
			return "/";

		foreach($this->patterns as $pattern)
			if($this->isMatch($url, $pattern))
				return $pattern;

		return null;
	}

	public function getParams(){

		return $this->params;
	}

	public function isMatch(string $url, string $pattern){

		$fn["duo-lookup"] = fn($splits, $idx)=>[$splits[0][$idx], $splits[1][$idx]];
		$fn["slash-explode"] = fn($path)=>arr(str($path)->split("/"))->filter()->values()->yield();
		$fn["type-checker"] = fn($type, $value)=>preg_match(sprintf("/^%s$/", [

			"int"=>"[0-9]+",
			"bool"=>"(true|false)",
			"alpha"=>"[A-Za-z]+",
			"alphanum"=>"[A-Za-z0-9_-]+",
			"float"=>"[+-]?\d+(\.\d+)?",
			"date"=>"(19?[0-9]{2}|20[0-1][0-4])-(0?[1-9]|1[0-2])-([0-2]?[0-9]|3[0-1])"//yyyy-mm-dd

		][$type]), $value);

		$ls_url = $fn["slash-explode"]($url);
		$ls_parts = $fn["slash-explode"]($pattern);
		if(count($ls_url) != count($ls_parts))
			return false;

		$idx = 0;
		$valid = [];
		$params = [];
		while($idx<=count($ls_url)-1){

			list($ppart, $purl) = $fn["duo-lookup"]([$ls_parts, $ls_url], $idx++);
			if(str($ppart)->notEquals($purl)){

				$type = null;
				if(preg_match("|\{\w+:\w+\}|", $ppart)){

					list($name, $type) = str($ppart)->replace(["{","}"], "")->split(":");
					$valid[] = $fn["type-checker"]($type, $purl);
				}
				elseif(preg_match("|\{\w+\}|", $ppart)){

					$name = str($ppart)->replace(["{","}"], "")->yield();
					$valid[] = 1;
				}
				else $valid[] = 0;

				@$params[$name] = $purl;
			}

			if($ppart == $purl)
				$valid[] = 1;
		}

		if(empty($valid))
			$valid[] = 0;

		$valid = arr($valid)->product();
		if($valid)
			$this->params = $params;

		return $valid;
	}
}