<?php
class BitwiseXorExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_BITWISE_XOR, $left, $right);
	}

	public function visit(AST $ast) {
		$new = parent::visit($ast);

		return $new;
	}

	public function toString() {
		return $this->group($this, $this->left) . '^' . $this->group($this, $this->right, false);
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 7;
	}
}