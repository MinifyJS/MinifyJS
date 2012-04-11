<?php
class MinusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			$test = new Number($left - $right);

			if (strlen($test->toString()) <= strlen($this->toString())) {
				return $test->visit($ast);
			}
		}

		return $this;
	}


	public function toString() {
		$r = $this->group($this, $this->right, false);
		if ($r[0] === '-') {
			$r = ' ' . $r;
		}

		return $this->group($this, $this->left) . '-' . $r;
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 12;
	}
}