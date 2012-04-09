<?php
class IdentifierExpression extends ConstantExpression {
	public function __construct(Identifier $i) {
		parent::__construct($i);
	}

	public function visit(AST $ast) {
		$this->left->used(true);

		return $this;
	}

	public function used() {
		return $this->left->used();
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
