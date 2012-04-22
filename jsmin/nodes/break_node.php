<?php
class BreakNode extends Node {
	protected $label;

	public function __construct(Identifier $label = null) {
		$this->label = $label;

		parent::__construct();
	}

	public function visit(AST $ast) {
		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->label) {
			$this->label->used(true);
		}
	}

	public function toString() {
		return 'break' . ($this->label ? Stream::legalStart($this->label->toString()) : '');
	}

	public function isBreaking() {
		return true;
	}
}