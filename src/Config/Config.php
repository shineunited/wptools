<?php

namespace ShineUnited\WordPressTools\Config;

use Composer\Composer;
use Symfony\Component\Filesystem\Filesystem;


class Config {
	private $filesystem;
	private $composer;

	private $pathInfo;
	private $pathAliases;

	private $configPaths;

	public function __construct(Composer $composer) {
		$this->filesystem = new Filesystem();
		$this->composer = $composer;

		$this->pathInfo = array();

		$this->definePath(
			'webroot',
			'Webroot directory path',
			'web'
		);

		$this->definePath(
			'home-dir',
			'WordPress home directory path',
			'web'
		);

		$this->definePath(
			'config-dir',
			'Configuration directory path',
			'cfg'
		);

		$this->definePath(
			'install-dir',
			'WordPress install directory path',
			'web/wp'
		);

		$this->definePath(
			'content-dir',
			'WordPress content directory path',
			'web/app'
		);

		$this->pathAliases = array(
			'working-dir'   => 'getWorkingDir',
			'vendor-dir'    => 'getVendorDir',
			'wpconfig-dir'  => 'getWPConfigDir',
			'muplugins-dir' => 'getMUPluginsDir',
			'plugins-dir'   => 'getPluginsDir',
			'themes-dir'    => 'getThemesDir',
			'uploads-dir'   => 'getUploadsDir',
			'upgrade-dir'   => 'getUpgradeDir'
		);

		$this->configPaths = array();
	}

	protected function definePath($name, $description, $default = null) {
		$this->pathInfo[$name] = array(
			'description' => $description,
			'default'     => $default
		);
	}

	private function getComposerConfig() {
		return $this->composer->getConfig();
	}

	public function getVendorDir() {
		$config = $this->getComposerConfig();

		return $config->get('vendor-dir');
	}

	public function getWorkingDir() {
		return getcwd();
	}

	public function getWPConfigDir() {
		$installDir = $this->getPath('install-dir', false);
		return dirname($installDir);
	}

	public function getMUPluginsDir() {
		$contentDir = $this->getPath('content-dir', false);
		return $contentDir . '/mu-plugins';
	}

	public function getPluginsDir() {
		$contentDir = $this->getPath('content-dir', false);
		return $contentDir . '/plugins';
	}

	public function getThemesDir() {
		$contentDir = $this->getPath('content-dir', false);
		return $contentDir . '/themes';
	}

	public function getUploadsDir() {
		$contentDir = $this->getPath('content-dir', false);
		return $contentDir . '/uploads';
	}

	public function getUpgradeDir() {
		$contentDir = $this->getPath('content-dir', false);
		return $contentDir . '/upgrade';
	}

	public function hasPath($name) {
		if(in_array($name, $this->listPathNames(true))) {
			return true;
		}

		return false;
	}

	public function getPath($name, $relativeTo = false) {
		if(!$this->hasPath($name)) {
			throw new \Exception('Unknown path name "' . $name . '"');
		}

		$targetPath = $this->getWorkingDir() . '/';
		if(isset($this->pathInfo[$name])) {
			if(isset($this->configPaths[$name])) {
				$targetPath .= $this->configPaths[$name];
			} else {
				throw new \Exception('Path not set "' . $name . '"');
			}
		} elseif(in_array($name, array_keys($this->pathAliases))) {
			$functionName = $this->pathAliases[$name];
			$targetPath = call_user_func(array($this, $functionName));
		}

		if(!$relativeTo) {
			return $targetPath;
		}

		if($this->filesystem->isAbsolutePath($relativeTo)) {
			return $this->filesystem->makePathRelative($targetPath, $relativeTo);
		}

		$relativeToPath = $this->getPath($relativeTo, false);
		$path = $this->filesystem->makePathRelative($targetPath, $relativeToPath);

		return rtrim($path, '/');
	}

	public function setPath($name, $path) {
		if(!in_array($name, $this->listPathNames())) {
			throw new \Exception('Unknown path name "' . $name . '"');
		}

		if($this->filesystem->isAbsolutePath($path)) {
			$path = $this->filesystem->makePathRelative($path, $this->getWorkingDir());
		}

		$this->configPaths[$name] = rtrim($path, '/');
	}

	public function listPathNames($all = true) {
		$names = array_keys($this->pathInfo);

		if($all) {
			return array_merge($names, array_keys($this->pathAliases));
		}

		return $names;
	}

	public function getPathDefault($name) {
		if(!isset($this->pathInfo[$name])) {
			throw new \Exception('Unknown path name "' . $name . '"');
		}

		return $this->pathInfo[$name]['default'];
	}

	public function getPathDescription($name) {
		if(!isset($this->pathInfo[$name])) {
			throw new \Exception('Unknown path name "' . $name . '"');
		}

		return $this->pathInfo[$name]['description'];
	}

	public function listPaths() {
		$paths = array();

		foreach(array_keys($this->pathInfo) as $name) {
			$paths[$name] = $this->getPath($name, 'working-dir');
		}

		return $paths;
	}
}
