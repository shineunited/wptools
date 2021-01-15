<?php

namespace ShineUnited\WordPressTools\Command;

use ShineUnited\WordPressTools\Filesystem\Filesystem;
use ShineUnited\WordPressTools\Filesystem\ComposerFile;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Command\RequireCommand as BaseCommand;
use Composer\Repository\ComposerRepository;
use Composer\Repository\PackageRepository;
use Composer\Repository\FilterRepository;



class RequireCommand extends BaseCommand {
	use FixPackagesInputTrait;

	protected function configure() {
		parent::configure();
		$this->setName('wp-require');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->fixPackagesInput($input, $output);

		$io = $this->getIO();


		$packages = $input->getArgument('packages');
		$packages = array_values($packages);

		$needsWPackagist = false;
		$needsKinsta = false;

		if(in_array('kinsta/kinsta-mu-plugins', $packages)) {
			$io->write('<info>One or more packages require the Kinsta repository</info>', true, IOInterface::VERBOSE);
			$needsKinsta = true;
		}

		foreach($packages as $packageName) {
			if(substr($packageName, 0, 11) == 'wpackagist-') {
				$io->write('<info>One or more packages require the WPackagist repository</info>', true, IOInterface::VERBOSE);
				$needsWPackagist = true;
				break;
			}
		}

		if($needsKinsta || $needsWPackagist) {
			$composer = $this->getComposer();
			$repositoryManager = $composer->getRepositoryManager();

			$hasWPackagist = false;
			$hasKinsta = false;

			foreach($repositoryManager->getRepositories() as $repository) {
				if($repository instanceof FilterRepository) {
					$repository = $repository->getRepository();
				}

				if($needsWPackagist && $repository instanceof ComposerRepository) {
					$repoConfig = $repository->getRepoConfig();
					if($repoConfig['url'] == 'https://wpackagist.org') {
						$io->write('<info>WPackagist repository is already installed</info>', true, IOInterface::VERBOSE);
						$needsWPackagist = false;
					}
				}

				if($needsKinsta && $repository instanceof PackageRepository) {
					foreach($repository->getPackages() as $package) {
						if($package->getName() == 'kinsta/kinsta-mu-plugins') {
							$io->write('<info>Kinsta repository is already installed</info>', true, IOInterface::VERBOSE);
							$needsKinsta = false;
							break;
						}
					}
				}
			}

			$filesystem = new Filesystem(getcwd());
			$filesystem['composer.json'] = new ComposerFile($filesystem['composer.json']);

			if($needsKinsta) {
				$io->write('<info>Adding Kinsta repository</info>', true, IOInterface::NORMAL);

				$filesystem['composer.json']->addRepository('kinsta', [
					'type' => 'package',
					'package' => [
						'name' => 'kinsta/kinsta-mu-plugins',
						'type' => 'wordpress-muplugin',
						'version' => '2.3.3',
						'require' => [
							'composer/installers' => '~1.0'
						],
						'dist' => [
							'url' => 'https://kinsta.com/kinsta-tools/kinsta-mu-plugins.zip',
							'type' => 'zip'
						]
					]
				]);

				$kinstaRepository = new PackageRepository([
					'package' => [
						'name'    => 'kinsta/kinsta-mu-plugins',
						'type'    => 'wordpress-muplugin',
						'version' => '2.3.3',
						'require' => [
							'composer/installers' => '~1.0'
						],
						'dist'    => [
							'url'  => 'https://kinsta.com/kinsta-tools/kinsta-mu-plugins.zip',
							'type' => 'zip'
						]
					]
				]);

				$repositoryManager->addRepository($kinstaRepository);
			}

			if($needsWPackagist) {
				$io->write('<info>Adding WPackagist repository</info>', true, IOInterface::NORMAL);

				$filesystem['composer.json']->addRepository('wpackagist', [
					'type' => 'composer',
					'url'  => 'https://wpackagist.org',
					'only' => [
						'wpackagist-plugin/*',
						'wpackagist-theme/*'
					]
				]);

				$config = $composer->getConfig();
				$httpDownloader = Factory::createHttpDownloader($io, $config);
				$eventDispatcher = $composer->getEventDispatcher();

				$wpackagistRepository = new ComposerRepository([
					'url' => 'https://wpackagist.org'
				], $io, $config, $httpDownloader, $eventDispatcher);

				$repositoryManager->addRepository($wpackagistRepository);
			}

			$filesystem->save();
		}

		return parent::execute($input, $output);
	}
}
