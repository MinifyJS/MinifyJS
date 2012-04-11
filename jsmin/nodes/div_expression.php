<?php
class DivExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_DIV, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		// division can be messy (1/3 = 0.333…)
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			$test = new Number($left / $right);

			if (strlen($test->toString()) <= strlen($this->toString())) {
				return $test;
			}
		}

		return $this;
	}

	public function toString() {
		return $this->binary('/');
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 13;
	}
}
