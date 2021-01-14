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
		// do nothing
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
}
