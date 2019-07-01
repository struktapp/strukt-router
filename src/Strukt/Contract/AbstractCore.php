<?php

namespace Strukt\Contract;

use Strukt\Core\Registry;

abstract class AbstractCore{

	public function core(){

		return Registry::getInstance();
	}
}