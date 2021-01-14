<?php

namespace ShineUnited\WordPressTools\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability {

	public function getCommands() {
		return array(
			new InitCommand(),
			new RequireCommand(),
			new RemoveCommand()
		);
	}
}
