<?php

namespace ShineUnited\WordPressTools\Filesystem;


class SimpleFile implements FileInterface {
	private $contents;

	public function __construct($contents) {
		$this->contents = $contents;
	}

	public function contents() {
		return $this->contents;
	}
}
