<?php
class MulExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_MUL, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return new Number($left * $right);
		}

		return $this;
	}

	public function toString() {
		return $this->group($this, $this->left) . '*' . $this->group($this, $this->right, false);
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 13;
	}
}
