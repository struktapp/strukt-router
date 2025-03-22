<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SymfonySessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
abstract class AbstractSession implements SessionInterface, SymfonySessionInterface{

    /**
     * @param string $name
     * @param mixed $default
     * 
     * @return mixed
     */
	public function get(string $name, mixed $default = null):mixed{

		return $this->bag[$name];
	}

    /**
     * @param string $name
     * @param mixed $value
     * 
     * @return void
     */
	public function set(string $name, mixed $value):void{

		$this->bag[$name] = $value;
	}

    /**
     * @return bool
     */
	public function start():bool{

		return true;
	}

    /**
     * @param string $name
     * 
     * @return bool
     */
	public function has(string $name):bool{

		return array_key_exists($name, $this->bag);
	}

    /**
     * @return string
     */
    public function getId():string{

    	return "";
    }

    /**
     * @param $id
     * 
     * @return void
     */
    public function setId(string $id):void{

    	//
    }

    /**
     * @return string
     */
    public function getName():string{

    	return "";
    }

    /**
     * @param string $name
     * 
     * @return void
     */
    public function setName(string $name):void{

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
    public function invalidate(?int $lifetime = null){

    	return true;
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     * 
     * @param bool $destroy
     * @param int|null $lifetime
     * 
     * @return bool
     */
    public function migrate(bool $destroy = false, ?int $lifetime = null): bool{;

    	return true;
    }

    /**
     * @return void
     */
    public function save():void{

    	//
    }

    /**
     * @return array
     */
    public function all():array{

    	return $this->bag;
    }

    /**
     * @param array $attributes
     * 
     * @return void
     */
    public function replace(array $attributes):void{

    	//
    }

    /**
     * @param $name
     * 
     * @return mixed
     */
    public function remove(/*string*/ $name):mixed{

    	$val = $this->bag[$name];
    	unset($this->bag);

    	return $val;

    }

    /**
     * @return void
     */
    public function clear():void{

    	//
    }

    /**
     * @return bool
     */
    public function isStarted():bool{

    	return true;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag
     * 
     * @return void
     */
    public function registerBag(SessionBagInterface $bag):void{

    	//
    }

    /**
     * @param $name
     * 
     * @return \Symfony\Component\HttpFoundation\Session\SessionBagInterface
     */
    public function getBag(/*string*/ $name):SessionBagInterface{

    	return null;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Storage\MetadataBag
     */
    public function getMetadataBag():MetadataBag{

    	return null;
    }
}