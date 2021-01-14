<?php

namespace ShineUnited\WordPressTools\Config;

use Symfony\Component\Filesystem\Filesystem;


class ConfigValidator {
	private $filesystem;
	private $rules;

	public function __construct() {
		$this->filesystem = new Filesystem();

		$this->rules = array();

		$this->addRule('webroot', 'working-dir');
		$this->addRule('home-dir', 'webroot');
		$this->addRule('config-dir', 'working-dir', 'webroot');
		$this->addRule('install-dir', 'home-dir');
		$this->addRule('content-dir', 'home-dir', 'install-dir');
	}

	private function addRule($pathName, $mustBeInsideDirs = array(), $mustBeOutsideDirs = array()) {
		$inside = array();
		if(is_array($mustBeInsideDirs)) {
			$inside = $mustBeInsideDirs;
		} elseif(is_string($mustBeInsideDirs)) {
			$inside[] = $mustBeInsideDirs;
		} else {
			// error?
		}

		$outside = array();
		if(is_array($mustBeOutsideDirs)) {
			$outside = $mustBeOutsideDirs;
		} elseif(is_string($mustBeOutsideDirs)) {
			$outside[] = $mustBeOutsideDirs;
		} else {
			// error?
		}

		$this->rules[] = array(
			'name'    => $pathName,
			'inside'  => $inside,
			'outside' => $outside
		);
	}

	public function validate(Config $config) {
		foreach($this->rules as $rule) {
			$absolutePath = $config->getPath($rule['name'], false);

			// check inside
			foreach($rule['inside'] as $insidePathName) {
				$insidePath = $config->getPath($insidePathName, false);
				if(!$this->isPathContained($absolutePath, $insidePath)) {
					print '[' . $rule['name'] . '] (' . $absolutePath . ')' . "\n";
					print '[' . $insidePathName . '] (' . $insidePath . ')' . "\n";
					throw new \Exception('Path "' . $rule['name'] . '" must be contained by "' . $insidePathName . '"');
				}
			}

			// check outside
			foreach($rule['outside'] as $outsidePathName) {
				$outsidePath = $config->getPath($outsidePathName, false);
				if($this->isPathContained($absolutePath, $outsidePath)) {
					print '[' . $rule['name'] . '] (' . $absolutePath . ')' . "\n";
					print '[' . $outsidePathName . '] (' . $outsidePath . ')' . "\n";
					print_r($config->listPaths()) . "\n";
					throw new \Exception('Path "' . $rule['name'] . '" must not be contained by "' . $outsidePathName . '"');
				}
			}
		}

		return true;
	}

	private function isPathContained($path, $containingPath) {
		$relativePath = $this->filesystem->makePathRelative($path, $containingPath);
		if(substr($relativePath, 0, 3) == '..' . DIRECTORY_SEPARATOR) {
			return false;
		}

		return true;
	}
}
