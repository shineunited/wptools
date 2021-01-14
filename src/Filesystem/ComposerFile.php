<?php

namespace ShineUnited\WordPressTools\Filesystem;

use Composer\Json\JsonManipulator;

class ComposerFile extends JsonManipulator implements FileInterface {
	private $contents;

	public function __construct($contents) {
		if($contents instanceof FileInterface) {
			$contents = $contents->contents();
		}

		parent::__construct($contents);
	}

	public function contents() {
		return $this->getContents();
	}
}
