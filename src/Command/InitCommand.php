<?php

namespace ShineUnited\WordPressTools\Command;

use ShineUnited\WordPressTools\Config\Config;
use ShineUnited\WordPressTools\Config\ConfigValidator;
use ShineUnited\WordPressTools\Twig\ConfigExtension;
use ShineUnited\WordPressTools\Filesystem\Filesystem;
use ShineUnited\WordPressTools\Filesystem\ComposerFile;
use ShineUnited\WordPressTools\Filesystem\GitIgnoreFile;
use ShineUnited\WordPressTools\Filesystem\TemplateFile;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
//use Symfony\Component\Console\Question\Question;
use Composer\Command\BaseCommand;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
//use Symfony\Component\Filesystem\Filesystem;
use Composer\Factory;
//use Composer\Json\JsonManipulator;
//use GitIgnoreWriter\GitIgnoreWriter;

class InitCommand extends BaseCommand {
	private $config;

	protected function configure() {
		parent::configure();
		$this->setName('wp-init');

		$requireCommand = new RequireCommand();
		$requireDefinition = $requireCommand->getDefinition();

		$definition = $this->getDefinition();
		if($requireDefinition->hasOption('fixed')) {
			$definition->addOption($requireDefinition->getOption('fixed'));
		}

		$definition->addOption($requireDefinition->getOption('dry-run'));
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$io = $this->getIO();
		$config = $this->getConfig();

		foreach($config->listPathNames(false) as $pathName) {
			$default = $config->getPathDefault($pathName);

			$questionParts = array();
			$questionParts[] = '<comment>';
			$questionParts[] = $config->getPathDescription($pathName);
			if(!is_null($default)) {
				$questionParts[] = ' <info>(';
				$questionParts[] = $default;
				$questionParts[] = ')';
			}
			$questionParts[] = '</info>: ';

			$question = implode('', $questionParts);

			$result = $io->ask($question, $default);

			$config->setPath($pathName, $result);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config = $this->getConfig();

		$validator = new ConfigValidator();
		$validator->validate($config);

		// setup twig environment
		$incPath = dirname(dirname(__DIR__)) . '/inc';
		$loader = new FilesystemLoader($incPath);
		$twig = new Environment($loader);
		$twig->addExtension(new ConfigExtension($config));

		// setup filesystem
		$filesystem = new Filesystem($config->getPath('working-dir'));

		if(isset($filesystem['.gitignore'])) {
			$filesystem['.gitignore'] = new GitIgnoreFile($filesystem['.gitignore']);
		} else {
			$filesystem['.gitignore'] = new GitIgnoreFile();
		}


		// wordpress
		$wpUpgradePath = $config->getPath('upgrade-dir', 'working-dir');
		$wpContentPath = $config->getPath('content-dir', 'working-dir');
		$wpInstallPath = $config->getPath('install-dir', 'working-dir');

		if(!$filesystem['.gitignore']->exists($wpUpgradePath) || !$filesystem['.gitignore']->exists($wpInstallPath)) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# wordpress');
		}

		if(!$filesystem['.gitignore']->exists($wpInstallPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after('# wordpress', $wpInstallPath);
		}

		if(!$filesystem['.gitignore']->exists($wpUpgradePath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after('# wordpress', $wpUpgradePath);
		}


		// {webroot}/index.php
		$webrootIndexPath = $config->getPath('home-dir', 'working-dir') . '/index.php';
		$filesystem[$webrootIndexPath] = new TemplateFile($twig, 'wordpress/index.php');

		// {webroot}/status.php
		$statusPath = $config->getPath('home-dir', 'working-dir') . '/status.php';
		$filesystem[$statusPath] = new TemplateFile($twig, 'wordpress/status.php');

		// {webroot}/.htaccess
		$htaccessPath = $config->getPath('home-dir', 'working-dir') . '/.htaccess';
		$filesystem[$htaccessPath] = new TemplateFile($twig, 'wordpress/htaccess');

		// {wpconfig-dir}/wp-config.php
		$wpconfigPath = $config->getPath('wpconfig-dir', 'working-dir') . '/wp-config.php';
		$filesystem[$wpconfigPath] = new TemplateFile($twig, 'wordpress/wp-config.php');


		// mu-plugins
		$mupluginsPath = $config->getPath('muplugins-dir', 'working-dir');

		// {muplugins-dir}/autoloader.php
		$mupluginsAutoloaderPath = $mupluginsPath . '/autoloader.php';
		$filesystem[$mupluginsAutoloaderPath] = new TemplateFile($twig, 'wordpress/autoloader.php');

		// {muplugins-dir}/index.php
		$mupluginsIndexPath = $mupluginsPath . '/index.php';
		$filesystem[$mupluginsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');


		if(!$filesystem['.gitignore']->exists($mupluginsPath . '/*')) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# mu-plugins');
			$filesystem['.gitignore']->add($mupluginsPath . '/*');
		}

		if(!$filesystem['.gitignore']->exists('!' . $mupluginsAutoloaderPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after($mupluginsPath . '/*', '!' . $mupluginsAutoloaderPath);
		}

		if(!$filesystem['.gitignore']->exists('!' . $mupluginsIndexPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after($mupluginsPath . '/*', '!' . $mupluginsIndexPath);
		}


		// plugins
		$pluginsPath = $config->getPath('plugins-dir', 'working-dir');

		// {plugins-dir}/index.php
		$pluginsIndexPath = $pluginsPath . '/index.php';
		$filesystem[$pluginsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		if(!$filesystem['.gitignore']->exists($pluginsPath . '/*')) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# plugins');
			$filesystem['.gitignore']->add($pluginsPath . '/*');
		}

		if(!$filesystem['.gitignore']->exists('!' . $pluginsIndexPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after($pluginsPath . '/*', '!' . $pluginsIndexPath);
		}


		// themes
		$themesPath = $config->getPath('themes-dir', 'working-dir');

		// {themes-dir}/index.php
		$themesIndexPath = $themesPath . '/index.php';
		$filesystem[$themesIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');


		if(!$filesystem['.gitignore']->exists($themesPath . '/*')) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# themes');
			$filesystem['.gitignore']->add($themesPath . '/*');
		}

		if(!$filesystem['.gitignore']->exists('!' . $themesIndexPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after($themesPath . '/*', '!' . $themesIndexPath);
		}

		// uploads
		$uploadsPath = $config->getPath('uploads-dir', 'working-dir');

		$uploadsIndexPath = $uploadsPath . '/index.php';
		$filesystem[$uploadsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		if(!$filesystem['.gitignore']->exists($uploadsPath . '/*')) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# uploads');
			$filesystem['.gitignore']->add($uploadsPath . '/*');
		}

		if(!$filesystem['.gitignore']->exists('!' . $uploadsIndexPath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after($uploadsPath . '/*', '!' . $uploadsIndexPath);
		}



		// {config-dir}/application.php
		$configApplicationPath = $config->getPath('config-dir', 'working-dir') . '/application.php';
		$filesystem[$configApplicationPath] = new TemplateFile($twig, 'config/application.php');

		// {config-dir}/environments/development.php
		$configDevelopmentPath = $config->getPath('config-dir', 'working-dir') . '/environments/development.php';
		$filesystem[$configDevelopmentPath] = new TemplateFile($twig, 'config/development.php');

		// {config-dir}/environments/staging.php
		$configStagingPath = $config->getPath('config-dir', 'working-dir') . '/environments/staging.php';
		$filesystem[$configStagingPath] = new TemplateFile($twig, 'config/staging.php');


		// environment

		// {working-dir}/.env.example
		$dotenvExamplePath = '.env.example';
		$filesystem[$dotenvExamplePath] = new TemplateFile($twig, 'config/dotenv.example');

		if(!$filesystem['.gitignore']->exists('.env')) {
			$filesystem['.gitignore']->eof();
			$filesystem['.gitignore']->add("\n" . '# environment');
			$filesystem['.gitignore']->add('.env');
		}

		if(!$filesystem['.gitignore']->exists('.env.*')) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after('.env', '.env.*');
		}

		if(!$filesystem['.gitignore']->exists('!' . $dotenvExamplePath)) {
			$filesystem['.gitignore']->rewind();
			$filesystem['.gitignore']->after('.env.*', '!' . $dotenvExamplePath);
		}


		// wp-cli
		$wpcliPath = 'wp-cli.yml';
		$filesystem[$wpcliPath] = new TemplateFile($twig, 'wordpress/wp-cli.yml');

		if(!$filesystem['.gitignore']->exists('wp-cli.local.yml')) {
			$filesystem['.gitignore']->add("\n" . '# wp-cli');
			$filesystem['.gitignore']->add('wp-cli.local.yml');
		}


		// modify composer file
		$composerFilePath = Factory::getComposerFile();

		$filesystem[$composerFilePath] = new ComposerFile($filesystem[$composerFilePath]);

		// extra.wordpress-install-dir
		$filesystem[$composerFilePath]->addProperty('extra.wordpress-install-dir', $wpInstallPath);

		// extra.installer-paths
		$filesystem[$composerFilePath]->addProperty('extra.installer-paths.' . $mupluginsPath . '/{$name}', array('type:wordpress-muplugin'));
		$filesystem[$composerFilePath]->addProperty('extra.installer-paths.' . $pluginsPath . '/{$name}', array('type:wordpress-plugin'));
		$filesystem[$composerFilePath]->addProperty('extra.installer-paths.' . $themesPath . '/{$name}', array('type:wordpress-theme'));
		$filesystem[$composerFilePath]->addProperty('extra.installer-paths.' . $wpContentPath . '/{$name}', array('type:wordpress-dropin'));


		// save filesystem
		if(!$input->getOption('dry-run')) {
			$filesystem->save();
		}

		// run requires
		$requireCommand = $this->getApplication()->find('wp-require');
		$requireDefinition = $requireCommand->getDefinition();

		$requireParameters = array();

		$requireParameters['packages'] = array('bedrock');

		if($input->getOption('fixed')) {
			$requireParameters['--fixed'] = true;
		}
		if($input->getOption('dry-run')) {
			$requireParameters['--dry-run'] = true;
		}

		$requireInput = new ArrayInput($requireParameters);

		$returnCode = $requireCommand->run($requireInput, $output);
	}

	private function getConfig() {
		if($this->config instanceof Config) {
			return $this->config;
		}

		$composer = $this->getComposer();

		return $this->config = new Config($composer);
	}
}
