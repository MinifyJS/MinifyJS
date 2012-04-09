<?php
class MinusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		if ($this->left instanceof Number && $this->right instanceof Number) {
			return new Number($this->left->value() - $this->right->value());
		}

		return $this;
	}


	public function toString() {
		$r = (string)$this->right;
		if ($r[0] === '-') {
			$r = ' ' . $r;
		}

		return $this->left . '-' . $r;
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 12;
	}
}