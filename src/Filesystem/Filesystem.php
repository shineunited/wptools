<?php

namespace ShineUnited\WordPressTools\Filesystem;


class Filesystem implements \ArrayAccess {
	private $basedir;
	private $files;

	public function __construct($basedir) {
		$this->basedir = $basedir;
		$this->files = array();
	}

	public function offsetExists($offset) {
		return $this->exists($offset);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->unset($offset);
	}

	public function exists($path) {
		if(isset($this->files[$path])) {
			return true;
		}

		if(is_file($path)) {
			return true;
		}

		return false;
	}

	public function get($path) {
		if(!$this->exists($path)) {
			throw new \Exception('File does not exist: ' . $path);
		}

		if(isset($this->files[$path])) {
			return $this->files[$path];
		}

		return $this->files[$path] = new SimpleFile(file_get_contents($path));
	}

	public function set($path, FileInterface $file) {
		$this->files[$path] = $file;
	}

	public function unset($path) {
		if($this->exists($path)) {
			unset($this->files[$path]);
		}
	}

	public function save() {
		foreach($this->files as $path => $file) {
			$fullpath = $this->basedir . '/' . $path;
			$dir = dirname($path);
			if(!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}

			file_put_contents($path, $file->contents());
		}
	}
}
