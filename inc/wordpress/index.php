<?php

/* Start WordPress */
require(__DIR__ . '/{{ path('install-dir', 'home-dir') }}/index.php');

if(!defined('WEBROOT')) {
	define('WEBROOT', __DIR__);
}
