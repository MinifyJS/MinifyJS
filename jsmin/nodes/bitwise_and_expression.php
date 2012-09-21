<?php
class BitwiseAndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_BITWISE_AND, $left, $right);
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $that->asNumber()) {
			return AST::bestOption(array(
				new Number($result),
				$that->resolveLeftSequence()
			));
		}

		return $that->resolveLeftSequence();
	}

	public function toString() {
		return $this->binary('&');
	}

	public function type() {
		return null;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return (int)$left & (int)$right;
		}
	}

	public function precedence() {
		return 8;
	}
}
