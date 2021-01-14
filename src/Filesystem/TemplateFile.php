<?php

namespace ShineUnited\WordPressTools\Filesystem;

use Twig\Environment;


class TemplateFile implements FileInterface {
	private $environment;
	private $template;
	private $context;

	public function __construct(Environment $environment, $template, array $context = array()) {
		$this->environment = $environment;
		$this->template = $template;
		$this->context = $context;
	}

	public function contents() {
		return $this->environment->render($this->template, $this->context);
	}
}
