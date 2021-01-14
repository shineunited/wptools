<?php

namespace ShineUnited\WordPressTools\Command;

use ShineUnited\WordPressTools\Filesystem\Filesystem;
use ShineUnited\WordPressTools\Filesystem\ComposerFile;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\RequireCommand as BaseCommand;

use Composer\Repository\ComposerRepository;
use Composer\Repository\PackageRepository;
use Composer\Repository\FilterRepository;
use Composer\Factory;


class RequireCommand extends BaseCommand {
	use FixPackagesInputTrait;

	protected function configure() {
		parent::configure();
		$this->setName('wp-require');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->fixPackagesInput($input, $output);

		$composer = $this->getComposer();
		$repositoryManager = $composer->getRepositoryManager();

		$hasWPackagist = false;
		$hasKinsta = false;

		foreach($repositoryManager->getRepositories() as $repository) {
			print get_class($repository) . "\n";

			if($repository instanceof FilterRepository) {
				$repository = $repository->getRepository();
			}

			if(!$hasWPackagist && $repository instanceof ComposerRepository) {
				$repoConfig = $repository->getRepoConfig();
				if($repoConfig['url'] == 'https://wpackagist.org') {
					$hasWPackagist = true;
				}
			}

			if(!$hasKinsta && $repository instanceof PackageRepository) {
				foreach($repository->getPackages() as $package) {
					if($package->getName() == 'kinsta/kinsta-mu-plugins') {
						$hasKinsta = true;
						break;
					}
				}
			}
		}

		$packages = $input->getArgument('packages');
		$packages = array_values($packages);

		$needsWPackagist = false;
		$needsKinsta = false;

		if(in_array('kinsta/kinsta-mu-plugins', $packages)) {
			$needsKinsta = true;
		}

		foreach($packages as $packageName) {
			if(substr($packageName, 0, 11) == 'wpackagist-') {
				$needsWPackagist = true;
				break;
			}
		}

		if($needsKinsta || $needsWPackagist) {
			$filesystem = new Filesystem(getcwd());
			$filesystem['composer.json'] = new ComposerFile($filesystem['composer.json']);

			if($needsKinsta) {
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

				print 'Adding Kinsta' . "\n";
				$repositoryManager->addRepository($kinstaRepository);
			}

			if($needsWPackagist) {
				$filesystem['composer.json']->addRepository('wpackagist', [
					'type' => 'composer',
					'url'  => 'https://wpackagist.org',
					'only' => [
						'wpackagist-plugin/*',
						'wpackagist-theme/*'
					]
				]);

				$io = $this->getIO();
				$config = $composer->getConfig();
				$httpDownloader = Factory::createHttpDownloader($io, $config);
				$eventDispatcher = $composer->getEventDispatcher();

				$wpackagistRepository = new ComposerRepository([
					'url' => 'https://wpackagist.org'
				], $io, $config, $httpDownloader, $eventDispatcher);

				print 'Adding WPackagist' . "\n";
				$repositoryManager->addRepository($wpackagistRepository);
			}

			$filesystem->save();
		}


		return parent::execute($input, $output);
	}
}
