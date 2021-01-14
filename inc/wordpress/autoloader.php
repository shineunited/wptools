<?php

use Roots\Bedrock\Autoloader;

if(is_blog_installed()) {
	new Autoloader();
}
