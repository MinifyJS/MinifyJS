<?php
class MulExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_MUL, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $this));
		}

		return $this;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return $left * $right;
		}
	}

	public function toString() {
		return $this->binary('*');
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 13;
	}
}
