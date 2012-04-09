<?php
class UnaryPlusExpression extends UnaryExpression {
	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		if ($this->left->type() === 'number') {
			return $this->left;
		}

		return $this;
	}

	public function toString() {
		return '+' . $this->left->toString();
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 14;
	}
}