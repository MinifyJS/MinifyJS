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

	public function gone() {
		if ($this->label) {
			$this->label->used(false);
		}
	}

	public function optimizeBreak() {
		if (!$this->hasLabel()) {
			return new VoidExpression(new Number(0));
		}

		return $this;
	}

	public function countLetters(&$letters) {
		foreach(array('c', 'o', 'n', 't', 'i', 'n', 'u', 'e') as $l) {
			$letters[$l] += 1;
		}
	}

	public function isBreaking() {
		return true;
	}
}