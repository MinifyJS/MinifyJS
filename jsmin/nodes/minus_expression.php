<?php
class MinusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $this));
		}

		return $this;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return $left - $right;
		}
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