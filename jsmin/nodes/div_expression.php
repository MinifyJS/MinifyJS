<?php
class DivExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_DIV, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $this));
		}

		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			if ($right == 0) {
				$fixLeft = new Number($left == 0 ? 0 : ($left < 0 ? -1 : 1));
				$fixRight = new Number(0);
				if ($left < 0 || $right === -0) {
					$fixRight = new UnaryMinusExpression($fixRight);
				}

				return new DivExpression($fixLeft, $fixRight);
			}
		}

		return $this;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			if ($right == 0) {
				if ($left == 0) {
					return NAN;
				}

				return null;
			}

			if (is_nan($left) || is_nan($right)) {
				return NAN;
			}

			$expr = new Number(bcdiv($left, $right, 100));
			return $expr->asNumber();
		}
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
