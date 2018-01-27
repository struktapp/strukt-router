<?php

namespace Strukt\Router;

/**
 * UrlMatcher class.
 *
 * @author Moderator <pitsolu@gmail.com>
 */
class Matcher{

	/**
	* Url pattern
	*
	* @var string 
	*/
	private $pattern;

	/**
	* Url parameters
	*
	* @var array 
	*/
	private $params;

	/**
     * Constructor
     *
     * @param string $pattern url pattern
     */
	public function __construct($pattern){

		$this->pattern = trim($pattern);

		$this->params = [];
	}

	/**
     * Match url to specific url pattern
     *
     * @param string $url route
     *
     * @return boolean
     */
	public function isMatch($url){

		$url = trim($url);
		if($url == $this->pattern)
			return true;

		$parts = explode("/", trim($url, "/"));
		$pattern = explode("/", trim($this->pattern, "/"));

		if(count($parts) != count($pattern))
			return false;

		// print_r($pattern);

		$regex = array();
		foreach($pattern as $key=>$url_item){

			if(preg_match_all("|{(.*):(.*)}|", $url_item, $matches)){

				if(in_array(reset($matches[2]),  array("int", "bool", "alpha", "float", "date"))){

					switch(reset($matches[2])){

						case "int":
							$regex[] = "[0-9]+";
						break;
						case "bool":
							$regex[] = "(true|false)";
						break;
						case "alpha":
							$regex[] = "[A-Za-z]+";
						break;
						case "float":
							$regex[] = "[+-]?\d+(\.\d+)?";
						break;
						case "date"://yyyy-mm-dd
							$regex[] = "(19?[0-9]{2}|20[0-1][0-4])-(0?[1-9]|1[0-2])-([0-2]?[0-9]|3[0-1])";
						break;
					}

					$this->params[reset($matches[1])] = $parts[$key];
				}
			}
			elseif(preg_match_all("|{(.*)}|", $url_item, $matches)){

				$regex[] = ".*";
				$this->params[reset($matches[1])] = $parts[$key];
			}
			else $regex[] = $url_item;
		}

		return (bool)preg_match(sprintf("/^%s$/", implode("\/", $regex)), trim($url, "/"));
	}

	/**
     * get url params
     *
     * @return array
     */
	public function getParams(){

		return $this->params;
	}
}