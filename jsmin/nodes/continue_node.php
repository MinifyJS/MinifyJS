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

	public function collectStatistics(AST $ast) {
		if ($this->label) {
			$this->label->used(true);
		}
	}

	public function hasLabel() {
		return !!$this->label;
	}

	public function toString() {
		return 'continue' . ($this->label ? Stream::legalStart($this->label->toString()) : '');
	}

	public function optimizeBreak() {
		return new VoidExpression(new Number(0));
	}

	public function isBreaking() {
		return true;
	}
}