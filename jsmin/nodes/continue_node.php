<?php
class ContinueNode extends Node {
	protected $label;

	public function __construct(Identifier $label = null) {
		$this->label = $label;

		parent::__construct();
	}

	public function visit(AST $ast) {
		return $this;
	}

	public function collectStatistics(AST $ast) {}

	public function toString() {
		return 'continue' . ($this->label ? Stream::legalStart($this->label->toString()) : '');
	}
}