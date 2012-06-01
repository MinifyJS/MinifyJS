<?php
class MulExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_MUL, $left, $right);
	}

	public function visit(AST $ast) {
		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $this));
		}

		if ($this->left instanceof UnaryMinusExpression && $this->right instanceof UnaryMinusExpression) {
			return new MulExpression($this->left->left(), $this->right->left());
		}

		return $this;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return bcmul($left, $right, 100);
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
