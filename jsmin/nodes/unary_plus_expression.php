<?php
class UnaryPlusExpression extends UnaryExpression {
	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

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
		return $this->unary('+');
	}

	public function asNumber() {
		return $this->left->asNumber();
	}

	public function type() {
		return 'number';
	}

    public function mayInline() {
        return $this->left->mayInline();
    }

	public function hasSideEffects() {
		return $this->left->hasSideEffects();
	}

	public function precedence() {
		return 14;
	}
}
