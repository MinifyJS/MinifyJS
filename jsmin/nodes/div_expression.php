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
				// x/-0 is not easily detectable in php…
				$leftNegative = $this->left instanceof UnaryMinusExpression;
				$rightNegative = $this->right instanceof UnaryMinusExpression;

				if ($left == 0 || !($leftNegative xor $rightNegative)) {
					return new DivExpression(new Number($left == 0 ? 0 : 1), new Number(0));
				} else {
					return new DivExpression(new Number(1), new UnaryMinusExpression(new Number(0)));
				}
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

			return bcdiv($left, $right, 100);
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
