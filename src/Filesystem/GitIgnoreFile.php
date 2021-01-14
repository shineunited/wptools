<?php

namespace ShineUnited\WordPressTools\Filesystem;

use GitIgnoreWriter\GitIgnoreWriter;


class GitIgnoreFile extends GitIgnoreWriter implements FileInterface {

	public function __construct($contents = null) {
		if(is_null($contents)) {
			$contents = array();
		} elseif($contents instanceof FileInterface) {
			$contents = $contents->contents();
		}

		if(is_string($contents)) {
			$contents = explode(PHP_EOL, $contents);
		}

		if(!is_array($contents)) {
			throw new \Exception('Unexpected type: ' . gettype($contents));
		}

		$this->buffer = array_map('trim', $contents);
		$this->pointer = count($this->buffer);
	}

	/*
	public function load($filePath) {
		// ignore
	}

	public function save($filePath = null) {
		// ignore
	}
	*/

	public function contents() {
		return implode(PHP_EOL, $this->buffer) . "\n";
	}
}
