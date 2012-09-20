<?php
class DivExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_DIV, $left, $right);
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $that->asNumber()) {
			$option = new Number($result);
			return AST::bestOption(array($option->visit($ast, $parent), $that));
		}

		if ($that->left->isInfinity() && $that->right->isInfinity()) {
			return new DivExpression(new Number(0), new Number(0));
		}

		if ((null !== $left = $that->left->asNumber()) && (null !== $right = $that->right->asNumber())) {
			if ($right == 0) {
				// x/-0 is not easily detectable in php…
				$leftNegative = $that->left instanceof UnaryMinusExpression;
				$rightNegative = $that->right instanceof UnaryMinusExpression;

				if ($left == 0 || !($leftNegative xor $rightNegative)) {
					return new DivExpression(new Number($left == 0 ? 0 : 1), new Number(0));
				} else {
					return new DivExpression(new Number(1), new UnaryMinusExpression(new Number(0)));
				}
			}
		}

		return $that;
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
        if (AST::$options['beautify']) {
            return $this->binary('/');
        }

        $r = $this->group($this, $this->right, false);
        if ($r[0] === '/') {
            $r = ' ' . $r;
        }

        return $this->group($this, $this->left) . '/' . $r;
    }

	public function type() {
		return 'number';
	}

	public function isInfinity() {
		return $this->right->asNumber() === '0';
	}

	public function precedence() {
		return 13;
	}
}
