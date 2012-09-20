<?php
class BitwiseXorExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_BITWISE_XOR, $left, $right);
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		if (null !== $result = $that->asNumber()) {
			return AST::bestOption(array(new Number($result), $that));
		}

		return $that;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return (int)$left ^ (int)$right;
		}
	}

	public function toString() {
		return $this->binary('^');
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 7;
	}
}
