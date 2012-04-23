<?php
class UnaryMinusExpression extends UnaryExpression {
	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function toString() {
		return $this->unary('-');
	}

	public function asNumber() {
		if (null !== $left = $this->left->asNumber()) {
			return -$left;
		}
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 14;
	}
}