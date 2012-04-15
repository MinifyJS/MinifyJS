<?php
class UnaryPlusExpression extends UnaryExpression {
	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		if ($this->left->type() === 'number') {
			return $this->left;
		}

		if (null !== $value = $this->left->asNumber()) {
			if (is_nan($value)) {
				return new DivExpression(new Number(0), new Number(0));
			}
		}

		return $this;
	}

	public function toString() {
		return '+' . $this->left->toString();
	}

	public function asNumber() {
		return $this->left->asNumber();
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 14;
	}
}