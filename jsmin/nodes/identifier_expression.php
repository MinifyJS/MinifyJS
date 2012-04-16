<?php
class IdentifierExpression extends ConstantExpression {
	protected $write = false;

	public function __construct(Identifier $i, $write = false) {
		parent::__construct($i);
		$this->write = $write;
	}

	public function visit(AST $ast) {
		if (!$this->write) {
			// check for some common variables
		}

		//if (!$this->write && $this->left->name() === 'undefined') {
		//	return new VoidExpression(new Number(0));
		//}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->used(true);
	}

	public function keep() {
		return $this->left->keep() || $this->used();
	}

	public function write() {
		$this->write = true;
	}

	public function used($bool = null) {
		return max($this->left->used($bool), $this->write ? 1 : 0);
	}

	public function value() {
		return $this->left->name();
	}

	public function __toString() {
		return $this->left->toString();
	}

	public function get() {
		return $this->left;
	}
}
