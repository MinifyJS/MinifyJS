<?php
class AndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_AND, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		return $this->group($this, $this->left) . '&&' . $this->group($this, $this->right, false);
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 5;
	}
}