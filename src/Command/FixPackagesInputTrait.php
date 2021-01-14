<?php

namespace ShineUnited\WordPressTools\Command;

use Symfony\Component\Console\Input\InputInterface;

trait FixPackagesInputTrait {

	protected function fixPackagesInput(InputInterface $input) {
		$packages = $input->getArgument('packages');
		$packages = array_values($packages);

		$fixedPackages = array();
		foreach($packages as $package) {
			if(strtolower(substr($package, 0, 7)) == 'plugin/') {
				$fixedPackages[] = 'wpackagist-plugin/' . substr($package, 7);
				continue;
			}

			if(strtolower(substr($package, 0, 6)) == 'theme/') {
				$fixedPackages[] = 'wpackagist-theme/' . substr($package, 6);
				continue;
			}

			if($package == 'kinsta') {
				$fixedPackages[] = 'kinsta/kinsta-mu-plugins';
				continue;
			}

			if($package == 'bedrock') {
				$fixedPackages[] = 'composer/installers';
				$fixedPackages[] = 'vlucas/phpdotenv';
				$fixedPackages[] = 'oscarotero/env';
				$fixedPackages[] = 'roots/wordpress';
				$fixedPackages[] = 'roots/wp-config';
				$fixedPackages[] = 'roots/wp-password-bcrypt';
				$fixedPackages[] = 'roots/bedrock-autoloader';
				$fixedPackages[] = 'roots/bedrock-disallow-indexing';
				$fixedPackages[] = 'wearerequired/register-default-theme-directory';
				continue;
			}

			$fixedPackages[] = $package;
		}

		$input->setArgument('packages', $fixedPackages);
	}
}
