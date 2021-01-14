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
use Symfony\Component\Console\Question\Question;
use Composer\Command\BaseCommand;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
//use Symfony\Component\Filesystem\Filesystem;
use Composer\Factory;
//use Composer\Json\JsonManipulator;
//use GitIgnoreWriter\GitIgnoreWriter;

class InitCommand extends BaseCommand {
	private $config;
	private $pathOptions;

	protected function configure() {
		$this->setName('wp-init');

		$this->pathOptions = array();

		$this->addPathOption(
			'webroot',
			'Webroot directory path',
			'web'
		);

		$this->addPathOption(
			'home-dir',
			'WordPress home directory path',
			'web'
		);

		$this->addPathOption(
			'config-dir',
			'Configuration directory path',
			'cfg'
		);

		$this->addPathOption(
			'install-dir',
			'WordPress install directory path',
			'web/wp'
		);

		$this->addPathOption(
			'content-dir',
			'WordPress content directory path',
			'web/app'
		);

		foreach($this->listPathOptionNames() as $optionName) {
			$this->addOption(
				$optionName,
				null,
				InputOption::VALUE_REQUIRED,
				$this->getPathOptionDescription($optionName)
			);
		}

		$this->passThruOptions = array(
			'dry-run',
			'prefer-source',
			'prefer-dist',
			'fixed',
			'no-suggest',
			'no-progress'
		);

		$requireCommand = new RequireCommand();
		$requireDefinition = $requireCommand->getDefinition();
		$definition = $this->getDefinition();

		foreach($this->passThruOptions as $optionName) {
			if($requireDefinition->hasOption($optionName)) {
				$option = $requireDefinition->getOption($optionName);
				$definition->addOption($option);
			}
		}
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$config = $this->getConfig();
		$questionHelper = $this->getHelper('question');

		foreach($this->listPathOptionNames() as $optionName) {
			if($input->hasOption($optionName) && $input->getOption($optionName)) {
				continue;
			}

			try {
				$config->getPath($optionName);
				continue;
			} catch(\Exception $e) {
				// ignore
			}

			$optionConfig = $this->getPathOptionConfig($optionName);
			$question = new Question(
				'<comment>' . $optionConfig['description'] . ' <info>(' . $optionConfig['default'] . ')</info>: ',
				$optionConfig['default']
			);

			$result = $questionHelper->ask($input, $output, $question);

			$input->setOption($optionName, $result);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config = $this->getConfig();

		foreach($this->listPathOptionNames() as $name) {
			if($path = $input->getOption($name)) {
				$config->setPath($name, $path);
			}
		}

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

		//$gitignoreRules = array();

		// system
		/*
		$gitignoreRules[] = array(
			'section' => 'system',
			'rule'    => '.Trashes'
		);

		$gitignoreRules[] = array(
			'section' => 'system',
			'rule'    => '.DS_Store'
		);
		*/

		// composer
		/*
		$gitignoreRules[] = array(
			'section' => 'composer',
			'rule'    => 'composer.lock'
		);

		$gitignoreRules[] = array(
			'section' => 'composer',
			'rule'    => 'composer.phar'
		);

		$gitignoreRules[] = array(
			'section' => 'composer',
			'rule'    => $config->getPath('vendor-dir', 'working-dir')
		);
		*/

		// wordpress
		/*
		$gitignoreRules[] = array(
			'section' => 'wordpress',
			'rule'    => $config->getPath('upgrade-dir', 'working-dir')
		);

		$gitignoreRules[] = array(
			'section' => 'wordpress',
			'rule'    => $config->getPath('install-dir', 'working-dir')
		);
		*/

		$wpUpgradePath = $config->getPath('upgrade-dir', 'working-dir');
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

		/*
		$filesystem->dumpFile($config->getPath('home-dir') . '/index.php', $twig->render(
			'wordpress/index.php'
		));
		*/

		// {webroot}/status.php
		$statusPath = $config->getPath('home-dir', 'working-dir') . '/status.php';
		$filesystem[$statusPath] = new TemplateFile($twig, 'wordpress/status.php');

		/*
		$filesystem->dumpFile($config->getPath('home-dir') . '/status.php', $twig->render(
			'wordpress/status.php'
		));
		*/

		// {webroot}/.htaccess
		$htaccessPath = $config->getPath('home-dir', 'working-dir') . '/.htaccess';
		$filesystem[$htaccessPath] = new TemplateFile($twig, 'wordpress/htaccess');

		/*
		$filesystem->dumpFile($config->getPath('home-dir') . '/.htaccess', $twig->render(
			'wordpress/htaccess'
		));
		*/

		// {wpconfig-dir}/wp-config.php
		$wpconfigPath = $config->getPath('wpconfig-dir', 'working-dir') . '/wp-config.php';
		$filesystem[$wpconfigPath] = new TemplateFile($twig, 'wordpress/wp-config.php');

		/*
		$filesystem->dumpFile($config->getPath('wpconfig-dir') . '/wp-config.php', $twig->render(
			'wordpress/wp-config.php'
		));
		*/

		// mu-plugins
		/*
		$gitignoreRules[] = array(
			'section' => 'mu-plugins',
			'rule'    => $config->getPath('muplugins-dir', 'working-dir') . '/*'
		);
		*/

		// {muplugins-dir}/autoloader.php
		$mupluginsAutoloaderPath = $config->getPath('muplugins-dir', 'working-dir') . '/autoloader.php';
		$filesystem[$mupluginsAutoloaderPath] = new TemplateFile($twig, 'wordpress/autoloader.php');

		/*
		$filesystem->dumpFile($config->getPath('muplugins-dir') . '/autoloader.php', $twig->render(
			'wordpress/autoloader.php'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'mu-plugins',
			'rule'    => '!' . $config->getPath('muplugins-dir', 'working-dir') . '/autoloader.php',
			'after'   => $config->getPath('muplugins-dir', 'working-dir') . '/*'
		);
		*/

		// {muplugins-dir}/index.php
		$mupluginsIndexPath = $config->getPath('muplugins-dir', 'working-dir') . '/index.php';
		$filesystem[$mupluginsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		/*
		$filesystem->dumpFile($config->getPath('muplugins-dir') . '/index.php', $twig->render(
			'wordpress/index-placeholder.php'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'mu-plugins',
			'rule'    => '!' . $config->getPath('muplugins-dir', 'working-dir') . '/index.php',
			'after'   => $config->getPath('muplugins-dir', 'working-dir') . '/*'
		);
		*/

		$mupluginsPath = $config->getPath('muplugins-dir', 'working-dir');
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
		/*
		$gitignoreRules[] = array(
			'section' => 'plugins',
			'rule'    => $config->getPath('plugins-dir', 'working-dir') . '/*'
		);
		*/

		// {plugins-dir}/index.php
		$pluginsIndexPath = $config->getPath('plugins-dir', 'working-dir') . '/index.php';
		$filesystem[$pluginsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		/*
		$filesystem->dumpFile($config->getPath('plugins-dir') . '/index.php', $twig->render(
			'wordpress/index-placeholder.php'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'plugins',
			'rule'    => '!' . $config->getPath('plugins-dir', 'working-dir') . '/index.php',
			'after'   => $config->getPath('plugins-dir', 'working-dir') . '/*'
		);
		*/

		$pluginsPath = $config->getPath('plugins-dir', 'working-dir');

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
		/*
		$gitignoreRules[] = array(
			'section' => 'themes',
			'rule'    => $config->getPath('themes-dir', 'working-dir') . '/*'
		);
		*/

		// {themes-dir}/index.php
		$themesIndexPath = $config->getPath('themes-dir', 'working-dir') . '/index.php';
		$filesystem[$themesIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		/*
		$filesystem->dumpFile($config->getPath('themes-dir') . '/index.php', $twig->render(
			'wordpress/index-placeholder.php'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'themes',
			'rule'    => '!' . $config->getPath('themes-dir', 'working-dir') . '/index.php',
			'after'   => $config->getPath('themes-dir', 'working-dir') . '/*'
		);
		*/

		$themesPath = $config->getPath('themes-dir', 'working-dir');

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



		/*
		$gitignoreRules[] = array(
			'section' => 'uploads',
			'rule'    => $config->getPath('uploads-dir', 'working-dir') . '/*'
		);
		*/

		$uploadsIndexPath = $config->getPath('uploads-dir', 'working-dir') . '/index.php';
		$filesystem[$uploadsIndexPath] = new TemplateFile($twig, 'wordpress/index-placeholder.php');

		/*
		$filesystem->dumpFile($config->getPath('uploads-dir') . '/index.php', $twig->render(
			'wordpress/index-placeholder.php'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'uploads',
			'rule'    => '!' . $config->getPath('uploads-dir', 'working-dir') . '/index.php',
			'after'   => $config->getPath('uploads-dir', 'working-dir') . '/*'
		);
		*/

		$uploadsPath = $config->getPath('uploads-dir', 'working-dir');

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

		/*
		$filesystem->dumpFile($config->getPath('config-dir') . '/application.php', $twig->render(
			'config/application.php'
		));
		*/

		// {config-dir}/environments/development.php
		$configDevelopmentPath = $config->getPath('config-dir', 'working-dir') . '/environments/development.php';
		$filesystem[$configDevelopmentPath] = new TemplateFile($twig, 'config/development.php');

		/*
		$filesystem->dumpFile($config->getPath('config-dir') . '/environments/development.php', $twig->render(
			'config/development.php'
		));
		*/

		// {config-dir}/environments/staging.php
		$configStagingPath = $config->getPath('config-dir', 'working-dir') . '/environments/staging.php';
		$filesystem[$configStagingPath] = new TemplateFile($twig, 'config/staging.php');

		/*
		$filesystem->dumpFile($config->getPath('config-dir') . '/environments/staging.php', $twig->render(
			'config/staging.php'
		));
		*/


		// environment

		/*
		$gitignoreRules[] = array(
			'section' => 'environment',
			'rule'    => '.env'
		);

		$gitignoreRules[] = array(
			'section' => 'environment',
			'rule'    => '.env.*',
			'after'   => '.env'
		);
		*/

		// {working-dir}/.env.example
		$dotenvExamplePath = '.env.example';
		$filesystem[$dotenvExamplePath] = new TemplateFile($twig, 'config/dotenv.example');

		/*
		$filesystem->dumpFile($config->getPath('working-dir') . '/.env.example', $twig->render(
			'config/dotenv.example',
			array()
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'environment',
			'rule'    => '!.env.example',
			'after'   => '.env.*'
		);
		*/

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

		/*
		$filesystem->dumpFile($config->getPath('working-dir') . '/wp-cli.yml', $twig->render(
			'wordpress/wp-cli.yml'
		));
		*/

		/*
		$gitignoreRules[] = array(
			'section' => 'wp-cli',
			'rule'    => 'wp-cli.local.yml'
		);
		*/

		if(!$filesystem['.gitignore']->exists('wp-cli.local.yml')) {
			$filesystem['.gitignore']->add("\n" . '# wp-cli');
			$filesystem['.gitignore']->add('wp-cli.local.yml');
		}




		// modify gitignore
		/*
		$gitignoreWriter = new GitIgnoreWriter($config->getPath('working-dir') . '/.gitignore');

		// create sections
		foreach($gitignoreRules as $rule) {
			if($gitignoreWriter->exists('# ' . $rule['section'])) {
				continue;
			}

			if($gitignoreWriter->exists($rule['rule'])) {
				continue;
			}

			$gitignoreWriter->add("\n" . '# ' . $rule['section']);
		}

		foreach($gitignoreRules as $rule) {
			if($gitignoreWriter->exists($rule['rule'])) {
				continue;
			}

			$gitignoreWriter->rewind();

			$after = '# ' . $rule['section'];
			if(isset($rule['after'])) {
				$after = $rule['after'];
			}

			$gitignoreWriter->after($after, $rule['rule']);
		}
		$gitignoreWriter->save();
		*/




		/*
		$filesystem->appendToFile($config->getPath('working-dir') . '/.gitignore', implode("\n", array(
			'# System',
			'.DS_Store',
			'.Trashes',
			'',
			'# Environment',
			'.env',
			'',
			'# Composer',
			'composer.phar',
			//'composer.lock',
			$config->getPath('vendor-dir', 'working-dir'),
			'',
			'# WordPress',
			$config->getPath('install-dir', 'working-dir'), // ignore install directory
			'',
			'# Application',
			$config->getPath('content-dir', 'working-dir') . '/mu-plugins/*',
			'!' . $config->getPath('content-dir', 'working-dir') . '/mu-plugins/index.php',
			'!' . $config->getPath('content-dir', 'working-dir') . '/mu-plugins/autoloader.php',
			$config->getPath('content-dir', 'working-dir') . '/plugins/*',
			'!' . $config->getPath('content-dir', 'working-dir') . '/plugins/index.php',
			$config->getPath('content-dir', 'working-dir') . '/themes/*',
			'!' . $config->getPath('content-dir', 'working-dir') . '/themes/index.php',
			$config->getPath('content-dir', 'working-dir') . '/upgrade',
			'',
			'# Uploads',
			$config->getPath('uploads-dir', 'working-dir') . '/*',
			'!' . $config->getPath('uploads-dir', 'working-dir') . '/index.php'
		)));
		*/



		// modify composer file
		$filesystem['composer.json'] = new ComposerFile($filesystem[Factory::getComposerFile()]);

		$filesystem['composer.json']->addProperty('extra.wptools', array(
			'version' => 1,
			'paths'   => $config->listPaths()
		));

		/*
		$composerFile = Factory::getComposerFile();

		$composerContents = file_get_contents($composerFile);



		$manipulator = new JsonManipulator($composerContents);

		$manipulator->addProperty('extra.wptools', array(
			'version' => 1,
			'paths'   => $config->listPaths()
		));

		$filesystem->dumpFile($composerFile, $manipulator->getContents());
		*/

		$filesystem->save();

		$requireCommand = $this->getApplication()->find('wp-require');

		$requireArguments = array();
		$requireArguments['packages'] = 'bedrock';
		if($requireCommand->getDefinition()->hasOption('fixed') && $input->getOption('fixed')) {
			$requireArguments['--fixed'] = true;
		}

		$requireInput = new ArrayInput($requireArguments);

		$returnCode = $requireCommand->run($requireInput, $output);

		/*
		// install required packages
		$requireCommand = $this->getApplication()->find('require');

		$requireArguments = array();
		$requireArguments['packages'] = array(
			'composer/installers',
			'vlucas/phpdotenv',
			'oscarotero/env',
			'roots/wordpress',
			'roots/wp-config',
			'roots/wp-password-bcrypt',
			'roots/bedrock-autoloader',
			'roots/bedrock-disallow-indexing',
			'wearerequired/register-default-theme-directory'
		);

		if($requireCommand->getDefinition()->hasOption('fixed') && $config->getPackageType() == 'project') {
			$requireArguments['--fixed'] = true;
		}

		$requireInput = new ArrayInput($requireArguments);

		$returnCode = $requireCommand->run($requireInput, $output);
		*/

		$output->writeln('Executing');
	}

	private function addPathOption($name, $description, $default) {
		$this->pathOptions[$name] = array(
			'description' => $description,
			'default'     => $default
		);
	}

	private function listPathOptionNames() {
		return array_keys($this->pathOptions);
	}

	private function hasPathOptionConfig($name) {
		if(isset($this->pathOptions[$name])) {
			return true;
		}

		return false;
	}

	private function getPathOptionConfig($name) {
		if(!$this->hasPathOptionConfig($name)) {
			throw new \Exception('Unknown path option "' . $name . '"');
		}

		return $this->pathOptions[$name];
	}

	private function getPathOptionDefault($name) {
		$config = $this->getPathOptionConfig($name);
		return $config['default'];
	}

	private function getPathOptionDescription($name) {
		$config = $this->getPathOptionConfig($name);
		return $config['description'] . '.';
	}

	private function getPathOptionQuestion($name) {
		$config = $this->getPathOptionConfig($name);
		return '<comment>' . $config['description'] . ' <info>(' . $config['default'] . ')</info>: ';
	}

	private function getConfig() {
		if($this->config instanceof Config) {
			return $this->config;
		}

		$composer = $this->getComposer();

		return $this->config = new Config($composer);
	}
}
