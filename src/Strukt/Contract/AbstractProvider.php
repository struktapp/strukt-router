<?php

namespace Strukt\Contract;

use Strukt\Core\Registry;

abstract class AbstractProvider{

	protected $registry;

	public function core(){

		return Registry::getInstance();
	}
}