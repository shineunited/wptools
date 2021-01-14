<?php

namespace ShineUnited\WordPressTools\Config;

use Composer\Composer;
use Symfony\Component\Filesystem\Filesystem;


class Config {
	private $filesystem;
	private $composer;

	private $pathNames;
	private $pathAliases;

	private $configPaths;

	public function __construct(Composer $composer) {
		$this->filesystem = new Filesystem();
		$this->composer = $composer;

		$this->pathNames = array(
			'webroot',
			'home-dir',
			'config-dir',
			'install-dir',
			'content-dir'
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

	private function getComposerPackage() {
		return $this->composer->getPackage();
	}

	private function getComposerExtra() {
		return $this->getComposerPackage()->getExtra();
	}

	private function getComposerConfig() {
		return $this->composer->getConfig();
	}

	private function getWPToolsConfig() {
		$extra = $this->getComposerExtra();

		if(!isset($extra['wptools']) || !is_array($extra['wptools'])) {
			throw new \Exception('WPTools config not found');
		}

		return $extra['wptools'];
	}

	public function getPackageType() {
		return $this->getComposerPackage()->getType();
	}

	public function getVersion() {
		$config = $this->getWPToolsConfig();

		if(isset($config['version'])) {
			return $config['version'];
		}

		return false;
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
		if(in_array($name, $this->pathNames)) {
			return true;
		}

		if(in_array($name, array_keys($this->pathAliases))) {
			return true;
		}

		return false;
	}

	public function getPath($name, $relativeTo = false) {
		if(!$this->hasPath($name)) {
			throw new \Exception('Unknown path requested "' . $name . '"');
		}

		$targetPath = $this->getWorkingDir() . '/';
		if(in_array($name, $this->pathNames)) {
			if(isset($this->configPaths[$name])) {
				// first check local
				$targetPath .= $this->configPaths[$name];
			} else {
				// check package config
				$config = $this->getWPToolsConfig();

				if(!isset($config['paths']) || !is_array($config['paths']) || !isset($config['paths'][$name])) {
					throw new \Exception('Path not defined "' . $name . '"');
				}

				$targetPath .= $config['paths'][$name];
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
		if(!in_array($name, $this->pathNames)) {
			throw new \Exception('Unknown path name "' . $name . '"');
		}

		if($this->filesystem->isAbsolutePath($path)) {
			$path = $this->filesystem->makePathRelative($path, $this->getWorkingDir());
		}

		$this->configPaths[$name] = rtrim($path, '/');
	}

	public function listPaths() {
		$paths = array();

		foreach($this->pathNames as $name) {
			$paths[$name] = $this->getPath($name, 'working-dir');
		}

		return $paths;
	}
}
