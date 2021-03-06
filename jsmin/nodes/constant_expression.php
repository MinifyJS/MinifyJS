<?php
class ConstantExpression extends Expression {
	public function __construct($value) {
		$this->left = $value;

		parent::__construct();
	}

	public function collectStatistics(AST $ast) {}

	public function toString() {
		return $this->left->toString();
	}

	public function visit(AST $ast, Node $parent = null) {
		return $this;
	}

	public function isConstant() {
		return true;
	}

	public function gone() {

	}

	public function first() {
		return $this;
	}

	public function precedence() {
		return null;
	}

	public function hasSideEffects() {
		return false;
	}

	public function mayInline() {
		return true;
	}
}