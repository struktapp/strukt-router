<?php

namespace Strukt\Contract;

use Strukt\Core\Registry;

abstract class AbstractMiddleware{

	protected $registry;

	public function core(){

		return Registry::getInstance();
	}
}