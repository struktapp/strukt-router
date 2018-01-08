<?php

use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
use Kambo\Http\Message\Response;

use Strukt\Core\Registry;
use Strukt\Event\Single;
use Strukt\Fs;

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");
// $loader->add('Strukt', "../strukt-commons/src");

$registry = Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");