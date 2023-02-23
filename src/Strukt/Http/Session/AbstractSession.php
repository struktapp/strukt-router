<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SymfonySessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

abstract class AbstractSession implements SessionInterface, SymfonySessionInterface{

	public function get(/*string*/ $name, $default = null){

		return $this->bag[$name];
	}

	public function set(/*string*/ $name, $value){

		$this->bag[$name] = $value;
	}

	public function start():bool{

		return true;
	}

	public function has($name):bool{

		return array_key_exists($name, $this->bag);
	}

    public function getId():string{

    	return "";
    }

    public function setId(/*string*/ $id):void{

    	//
    }

    public function getName(){

    	return "";
    }

    public function setName(/*string*/ $name):void{

    	//
    }

    /**
     * Invalidates the current session.
     *
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     *
     * @return bool
     */
    public function invalidate($lifetime = null){

    	return true;
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     */
    public function migrate($destroy = false, $lifetime = null):bool{

    	return true;
    }

    public function save():void{

    	//
    }

    public function all():array{

    	return $this->bag;
    }

    public function replace(array $attributes):void{

    	//
    }

    public function remove(/*string*/ $name):mixed{

    	$val = $this->bag[$name];
    	unset($this->bag);

    	return $val;

    }

    public function clear():void{

    	//
    }

    public function isStarted():bool{

    	return true;
    }

    public function registerBag(SessionBagInterface $bag):void{

    	//
    }

    public function getBag(/*string*/ $name):SessionBagInterface{

    	return null;
    }

    public function getMetadataBag():MetadataBag{

    	return null;
    }
}