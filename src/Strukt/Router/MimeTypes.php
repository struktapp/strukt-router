<?php

namespace Strukt\Router;

class MimeTypes{

	public static function register(){

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("mimeTypes")){

			$registry->set("mimeTypes", array(

				"png"=>"image/png",
				"gif"=>"image/gif",
				"jpeg"=>"image/jpeg",
				"jpg"=>"image/jpeg",
				"swf"=>"application/x-shockwave-flash",
				"swc"=>"application/x-shockwave-flash",
				"psd"=>"image/psd",
				"bmp"=>"image/bmp",
				"tiff"=>"image/tiff",
				"css"=>"text/css",
				"js"=>"text/js",
				"text"=>"text/plain",
				"json"=>"application/json",
				"html"=>"text/html"
			));
		}
		else{

			$mimeTypes = $registry->get("mimeTypes");

			if(empty($mimeTypes))
				throw new \Exception("Mime types not found in registered registry entry!");
			elseif(!is_array($mimeTypes))
				throw new \Exception("Mime types be an array!");
		}
	}
}