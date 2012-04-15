<?php
class Boolean extends ConstantExpression {
	public function toString() {
		return $this->left ? 'true' : 'false';
	}

	public function type() {
		return 'boolean';
	}

	public function visit(AST $ast) {
		if (!AST::$options['crush-bool']) {
			return $this;
		}

		return new NotExpression(new Number($this->negate()->asNumber()));
	}

	public function asBoolean() {
		return $this->left;
	}

	public function asNumber() {
		return $this->left ? 1 : 0;
	}

	public function asString() {
		return $this->left ? 'true' : 'false';
	}

	public function negate() {
		return new Boolean(!$this->left);
	}
}