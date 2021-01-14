<?php

namespace ShineUnited\WordPressTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\RemoveCommand as BaseCommand;

class RemoveCommand extends BaseCommand {
	use FixPackagesInputTrait;

	protected function configure() {
		parent::configure();
		$this->setName('wp-remove');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->fixPackagesInput($input, $output);

		return parent::execute($input, $output);
	}
}
