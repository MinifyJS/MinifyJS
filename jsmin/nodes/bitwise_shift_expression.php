<?php
class BitwiseShiftExpression extends BinaryExpression {
	public function __construct($type, Expression $left, Expression $right) {
		parent::__construct($type, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		return $this->binary($this->type);
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 11;
	}
}