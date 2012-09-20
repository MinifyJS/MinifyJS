<?php
class UnaryMinusExpression extends UnaryExpression {
	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

		if ($this->left instanceof UnaryMinusExpression) {
			$result = new UnaryPlusExpression($this->left->left());
			return $result->visit($ast, $parent);
		}

		if (null !== $value = $this->left->asNumber()) {
			if (is_nan($value)) {
				return new DivExpression(new Number(0), new Number(0));
			}
		}

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
