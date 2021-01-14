<?php

namespace ShineUnited\WordPressTools;

use ShineUnited\WordPressTools\Config\Config;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

use Composer\Repository\RepositoryFactory;

class Plugin implements PluginInterface, Capable {

	public function activate(Composer $composer, IOInterface $io) {

		$this->configureInstallerPaths($composer);
	}

	public function deactivate(Composer $composer, IOInterface $io) {
		// do nothing
	}

	public function uninstall(Composer $composer, IOInterface $io) {
		// do nothing
	}

	public function getCapabilities() {
		return array(
			'Composer\Plugin\Capability\CommandProvider' => 'ShineUnited\WordPressTools\Command\CommandProvider'
		);
	}

	private function configureInstallerPaths(Composer $composer) {
		$config = new Config($composer);

		$package = $composer->getPackage();
		$extra = $package->getExtra();

		try {
			$installDir = $config->getPath('install-dir', 'working-dir');

			if(!isset($extra['wordpress-install-dir'])) {
				$extra['wordpress-install-dir'] = $installDir;
			}
		} catch(\Exception $e) {
			// skip
		}

		try {
			$contentDir = $config->getPath('content-dir', 'working-dir');

			$wordpressInstallerPaths = array(
				'type:wordpress-muplugin' => $contentDir . '/mu-plugins/{$name}',
				'type:wordpress-plugin'   => $contentDir . '/plugins/{$name}',
				'type:wordpress-theme'    => $contentDir . '/themes/{$name}',
				'type:wordpress-dropin'   => $contentDir . '/{$name}'
			);

			$installerPaths = array();
			if(isset($extra['installer-paths'])) {
				$installerPaths = $extra['installer-paths'];
			}

			foreach($wordpressInstallerPaths as $wordpressPattern => $wordpressPath) {
				foreach($installerPaths as $path => $patterns) {
					if(in_array($wordpressPattern, $patterns)) {
						// pattern already defined, skip
						continue 2;
					}
				}

				$installerPaths[$wordpressPath] = array($wordpressPattern);
			}

			$extra['installer-paths'] = $installerPaths;
		} catch(\Exception $e) {
			// skip
		}

		$package->setExtra($extra);
	}
}
