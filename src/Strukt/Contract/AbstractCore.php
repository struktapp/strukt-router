<?php

namespace Strukt\Contract;

use Strukt\Core\Registry;

abstract class AbstractCore{

	protected function core(){

		return Registry::getInstance();
	}
}