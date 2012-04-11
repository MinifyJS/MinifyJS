<?php
class BitwiseAndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_BITWISE_AND, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		return $this->binary('&');
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 8;
	}
}