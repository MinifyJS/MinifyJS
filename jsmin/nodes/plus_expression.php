<?php
class PlusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		if ($this->left->type() === 'string' || $this->right->type() === 'string') {
			if ((null !== $left = $this->left->asString()) && (null !== $right = $this->right->asString())) {
				return new String($left . $right);
			}
		}

		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			return new Number($left + $right);
		}

		return $this;
	}

	public function toString() {
		$r = $this->group($this, $this->right, false);
		if ($r[0] === '+') {
			$r = ' ' . $r;
		}

		return $this->group($this, $this->left) . '+' . $r;
	}

	public function type() {
		if ($this->left()->type() === 'string' || $this->right->type() === 'string') {
			return 'string';
		}
	}

	public function precedence() {
		return 12;
	}
}
