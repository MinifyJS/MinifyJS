<?php
class MulExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_MUL, $left, $right);
	}

	public function visit(AST $ast) {
		$that = parent::visit($ast);

		if (null !== $result = $that->asNumber()) {
			return AST::bestOption(array(new Number($result), $that));
		}

		if ($that->left instanceof UnaryMinusExpression && $that->right instanceof UnaryMinusExpression) {
			return new MulExpression($that->left->left(), $that->right->left());
		}

		return $that;
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
