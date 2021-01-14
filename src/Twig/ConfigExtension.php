<?php

namespace ShineUnited\WordPressTools\Twig;

use ShineUnited\WordPressTools\Config\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class ConfigExtension extends AbstractExtension {
	private $config;

	public function __construct(Config $config) {
		$this->config = $config;
	}

	public function getGlobals() {
		return array(
			'config' => $this->config
		);
	}

	public function getFunctions() {
		return array(
			new TwigFunction('path', array($this, 'getPath'))
		);
	}

	public function getPath($name, $relativeTo = false) {
		return $this->config->getPath($name, $relativeTo);
	}
}
