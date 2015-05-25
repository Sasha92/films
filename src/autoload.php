<?php
define('ROOT_DIR', realpath(__DIR__ . '/..'));

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->add('Films', __DIR__);